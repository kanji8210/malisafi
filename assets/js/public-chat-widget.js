(function(){
    if(typeof window.malisafiPublicChat === 'undefined') return;
    var cfg = window.malisafiPublicChat;

        // Create widget HTML using a template literal (header, messages, quick replies, input bar)
        var widgetHtml = `
        <div id="malisafi-public-widget" class="malisafi-public-widget-closed" aria-live="polite">
            <div class="malisafi-widget-header">
                <div class="malisafi-widget-status" id="malisafi-widget-status">${cfg.i18n.agentOffline}</div>
                <button id="malisafi-public-widget-toggle" class="malisafi-widget-toggle" aria-expanded="false">
                    <span class="chat-icon">💬</span>
                    <span class="chat-label">${cfg.i18n.startChat}</span>
                </button>
            </div>
            <div id="malisafi-public-widget-panel" class="malisafi-widget-panel" style="display:none;">
                <div id="malisafi-public-widget-messages" class="malisafi-widget-messages" role="log" aria-live="polite">${cfg.i18n.welcome || ''}</div>
                <div id="malisafi-typing-indicator" class="malisafi-typing-indicator" style="display:none;"><span></span><span></span><span></span></div>
                <form id="malisafi-public-widget-form" class="malisafi-widget-form" autocomplete="off">
                    <div class="malisafi-input-row" style="display:none;">
                        <input type="text" id="malisafi-widget-message" placeholder="Type your message..." />
                        <button type="submit" id="malisafi-widget-send" class="malisafi-send">${cfg.i18n.send}</button>
                    </div>
                    <div class="malisafi-start-row" style="padding:0 12px 10px;">
                        <button type="button" id="malisafi-widget-start" class="malisafi-start">${cfg.i18n.startChat}</button>
                    </div>
                    <div class="malisafi-widget-actions">
                        <button type="button" id="malisafi-public-end-chat" class="malisafi-end-chat" style="display:none;">${cfg.i18n.end}</button>
                    </div>
                </form>
            </div>
        </div>`;

    document.addEventListener('DOMContentLoaded', function(){
        var div = document.createElement('div');
        div.innerHTML = widgetHtml;
        document.body.appendChild(div.firstElementChild);

        var toggle = document.getElementById('malisafi-public-widget-toggle');
        var panel = document.getElementById('malisafi-public-widget-panel');
        var messagesEl = document.getElementById('malisafi-public-widget-messages');
        var form = document.getElementById('malisafi-public-widget-form');
        var messageEl = document.getElementById('malisafi-widget-message');
        var startBtn = document.getElementById('malisafi-widget-start');
        var sendBtn = document.getElementById('malisafi-widget-send');
        var inputRow = form ? form.querySelector('.malisafi-input-row') : null;

        var token = null;
        var threadId = null;
        var polling = null;
        var unread = 0;
        var sessionKey = 'malisafi_public_chat_session';
        var session = null;
        var currentAgent = null;
        var contactName = '';
        var contactEmail = '';
        var contactPhone = '';

        // Restore minimized state
        var minimized = localStorage.getItem('malisafi_public_widget_minimized') === '1';
        if (minimized) {
            panel.style.display = 'none';
            toggle.classList.remove('open');
        }

        // Add unread badge element
        var badge = document.createElement('span');
        badge.id = 'malisafi-public-widget-badge';
        badge.style.display = 'none';
        badge.className = 'malisafi-widget-badge';
        toggle.appendChild(badge);

        function escapeHtml(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

        // single-line input: no autosize needed; hide/disable inputs until chat started
        if (messageEl) { messageEl.disabled = true; }
        if (sendBtn) { sendBtn.style.display = 'none'; }

        toggle.addEventListener('click', function(){
            if(panel.style.display === 'none'){
                panel.style.display = 'block';
                toggle.classList.add('open');
                // start or resume chat
                if(!token){
                    // Try to resume session from localStorage first
                    var raw = localStorage.getItem(sessionKey);
                    if (raw) {
                        try { session = JSON.parse(raw); } catch(e) { session = null; }
                    }
                    if (session && session.token) {
                        token = session.token; threadId = session.threadId || null;
                        messagesEl.innerHTML = '';
                        fetchMessages();
                        polling = setInterval(fetchMessages, cfg.pollInterval || 5000);
                        var eb2 = document.getElementById('malisafi-public-end-chat'); if (eb2) eb2.style.display = 'inline-block';
                        // show input row for resumed session
                        if (inputRow) inputRow.style.display = 'flex';
                        if (messageEl) { messageEl.disabled = false; }
                        if (sendBtn) { sendBtn.style.display = 'inline-block'; }
                        if (startBtn) startBtn.style.display = 'none';
                        showWelcomeIfNeeded();
                    } else {
                        // No session: show contact form immediately and hide inputs until started
                        if (messageEl) messageEl.disabled = true;
                        if (sendBtn) sendBtn.style.display = 'none';
                        if (inputRow) inputRow.style.display = 'none';
                        try { showContactForm(); } catch(e) {}
                    }
                }
                // reset unread
                unread = 0; badge.style.display = 'none'; badge.textContent = '';
                localStorage.setItem('malisafi_public_widget_minimized','0');
            } else {
                panel.style.display = 'none';
                toggle.classList.remove('open');
                if(polling) clearInterval(polling);
                localStorage.setItem('malisafi_public_widget_minimized','1');
            }
        });

        // Start button handler: for new chats show contact form first
        function showContactForm(){
            // build a simple inline contact form above the start button
            var container = document.createElement('div');
            container.className = 'malisafi-contact-form';
            container.innerHTML = '\n                <input type="text" id="malisafi-contact-name" placeholder="Name" />\n                <input type="email" id="malisafi-contact-email" placeholder="Email (optional)" />\n                <input type="tel" id="malisafi-contact-phone" placeholder="Phone (optional)" />\n                <div style="display:flex;gap:8px;margin-top:8px;"><button id="malisafi-contact-submit" class="malisafi-start">Start chat</button><button id="malisafi-contact-cancel" class="malisafi-start" style="background:#eee;color:#333;border:1px solid #ddd">Cancel</button></div>';
            var startRow = document.querySelector('.malisafi-start-row');
            if (startRow) startRow.parentNode.insertBefore(container, startRow);
            // Hide the original start button to avoid duplicate start controls
            try { if (startBtn) startBtn.style.display = 'none'; } catch(e) {}
            var submit = document.getElementById('malisafi-contact-submit');
            var cancel = document.getElementById('malisafi-contact-cancel');
            submit.addEventListener('click', function(){
                var n = document.getElementById('malisafi-contact-name');
                var e = document.getElementById('malisafi-contact-email');
                var p = document.getElementById('malisafi-contact-phone');
                contactName = n ? n.value.trim() : '';
                contactEmail = e ? e.value.trim() : '';
                contactPhone = p ? p.value.trim() : '';
                // remove form
                container.parentNode.removeChild(container);
                if (startBtn) startBtn.style.display = 'none';
                startPublicChat({ name: contactName, email: contactEmail, phone: contactPhone });
            });
            cancel.addEventListener('click', function(){
                if (container && container.parentNode) container.parentNode.removeChild(container);
            });
        }

        if (startBtn) {
            startBtn.addEventListener('click', function(){
                // if there's a resumed session, start immediately; otherwise ask for contact
                if (session && session.token) {
                    if (startBtn) startBtn.style.display = 'none';
                    startPublicChat();
                } else {
                    showContactForm();
                }
            });
        }

        // End chat button handler
        var endBtn = document.getElementById('malisafi-public-end-chat');
        if (endBtn) {
            endBtn.addEventListener('click', function(){
                if (!token) return;
                var fd = new FormData(); fd.append('action','malisafi_chat_end_public'); fd.append('token', token);
                fetch(cfg.ajaxurl, {method:'POST', credentials:'same-origin', body: fd}).then(function(r){ return r.json(); }).then(function(resp){
                    if (resp && resp.success) {
                        // mark session ended
                        if (!session) session = {};
                        session.ended = true;
                        try { localStorage.setItem(sessionKey, JSON.stringify(session)); } catch(e) {}
                        messagesEl.innerHTML = '<div class="malisafi-chat-ended">' + (cfg.i18n && cfg.i18n.chatEnded ? cfg.i18n.chatEnded : 'This chat has ended. Start a new chat.') + '</div>' +
                            '<div class="malisafi-chat-actions"><button id="malisafi-public-start-new">Start new chat</button></div>';
                        var eb_hide = document.getElementById('malisafi-public-end-chat'); if (eb_hide) eb_hide.style.display = 'none';
                        if (messageEl) messageEl.disabled = true;
                        if (startBtn) startBtn.style.display = 'inline-block';
                        if (polling) { clearInterval(polling); polling = null; }
                    }
                }).catch(function(){});
            });
        }

        function startPublicChat(contact){
            // call admin-ajax to create a public thread + token
            function doStart(recaptchaToken){
                var form = new FormData();
                form.append('action','malisafi_chat_start_public');
            // send visitor contact fields if provided
            var c = contact || {};
            form.append('name', c.name || '');
            form.append('email', c.email || '');
            form.append('phone', c.phone || '');
                if(recaptchaToken) form.append('g-recaptcha-response', recaptchaToken);
                fetch(cfg.ajaxurl, {method:'POST', credentials:'same-origin', body: form})
                .then(function(r){ return r.json(); })
                .then(function(res){
                    if(res.success && res.data){
                        token = res.data.token;
                        threadId = res.data.thread_id;
                        messagesEl.innerHTML = '';
                        // persist session for returning visitors (store provided contact if any)
                        var c = contact || {};
                        session = { token: token, threadId: threadId, name: c.name || '', email: c.email || '', phone: c.phone || '', ended: false };
                        try { localStorage.setItem(sessionKey, JSON.stringify(session)); } catch(e) {}
                        showWelcomeIfNeeded();
                        fetchMessages();
                        polling = setInterval(fetchMessages, cfg.pollInterval || 5000);
                        var eb = document.getElementById('malisafi-public-end-chat'); if (eb) eb.style.display = 'inline-block';
                        // show message input and send button now that chat started
                        if (inputRow) inputRow.style.display = 'flex';
                        if (messageEl) messageEl.disabled = false;
                        if (startBtn) startBtn.style.display = 'none';
                        if (sendBtn) { sendBtn.style.display = 'inline-block'; updateSendState(); }
                    } else {
                        messagesEl.innerHTML = '<div class="error">Unable to start chat.</div>';
                    }
                }).catch(function(){ messagesEl.innerHTML = '<div class="error">Unable to start chat.</div>'; });
            }

            if(cfg.recaptchaSiteKey){
                if(typeof grecaptcha === 'undefined'){
                    var s = document.createElement('script');
                    s.src = 'https://www.google.com/recaptcha/api.js?render=' + cfg.recaptchaSiteKey;
                    s.onload = function(){ grecaptcha.ready(function(){ grecaptcha.execute(cfg.recaptchaSiteKey, {action:'start_chat'}).then(function(token){ doStart(token); }); }); };
                    document.head.appendChild(s);
                } else {
                    grecaptcha.ready(function(){ grecaptcha.execute(cfg.recaptchaSiteKey, {action:'start_chat'}).then(function(token){ doStart(token); }); });
                }
            } else {
                doStart(null);
            }
        }

        // Render messages as bubbles (with avatar initials, timestamps)
        function renderMessagesList(messages){
            if(!messages || !messages.length) return;
            var html = messages.map(function(m){
                var fromAgent = (m.sender_id && m.sender_id>0);
                var who = m.senderName || (fromAgent ? 'Agent' : (session && session.name ? session.name : 'You'));
                var time = m.created_at ? ('<span class="ts">'+escapeHtml(m.created_at)+'</span>') : '';
                var initials = who.split(' ').map(function(p){ return p.charAt(0); }).slice(0,2).join('').toUpperCase();
                var cls = fromAgent ? 'agent' : 'client';
                var avatarHtml = '<div class="avatar">'+escapeHtml(initials)+'</div>';
                // If agent and currentAgent has an avatar URL, use it
                if (fromAgent && currentAgent && currentAgent.avatar) {
                    avatarHtml = '<div class="avatar"><img src="'+escapeHtml(currentAgent.avatar)+'" alt="'+escapeHtml(currentAgent.name||'')+'" style="width:100%;height:100%;border-radius:50%"/></div>';
                }
                return '<div class="msg '+cls+'">'+avatarHtml+'<div class="bubble"><div class="bubble-body">'+escapeHtml(m.message)+'</div>'+time+'</div></div>';
            }).join('');
            messagesEl.innerHTML = html;
        }

        function fetchMessages(){
            if(!token) return;
            fetch(cfg.restBase + token)
            .then(function(r){ return r.json(); })
            .then(function(data){
                if (!data) return;
                // update agent info for UI
                if (data.agent) {
                    currentAgent = data.agent;
                    var statusEl = document.getElementById('malisafi-widget-status');
                    if (statusEl) {
                        if (currentAgent && currentAgent.name) {
                            statusEl.textContent = currentAgent.name + ' • ' + (currentAgent.typing ? (cfg.i18n.agentOnline || 'Agent is online') : (cfg.i18n.agentOnline || 'Agent is online'));
                        } else {
                            statusEl.textContent = cfg.i18n.agentOffline || 'Agent is offline';
                        }
                    }
                    var typingEl = document.getElementById('malisafi-typing-indicator');
                    if (typingEl) {
                        typingEl.style.display = (currentAgent && currentAgent.typing) ? 'block' : 'none';
                    }
                }
                // If thread has been ended on server, update UI and stop polling
                if (data.status && data.status !== 'active') {
                    // mark session ended
                    if (!session) session = {};
                    session.ended = true;
                    try { localStorage.setItem(sessionKey, JSON.stringify(session)); } catch(e) {}
                    messagesEl.innerHTML = '<div class="malisafi-chat-ended">' + (cfg.i18n && cfg.i18n.chatEnded ? cfg.i18n.chatEnded : 'This chat has ended. Start a new chat.') + '</div>' +
                        '<div class="malisafi-chat-actions"><button id="malisafi-public-start-new">Start new chat</button></div>';
                    var eb_hide2 = document.getElementById('malisafi-public-end-chat'); if (eb_hide2) eb_hide2.style.display = 'none';
                    if (messageEl) messageEl.disabled = true;
                    if (startBtn) startBtn.style.display = 'inline-block';
                    // stop polling
                    if (polling) { clearInterval(polling); polling = null; }
                    // wire start new
                    setTimeout(function(){
                        var btn = document.getElementById('malisafi-public-start-new');
                        if (btn) btn.addEventListener('click', function(){
                            try { localStorage.removeItem(sessionKey); } catch(e) {}
                            token = null; threadId = null; session = null;
                            startPublicChat();
                        });
                    }, 50);
                    return;
                }
                if(data && data.messages){
                    renderMessagesList(data.messages);

                    // if panel hidden, increase unread badge
                    if (panel.style.display === 'none') {
                        unread++; badge.textContent = unread; badge.style.display = 'inline-block';
                    }

                    // Always scroll to the bottom so newest message and input are visible
                    try { messagesEl.scrollTop = messagesEl.scrollHeight; } catch(e){}
                }
            }).catch(function(){});
        }

        form.addEventListener('submit', function(e){
            e.preventDefault();
            var msg = messageEl.value.trim();
            if(!msg){ alert(cfg.i18n.emptyMessage); return; }
            var payload = { message: msg, name: '', email: '' };
            if(!token){ alert('Chat not initialized'); return; }
            function doSend(recaptchaToken){
                if(recaptchaToken) payload['g-recaptcha-response'] = recaptchaToken;
                fetch(cfg.restBase + token, {
                    method: 'POST',
                    headers: {'Content-Type':'application/json'},
                    body: JSON.stringify(payload),
                    credentials: 'same-origin'
                }).then(function(r){ return r.json(); }).then(function(resp){
                    // handle server-side ended response
                    if (resp && resp.ended) {
                        // mark session ended and inform user
                        if (!session) session = {};
                        session.ended = true;
                        try { localStorage.setItem(sessionKey, JSON.stringify(session)); } catch(e) {}
                        messagesEl.innerHTML = '<div class="malisafi-chat-ended">' + (cfg.i18n && cfg.i18n.chatEnded ? cfg.i18n.chatEnded : 'This chat has ended. Start a new chat.') + '</div>' +
                            '<div class="malisafi-chat-actions"><button id="malisafi-public-start-new">Start new chat</button></div>';
                        var eb_hide3 = document.getElementById('malisafi-public-end-chat'); if (eb_hide3) eb_hide3.style.display = 'none';
                        if (messageEl) messageEl.disabled = true;
                        if (startBtn) startBtn.style.display = 'inline-block';
                        if (polling) { clearInterval(polling); polling = null; }
                        setTimeout(function(){
                            var btn = document.getElementById('malisafi-public-start-new');
                            if (btn) btn.addEventListener('click', function(){
                                try { localStorage.removeItem(sessionKey); } catch(e) {}
                                token = null; threadId = null; session = null;
                                startPublicChat();
                            });
                        }, 50);
                        return;
                    }
                    messageEl.value = '';
                    // persist minimal session (no contact fields)
                    if (!session) session = {};
                    try { localStorage.setItem(sessionKey, JSON.stringify(session)); } catch(e) {}
                    // refresh messages and ensure user sees latest message
                    fetchMessages();
                    setTimeout(function(){ try { messagesEl.scrollTop = messagesEl.scrollHeight; } catch(e){} }, 80);
                }).catch(function(){ alert('Send failed'); });
            }

            if(cfg.recaptchaSiteKey){
                if(typeof grecaptcha === 'undefined'){
                    var s = document.createElement('script');
                    s.src = 'https://www.google.com/recaptcha/api.js?render=' + cfg.recaptchaSiteKey;
                    s.onload = function(){ grecaptcha.ready(function(){ grecaptcha.execute(cfg.recaptchaSiteKey, {action:'public_message'}).then(function(token){ doSend(token); }); }); };
                    document.head.appendChild(s);
                } else {
                    grecaptcha.ready(function(){ grecaptcha.execute(cfg.recaptchaSiteKey, {action:'public_message'}).then(function(token){ doSend(token); }); });
                }
            } else {
                doSend(null);
            }
        });

        // Enable/disable send button based on content
        var sendBtn = document.getElementById('malisafi-widget-send');
        function updateSendState(){
            if(!sendBtn) return;
            var has = messageEl && messageEl.value.trim().length > 0;
            sendBtn.disabled = !has;
            if(has) sendBtn.classList.add('active'); else sendBtn.classList.remove('active');
        }
        if(messageEl){ messageEl.addEventListener('input', updateSendState); updateSendState(); }

        // sendMessage removed; messages are submitted via the form submit

        // Show welcome message when session has name/email
        function showWelcomeIfNeeded(){
            if (session && session.name) {
                var msg = (cfg.i18n && cfg.i18n.welcome) ? cfg.i18n.welcome.replace('%s', session.name) : ('Hi ' + session.name + ', thanks for joining!');
                messagesEl.innerHTML = '<div class="msg client"><div class="avatar">'+(session.name.charAt(0).toUpperCase())+'</div><div class="bubble"><div class="bubble-body">'+escapeHtml(msg)+'</div></div></div>' + (messagesEl.innerHTML||'');
                messagesEl.scrollTop = messagesEl.scrollHeight;
            }
        }
    });
})();
