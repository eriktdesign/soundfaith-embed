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

	public $sermon_regex = '/soundfaith\.com\/(sermons)\/(\d+)/i';
	public $profile_regex = '/soundfaith\.com\/(profile)\/(.+)/i';

	/**
	 * Initialize the plugin by adding providers. 
	 * In the future, options page functionality will be added here.
	 */
	public function __construct() {
		$this->add_providers();
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

	public function get_embed_url( $type, $id ) {
		$url = trailingslashit( $this->embed_base . $type ) . $id;

		if( "profile" == $type ) {
			$url = trailingslashit( $url ) . 'recent';
			return add_query_arg( $this->profile_options, $url );
		} else {
			return add_query_arg( $this->sermon_options, $url );
		}

	}

}

$SF_Embed = new SF_Embed();
