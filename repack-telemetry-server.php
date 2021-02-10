<?php

/**
 * Plugin Name:   RePack Telemetry Server
 * Plugin URI:    https://WeRePack.org/
 * Description:   Gathering anonymous data from sites using the RePack plugin and providing some useful stats and insights.
 * Author:        Philipp Wellmer
 * Author URI:    http://werepack.org
 * Version:       1.0
 * Text Domain:   repack-ts
 *
 * GitHub Plugin URI: https://github.com/ouun/repack-telemetry-server
 *
 * @package     RePack Telemetry Server
 * @author      Ari Stathopoulos
 * @author      Philipp Wellmer
 * @copyright   Copyright (c) 2019, Aristeides Stathopoulos
 * @copyright   Copyright (c) 2021, Philipp Wellmer
 * @license     https://opensource.org/licenses/GPL-2.0
 * @since       1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load files
require_once __DIR__ . '/inc/post-type.php';
require_once __DIR__ . '/inc/class-DownloadRemoteImage.php';
require_once __DIR__ . '/inc/class-GetSupporterSite.php';
require_once __DIR__ . '/inc/class-LogSupporterSite.php';

// Init
new RePack_Telemetry_Server\LogSupporterSite();
new RePack_Telemetry_Server\GetSupporterSite();

// phpcs:enable
