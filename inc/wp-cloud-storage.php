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
	}

	/**
	 *
	 */
	public function action_pre_get_posts( $query ) {
		// if ( $query->is_main_query() )
		//	$query->set( 'post_type', 'attachment' );
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
}
WP_Cloud_Storage_Base::get_instance();
