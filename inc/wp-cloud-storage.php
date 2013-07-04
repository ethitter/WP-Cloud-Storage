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
		add_action( 'pre_get_posts', array( $this, 'action_pre_get_posts' ) );

		add_filter( 'query_vars', array( $this, 'filter_query_vars' ) );
		add_action( 'generate_rewrite_rules', array( $this, 'action_generate_rewrite_rules' ), 99 );

		add_action( 'attachment_link', array( $this, 'filter_attachment_link' ), 10, 2 );
		add_filter( 'wp_get_attachment_link', array( $this, 'filter_wp_get_attachment_link' ), 10, 4 );

		add_action( 'template_redirect', array( $this, 'action_template_redirect' ) );

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
		add_action( 'admin_menu', array( $this, 'action_admin_menu' ) );
		add_action( 'admin_bar_menu', array( $this, 'admin_bar' ), 99 );
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
	 *
	 * @param array $vars
	 * @filter query_vars
	 * @return array
	 */
	public function filter_query_vars( $vars ) {
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
	 * Modify attachment link on single pages
	 *
	 * @param string $link
	 * @param int $id
	 * @param mixed $size
	 * @param bool $permalink
	 * @uses is_singular
	 * @uses this::download_link
	 * @uses __
	 * @uses esc_attr
	 * @uses apply_filters
	 * @uses get_the_title
	 * @filter wp_get_attachment_link
	 * @return string
	 */
	public function filter_wp_get_attachment_link( $link, $id, $size, $permalink ) {
		if ( is_singular() && ! $permalink ) {
			$link = preg_replace( "#(?<=href=(\"|'))[^\"']+(?=(\"|'))#", $this->download_link( $id ), $link );

			$title = sprintf( __( 'Click to download &quot;%s&quot;', 'wp_cloud_storage' ), esc_attr( apply_filters( 'the_title', get_the_title( $id ) ) ) );
			$link = preg_replace( "#(?<=title=(\"|'))[^\"']+(?=(\"|'))#", $title, $link );
		}

		return $link;
	}

	/**
	 * @todo better checking of passed post ID
	 */
	public function action_template_redirect() {
		if ( get_query_var( $this->qv ) == $this->download_base ) {
			$id = (int) get_query_var( 'p' );

			if ( ! $id )
				wp_die( 'The requested item could not be located.' );

			$attached_item = get_attached_file( $id );

			if ( ! $attached_item )
				wp_die( 'The requested item could not be located.' );

			header( 'Content-Description: File Transfer' );
		    header( 'Content-Type: application/octet-stream' );
		    header( 'Content-Disposition: attachment; filename=' . pathinfo( $attached_item, PATHINFO_BASENAME ) );
		    header( 'Content-Transfer-Encoding: binary' );
		    header( 'Expires: 0' );
		    header( 'Cache-Control: must-revalidate' );
		    header( 'Pragma: public' );
		    header( 'Content-Length: ' . filesize( $attached_item ) );
		    ob_clean();
		    flush();
		    readfile( $attached_item );
		    exit;
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
	 * Retrieve download link for given attachment ID
	 *
	 * @param int $id
	 * @uses user_trailingslashit
	 * @uses home_url
	 * @return string
	 */
	public function download_link( $id ) {
		return user_trailingslashit( home_url( $this->download_base . '/' . $id ) );
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

	/**
	 * Remove items from menu and Dashboard
	 *
	 * @global $menu
	 * @global $submenu
	 * @uses remove_meta_box
	 * @action admin_menu
	 * @return null
	 */
	public function action_admin_menu() {
		// Remove Posts menu item
		global $menu, $submenu;
		unset( $menu[5] );
		unset( $submenu['edit.php']);

		// Hide Right Now metabox since it's generally useless for us
		remove_meta_box( 'dashboard_right_now', 'dashboard', 'core' );
		remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'core' );
		remove_meta_box( 'dashboard_recent_drafts', 'dashboard', 'core' );
		remove_meta_box( 'dashboard_quick_press', 'dashboard', 'core' );
	}

	/**
	 * Remove "new content" menu item
	 *
	 * Most items in the list aren't relevant, so let's lose the clutter.
	 *
	 * @global $wp_admin_bar
	 * @action admin_bar_menu
	 * @return null
	 */
	public function admin_bar() {
		global $wp_admin_bar;

		$wp_admin_bar->remove_menu( 'new-content' );
	}
}
WP_Cloud_Storage_Base::get_instance();

/**
 * Retrieve download link for given attachment ID
 *
 * @param int $id
 * @uses get_the_ID
 * @uses WP_Cloud_Storage_Base::download_link
 * @return string
 */
function wp_cloud_storage_get_download_link( $id = false ) {
	$id = (int) $id;
	if ( ! $id )
		$id = get_the_ID();

	return WP_Cloud_Storage_Base::get_instance()->download_link( $id );
}
