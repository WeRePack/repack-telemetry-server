<?php

/**
 * Log data.
 *
 * @package     RePack Telemetry Server
 * @author      Philipp Wellmer
 * @copyright   Copyright (c) 2021, Philipp Wellmer
 * @license     https://opensource.org/licenses/GPL-2.0
 * @since       1.0
 */

namespace RePack_Telemetry_Server;

use WP_REST_Server;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Log data in the db.
 *
 * @since 1.0
 */
class LogSupporterSite {






	/**
	 * Prefix for our settings.
	 *
	 * @access private
	 * @since 1.0
	 * @var string
	 */
	private $option_prefix = 'repack_telemetry_data';

	/**
	 * The Site URL.
	 *
	 * @access private
	 * @since 1.0
	 * @var string
	 */
	private $site_url;

	/**
	 * The Site Host.
	 *
	 * @access private
	 * @since 1.0
	 * @var string
	 */
	private $site_host;


	/**
	 * The Site Language.
	 *
	 * @access private
	 * @since 1.0
	 * @var string
	 */
	private $site_lang;

	/**
	 * The Repack Start.
	 *
	 * @access private
	 * @since 1.0
	 * @var string
	 */
	private $repack_start;

	/**
	 * The Repack Consent Counter.
	 *
	 * @access private
	 * @since 1.0
	 * @var string
	 */
	private $repack_counter;

	/**
	 * The Repack Consent Ratio.
	 *
	 * @access private
	 * @since 1.0
	 * @var string
	 */
	private $repack_ratio;

	/**
	 * The last submit time.
	 *
	 * @access private
	 * @since 1.0
	 * @var string
	 */
	private $repack_last_sent;

	/**
	 * Constructor.
	 *
	 * @access public
	 * @since 1.0
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'werepack_api_register_route' ) );
	}

	/**
	 * Register Site REST API Route
	 * @since 2.0
	 *
	 * @return void
	 */
	public function werepack_api_register_route() {
		register_rest_route(
			'community/v1',
			'/sites',
			array(
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => array( $this, 'werepack_api_process_request' ),
			)
		);
	}

	/**
	 * Process API Request
	 * @since 2.0
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function werepack_api_process_request( \WP_REST_Request $request ) {
		$request_body = $request->get_body_params();
		$response     = new \WP_REST_Response(
			array(
				'message' => 'Successful',
			)
		);
		$response->set_status( 200 );

		// Get data from request & check completeness
		$missing_data = $this->get_missing_data_from_request( $request_body );

		if ( ! empty( $missing_data ) ) {
			return new \WP_Error(
				'invalid_request',
				'Error: Passed data is incomplete: ' . implode( ', ', $missing_data ) . ' missing. Please update WeRePack Plugin to latest version.',
				array(
					'status' => 403,
				)
			);
		}

		// Continue if required data is available && if site is pingable
		if ( $this->can_ping_site( $this->site_url ) ) {
			// Lookup existing supporter
			$supporter_id = $this->get_site_post();

			// Create post
			$this->create_site_post( $supporter_id );

			$response = new \WP_REST_Response(
				array(
					'message' => 'Data submitted successfully.',
				)
			);
			$response->set_status( 200 );
			return $response;
		} else {
			return new \WP_Error(
				'invalid_request',
				'Error: We were unable to reach your site.',
				array(
					'status' => 403,
				)
			);
		}
	}

	/**
	 * Get data from the request and set as object properties.
	 *
	 * @access private
	 * @return array
	 * @since 2.0
	 */
	private function get_missing_data_from_request( $request_body ) {
		$missing_data = array();

		$data_to_collect = array(
			'site_url',
			'site_lang',
			'repack_start',
			'repack_counter',
			'repack_ratio',
			'repack_coupon',
			'repack_last_sent',
		);

		foreach ( $data_to_collect as $property ) {
			if ( isset( $request_body[ $property ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.NoNonceVerification
				$this->$property = sanitize_text_field( wp_unslash( $request_body[ $property ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.NoNonceVerification
			} else {
				$missing_data[] = $property;
			}
		}


		ray($request_body);
		ray($missing_data);

		// Additionally, set Site Host
		$this->site_host = wp_parse_url( $this->site_url, PHP_URL_HOST );

		return $missing_data;
	}

	/**
	 * Look up existing supporter site
	 *
	 * @return int
	 */
	private function get_site_post() {
		return get_page_by_title( $this->site_host, OBJECT, 'repack_sites' )->ID;
	}

	/**
	 * Check if a URL is publicy pingable
	 *
	 * @param $url
	 *
	 * @return bool
	 */
	public function can_ping_site( $url ) {
		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 30,
				'headers' => array(
					'referer' => home_url(),
				),
			)
		);

		return is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ? false : true;
	}

	/**
	 * Creat/Update Supporter Post
	 *
	 * @param int $supporter_id
	 */
	private function create_site_post( $supporter_id = 0 ) {
		// Create / update Post
		$supporter_id = wp_insert_post(
			array(
				'ID'                => $supporter_id,
				'post_type'         => 'repack_sites',
				'post_date_gmt'     => gmdate( 'Y-m-d H:i', $this->repack_start ),
				'post_modified_gmt' => gmdate( 'Y-m-d H:i', $this->repack_last_sent ),
				'post_title'        => wp_strip_all_tags( $this->site_host ),
				'post_content'      => wp_strip_all_tags( $this->site_url ),
				'post_status'       => $supporter_id > 0 ? get_post_status( $supporter_id ) : 'pending',
				'post_author'       => 1,
			)
		);

		// Log data for PHP version and theme.
		foreach ( array( 'site_url', 'site_lang', 'repack_start', 'repack_counter', 'repack_ratio', 'repack_coupon', 'repack_last_sent' ) as $data_key ) {
			// Skip if no data defined.
			if ( ! $this->$data_key ) {
				continue;
			}

			$meta_name = $this->option_prefix . '_' . $data_key;

			// Update existing data.
			update_post_meta(
				$supporter_id,
				$meta_name,
				$this->$data_key
			);
		}

		// Log historical summary
		$history_meta_name = $this->option_prefix . '_history';

		// We want to log data per-week. E.g. '2021-48'
		$date_key = gmdate( 'Y-W' );

		// Get available history
		$history = (array) get_post_meta(
			$supporter_id,
			$history_meta_name,
			true
		);

		$history[ $date_key ] = array(
			'repack_start'     => $this->repack_start,
			'repack_last_sent' => $this->repack_last_sent,
			'repack_counter'   => $this->repack_counter,
			'repack_ratio'     => $this->repack_ratio,
		);

		update_post_meta(
			$supporter_id,
			$history_meta_name,
			$history
		);

		// Newly created supporter is "pending"
		do_action( 'repack_telemetry:after_supporter_updated', (int) $supporter_id, (string) get_post_status( $supporter_id ) );
	}
}
