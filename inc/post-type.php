<?php
/**
 * Post Type & Custom Fields for Supporter Sites.
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
 * Add Supporter Sites Post Type
 */
if ( ! function_exists( 'repack_supporter_sites_post_type' ) ) {
	// Register Custom Post Type
	add_action(
		'init',
		function () {
			$labels  = array(
				'name'                  => _x( 'Supporter Sites', 'Post Type General Name', 'repack-ts' ),
				'singular_name'         => _x( 'Supporter Site', 'Post Type Singular Name', 'repack-ts' ),
				'menu_name'             => __( 'Supporter Sites', 'repack-ts' ),
				'name_admin_bar'        => __( 'Supporter Sites', 'repack-ts' ),
				'archives'              => __( 'Supporter Sites', 'repack-ts' ),
				'attributes'            => __( 'Site Attributes', 'repack-ts' ),
				'parent_item_colon'     => __( 'Parent Item:', 'repack-ts' ),
				'all_items'             => __( 'All Sites', 'repack-ts' ),
				'add_new_item'          => __( 'Add New Site', 'repack-ts' ),
				'add_new'               => __( 'Add New', 'repack-ts' ),
				'new_item'              => __( 'New Site', 'repack-ts' ),
				'edit_item'             => __( 'Edit Site', 'repack-ts' ),
				'update_item'           => __( 'Update site', 'repack-ts' ),
				'view_item'             => __( 'View Site', 'repack-ts' ),
				'view_items'            => __( 'View Sites', 'repack-ts' ),
				'search_items'          => __( 'Search Site', 'repack-ts' ),
				'not_found'             => __( 'Not found', 'repack-ts' ),
				'not_found_in_trash'    => __( 'Not found in Trash', 'repack-ts' ),
				'featured_image'        => __( 'Screenshot', 'repack-ts' ),
				'set_featured_image'    => __( 'Set Screenshot', 'repack-ts' ),
				'remove_featured_image' => __( 'Remove Screenshot', 'repack-ts' ),
				'use_featured_image'    => __( 'Use as Screenshot', 'repack-ts' ),
				'insert_into_item'      => __( 'Insert into site', 'repack-ts' ),
				'uploaded_to_this_item' => __( 'Uploaded to this site', 'repack-ts' ),
				'items_list'            => __( 'Sites list', 'repack-ts' ),
				'items_list_navigation' => __( 'Sites list navigation', 'repack-ts' ),
				'filter_items_list'     => __( 'Filter sites list', 'repack-ts' ),
			);
			$rewrite = array(
				'slug'       => 'supporter',
				'with_front' => true,
				'pages'      => true,
				'feeds'      => true,
			);
			$args    = array(
				'label'               => __( 'Supporter Site', 'repack-ts' ),
				'description'         => __( 'WeRePack Supporter Sites', 'repack-ts' ),
				'labels'              => $labels,
				'supports'            => array( 'title', 'thumbnail', 'trackbacks', 'custom-fields' ),
				'hierarchical'        => false,
				'public'              => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'menu_position'       => 20,
				'menu_icon'           => 'dashicons-heart',
				'show_in_admin_bar'   => true,
				'show_in_nav_menus'   => true,
				'can_export'          => true,
				'has_archive'         => true,
				'exclude_from_search' => false,
				'publicly_queryable'  => true,
				'rewrite'             => $rewrite,
				'capability_type'     => 'page',
				'show_in_rest'        => true,
			);
			register_post_type( 'repack_sites', $args );
		},
		0
	);
}

/**
 * Add Expired Post Status
 */
if ( ! function_exists( 'repack_supporter_sites_post_status' ) ) {

	// Register Custom Status
	add_action(
		'init',
		function () {

			$args = array(
				'label'                     => _x( 'Expired', 'Status General Name', 'repack-ts' ),
				'label_count'               => _n_noop( 'Expired (%s)', 'Expired (%s)', 'repack-ts' ),
				'public'                    => false,
				'show_in_admin_all_list'    => false,
				'show_in_admin_status_list' => true,
				'exclude_from_search'       => true,
				'post_type'                 => array( 'repack_sites' ),
				'dashicon'                  => 'dashicons-hidden',
			);
			register_post_status( 'Expired', $args );

		},
		5
	);
}
