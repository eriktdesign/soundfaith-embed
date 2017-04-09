<?php 

/**
 *
 * @link              http://eriktdesign.com
 * @since             1.0.0
 * @package           SF_Embed
 *
 * @wordpress-plugin
 * Plugin Name:       SoundFaith Embed
 * Plugin URI:        http://eriktdesign.com
 * Description:       Enables SoundFaith.com as an embed provider
 * Version:           1.0.0
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

// Include settings page
if( ! class_exists( 'SF_Embed_Admin' ) ) {
	require_once('inc/soundfaith-embed-admin.php');
}

class SF_Embed {

	// Plugin version
	public $version = "1.0.0";

	public $embed_base = "https://soundfaith.com/embed/";

	public $sermon_defaults = array( 
		'includeSermonDetails' 	=> 'true',
	);

	public $profile_defaults = array(
		'includeSermonDetails' 	=> 'false',
		'includePlaylist' 		=> 'true',
		'includeThumbnail' 		=> 'true',
		'includeSpeaker' 		=> 'true',
		'includeSeries' 		=> 'true',
		'includeDatePresented' 	=> 'true',		
	);

	protected $sermon_options;
	protected $profile_options;

	protected $sermon_regex = '/soundfaith\.com\/(sermons)\/(\d+)/i';
	protected $profile_regex = '/soundfaith\.com\/(profile)\/(.+)/i';

	/**
	 * Initialize the plugin by adding providers. 
	 * In the future, options page functionality will be added here.
	 */
	public function __construct() {
		// Initialize default settings
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		// Remove options on deactivate
		register_deactivation_hook( __FILE__, array( $this, 'deactivate') );

		$this->sermon_options = get_option( 'soundfaith_embed_sermon_options' );
		$this->profile_options = get_option( 'soundfaith_embed_profile_options' );

		add_action( 'init', array( $this, 'settings_init' ) );
		$this->add_providers();
		add_action( 'wp_footer', array( $this, 'debug' ) );
	}

	/**
	 * Initialize plugin options with defaults
	 * @return void
	 */
	public function activate() {
		add_option( 'soundfaith_embed_sermon_options', $this->sermon_defaults );
		add_option( 'soundfaith_embed_profile_options', $this->profile_defaults );
	}

	/**
	 * Remove plugin options from database
	 * @return void
	 */
	public function deactivate() {
		delete_option( 'soundfaith_embed_sermon_options' );
		delete_option( 'soundfaith_embed_profile_options' );
	}

	/**
	 * Register embeds to their handlers
	 * @return void
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

	/**
	 * Generate the embed code for a SoundFaith sermon or profile
	 * @param  array $matches Regex matches for URL
	 * @param  array $attr    Attributes (for shortcode?)
	 * @param  string $url     Original URL
	 * @param  string $rawattr Raw attributes
	 * @return string          HTML for iframe embed
	 */
	public function sf_embed( $matches, $attr, $url, $rawattr ) {
		// The first match in the embed regex is the "type", either sermon or profile
		$type = $matches[1];
		// The second match is the numerical ID. This might break for profiles, but seems to work currently
		$id = $matches[2];

		// Using the type and ID, get the embed URL and dimensions for the iframe
		$embed_url = $this->get_embed_url( $type, $id );
		$dimensions = $this->get_dimensions( $type );

		// Construct the iframe tag
		$embed = sprintf(
			'<iframe frameborder="0" scrolling="no" allowfullscreen src="%s" width="%d" height="%d"></iframe>',
			$embed_url,
			$dimensions['width'],
			$dimensions['height']
		);

		// Return completed tag, allow for filtering
		return apply_filters( 'embed_sf', $embed, $matches, $attr, $url, $rawattr );
	}

	/**
	 * Get the URL for the iframe src
	 * @param  string $type Either sermon or profile
	 * @param  string $id   ID of sermon or profile
	 * @return string       URL for iframe src with query args added
	 */
	public function get_embed_url( $type, $id ) {
		// Build the url with base + type + id and slashes
		$url = trailingslashit( $this->embed_base . $type ) . $id;

		// Return the URL with the query args attached
		if( "profile" == $type ) {
			// Profiles need /recent on the end of the URL
			$url = trailingslashit( $url ) . 'recent';
			return add_query_arg( $this->profile_options, $url );
		} else {
			return add_query_arg( $this->sermon_options, $url );
		}
	}

	/**
	 * If we're in admin section, load the settings page class
	 * @return void
	 */
	public function settings_init() {
		if( is_admin() ) $SF_Embed_Admin = new SF_Embed_Admin();
	}

}

$SF_Embed = new SF_Embed();