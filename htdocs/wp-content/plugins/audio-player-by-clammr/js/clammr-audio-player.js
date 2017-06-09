var clammrPlugin = clammrPlugin || {};

clammrPlugin.MediaPlayer = {
    init: function (selector) {
        jQuery(selector).mediaelementplayer( {
            features: ['playpause', 'current', 'progress', 'duration', 'volume', 'clammrit']
        });
    }
};

(function($) {
    MediaElementPlayer.prototype.buildclammrit = function(player, controls, layers, media) {
        var
            clammrit =  
            $('<div class="mejs-clammr-button">' +
                '<button type="button" title="Share Clip">Share Clip</button>' +
            '</div>')            
            .appendTo(controls)            
            .click(function() {                
                player.pause();

                var $clammrPlayer = clammrit.closest(".clammr-player");
                var title = $clammrPlayer.attr("data-title");
                var imageUrl = $clammrPlayer.attr("data-imageUrl");
                var startTime = Math.max(0, (player.getCurrentTime() - 15) * 1000); // rewind 15 seconds
                var referralName = "WordPress-" + window.location.hostname;
             
                var url = "http://www.clammr.com/app/clammr/crop" +
                    "?audioUrl=" + encodeURIComponent(player.media.src) +
                    "&audioStartTime=" + startTime +
                    "&attributeUrl=" + encodeURIComponent(window.location.href) + 
                    "&title=" + encodeURIComponent(title) +
                    "&imageUrl=" + encodeURIComponent(imageUrl) +
                    "&referralName=" + encodeURIComponent(referralName);
                 window.open(url, 'cropPlugin', 'width=1000, height=750, scrollbars=1, resizable=1');
            });   
            
        // MediaElement has a bug where if the timestamps change size (audio > 60mins), the controls don't resize        
        media.addEventListener('loadedmetadata', function() {           
            setTimeout(function() {                
                player.resetSize();
            }, 500);
	        
		}, false);  

        
    }
})(jQuery);