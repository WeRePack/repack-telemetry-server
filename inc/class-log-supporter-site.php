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

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Log data in the db.
 *
 * @since 1.0
 */
class logSupporterSite {


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
		add_action( 'wp', array( $this, 'init' ) );
	}

	/**
	 * Things to do, places to see.
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function init() {

		// Early exit if this is not a request we want to log.
		if ( ! isset( $_POST['action'] ) || 'repack-stats' !== sanitize_text_field( wp_unslash( $_POST['action'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.NoNonceVerification
			return;
		}

		// Get data from request & check completeness
		$continue_processing = $this->get_data_from_request();

		// Exit if required data is not available.
		if ( ! $continue_processing ) {
			return;
		}

		// Lookup existing supporter
		$supporter_id = $this->get_site_post();

		// Create post
		$this->create_site_post( $supporter_id );
	}

	/**
	 * Get data from the request and set as object properties.
	 *
	 * @access private
	 * @since 1.0
	 * @return bool
	 */
	private function get_data_from_request() {
		$continue_processing = true;

		$data_to_collect = array(
			'siteURL'        => 'site_url',
			'siteLang'       => 'site_lang',
			'repackStart'    => 'repack_start',
			'repackCounter'  => 'repack_counter',
			'repackRatio'    => 'repack_ratio',
			'repackLastSent' => 'repack_last_sent',
		);

		foreach ( $data_to_collect as $key => $property ) {
			if ( isset( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.NoNonceVerification
				$this->$property = sanitize_text_field( wp_unslash( $_POST[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.NoNonceVerification
			} else {
				// Do not process further actions
				$continue_processing = false;
			}
		}

		// Additionally set Site Host
		$this->site_host = wp_parse_url( $this->site_url, PHP_URL_HOST );

		return $continue_processing;
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
		foreach ( array( 'site_url', 'site_lang', 'repack_start', 'repack_counter', 'repack_ratio', 'repack_last_sent' ) as $data_key ) {
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
	}
}
