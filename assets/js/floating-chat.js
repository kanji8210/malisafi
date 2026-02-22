  // Error box
  if ($('.malisafi-public-chat-error').length === 0) {
    $('.malisafi-public-chat-modal .malisafi-public-chat-input-area').append('<div class="malisafi-public-chat-error" style="color:#d63638;font-size:14px;margin-top:4px;display:none;"></div>');
  }

  // Error handling helpers
  function showChatError(msg) {
    var errorBox = $('.malisafi-public-chat-error');
    var input = $('.malisafi-public-chat-input');
    var sendBtn = $('.malisafi-public-chat-send');
    errorBox.text(msg).show();
    input.css('border','1px solid #d63638');
    sendBtn.prop('disabled', true);
    setTimeout(function() {
      errorBox.fadeOut(200);
      input.css('border','1px solid var(--mls-border-light)');
      sendBtn.prop('disabled', false);
    }, 3500);
  }

  // Send message logic
  $('.malisafi-public-chat-send').on('click', function() {
    var input = $('.malisafi-public-chat-input');
    var msg = input.val().trim();
    if (!msg) {
      showChatError('Message cannot be empty.');
      return;
    }
    // Add to sampleMessages for demo
    sampleMessages.push({name: 'You', text: msg, time: new Date().toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'})});
    renderPublicChatMessages(sampleMessages);
    input.val('');
    $('.malisafi-public-chat-send').prop('disabled', false);
    $('.malisafi-public-chat-input').css('border','1px solid var(--mls-border-light)');
  });

  // Enter key sends message
  $('.malisafi-public-chat-input').on('keydown', function(e) {
    if (e.key === 'Enter') {
      if ($('body').length && $('.malisafi-floating-chat-btn').length === 0) {
        $('body').append('<div class="malisafi-floating-chat-btn" title="Chat with Us"><span class="dashicons dashicons-format-chat"></span><span style="margin-left:8px; font-size:1.1rem;">Chat with Us</span></div>');
    $('.malisafi-public-chat-error').hide();
    $(this).css('border','1px solid var(--mls-border-light)');
    $('.malisafi-public-chat-send').prop('disabled', false);
  });
// --- Public Chat UI ---
$(document).ready(function() {
  // ...existing code...

  // Public chat AJAX logic
  var publicChatMessages = [];

  function renderPublicChatMessages(messages) {
    var msgArea = $('.malisafi-public-chat-messages');
    msgArea.empty();
    messages.forEach(function(msg) {
      msgArea.append(
        '<div class="malisafi-public-chat-msg" style="margin-bottom:12px;">' +
          '<div style="font-weight:600;color:var(--mls-dark);">' + msg.name + '</div>' +
          '<div style="margin:4px 0 0 0;color:var(--mls-text-primary);">' + msg.text + '</div>' +
          '<div style="font-size:12px;color:var(--mls-grey-green);margin-top:2px;">' + msg.time + '</div>' +
        '</div>'
      );
    });
    msgArea.scrollTop(msgArea[0].scrollHeight);
  }

  // Fetch messages from backend
  function fetchPublicChatMessages() {
    $.post(malisafiChat.ajaxurl, {
      action: 'malisafi_public_chat_fetch',
      nonce: malisafiChat.nonce
    }, function(resp) {
      if (resp.success && Array.isArray(resp.data.messages)) {
        publicChatMessages = resp.data.messages;
        renderPublicChatMessages(publicChatMessages);
      }
    });
  }

  // Initial fetch
  fetchPublicChatMessages();

  // Poll every 10s
  setInterval(fetchPublicChatMessages, 10000);

  // Send message logic
  $('.malisafi-public-chat-send').on('click', function() {
    var input = $('.malisafi-public-chat-input');
    var msg = input.val().trim();
    if (!msg) {
      showChatError('Message cannot be empty.');
      return;
    }
    $('.malisafi-public-chat-send').prop('disabled', true);
    $.post(malisafiChat.ajaxurl, {
      action: 'malisafi_public_chat_send',
      nonce: malisafiChat.nonce,
      message: msg
    }, function(resp) {
      $('.malisafi-public-chat-send').prop('disabled', false);
      if (resp.success) {
        input.val('');
        fetchPublicChatMessages();
        $('.malisafi-public-chat-input').css('border','1px solid var(--mls-border-light)');
      } else {
        showChatError(resp.data && resp.data.message ? resp.data.message : 'Unable to send message.');
      }
    }).fail(function() {
      $('.malisafi-public-chat-send').prop('disabled', false);
      showChatError('Unable to send message.');
    });
  });

  // ...existing code...
});
// --- Public Chat UI ---
// Ensure chat button is placed everywhere, even on AJAX-loaded pages
(function ensureChatButton() {
  function injectChatButton() {
    console.log('[Malisafi Chat Debug] injectChatButton called');
    if ($('body').length && $('.malisafi-public-chat-float').length === 0) {
      console.log('[Malisafi Chat Debug] Appending floating chat button');
      $('body').append('<div class="malisafi-public-chat-float">💬</div>');
    }
    if ($('body').length && $('.malisafi-public-chat-modal').length === 0) {
      console.log('[Malisafi Chat Debug] Appending public chat modal');
      $('body').append(
        '<div class="malisafi-public-chat-modal" style="display:none;position:fixed;bottom:80px;right:32px;z-index:10000;background:var(--mls-light-grey);border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,0.18);width:360px;max-width:98vw;max-height:80vh;overflow:hidden;flex-direction:column;">' +
          '<div class="malisafi-public-chat-header" style="background:var(--mls-dark);color:#fff;padding:16px 20px;font-weight:600;font-size:18px;">Public Chat</div>' +
          '<div class="malisafi-public-chat-messages" style="flex:1;overflow-y:auto;padding:16px 20px;background:var(--mls-light-grey);"></div>' +
          '<div class="malisafi-public-chat-input-area" style="padding:12px 20px;background:var(--mls-light-grey);display:flex;gap:8px;align-items:center;">' +
            '<input type="text" class="malisafi-public-chat-input" placeholder="Type your message..." style="flex:1;padding:8px 12px;border-radius:8px;border:1px solid var(--mls-border-light);background:#fff;font-size:15px;" />' +
            '<button class="malisafi-public-chat-send" style="background:var(--mls-dark);color:#fff;border:none;padding:8px 16px;border-radius:8px;font-weight:600;">Send</button>' +
          '</div>' +
        '</div>'
      );
    }
  }
  injectChatButton();
    console.log('[Malisafi Chat Debug] injectChatButton executed');
  // Re-inject on DOM changes (for SPA/AJAX sites)
  var observer = new MutationObserver(function() { injectChatButton(); });
    console.log('[Malisafi Chat Debug] MutationObserver attached');
  observer.observe(document.documentElement, {childList:true,subtree:true});

  // Show modal on button click (fix selector)
  $(document).on('click', '.malisafi-floating-chat-btn', function() {
      console.log('[Malisafi Chat Debug] Floating chat button clicked');
    $('.malisafi-public-chat-modal').fadeIn(180);
  });

  // Hide modal on outside click
  $(document).on('mousedown', function(e) {
      console.log('[Malisafi Chat Debug] mousedown event for modal');
    var modal = $('.malisafi-public-chat-modal');
    if (modal.is(':visible') && !modal.is(e.target) && modal.has(e.target).length === 0 && !$('.malisafi-floating-chat-btn').is(e.target)) {
      modal.fadeOut(120);
    }
  });
})();
jQuery(document).ready(function($) {
  // Floating chat button
  var chatBtn = $('<div class="malisafi-floating-chat-btn" title="Chat with Us"><span class="dashicons dashicons-format-chat"></span><span style="margin-left:8px; font-size:1.1rem;">Chat with Us</span></div>');
  // Remove any existing floating chat button to avoid duplicates
  $('.malisafi-floating-chat-btn').remove();
  $('body').append(chatBtn);

  // Modal markup
  var modal = $('<div class="malisafi-chat-modal" style="display:none;">\
    <div class="malisafi-chat-modal-content">\
      <span class="malisafi-chat-modal-close">&times;</span>\
      <div class="malisafi-chat-modal-body">\
        <div style="font-weight:600;font-size:1.15rem;margin-bottom:8px;color:var(--mls-dark);">Chat with Us</div>\
        <label for="malisafi-chat-select-role" style="font-size:0.98rem;">Select recipient:</label>\
        <select id="malisafi-chat-select-role" style="margin-bottom:12px; width:100%; border-radius:6px; border:1px solid var(--mls-border-light); padding:6px 8px;">\
          <option value="">select role</option>\
          <option value="admin">Admin</option>\
          <option value="moderator">Moderator</option>\
          <option value="agent">Agent</option>\
        </select>\
        <div class="malisafi-chat-modal-notice" style="display:none; color:var(--mls-accent); margin-bottom:12px;"></div>\
        <div class="malisafi-chat-thread-area"></div>\
      </div>\
    </div>\
  </div>');
  $('body').append(modal);

  chatBtn.on('click', function() {
    modal.show();
    modal.find('.malisafi-chat-thread-area').empty();
    modal.find('.malisafi-chat-modal-notice').hide();
    modal.find('#malisafi-chat-select-role').val('admin');
  });

  modal.find('.malisafi-chat-modal-close').on('click', function() {
    modal.hide();
  });

  modal.find('#malisafi-chat-select-role').on('change', function() {
    var role = $(this).val();
    modal.find('.malisafi-chat-thread-area').empty();
    if (role === 'agent') {
      modal.find('.malisafi-chat-modal-notice').text('To contact an agent, please use the property inquiry form.').show();
      return;
    } else {
      modal.find('.malisafi-chat-modal-notice').hide();
    }
    // Find admin/moderator user
    var recipientId = 0;
    var recipientLabel = '';
    if (role === 'admin') {
      recipientId = malisafiChat.adminId;
      recipientLabel = 'Admin';
    } else if (role === 'moderator') {
      recipientId = malisafiChat.moderatorId;
      recipientLabel = 'Moderator';
    }
    if (recipientId > 0) {
      // Show online status (simulate: online if admin/moderator exists)
      var onlineStatus = '<span class="malisafi-chat-online-indicator" style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#2ecc40;margin-right:6px;"></span> Online';
      var statusHtml = '<div style="margin-bottom:8px;font-size:0.98rem;color:var(--mls-dark);">'+recipientLabel+' <span style="font-size:0.92rem;color:#2ecc40;">'+onlineStatus+'</span></div>';
      // Always render chat UI, even if AJAX fails
      var renderChatUI = function(messages, threadId, userName) {
        var html = '<div class="malisafi-chat-thread">';
        html += statusHtml;
        html += '<div class="malisafi-chat-messages">';
        if (!messages || messages.length === 0) {
          if (userName) {
            html += '<div class="malisafi-chat-message"><strong>' + recipientLabel + ':</strong> Hi, ' + userName + '!</div>';
          } else {
            html += '<div class="malisafi-chat-message"><strong>' + recipientLabel + ':</strong> Hi, can I have your name?</div>';
          }
        } else {
          messages.forEach(function(msg) {
            html += '<div class="malisafi-chat-message' + (msg.isOwn ? ' own' : '') + '"><strong>' + msg.senderName + ':</strong> ' + msg.message + '<br><small>' + msg.createdAtHuman + '</small></div>';
          });
        }
        html += '</div>';
        html += '<textarea class="malisafi-chat-input" placeholder="'+malisafiChat.i18n.typeMessage+'"></textarea>';
        html += '<button class="malisafi-chat-send-btn">'+malisafiChat.i18n.send+'</button>';
        html += '<div class="malisafi-chat-send-error" style="color:#d63638;margin-top:6px;display:none;"></div>';
        html += '</div>';
        modal.find('.malisafi-chat-thread-area').html(html);
        // Send message handler
        modal.find('.malisafi-chat-send-btn').off('click').on('click', function() {
          var msg = modal.find('.malisafi-chat-input').val();
          var errorBox = modal.find('.malisafi-chat-send-error');
          errorBox.hide();
          if (!threadId) {
            // Try to create thread before sending
            $.post(malisafiChat.ajaxurl, {
              action: 'malisafi_chat_open_thread',
              nonce: malisafiChat.nonce,
              target_user_id: recipientId
            }, function(threadResp) {
              if (threadResp.success && threadResp.data.threadId) {
                threadId = threadResp.data.threadId;
                sendChatMessage(threadId, msg, errorBox);
              } else {
                errorBox.text('Unable to start chat. Please try again.').show();
              }
            }).fail(function() {
              errorBox.text('Unable to start chat. Please try again.').show();
            });
          } else {
            sendChatMessage(threadId, msg, errorBox);
          }
        });
        function sendChatMessage(threadId, msg, errorBox) {
          $.post(malisafiChat.ajaxurl, {
            action: 'malisafi_chat_send_message',
            nonce: malisafiChat.nonce,
            thread_id: threadId,
            message: msg
          }, function(sendResp) {
            if (sendResp.success && sendResp.data.message) {
              var newMsg = sendResp.data.message;
              var msgHtml = '<div class="malisafi-chat-message own"><strong>' + newMsg.senderName + ':</strong> ' + newMsg.message + '<br><small>' + newMsg.createdAtHuman + '</small></div>';
              modal.find('.malisafi-chat-messages').append(msgHtml);
              modal.find('.malisafi-chat-input').val('');
              errorBox.hide();
              // Switch to live chat mode if admin is online
              if (recipientId === malisafiChat.adminId) {
                modal.find('.malisafi-chat-modal-body').addClass('malisafi-live-mode');
                // Show live indicator
                if (modal.find('.malisafi-chat-live-indicator').length === 0) {
                  modal.find('.malisafi-chat-modal-body').prepend('<div class="malisafi-chat-live-indicator" style="color:var(--mls-accent);margin-bottom:8px;font-weight:600;">Live chat: Admin is online</div>');
                }
                // Optionally, start polling for new messages
                startLivePolling(threadId);
              }
            } else {
              errorBox.text(malisafiChat.i18n.sendFailed).show();
            }
          }).fail(function() {
            errorBox.text('Unable to send message. Please try again.').show();
          });
        }

        // Live polling for new messages if admin is online
        var livePollingInterval = null;
        function startLivePolling(threadId) {
          if (livePollingInterval) clearInterval(livePollingInterval);
          var lastMessageId = modal.find('.malisafi-chat-message').last().data('id') || 0;
          livePollingInterval = setInterval(function() {
            $.post(malisafiChat.ajaxurl, {
              action: 'malisafi_chat_fetch_messages',
              nonce: malisafiChat.nonce,
              thread_id: threadId,
              last_message_id: lastMessageId
            }, function(resp) {
              if (resp.success && resp.data.messages && resp.data.messages.length > 0) {
                resp.data.messages.forEach(function(msg) {
                  var msgHtml = '<div class="malisafi-chat-message" data-id="' + msg.id + '"><strong>' + msg.senderName + ':</strong> ' + msg.message + '<br><small>' + msg.createdAtHuman + '</small></div>';
                  modal.find('.malisafi-chat-messages').append(msgHtml);
                  lastMessageId = msg.id;
                });
              }
            });
          }, 5000); // Poll every 5 seconds
        }
      };
      // Bootstrap chat and open thread
      var bootstrapTimeout = setTimeout(function() {
        // If no response in 10s, show busy form
        showBusyForm();
      }, 10000);
      $.post(malisafiChat.ajaxurl, {
        action: 'malisafi_chat_bootstrap',
        nonce: malisafiChat.nonce
      }, function(resp) {
        clearTimeout(bootstrapTimeout);
        var userName = '';
        if (resp.success && resp.data && resp.data.currentUser && resp.data.currentUser.name) {
          userName = resp.data.currentUser.name;
        }
        $.post(malisafiChat.ajaxurl, {
          action: 'malisafi_chat_open_thread',
          nonce: malisafiChat.nonce,
          target_user_id: recipientId
        }, function(threadResp) {
          clearTimeout(bootstrapTimeout);
          if (threadResp.success) {
            renderChatUI(threadResp.data.messages, threadResp.data.threadId, userName);
          } else {
            renderChatUI([], null, userName);
          }
        }).fail(function() {
          clearTimeout(bootstrapTimeout);
          renderChatUI([], null, userName);
        });
      }).fail(function() {
        clearTimeout(bootstrapTimeout);
        showBusyForm();
      });

      function showBusyForm() {
        modal.find('.malisafi-chat-thread-area').html(
          '<div style="margin-bottom:12px;color:var(--mls-accent);font-weight:600;">All agents are busy. Please leave your details and we will contact you later.</div>' +
          '<form class="malisafi-chat-busy-form" style="display:flex;flex-direction:column;">' +
            '<label for="malisafi-busy-name">Name*</label>' +
            '<input type="text" id="malisafi-busy-name" name="name" placeholder="Your Name" required style="width:100%;margin-bottom:8px;padding:6px 8px;border-radius:6px;border:1px solid var(--mls-border-light);" />' +
            '<label for="malisafi-busy-email">Email*</label>' +
            '<input type="email" id="malisafi-busy-email" name="email" placeholder="Your Email" required style="width:100%;margin-bottom:8px;padding:6px 8px;border-radius:6px;border:1px solid var(--mls-border-light);" />' +
            '<label for="malisafi-busy-phone">Phone</label>' +
            '<input type="tel" id="malisafi-busy-phone" name="phone" placeholder="Your Phone" style="width:100%;margin-bottom:8px;padding:6px 8px;border-radius:6px;border:1px solid var(--mls-border-light);" />' +
            '<label for="malisafi-busy-message">Message*</label>' +
            '<textarea id="malisafi-busy-message" name="message" placeholder="Your Message" required style="width:100%;margin-bottom:8px;padding:6px 8px;border-radius:6px;border:1px solid var(--mls-border-light);"></textarea>' +
            '<button type="submit" class="button malisafi-chat-busy-send" style="background:var(--mls-dark);color:#fff;border:none;padding:8px 16px;border-radius:6px;min-width:90px;align-self:flex-end;">Send</button>' +
            '<div class="malisafi-chat-busy-error" style="color:#d63638;margin-top:6px;display:none;"></div>' +
          '</form>'
        );
        modal.find('.malisafi-chat-busy-form').on('submit', function(e) {
          e.preventDefault();
          var form = $(this);
          var errorBox = form.find('.malisafi-chat-busy-error');
          errorBox.hide();
          var data = {
            action: 'malisafi_chat_store_contact',
            nonce: malisafiChat.nonce,
            name: form.find('input[name="name"]').val(),
            email: form.find('input[name="email"]').val(),
            phone: form.find('input[name="phone"]').val(),
            message: form.find('textarea[name="message"]').val()
          };
          $.post(malisafiChat.ajaxurl, data, function(resp) {
            if (resp.success) {
              form.replaceWith('<div style="color:var(--mls-accent);font-weight:600;">Thank you! We will contact you soon.</div>');
            } else {
              errorBox.text('Unable to send message. Please try again.').show();
            }
          }).fail(function() {
            errorBox.text('Unable to send message. Please try again.').show();
          });
        });
      }
    }
  });
  // Trigger change to load default admin chat
  modal.find('#malisafi-chat-select-role').trigger('change');
});