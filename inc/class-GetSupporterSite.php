<?php

/**
 * Get supporter data.
 *
 * @package     RePack Telemetry Server
 * @author      Philipp Wellmer
 * @copyright   Copyright (c) 2021, Philipp Wellmer
 * @license     https://opensource.org/licenses/GPL-2.0
 * @since       1.0
 */

namespace RePack_Telemetry_Server;

class GetSupporterSite {




	function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Things to do, places to see.
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function init() {
		// Publish & update supporter posts
		add_action( 'wp_insert_post', array( $this, 'add_supporter_screenshot' ), 10, 1 );
		add_action( 'repack_schedule_set_supporter_screenshot', array( $this, 'add_supporter_screenshot' ), 10, 1 );
	}

	public static function get_supporter_meta( $supporter_id, $meta_key ) {
		return get_post_meta( $supporter_id, 'repack_telemetry_data_' . $meta_key, true );
	}

	/**
	 * Hook wp_insert_post
	 *
	 * @param $post_id
	 * @param $post
	 * @param $update
	 */
	public function add_supporter_screenshot( $post_id ) {
		if ( get_post_type( $post_id ) === 'repack_sites' ) {
			if ( ! has_post_thumbnail( $post_id ) ) {
				// Create new screenshot and attach to supporter post
				$url = self::get_supporter_meta( $post_id, 'site_url' );
				$this->getScreenshot( $post_id, $url );
			}
		}
	}

	/**
	 * Get and save screenshot to post
	 *
	 * @param $supporter_id int Supporter Post ID
	 * @param $url string Website URL
	 *
	 * @return bool
	 */
	private function getScreenshot( $supporter_id, $url ) {
		if ( ! empty( $url ) && ! empty( $supporter_id ) ) {
			// Prepare the URL
			$url = urlencode( $url );

			// Get remote
			$remote   = 'https://s0.wordpress.com/mshots/v1/' . $url . '?w=1600';
			$response = wp_remote_get( $remote );

			if ( is_array( $response ) && ! is_wp_error( $response ) ) {
				$headers = wp_remote_retrieve_headers( $response ); // array of http header lines

				if ( isset( $headers['content-type'] ) && $headers['content-type'] === 'image/jpeg' ) {
					// Screenshots are JPEG, we can continue
					if ( $supporter_id ) {
						$post_title = get_the_title( $supporter_id );

						$attachment_data = array(
							'title'       => $post_title,
							'caption'     => $post_title,
							'alt_text'    => $post_title,
							'description' => $post_title,
						);
					}

					$result = $this->set_remote_image_as_featured_image( $supporter_id, $remote, $attachment_data );

					if ( $result && ! is_wp_error( $result ) ) {
						return true;
					}
				}
			}

			// No success, try again in a minute
			wp_schedule_single_event( time() + 60, 'repack_schedule_set_supporter_screenshot', array( $supporter_id ) );
		}

		return false;
	}

	/**
	 * Download a remote image, insert it into the media library
	 * and set it as a post's featured image.
	 *
	 * @param string $post_id        The ID of the post.
	 * @param string $url            The URL for the remote image.
	 *
	 * @param array $attachment_data {
	 *     Optional. Data to be used for the attachment.
	 *
	 *     @type string $title       The title. Also used to create the filename (ex: name-of-file.png).
	 *     @type string $caption     The caption.
	 *     @type string $alt_text    The alt text.
	 *     @type string $description The description.
	 * }
	 * @return bool True on success or false on failure.
	 */
	private function set_remote_image_as_featured_image( $post_id, $url, $attachment_data = array() ) {
		$download_remote_image = new DownloadRemoteImage( $url, $attachment_data );
		$attachment_id         = $download_remote_image->download();

		if ( ! $attachment_id ) {
			return false;
		}

		return set_post_thumbnail( $post_id, $attachment_id );
	}
}
