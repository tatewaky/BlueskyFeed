<?php
/**
 * Plugin Name: BlueSky Feed
 * Description: Provides a Bluesky Feed Plugin.
 * Version: 1.0
 * Author: Jason Acuna
 * License: GPL2
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Enqueue CSS for the plugin
function bluesky_enqueue_styles() {
    wp_enqueue_style(
        'bluesky-feed-css',
        plugin_dir_url( __FILE__ ) . 'css/bluesky-feed.css',
        [],
        '1.0'
    );
}
add_action( 'wp_enqueue_scripts', 'bluesky_enqueue_styles' );

// Include the Bluesky class file
require_once plugin_dir_path( __FILE__ ) . 'src/Bluesky.php';

new Bluesky();

