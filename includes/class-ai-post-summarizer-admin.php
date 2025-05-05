<?php
// includes/class-ai-post-summarizer-admin.php

if ( ! defined( 'WPINC' ) ) {
    die;
}

class AI_Post_Summarizer_Admin {

    private $option_group = 'ai_post_summarizer_option_group';
    private $page_slug = 'ai-post-summarizer-settings';

    // ... (constructor, add_plugin_page, create_admin_page remain the same) ...
    public function __construct() {}

    public function add_plugin_page() {
        add_options_page(
            'AI Content Tools Settings', // Updated Page Title
            'AI Content Tools',          // Updated Menu Title
            'manage_options',
            $this->page_slug,
            array( $this, 'create_admin_page' )
        );
    }

    public function create_admin_page() {
        ?>
        <div class="wrap">
            <h1>AI Content Tools Settings</h1> <p>Configure settings for AI-generated summaries and polls.</p>
            <form method="post" action="options.php">
                <?php
                settings_fields( $this->option_group );
                do_settings_sections( $this->page_slug );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function page_init() {
        register_setting(
            $this->option_group,
            AI_POST_SUMMARIZER_OPTIONS,
            array( $this, 'sanitize_options' )
        );

        // Section 1: API Settings
        add_settings_section(
            'api_settings_section',
            'Gemini API Settings',
            array( $this, 'print_api_section_info' ),
            $this->page_slug
        );
        add_settings_field(
            'gemini_api_key',
            'Gemini API Key',
            array( $this, 'gemini_api_key_callback' ),
            $this->page_slug,
            'api_settings_section'
        );

        // Section 2: Summary Settings
        add_settings_section(
            'summary_settings_section', // New section ID for clarity
            'Summary Settings',
            array( $this, 'print_summary_section_info' ),
            $this->page_slug
        );
        add_settings_field(
            'summary_bg_color',
            'Summary Block Background Color',
            array( $this, 'summary_bg_color_callback' ),
            $this->page_slug,
            'summary_settings_section'
        );

        // Section 3: Poll Settings (NEW)
        add_settings_section(
            'poll_settings_section',
            'Poll Settings',
            array( $this, 'print_poll_section_info' ),
            $this->page_slug
        );
        add_settings_field(
            'enable_polls', // New field ID
            'Enable AI-Generated Polls',
            array( $this, 'enable_polls_callback' ), // New callback
            $this->page_slug,
            'poll_settings_section'
        );
        
        // Section 4: How to Use
        add_settings_section(
            'how_to_use_section',
            'How to Use Shortcodes',
            array( $this, 'print_how_to_use_section_info' ),
            $this->page_slug
        );
    }

    public function sanitize_options( $input ) {
        $new_input = array();
        $options = get_option( AI_POST_SUMMARIZER_OPTIONS, array() ); // Ensure $options is an array

        // API Key
        $new_input['gemini_api_key'] = isset( $input['gemini_api_key'] ) ? sanitize_text_field( $input['gemini_api_key'] ) : (isset($options['gemini_api_key']) ? $options['gemini_api_key'] : '');

        // Summary Background Color
        if ( isset( $input['summary_bg_color'] ) && preg_match( '/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/', $input['summary_bg_color'] ) ) {
            $new_input['summary_bg_color'] = sanitize_hex_color( $input['summary_bg_color'] );
        } else {
            $new_input['summary_bg_color'] = isset( $options['summary_bg_color'] ) ? $options['summary_bg_color'] : '#f0f0f0';
            if ( isset( $input['summary_bg_color'] ) ) { // Only add error if input was actually provided and invalid
                 add_settings_error('summary_bg_color', 'invalid_hex_color', 'Invalid hex color for summary. Reverted to previous or default.');
            }
        }

        // Enable Polls (Checkbox)
        $new_input['enable_polls'] = isset( $input['enable_polls'] ) ? (bool) $input['enable_polls'] : false;

        return $new_input;
    }

    // Section Info Callbacks
    public function print_api_section_info() {
        print 'Configure your Google Gemini API Key to enable AI features.';
    }
    public function print_summary_section_info() {
        print 'Customize the appearance and behavior of AI-generated summaries.';
    }
    public function print_poll_section_info() {
        print 'Configure settings for AI-generated polls.';
    }
    public function print_how_to_use_section_info() {
        print '<p>To display AI-generated content within your posts, use the following shortcodes:</p>';
        echo '<p>For Summaries: <code>[ai_summary]</code></p>';
        echo '<p>For Polls: <code>[ai_poll]</code></p>';
        print '<p>Content is generated (or updated) automatically when you save or publish a post of type "Post", if the respective feature is enabled and the API key is set.</p>';
    }


    // Field Callbacks
    public function gemini_api_key_callback() {
        $options = get_option( AI_POST_SUMMARIZER_OPTIONS, array() );
        $api_key = isset( $options['gemini_api_key'] ) ? esc_attr( $options['gemini_api_key'] ) : '';
        printf(
            '<input type="password" id="gemini_api_key" name="%s[gemini_api_key]" value="%s" class="regular-text" autocomplete="off" />',
            esc_attr( AI_POST_SUMMARIZER_OPTIONS ), $api_key
        );
        echo '<p class="description">Enter your Google Gemini API Key. Get one from <a href="https://aistudio.google.com/" target="_blank" rel="noopener noreferrer">Google AI Studio</a>.</p>';
        if (empty($api_key)) {
            echo '<p style="color: red;">API Key is not set. AI features will not work.</p>';
        }
    }

    public function summary_bg_color_callback() {
        $options = get_option( AI_POST_SUMMARIZER_OPTIONS, array() );
        $bg_color = isset( $options['summary_bg_color'] ) ? esc_attr( $options['summary_bg_color'] ) : '#f0f0f0';
        printf(
            '<input type="text" id="summary_bg_color" name="%s[summary_bg_color]" value="%s" class="ai-summarizer-color-picker" data-default-color="#f0f0f0" />',
            esc_attr( AI_POST_SUMMARIZER_OPTIONS ), $bg_color
        );
        echo '<p class="description">Background color for the summary block. Click field for color picker.</p>';
    }

    public function enable_polls_callback() { // NEW CALLBACK
        $options = get_option( AI_POST_SUMMARIZER_OPTIONS, array() );
        $enable_polls = isset( $options['enable_polls'] ) ? (bool) $options['enable_polls'] : false;
        printf(
            '<input type="checkbox" id="enable_polls" name="%s[enable_polls]" value="1" %s />',
            esc_attr( AI_POST_SUMMARIZER_OPTIONS ), checked( 1, $enable_polls, false )
        );
        echo '<label for="enable_polls"> Enable AI-Generated Polls</label>';
        echo '<p class="description">If checked, a poll related to the post content will be automatically generated and stored when a post is saved (requires API key).</p>';
    }

    // ... (enqueue_styles and enqueue_scripts remain the same) ...
    public function enqueue_styles( $hook_suffix ) {
        $expected_hook = 'settings_page_' . $this->page_slug;
        if ( $expected_hook !== $hook_suffix ) return;
        wp_enqueue_style( 'ai-post-summarizer-admin-css', AI_POST_SUMMARIZER_PLUGIN_URL . 'assets/css/admin-style.css', array( 'wp-color-picker' ), AI_POST_SUMMARIZER_VERSION );
    }
    public function enqueue_scripts( $hook_suffix ) {
        $expected_hook = 'settings_page_' . $this->page_slug;
        if ( $expected_hook !== $hook_suffix ) return;
        wp_enqueue_script( 'ai-post-summarizer-admin-js', AI_POST_SUMMARIZER_PLUGIN_URL . 'assets/js/admin-script.js', array( 'jquery', 'wp-color-picker' ), AI_POST_SUMMARIZER_VERSION, true );
    }
}