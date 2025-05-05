<?php
// includes/class-ai-post-summarizer-public.php

if ( ! defined( 'WPINC' ) ) {
    die;
}

class AI_Post_Summarizer_Public {

    public function __construct() {
        // Hooks are added in the main plugin file
    }

    /**
     * Render the summary shortcode.
     * [ai_summary]
     */
    public function render_summary_shortcode( $atts ) {
        $atts = shortcode_atts( array(), $atts, 'ai_summary' );
        $post_id = get_the_ID();
        if ( ! $post_id ) return '';

        $summary_text = get_post_meta( $post_id, AI_Post_Summarizer_API::META_KEY_SUMMARY, true );
        $summary_error = get_post_meta( $post_id, AI_Post_Summarizer_API::META_KEY_SUMMARY_ERROR, true );

        if ( ! empty( $summary_error ) && current_user_can('edit_posts') ) {
            return '<div class="ai-post-summary-error"><strong>AI Summary Error:</strong> ' . esc_html( $summary_error ) . '</div>';
        }
        if ( empty( $summary_text ) ) return '';

        $options = get_option( AI_POST_SUMMARIZER_OPTIONS, array() );
        $bg_color = isset( $options['summary_bg_color'] ) ? sanitize_hex_color( $options['summary_bg_color'] ) : '#f0f0f0';
        $footnote_text = 'AI summary created with Google Gemini';

        $summary_lines = explode( "\n", trim( $summary_text ) );
        $html_list = '<ul class="ai-post-summary-list">';
        foreach ( $summary_lines as $line ) {
            $trimmed_line = trim( $line );
            if ( ! empty( $trimmed_line ) ) {
                $list_item_content = ltrim( $trimmed_line, '*- ');
                $html_list .= '<li>' . esc_html( $list_item_content ) . '</li>';
            }
        }
        $html_list .= '</ul>';

        $output = '<div class="ai-post-summary-block" style="background-color: ' . esc_attr( $bg_color ) . ';">';
        $output .= $html_list;
        $output .= '<p class="ai-summary-footnote">' . esc_html( $footnote_text ) . '</p>';
        $output .= '</div>';
        return $output;
    }

    /**
     * Render the poll shortcode.
     * [ai_poll]
     */
    public function render_poll_shortcode( $atts ) {
        $atts = shortcode_atts( array(), $atts, 'ai_poll' );
        $post_id = get_the_ID();
        if ( ! $post_id ) return '';

        $poll_data_json = get_post_meta( $post_id, AI_Post_Summarizer_API::META_KEY_POLL_DATA, true );
        $poll_error = get_post_meta( $post_id, AI_Post_Summarizer_API::META_KEY_POLL_ERROR, true );

        if ( ! empty( $poll_error ) && current_user_can('edit_posts') ) {
            return '<div class="ai-post-poll-error"><strong>AI Poll Error:</strong> ' . esc_html( $poll_error ) . '</div>';
        }
        if ( empty( $poll_data_json ) ) return '';

        $poll_data = json_decode( $poll_data_json, true );
        if ( ! $poll_data || empty( $poll_data['question'] ) || empty( $poll_data['options'] ) || !is_array($poll_data['options']) ) {
            return '';
        }

        $poll_votes = get_post_meta( $post_id, AI_Post_Summarizer_API::META_KEY_POLL_VOTES, true );
        if ( ! is_array( $poll_votes ) || count($poll_votes) !== count($poll_data['options']) ) {
            $poll_votes = array_fill(0, count($poll_data['options']), 0);
        }

        $cookie_name = 'ai_poll_voted_' . $post_id;
        $user_has_voted = isset( $_COOKIE[$cookie_name] );
        
        $output = '<div id="ai-poll-container-' . esc_attr( $post_id ) . '" class="ai-poll-container" data-postid="' . esc_attr( $post_id ) . '">';

        if ( $user_has_voted ) {
            $output .= $this->get_poll_results_html( $poll_data, $poll_votes, $post_id );
        } else {
            $output .= $this->get_poll_form_html( $poll_data, $post_id );
        }

        $output .= '</div>'; // Close ai-poll-container
        return $output;
    }

    /**
     * Helper to generate poll form HTML.
     */
    private function get_poll_form_html( $poll_data, $post_id ) {
        $poll_footnote_text = 'AI poll created with Google Gemini';

        $html = '<div class="ai-poll-form-wrapper">';
        $html .= '<div class="ai-poll-form">';
        $html .= '<h3 class="ai-poll-question">' . esc_html( $poll_data['question'] ) . '</h3>';
        $html .= '<form id="ai-poll-form-' . esc_attr( $post_id ) . '">';
        $html .= wp_nonce_field( 'ai_poll_vote_nonce_' . $post_id, '_ai_poll_nonce', true, false );
        $html .= '<input type="hidden" name="post_id" value="' . esc_attr( $post_id ) . '">';
        
        foreach ( $poll_data['options'] as $index => $option_text ) {
            $option_id = 'ai-poll-option-' . esc_attr( $post_id ) . '-' . $index;
            $html .= '<div class="ai-poll-option">';
            $html .= '<input type="radio" name="ai_poll_option" id="' . esc_attr( $option_id ) . '" value="' . esc_attr( $index ) . '" required>';
            $html .= '<label for="' . esc_attr( $option_id ) . '">' . esc_html( $option_text ) . '</label>';
            $html .= '</div>';
        }
        
        $html .= '<button type="submit" class="ai-poll-vote-button">Vote</button>';
        $html .= '</form>';
        $html .= '<div class="ai-poll-feedback" style="display:none;"></div>';
        $html .= '</div>'; 
        $html .= '<p class="ai-poll-footnote">' . esc_html( $poll_footnote_text ) . '</p>';
        $html .= '</div>'; 
        return $html;
    }

