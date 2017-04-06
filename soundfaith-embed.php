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

class SF_Embed {

	// Plugin version
	public $version = "0.1.0";

	public $embed_sermon_base = "https://soundfaith.com/embed/sermons/";
	public $embed_profile_base = "https://soundfaith.com/embed/profile/";

	public $embed_options = array(
		'includeSermonDetails' 	=> 'false',
		'includePlaylist' 		=> 'false',
		'includeThumbnail' 		=> 'false',
		'includeSpeaker' 		=> 'false',
		'includeSeries' 		=> 'false',
		'includeDatePresented' 	=> 'false',
	);

	public $sermon_regex = '/soundfaith\.com\/sermons\/(\d+)/i';
	public $profile_regex = '/soundfaith\.com\/profile\/(.+)/i';

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
		wp_embed_register_handler( 'sf_sermon', $this->sermon_regex, array( $this, 'sermon_embed' ) );
		wp_embed_register_handler( 'sf_profile', $this->profile_regex, array( $this, 'playlist_embed' ) );
	}

	/**
	 * Determine the proper dimensions for the iframe embed
	 * @return array Width and height in pixels for iframe
	 */
	public function get_dimensions() {
		// Check for the global content width
		if ( ! isset( $content_width ) ) {
			$content_width = 600;
		}

		// Set the embed width to the content width
		$width = $content_width;
		$height = 450; // default height

		// Determine height based on inclusion of playlist and sermon details
		if( 'false' == $this->embed_options['includePlaylist'] ) {
			if( 'true' == $this->embed_options['includeSermonDetails'] ) $height = ( 505 / 600 ) * $width;
			else $height = ( 450 / 600 ) * $width;
		} else {
			if( 'true' == $this->embed_options['includeSermonDetails'] ) $height = ( 818 / 600 ) * $width;
			else $height = ( 763 / 600 ) * $width;
		}

		// Return as an array of width and height
		return array(
			'width' => $width,
			'height' => $height,
		);
	}

	/**
	 * Generate embed URL for sermons
	 * @param  string $id ID of sermon on SoundFaith
	 * @return string     URL for embed version of sermon
	 */
	public function get_sermon_url( $id ) {
		$url = $this->embed_sermon_base . $id;
		$this->embed_options['includePlaylist'] = 'false';
		return add_query_arg( $this->embed_options, $url );
	}

	/**
	 * Generate embed URL for profile/playlists
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function get_playlist_url( $id ) {
		$url = $this->embed_profile_base . $id . '/recent';
		$this->embed_options['includePlaylist'] = 'true';
		return add_query_arg( $this->embed_options, $url );
	}

	/**
	 * Generate embed code for sermon
	 * @param  array $matches Array of matches to the URL regex
	 * @param  array $attr    Attributes for embed
	 * @param  string $url     Original URL
	 * @param  string $rawattr Raw attributes
	 * @return string          HTML output for embed
	 */
	public function sermon_embed( $matches, $attr, $url, $rawattr ) {
		$embed_url = $this->get_sermon_url($matches[1]);
		$dimensions = $this->get_dimensions();
		
		$embed = sprintf(
			'<iframe frameborder="0" scrolling="no" allowfullscreen src="%s" width="%d" height="%d"></iframe>',
			$embed_url,
			$dimensions['width'],
			$dimensions['height']
		);

		return apply_filters( 'embed_sf', $embed, $matches, $attr, $url, $rawattr );
	}

	/**
	 * Generate embed code for playlist
	 * @param  array $matches Array of matches to the URL regex
	 * @param  array $attr    Attributes for embed
	 * @param  string $url     Original URL
	 * @param  string $rawattr Raw attributes
	 * @return string          HTML output for embed
	 */
	public function playlist_embed( $matches, $attr, $url, $rawattr ) {
		$embed_url = $this->get_playlist_url($matches[1]);
		$dimensions = $this->get_dimensions();

		$embed = sprintf(
			'<iframe frameborder="0" scrolling="no" allowfullscreen src="%s" width="%d" height="%d"></iframe>',
			$embed_url,
			$dimensions['width'],
			$dimensions['height']
		);

		return apply_filters( 'embed_sf', $embed, $matches, $attr, $url, $rawattr );
	}

}

$SF_Embed = new SF_Embed();
