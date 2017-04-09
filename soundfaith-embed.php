<?php 

/**
 *
 * @link              http://eriktdesign.com
 * @since             0.2.0
 * @package           SF_Embed
 *
 * @wordpress-plugin
 * Plugin Name:       SoundFaith Embed
 * Plugin URI:        http://eriktdesign.com
 * Description:       Enables SoundFaith.com as an embed provider
 * Version:           0.2.0
 * Author:            Erik Teichmann
 * Author URI:        http://eriktdesign.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       sf-embed
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if( ! class_exists( 'SF_Embed_Admin' ) ) {
	require_once('inc/soundfaith-embed-admin.php');
}

class SF_Embed {

	// Plugin version
	public $version = "0.2.0";

	public $embed_base = "https://soundfaith.com/embed/";

	public $sermon_options = array( 
		'includeSermonDetails' 	=> 'true',
	);

	public $profile_options = array(
		'includeSermonDetails' 	=> 'false',
		'includePlaylist' 		=> 'true',
		'includeThumbnail' 		=> 'true',
		'includeSpeaker' 		=> 'true',
		'includeSeries' 		=> 'true',
		'includeDatePresented' 	=> 'true',		
	);

	public $default_options = array( 
		's_includeSermonDetails' => 'on',
		'includeSermonDetails' => 'on',
		'includeThumbnail' => '',
		'includeSpeaker' => '',
		'includeSeries' => '',
		'includeDatePresented' => '',
	);

	public $sermon_regex = '/soundfaith\.com\/(sermons)\/(\d+)/i';
	public $profile_regex = '/soundfaith\.com\/(profile)\/(.+)/i';

	/**
	 * Initialize the plugin by adding providers. 
	 * In the future, options page functionality will be added here.
	 */
	public function __construct() {
		// add_action( 'admin_init', array( $this, 'settings_init' ) );
		// add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'init', array( $this, 'settings_init' ) );
		$this->add_providers();
		add_action( 'wp_footer', array( $this, 'debug' ) );
	}

	/**
	 * Register embeds to their handlers
	 */
	public function add_providers() {
		wp_embed_register_handler( 'sf_sermon', $this->sermon_regex, array( $this, 'sf_embed' ) );
		wp_embed_register_handler( 'sf_profile', $this->profile_regex, array( $this, 'sf_embed' ) );
	}

	/**
	 * Determine the proper dimensions for the iframe embed
	 * @return array Width and height in pixels for iframe
	 */
	public function get_dimensions( $type ) {
		// Check for the global content width
		if ( ! isset( $content_width ) ) {
			$content_width = 600;
		}

		// Set the embed width to the content width
		$width = $content_width;
		$height = 450; // default height

		// Determine height based on inclusion of playlist and sermon details
		if( "sermon" == $type ) {
			if( 'true' == $this->sermon_options['includeSermonDetails'] ) $height = ( 505 / 600 ) * $width;
			else $height = ( 450 / 600 ) * $width;
		} elseif( "profile" == $type ) {
			if( 'true' == $this->profile_options['includeSermonDetails'] ) $height = ( 818 / 600 ) * $width;
			else $height = ( 763 / 600 ) * $width;
		}

		// Return as an array of width and height
		return array(
			'width' => $width,
			'height' => $height,
		);
	}

	public function get_options( $type ) {
		$options = get_option( 'soundfaith-embed-admin', array() );

		$options = wp_parse_args( $options, $this->default_options );

		if( $type == 'sermon' ) {
			return array( 'includeSermonDetails' => 'true' );
		} else {
			unset( $options['s_includeSermonDetails' ] );
		}

		return $options;
	}

	/**
	 * Generate the embed code for a SoundFaith sermon or profile
	 * @param  array $matches Regex matches for URL
	 * @param  array $attr    Attributes (for shortcode?)
	 * @param  string $url     Original URL
	 * @param  string $rawattr Raw attributes
	 * @return string          HTML for iframe embed
	 */
	public function sf_embed( $matches, $attr, $url, $rawattr ) {
		$type = $matches[1];
		$id = $matches[2];

		$embed_url = $this->get_embed_url( $type, $id );
		$dimensions = $this->get_dimensions( $type );

		$embed = sprintf(
			'<iframe frameborder="0" scrolling="no" allowfullscreen src="%s" width="%d" height="%d"></iframe>',
			$embed_url,
			$dimensions['width'],
			$dimensions['height']
		);

		return apply_filters( 'embed_sf', $embed, $matches, $attr, $url, $rawattr );
	}

	/**
	 * Get the URL for the iframe src
	 * @param  string $type Either sermon or profile
	 * @param  string $id   ID of sermon or profile
	 * @return string       URL for iframe src with query args added
	 */
	public function get_embed_url( $type, $id ) {
		$url = trailingslashit( $this->embed_base . $type ) . $id;

		if( "profile" == $type ) {
			$url = trailingslashit( $url ) . 'recent';
			return add_query_arg( $this->profile_options, $url );
		} else {
			return add_query_arg( $this->sermon_options, $url );
		}
	}

	public function settings_init() {
		if( is_admin() ) $SF_Embed_Admin = new SF_Embed_Admin();
	}

	public function debug() {
		$soundfaith_embed_sermon_options = get_option( 'soundfaith_embed_sermon_options' ); // Array of All Options
		$soundfaith_embed_profile_options = get_option( 'soundfaith_embed_profile_options' ); // Array of All Options
		echo '<h2>Debug</h2>';
		print_r( $soundfaith_embed_sermon_options );
		print_r( $soundfaith_embed_profile_options );
	}

}

$SF_Embed = new SF_Embed();