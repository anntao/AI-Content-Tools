<?php
/**
 * Plugin Name:       AI Post Summarizer & Polls
 * Plugin URI:        https://yourwebsite.com/plugins/ai-post-summarizer/
 * Description:       Generates post summaries and polls using Google Gemini AI. Shortcodes: [ai_summary], [ai_poll]
 * Version:           1.1.0 // Incremented version
 * Author:            Anntao Diaz
 * Author URI:        https://yourwebsite.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ai-post-summarizer
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Define plugin constants
define( 'AI_POST_SUMMARIZER_VERSION', '1.1.0' ); // Updated version
define( 'AI_POST_SUMMARIZER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AI_POST_SUMMARIZER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AI_POST_SUMMARIZER_OPTIONS', 'ai_post_summarizer_options' );

// Include class files
require_once AI_POST_SUMMARIZER_PLUGIN_DIR . 'includes/class-ai-post-summarizer-admin.php';
require_once AI_POST_SUMMARIZER_PLUGIN_DIR . 'includes/class-ai-post-summarizer-api.php';
require_once AI_POST_SUMMARIZER_PLUGIN_DIR . 'includes/class-ai-post-summarizer-public.php';

/**
 * Initialize the plugin.
 */
function ai_post_summarizer_init() {
    $options = get_option( AI_POST_SUMMARIZER_OPTIONS );

    if ( is_admin() ) {
        $admin_handler = new AI_Post_Summarizer_Admin();
        add_action( 'admin_menu', array( $admin_handler, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $admin_handler, 'page_init' ) );
        add_action( 'admin_enqueue_scripts', array( $admin_handler, 'enqueue_styles' ) );
        add_action( 'admin_enqueue_scripts', array( $admin_handler, 'enqueue_scripts' ) );
    }

    $public_handler = new AI_Post_Summarizer_Public();
    add_shortcode( 'ai_summary', array( $public_handler, 'render_summary_shortcode' ) );
    add_shortcode( 'ai_poll', array( $public_handler, 'render_poll_shortcode' ) ); // Added poll shortcode
    add_action( 'wp_enqueue_scripts', array( $public_handler, 'enqueue_public_scripts_and_styles' ) );

    // AJAX actions for poll voting
    add_action( 'wp_ajax_ai_submit_poll_vote', array( $public_handler, 'handle_poll_vote' ) );
    add_action( 'wp_ajax_nopriv_ai_submit_poll_vote', array( $public_handler, 'handle_poll_vote' ) );


    $api_key_present = ! empty( $options['gemini_api_key'] );
    $summaries_enabled = true; // Assuming summaries are always on if API key is there, or add a specific toggle later if needed.
    $polls_enabled = ! empty( $options['enable_polls'] ) && $options['enable_polls'];

    if ( $api_key_present ) {
        $api_handler = new AI_Post_Summarizer_API();
        if ( $summaries_enabled ) {
            add_action( 'save_post_post', array( $api_handler, 'generate_summary_on_save' ), 10, 3 );
        }
        if ( $polls_enabled ) {
            add_action( 'save_post_post', array( $api_handler, 'generate_poll_on_save' ), 10, 3 );
        }
    }
}
add_action( 'plugins_loaded', 'ai_post_summarizer_init' );

/**
 * Activation hook.
 */
function ai_post_summarizer_activate() {
    $default_options = array(
        'gemini_api_key' => '',
        'summary_bg_color' => '#f0f0f0',
        'enable_polls' => false, // Default to false
    );
    if ( false === get_option( AI_POST_SUMMARIZER_OPTIONS ) ) {
        add_option( AI_POST_SUMMARIZER_OPTIONS, $default_options );
    } else {
        // If options exist, ensure new options are added with defaults
        $current_options = get_option( AI_POST_SUMMARIZER_OPTIONS );
        $merged_options = array_merge( $default_options, $current_options );
        update_option( AI_POST_SUMMARIZER_OPTIONS, $merged_options );
    }
}
register_activation_hook( __FILE__, 'ai_post_summarizer_activate' );

// ... (deactivation and uninstall hooks remain the same) ...
/**
 * Deactivation hook.
 */
function ai_post_summarizer_deactivate() {
    // Nothing to do for V1.1
}
register_deactivation_hook( __FILE__, 'ai_post_summarizer_deactivate' );

/**
 * Uninstall hook.
 */
function ai_post_summarizer_uninstall() {
    delete_option( AI_POST_SUMMARIZER_OPTIONS );
    // Consider removing post meta related to summaries and polls if desired:
    // E.g., delete_post_meta_by_key( AI_Post_Summarizer_API::META_KEY_SUMMARY );
    // delete_post_meta_by_key( AI_Post_Summarizer_API::META_KEY_POLL_DATA ); etc.
}
register_uninstall_hook( __FILE__, 'ai_post_summarizer_uninstall' );
?>