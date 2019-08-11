<?php
/**
 * Handles loading of JS and CSS.
 *
 * @since 1.0.0
 * @package TenUp\Auto_Tweet
 */

namespace TenUp\AutoTweet\Admin\Assets;

use function TenUp\Auto_Tweet\Utils\{get_autotweet_meta, opted_into_autotweet};
use const TenUp\Auto_Tweet\Core\Post_Meta\{ENABLE_AUTOTWEET_KEY, TWEET_BODY_KEY};
use function TenUp\AutoTweet\REST\post_autotweet_meta_rest_route;

const SCRIPT_HANDLE = 'autotweet';

/**
 * Adds WP hook callbacks.
 *
 * @since 1.0.0
 */
function add_hook_callbacks() {
	add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\maybe_enqueue_classic_editor_assets' );
	add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\enqueue_editor_assets' );
}

/**
 * Enqueues assets for supported post type editors where the block editor is not active.
 *
 * @since 1.0.0
 * @param string $hook The current admin page.
 */
function maybe_enqueue_classic_editor_assets( $hook ) {
	if ( ! in_array( $hook, [ 'post-new.php', 'post.php' ], true ) ) {
		return;
	}

	if ( ! opted_into_autotweet( get_the_ID() ) ) {
		return;
	}

	$current_screen = get_current_screen();
	if ( method_exists( $current_screen, 'is_block_editor' ) && $current_screen->is_block_editor() ) {
		return;
	}

	$handle = 'admin_tenup-auto-tweet';
	wp_enqueue_script(
		$handle,
		trailingslashit( TUAT_URL ) . 'assets/js/admin-auto_tweet.js',
		[ 'jquery' ],
		TUAT_VERSION,
		true
	);

	wp_enqueue_style(
		$handle,
		trailingslashit( TUAT_URL ) . 'assets/css/admin-auto_tweet.css',
		[],
		TUAT_VERSION
	);

	localize_data( $handle );
}

/**
 * Enqueues block editor assets.
 *
 * @since 1.0.0
 */
function enqueue_editor_assets() {
	if ( ! opted_into_autotweet( get_the_ID() ) ) {
		return;
	}

	wp_enqueue_script(
		SCRIPT_HANDLE,
		trailingslashit( TUAT_URL ) . 'dist/autotweet.js',
		[ 'wp-plugins', 'wp-edit-post' ],
		TUAT_VERSION,
		true
	);

	localize_data();
}

/**
 * Passes data to Javascript.
 *
 * @since 1.0.0
 * @param string $handle Handle of the JS script intended to consume the data.
 */
function localize_data( $handle = SCRIPT_HANDLE ) {
	$post_id = intval( get_the_ID() );

	if ( empty( $post_id ) ) {
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:disable WordPress.Security.NonceVerification.Recommended
	}

	$localization = [
		'enabled'            => get_autotweet_meta( $post_id, ENABLE_AUTOTWEET_KEY ),
		'enableAutotweetKey' => ENABLE_AUTOTWEET_KEY,
		'nonce'              => wp_create_nonce( 'wp_rest' ),
		'restUrl'            => rest_url( post_autotweet_meta_rest_route( $post_id ) ),
		'tweetBodyKey'       => TWEET_BODY_KEY,
	];

	wp_localize_script( $handle, 'adminAutotweet', $localization );
}
