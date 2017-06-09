<?php
/**
 * @package Clammr
 * @version 1.1.5
 */
/*
Plugin Name: Audio Player by Clammr
Plugin URI: https://wordpress.org/plugins/audio-player-by-clammr/
Description: A drop-in replacement for the default Audio Player with the added ability to easily create audio highlights that can be shared on Clammr, Facebook and Twitter. The audio player is compatible on all major desktop and mobile browsers. For any questions or feedback, please email us at "support at clammr dot com"
Version: 1.1.5
Author: Clammr, Inc
Author URI: http://www.clammr.com
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class ClammrPlayer	{

    static $instance = false;
    public static function getInstance() {
		if ( !self::$instance )
			self::$instance = new self;
		return self::$instance;
	}

    private function __construct() {		        
        add_action( 'wp_enqueue_scripts', array( $this, 'addScripts' ) );	               
        add_action( 'wp_footer', array( $this, 'setupFooter' ) );
        add_action( 'wp_head', array( $this, 'setupHeader' ) );
  
        add_action( 'init', array( $this, 'initialize' ) );
        add_action( 'admin_init', array( $this, 'initializeAdmin' ) );
        add_action( 'admin_menu', array( $this, 'setupAdminMenu' ) );
	}

    public function initialize() {       
        add_shortcode( 'audio-clammr', array( $this, 'handleShortcode' ) );		

        // Check the setting as to whether we should override the audio shortcode (default on)
        $useAudioShortCode = get_option( 'useAudioShortcode', 1);
        if($useAudioShortCode) {
            remove_shortcode('audio');
		    add_shortcode('audio', array($this, 'handleShortcode'));
        }        
    }

    public function initializeAdmin() {                    
	    register_setting( 'clammr_settings_page', 'useAudioShortcode' );

	    add_settings_section(
		    'clammr_settings_mainsection_id', 
		    'Settings',
		    array($this, 'clammr_settings_section_output'), 
		    'clammr_settings_page'
	    );

	    add_settings_field( 
		    'clammr_settings_useAudioShortcode_id', 
		    'Override [audio] shortcode',
		    array($this, 'clammr_setting_useAudioShortcode_render'), 
		    'clammr_settings_page', 
		    'clammr_settings_mainsection_id'
	    );
    }

    public function setupAdminMenu() {        
        add_menu_page( 'Clammr Audio Player', 'Clammr Player', 'manage_options', 'clammr_audio_player', array($this,'clammr_audio_player_settings_page'), plugins_url( '/images/clammr_logo.png', __FILE__ ) );
    }

    function clammr_setting_useAudioShortcode_render(  ) {         
        $useAudioShortCode = get_option( 'useAudioShortcode', 1);
        ?>        
        <input name="useAudioShortcode" id="useAudioShortcode" type="checkbox" value="1" <?php checked( $useAudioShortCode, 1); ?> />
        <em>Checking this option will automatically replace all audio players rendered with the default [audio] shortcode</em>
        <?php
    }   

    function clammr_settings_section_output(  ) {         
	    echo '';
    }

    function clammr_audio_player_settings_page(  ) { 
	    ?>
	    <form action='options.php' method='post'>		
		    <h2>Clammr Audio Player</h2>		
            <p>A drop-in replacement for the default Audio Player with the added ability to easily create audio highlights that can be shared on Clammr, Facebook and Twitter. The audio player is compatible on all major desktop and mobile browsers. </p>
            <h3>Shortcode Format</h3>
            <code>[audio-clammr mp3="http://www.example.com/your_audio_file.mp3"]</code>            

		    <?php
		    do_settings_sections( 'clammr_settings_page' );
            settings_fields( 'clammr_settings_page' );	    
		    submit_button();
		    ?>		

	    </form>
	    <?php
    }

    public function handleShortcode( $attr, $content = "", $shortcode = "") {
        $post_id = get_post() ? get_the_ID() : 0;        
        static $instances = 0;
        $instances++;
                    
        $defaults_atts = array(
            'mp3'      => '',
            'src'      => '',
            'loop'     => '',
            'autoplay' => '',
            'preload'  => 'none'
        );
        
        $atts = shortcode_atts( $defaults_atts, $attr, 'audio' );        

        // Clammr It only supports mp3s. 
        // Check either the "mp3" attribute or the "src" for a valid mp3 file
        $mp3 = $atts['mp3'];
        $src = $atts['src'];
        if($mp3 == "" && $src != "") {
            if($this->endsWith(strtolower($src), ".mp3")) {
                $mp3 = $src;
            }
        }        
      
        // continue using default WP player if it's not an mp3
        if($mp3 == "") {
            return wp_audio_shortcode($atts);
        }
  
        $html_atts = array(            
            'id'       => sprintf( 'audio-%d-%d', $post_id, $instances ),
            'loop'     => wp_validate_boolean( $atts['loop'] ),
            'autoplay' => wp_validate_boolean( $atts['autoplay'] ),
            'preload'  => $atts['preload'],
            'style'    => 'width: 100%; visibility: hidden;',
        );

        // These ones should just be omitted altogether if they are blank
        foreach ( array( 'loop', 'autoplay', 'preload' ) as $a ) {
            if ( empty( $html_atts[$a] ) ) {
                unset( $html_atts[$a] );
            }
        }

        $attr_strings = array();
        foreach ( $html_atts as $k => $v ) {
            $attr_strings[] = $k . '="' . esc_attr( $v ) . '"';
        }
                             
        $imageUrl = $this->get_post_imageUrl(get_the_ID());
        $title = get_the_title();        
        $html = sprintf( '<div class="clammr-player" data-title="%s" data-imageUrl="%s"><audio %s src="%s"></audio></div>', esc_attr( $title ), $imageUrl, join(' ', $attr_strings), $mp3);
        return $html;
    }

    public function endsWith($haystack, $needle) {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }
        return (substr($haystack, -$length) === $needle);
    }

    public function setupHeader() {        
        // Initialize GA
        $initGA  = "";
        $initGA .= "<script>";
        $initGA .= "(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){";
        $initGA .= "(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),";
        $initGA .= "m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)";
        $initGA .= "})(window,document,'script','//www.google-analytics.com/analytics.js','ga');";
        $initGA .= "ga('create', 'UA-47029214-1', 'auto', 'clammrTracker');";
        $initGA .= "ga('clammrTracker.send', 'event', 'WordpressPlugin', 'PluginLoaded',  window.location.hostname);";
        $initGA .= "</script>";
        echo $initGA;
    }

    public function setupFooter() {
        // Initialize the players
        $initplayers  = "";
        $initplayers .= "<script>";
        $initplayers .= "jQuery(document).ready(function () {";
        $initplayers .= "clammrPlugin.MediaPlayer.init('.clammr-player audio');";
        $initplayers .= "});";
        $initplayers .= "</script>";   
        echo $initplayers;
    }

    public function addScripts() {   
        wp_register_style( 'clammr-player-style', plugins_url('/css/clammr-audio-player.css', __FILE__) );
        wp_enqueue_style( 'clammr-player-style' );
        wp_enqueue_style( 'wp-mediaelement' );

        wp_enqueue_script('clammr-player-script', plugins_url( '/js/clammr-audio-player.js' , __FILE__ ), array('jquery', 'wp-mediaelement') );            
    }

    function get_post_imageUrl( $postID ) {
	    $args = array(
		    'numberposts' => 1,
		    'order' => 'ASC',
		    'post_mime_type' => 'image',
		    'post_parent' => $postID,
		    'post_status' => null,
		    'post_type' => 'attachment',
	    );
	    $attachments = get_children( $args );
        
        $imageUrlToReturn = "";
        $biggestArea = 0;
	    if ( $attachments ) {           
            // returns the biggest image in this post
		    foreach ( $attachments as $attachment_id => $attachment ) {
                $image_attributes = wp_get_attachment_image_src( $attachment_id, 'full' );
                $url = $image_attributes[0];
                $width = $image_attributes[1];
                $height = $image_attributes[2];
                $area = $width * $height;

                if( $area > $biggestArea ) {
                    $biggestArea = $area;
                    $imageUrlToReturn = $url;
                }
		    }
	    }
        return $imageUrlToReturn;
    }
}

// Instantiate our class
$ClammrPlayer = ClammrPlayer::getInstance();


function clammrplayer_install() {
	$option = get_option('powerpress_clammr'); // will be either 0 or 1 if its been configured
	if( $option == '' ) // if empty (never configured)
		update_option('powerpress_clammr', 1); // Enable, the setting was never configured
}
add_action('activate_audio-player-by-clammr/clammr-audio-player.php', 'clammrplayer_install');
