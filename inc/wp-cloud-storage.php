<?php
/*
Plugin Name: WP Cloud Storage
Plugin URI: http://www.ethitter.com/
Description:
Author: Erick Hitter
Version: 0.1
Author URI: http://www.ethitter.com/

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class WP_Cloud_Storage_Base {
	/**
	 * Singleton
	 */
	private static $__instance = null;

	/**
	 * Class variables
	 */


	/**
	 * Silence is golden!
	 */
	private function __construct() {}

	/**
	 * Singleton implementation
	 *
	 * @uses self::setup
	 * @return object
	 */
	public static function get_instance() {
		if ( ! is_a( self::$__instance, __CLASS__ ) ) {
			self::$__instance = new self;

			self::$__instance->setup();
		}

		return self::$__instance;
	}

	/**
	 * Magic getter to provide access to class variables
	 *
	 * @param string $name
	 * @return mixed
	 */
	// public function __get( $name ) {
	//	if ( property_exists( $this, $name ) )
	//		return $this->$name;
	//	else
	//		return null;
	// }

	/**
	 * Register actions and filters
	 *
	 * @uses add_action
	 * @uses add_filter
	 * @return null
	 */
	private function setup() {
		add_action( 'pre_get_posts', array( $this, 'action_pre_get_posts' ) );

		add_filter( 'upload_mimes', array( $this, 'filter_upload_mimes' ) );

		// Commenting
		add_filter( 'pre_option_default_comment_status', array( $this, 'comment_ping_status' ) );
		add_filter( 'pre_option_default_ping_status', array( $this, 'comment_ping_status' ) );
		add_filter( 'pre_option_comment_moderation', array( $this, 'comment_moderation' ) );
		add_filter( 'pre_option_comment_whitelist', '__return_null' );
		add_filter( 'wp_insert_post_data', array( $this, 'comment_status_override' ), 10 );
		add_action( 'wp_loaded', array( $this, 'disable_comments' ) );
	}

	/**
	 *
	 */
	public function action_pre_get_posts( $query ) {
		if ( $query->is_main_query() ) {
			$query->set( 'post_type', 'attachment' );
			$query->set( 'post_status', 'inherit' );
		}
	}

	/**
	 *
	 */
	public function filter_upload_mimes( $types ) {
		$types['zip'] = 'application/zip';
		$types['ico'] = 'image/x-icon';
		$types['patch|diff'] = 'text/html';

		return $types;
	}

	/**
	 * Ensure no comments or pings are accepted on new posts
	 *
	 * @param string $status
	 * @filter pre_option_default_comment_status
	 * @filter pre_option_default_ping_status
	 * @return string
	 */
	public function comment_ping_status( $status ) {
		return 'closed';
	}

	/**
	 * Enforce administrator comment moderation
	 *
	 * @param string $moderation
	 * @filter pre_option_comment_moderation
	 * @return int
	 */
	public function comment_moderation( $moderation ) {
		return 1;
	}

	/**
	 * Ensure that all new and updated posts are closed to pings and comments
	 *
	 * @param array $data
	 * @filter wp_insert_post_data
	 * @return array
	 */
	public function comment_status_override( $data ) {
		$data['comment_status'] = $data['ping_status'] = 'closed';

		return $data;
	}

	/**
	 * Strip comments support from all post types that have it
	 *
	 * @uses get_post_types
	 * @uses post_type_supports
	 * @uses remove_post_type_support
	 * @action wp_loaded
	 * @return null
	 */
	public function disable_comments() {
		$post_types = get_post_types();

		foreach ( $post_types as $post_type ) {
			if ( post_type_supports( $post_type, 'comments' ) )
				remove_post_type_support( $post_type, 'comments' );
		}
	}
}
WP_Cloud_Storage_Base::get_instance();
