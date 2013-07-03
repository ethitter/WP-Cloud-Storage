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

	private $rewrite_base = 'f';
	private $download_base = 'dl';
	private $qv = 'wpcs-action';

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
		add_action( 'parse_request', array( $this, 'action_parse_request' ) );

		add_action( 'pre_get_posts', array( $this, 'action_pre_get_posts' ) );

		add_filter( 'query_vars', array( $this, 'filter_query_vars' ) );
		add_action( 'generate_rewrite_rules', array( $this, 'action_generate_rewrite_rules' ), 99 );
		add_action( 'attachment_link', array( $this, 'filter_attachment_link' ), 10, 2 );

		add_filter( 'upload_mimes', array( $this, 'filter_upload_mimes' ) );

		// Disable commenting
		add_filter( 'pre_option_default_comment_status', array( $this, 'comment_ping_status' ) );
		add_filter( 'pre_option_default_ping_status', array( $this, 'comment_ping_status' ) );
		add_filter( 'pre_option_comment_moderation', array( $this, 'comment_moderation' ) );
		add_filter( 'pre_option_comment_whitelist', '__return_null' );
		add_filter( 'wp_insert_post_data', array( $this, 'comment_status_override' ), 10 );
		add_action( 'wp_loaded', array( $this, 'disable_comments' ) );

		// Misc
		add_action( 'init', array( $this, 'disable_wp_core_features' ) );
	}

	/**
	 *
	 */
	public function action_parse_request( $request ) {
		if ( array_key_exists( $this->qv, $request->query_vars ) && $this->download_base == $request->query_vars[ $this->qv ] ) {
			$id = array_key_exists( 'p', $request->query_vars ) ? (int) $request->query_vars['p'] : false;

			if ( ! $id )
				wp_die( 'The requested item could not be located.' );

			$attached_item = wp_get_attachment_url( $id );

			if ( ! $attached_item )
				wp_die( 'The requested item could not be located.' );

			$mime_type = get_post_mime_type( $id );

			$filename = pathinfo( $attached_item, PATHINFO_BASENAME );

			header( 'Content-Description: File Transfer' );
			header( 'Content-Type: ' . $mime_type );
			header( 'Content-Transfer-Encoding: binary' );
			// header( 'Content-Length: ' . filesize( $attached_item ) );
			header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
			header( 'Location: ' . $attached_item );
			// readfile( $attached_item );
			exit;
		}
	}

	/**
	 *
	 */
	public function action_pre_get_posts( $query ) {
		if ( ! is_admin() && $query->is_main_query() ) {
			$query->set( 'post_type', 'attachment' );
			$query->set( 'post_status', 'inherit' );
		}
	}

	/**
	 * Add a new query var specific to this plugin
	 */
	public function filter_query_vars( $vars) {
		$vars[] = $this->qv;

		return $vars;
	}

	/**
	 * Register rewrite rules for short URLs in the form of "f/123" and "dl/123" for attachments
	 *
	 * @param object $rewrite
	 * @action generate_rewrite_rules
	 * @return null
	 */
	public function action_generate_rewrite_rules( $rewrite ) {
		$short_rules = array(
			'(' . $this->rewrite_base . '|' . $this->download_base . ')' . '/([\d]+)/?$' => $rewrite->index . '?p=$matches[2]&' . $this->qv . '=$matches[1]',
			'(' . $this->rewrite_base . '|' . $this->download_base . ')' . '/([\d]+)/trackback/?$' => $rewrite->index . '?p=$matches[2]&tb=1&' . $this->qv . '=$matches[1]',
			'(' . $this->rewrite_base . '|' . $this->download_base . ')' . '/([\d]+)/feed/(feed|rdf|rss|rss2|atom)/?$' => $rewrite->index . '?p=$matches[2]&feed=$matches[3]&' . $this->qv . '=$matches[1]',
			'(' . $this->rewrite_base . '|' . $this->download_base . ')'. '/([\d]+)/(feed|rdf|rss|rss2|atom)/?$' => $rewrite->index . '?p=$matches[2]&feed=$matches[3]&' . $this->qv . '=$matches[1]',
			'(' . $this->rewrite_base . '|' . $this->download_base . ')' . '/([\d]+)/comment-page-([0-9]{1,})/?$' => $rewrite->index . '?p=$matches[2]&cpage=$matches[3]&' . $this->qv . '=$matches[1]',
		);

		$rewrite->rules = array_merge( $short_rules, $rewrite->rules );
	}

	/**
	 * Rewrite attachment links to be in the form of "f/123"
	 *
	 * @param string $link
	 * @param int $post_id
	 * @uses home_url
	 * @uses user_trailingslashit
	 * @filter attachment_link
	 * @return string
	 */
	public function filter_attachment_link( $link, $post_id ) {
		$link = home_url( $this->rewrite_base . '/' . $post_id );

		$link = user_trailingslashit( $link );

		return $link;
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

	/**
	 * Disable various unnecessary or irrelevant Core features
	 *
	 * @uses remove_action
	 * @action init
	 * @return null
	 */
	public function disable_wp_core_features() {
		remove_action( 'wp_head', 'feed_links', 2 );
		remove_action( 'wp_head', 'feed_links_extra', 3 );
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0 );
	}
}
WP_Cloud_Storage_Base::get_instance();