    /**
     * Helper to generate poll results HTML.
     */
    private function get_poll_results_html( $poll_data, $poll_votes, $post_id ) {
        $poll_footnote_text = 'AI poll created with Google Gemini';
        $total_votes = array_sum( $poll_votes );

        $html = '<div class="ai-poll-results-wrapper">';
        $html .= '<div class="ai-poll-results">';
        $html .= '<h3 class="ai-poll-question">' . esc_html( $poll_data['question'] ) . '</h3>';
        $html .= '<ul class="ai-poll-options-results">';

        foreach ( $poll_data['options'] as $index => $option_text ) {
            $votes_for_option = isset( $poll_votes[$index] ) ? intval( $poll_votes[$index] ) : 0;
            $percentage = ( $total_votes > 0 ) ? round( ( $votes_for_option / $total_votes ) * 100 ) : 0;
            
            $html .= '<li>';
            $html .= '<span class="ai-poll-option-text">' . esc_html( $option_text ) . '</span>';
            $html .= '<div class="ai-poll-result-bar-container">';
            $html .= '<div class="ai-poll-result-bar" style="width: ' . esc_attr( $percentage ) . '%;">';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '<span class="ai-poll-option-percentage">' . esc_html( $percentage ) . '%</span>';
            $html .= ' <span class="ai-poll-option-votes">(' . esc_html( $votes_for_option ) . ' votes)</span>';
            $html .= '</li>';
        }
        $html .= '</ul>';
        $html .= '<p class="ai-poll-total-votes">Total Votes: ' . esc_html( $total_votes ) . '</p>';
        $html .= '</div>'; 
        $html .= '<p class="ai-poll-footnote">' . esc_html( $poll_footnote_text ) . '</p>';
        $html .= '</div>'; 
        return $html;
    }

    /**
     * Handle AJAX poll vote submission.
     */
    public function handle_poll_vote() {
        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
        $nonce_value = isset( $_POST['_ai_poll_nonce'] ) ? sanitize_text_field( $_POST['_ai_poll_nonce'] ) : '';

        if ( ! $post_id || ! wp_verify_nonce( $nonce_value, 'ai_poll_vote_nonce_' . $post_id ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed or invalid post ID.' ) );
            return;
        }

        $selected_option_index = isset( $_POST['selected_option'] ) ? intval( $_POST['selected_option'] ) : -1;

        $poll_data_json = get_post_meta( $post_id, AI_Post_Summarizer_API::META_KEY_POLL_DATA, true );
        $poll_data = json_decode( $poll_data_json, true );

        if ( ! $poll_data || !isset($poll_data['options']) || !is_array($poll_data['options']) || $selected_option_index < 0 || $selected_option_index >= count( $poll_data['options'] ) ) { // Added check for $poll_data['options']
            wp_send_json_error( array( 'message' => 'Invalid poll data or option selected.' ) );
            return;
        }

        $poll_votes = get_post_meta( $post_id, AI_Post_Summarizer_API::META_KEY_POLL_VOTES, true );
        if ( ! is_array( $poll_votes ) || count($poll_votes) !== count($poll_data['options']) ) {
            $poll_votes = array_fill(0, count($poll_data['options']), 0);
        }
        
        $cookie_name = 'ai_poll_voted_' . $post_id;
        if (isset($_COOKIE[$cookie_name])) {
            $results_html = $this->get_poll_results_html( $poll_data, $poll_votes, $post_id );
            wp_send_json_success( array( 'already_voted' => true, 'html' => $results_html, 'message' => 'You have already voted on this poll.' ) );
            return;
        }

        $poll_votes[$selected_option_index]++;
        update_post_meta( $post_id, AI_Post_Summarizer_API::META_KEY_POLL_VOTES, $poll_votes );

        $cookie_expiry = time() + YEAR_IN_SECONDS;
        setcookie( $cookie_name, strval($selected_option_index), $cookie_expiry, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN );

        $results_html = $this->get_poll_results_html( $poll_data, $poll_votes, $post_id );
        wp_send_json_success( array( 'html' => $results_html, 'message' => 'Vote submitted successfully!' ) );
    }

    /**
     * Enqueue public scripts and styles.
     */
    public function enqueue_public_scripts_and_styles() {
        if ( is_singular('post') ) {
            wp_enqueue_style(
                'ai-content-tools-public-css',
                AI_POST_SUMMARIZER_PLUGIN_URL . 'assets/css/public-style.css',
                array(),
                AI_POST_SUMMARIZER_VERSION
            );

            global $post;
            // Ensure $post is an object and has post_content property before using has_shortcode
            if ( is_a($post, 'WP_Post') && has_shortcode( $post->post_content, 'ai_poll' ) ) {
                wp_enqueue_script(
                    'ai-poll-public-js',
                    AI_POST_SUMMARIZER_PLUGIN_URL . 'assets/js/public-poll.js',
                    array( 'jquery' ),
                    AI_POST_SUMMARIZER_VERSION,
                    array( 'strategy'  => 'defer' )
                );
                wp_localize_script(
                    'ai-poll-public-js',
                    'aiPollData',
                    array( 'ajax_url' => admin_url( 'admin-ajax.php' ) )
                );
            }
        }
    }
}