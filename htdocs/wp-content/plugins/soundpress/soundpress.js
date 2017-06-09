jQuery(function($) {
    $(document).ready(function(){
        $('#finish-soundpress').click(embed_soundcloud);
        $('.soundpressmodal-close').click(close_soundpress_window);
        
        $('#insert-soundpress').click(open_soundpress_window);
    });

    function open_soundpress_window() {
        $('#soundpress-form').show();
    }
    
    function close_soundpress_window(){
        $('#soundpress-form').hide();
    }
    
    function embed_soundcloud(){
        var url = $.trim($('#soundcloud_url_txt').val());
        var height = $.trim($('#sc_height_txt').val());
        var autoplay= document.getElementById('sc_autoplay_txt').checked;
        var showuser= document.getElementById('sc_showusername_txt').checked;
        var showart= document.getElementById('sc_showart_txt').checked;
        var soundframe = '';
        
        if(url != ''){
            if(height == ''){
                height = 'auto';
            }
            
            url = url.replace(':','%3A');//encoding for iframe url
            
            soundframe='<iframe width="100%" height="'+height+'" scrolling="no" frameborder="no" src="https://w.soundcloud.com/player/?url='+url+'&amp;auto_play='+autoplay+'&amp;hide_related=false&amp;show_comments=true&amp;show_user='+showuser+'&amp;show_reposts=false&amp;visual='+showart+'"></iframe>';
            
            wp.media.editor.insert(soundframe);
            close_soundpress_window();
        }else{
            alert('No SoundCloud URL specified.');
        }
    }
});