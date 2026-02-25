/*
 * Internal Chat frontend script
 * Uses localized `malisafiChat` object (provided by PHP)
 */
(function($){
    'use strict';

    $(document).ready(function(){
        if (typeof malisafiChat === 'undefined') {
            return;
        }

        var cfg = malisafiChat || {};

        // Provide a safe, no-op initializer so other scripts can call it
        window.malisafiInternalChatInit = window.malisafiInternalChatInit || function(){
            // Minimal safe init: no DOM mutations by default
            return { ready: true };
        };

        // If the floating widget is enabled, ensure polling functions exist (no-op until server handlers are added)
        if (cfg.isFloatingWidget) {
            window.malisafiInternalChat = window.malisafiInternalChat || {
                pollInterval: cfg.pollInterval || 10000,
                lastPoll: 0,
                startPolling: function(){},
                stopPolling: function(){}
            };
        }
    });

})(jQuery);