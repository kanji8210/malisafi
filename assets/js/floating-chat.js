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
            } else {
              errorBox.text(malisafiChat.i18n.sendFailed).show();
            }
          }).fail(function() {
            errorBox.text('Unable to send message. Please try again.').show();
          });
        }
      };
      // Bootstrap chat and open thread
      $.post(malisafiChat.ajaxurl, {
        action: 'malisafi_chat_bootstrap',
        nonce: malisafiChat.nonce
      }, function(resp) {
        var userName = '';
        if (resp.success && resp.data && resp.data.currentUser && resp.data.currentUser.name) {
          userName = resp.data.currentUser.name;
        }
        $.post(malisafiChat.ajaxurl, {
          action: 'malisafi_chat_open_thread',
          nonce: malisafiChat.nonce,
          target_user_id: recipientId
        }, function(threadResp) {
          if (threadResp.success) {
            renderChatUI(threadResp.data.messages, threadResp.data.threadId, userName);
          } else {
            // Render UI with no messages, no threadId
            renderChatUI([], null, userName);
          }
        }).fail(function() {
          renderChatUI([], null, userName);
        });
      }).fail(function() {
        renderChatUI([], null, '');
      });
    }
  });
  // Trigger change to load default admin chat
  modal.find('#malisafi-chat-select-role').trigger('change');
});