<?php
// includes/class-ai-post-summarizer-api.php

if ( ! defined( 'WPINC' ) ) {
    die;
}

class AI_Post_Summarizer_API {

    const GEMINI_API_ENDPOINT_PATTERN = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s';
    const DEFAULT_MODEL = 'gemini-1.5-flash-latest';

    // Summary Meta Keys
    const META_KEY_SUMMARY = '_ai_post_summary_text';
    const META_KEY_SUMMARY_ERROR = '_ai_post_summary_error';

    // Poll Meta Keys
    const META_KEY_POLL_DATA = '_ai_post_poll_data';
    const META_KEY_POLL_VOTES = '_ai_post_poll_votes';
    const META_KEY_POLL_ERROR = '_ai_post_poll_error';

    public function __construct() {}

    // --- generate_summary_on_save method remains the same ---
    public function generate_summary_on_save( $post_id, $post, $update ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( wp_is_post_revision( $post_id ) ) return;
        if ( $post->post_status === 'auto-draft' ) return;
        if ( $post->post_type !== 'post' ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        $options = get_option( AI_POST_SUMMARIZER_OPTIONS, array() );
        $api_key = isset( $options['gemini_api_key'] ) ? trim( $options['gemini_api_key'] ) : '';

        if ( empty( $api_key ) ) {
            update_post_meta( $post_id, self::META_KEY_SUMMARY_ERROR, 'Gemini API key not configured.' );
            delete_post_meta( $post_id, self::META_KEY_SUMMARY);
            return;
        }

        $post_content_stripped = wp_strip_all_tags( strip_shortcodes( $post->post_content ) );
        if ( empty( trim( $post_content_stripped ) ) ) {
            update_post_meta( $post_id, self::META_KEY_SUMMARY, '' );
            delete_post_meta( $post_id, self::META_KEY_SUMMARY_ERROR );
            return;
        }
        if (mb_strlen($post_content_stripped) > 30000) {
            $post_content_stripped = mb_substr($post_content_stripped, 0, 30000);
        }

        $prompt = sprintf(
            "Summarize the following text into a maximum of 3 bullet points. Each bullet point must be concise, start with a standard bullet character (like '*' or '-'), and be on its own line. Provide only the bullet points, without any introductory or concluding sentences.\n\nText to summarize:\n%s",
            $post_content_stripped
        );
        
        $api_url = sprintf( self::GEMINI_API_ENDPOINT_PATTERN, self::DEFAULT_MODEL, $api_key );
        $request_body = ['contents' => [['parts' => [['text' => $prompt]]]], 'generationConfig' => ['temperature' => 0.6, 'maxOutputTokens' => 300]];
        $args = ['body' => wp_json_encode( $request_body ), 'headers' => ['Content-Type' => 'application/json'], 'timeout' => 60, 'data_format' => 'body'];
        $response = wp_remote_post( $api_url, $args );

        if ( is_wp_error( $response ) ) {
            update_post_meta( $post_id, self::META_KEY_SUMMARY_ERROR, 'Summary API call failed: ' . $response->get_error_message() );
            delete_post_meta( $post_id, self::META_KEY_SUMMARY );
            return;
        }

        $response_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );
        $decoded_body = json_decode( $response_body, true );

        if ( $response_code === 200 && isset( $decoded_body['candidates'][0]['content']['parts'][0]['text'] ) ) {
            $summary_text = $decoded_body['candidates'][0]['content']['parts'][0]['text'];
            $summary_lines = explode( "\n", $summary_text );
            $bullet_points = [];
            foreach( $summary_lines as $line ) {
                $trimmed_line = trim( $line );
                if ( !empty($trimmed_line) && (strpos($trimmed_line, '*') === 0 || strpos($trimmed_line, '-') === 0) ) {
                    $cleaned_line = ltrim($trimmed_line, '*- ');
                    $bullet_points[] = '* ' . $cleaned_line;
                } elseif (!empty($trimmed_line) && count($bullet_points) < 3) {
                    $bullet_points[] = '* ' . $trimmed_line;
                }
            }
            if ( count( $bullet_points ) > 3 ) $bullet_points = array_slice( $bullet_points, 0, 3 );
            $final_summary = implode( "\n", $bullet_points );

            update_post_meta( $post_id, self::META_KEY_SUMMARY, $final_summary );
            delete_post_meta( $post_id, self::META_KEY_SUMMARY_ERROR );
        } elseif (isset($decoded_body['promptFeedback']['blockReason'])) {
            $error_message = 'Summary generation blocked. Reason: ' . $decoded_body['promptFeedback']['blockReason'];
            // Add safety rating details if available
            update_post_meta( $post_id, self::META_KEY_SUMMARY_ERROR, $error_message );
            delete_post_meta( $post_id, self::META_KEY_SUMMARY );
        } else {
            $error_message = 'Summary API error (' . esc_html($response_code) . '): ';
            $error_message .= isset( $decoded_body['error']['message'] ) ? esc_html( $decoded_body['error']['message'] ) : 'Unexpected response.';
            update_post_meta( $post_id, self::META_KEY_SUMMARY_ERROR, $error_message );
            delete_post_meta( $post_id, self::META_KEY_SUMMARY );
        }
    }


    /**
     * Generate poll on post save.
     */
    public function generate_poll_on_save( $post_id, $post, $update ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( wp_is_post_revision( $post_id ) ) return;
        if ( $post->post_status === 'auto-draft' ) return;
        if ( $post->post_type !== 'post' ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        $options = get_option( AI_POST_SUMMARIZER_OPTIONS, array() );
        if ( empty( $options['enable_polls'] ) || empty( $options['gemini_api_key'] ) ) {
            return;
        }
        $api_key = trim( $options['gemini_api_key'] );

        $post_content_stripped = wp_strip_all_tags( strip_shortcodes( $post->post_content ) );
        if ( empty( trim( $post_content_stripped ) ) ) {
            update_post_meta( $post_id, self::META_KEY_POLL_DATA, '' );
            update_post_meta( $post_id, self::META_KEY_POLL_VOTES, array(0,0,0) );
            delete_post_meta( $post_id, self::META_KEY_POLL_ERROR );
            return;
        }
        if (mb_strlen($post_content_stripped) > 25000) {
            $post_content_stripped = mb_substr($post_content_stripped, 0, 25000);
        }

        // --- UPDATED PROMPT FOR OPINION-BASED POLLS ---
        $prompt = sprintf(
            "Analyze the following news article. Your task is to create an **opinion-based poll question** that sparks discussion or gauges reader sentiment about the broader implications, personal viewpoints, or potential future developments related to the article's main subject. The question should **not** be a test of reading comprehension of the article's explicit content. Instead, it should ask for a personal perspective or prediction. Provide exactly 3 distinct and concise answer options that reflect a range of possible opinions or outcomes. Format your entire output strictly as a single JSON object with keys 'question' (string) and 'options' (array of 3 strings).\n\nArticle Text:\n%s",
            $post_content_stripped
        );
        // --- END OF UPDATED PROMPT ---

        $api_url = sprintf( self::GEMINI_API_ENDPOINT_PATTERN, self::DEFAULT_MODEL, $api_key );
        $request_body = array(
            'contents' => array(
                array(
                    'parts' => array(
                        array( 'text' => $prompt )
                    )
                )
            ),
            'generationConfig' => array(
                'temperature' => 0.75, // Slightly higher temp might encourage more varied opinion options
                'topP' => 0.9,
                'topK' => 40,
                'maxOutputTokens' => 400,
                'responseMimeType' => 'application/json',
            )
        );

        $args = array(
            'body'        => wp_json_encode( $request_body ),
            'headers'     => array( 'Content-Type' => 'application/json' ),
            'timeout'     => 70,
            'data_format' => 'body',
        );

        $response = wp_remote_post( $api_url, $args );

        if ( is_wp_error( $response ) ) {
            update_post_meta( $post_id, self::META_KEY_POLL_ERROR, 'Poll API call failed: ' . $response->get_error_message() );
            delete_post_meta( $post_id, self::META_KEY_POLL_DATA );
            delete_post_meta( $post_id, self::META_KEY_POLL_VOTES );
            return;
        }

        $response_code = wp_remote_retrieve_response_code( $response );
        $response_body_text = wp_remote_retrieve_body( $response );
        $decoded_response = json_decode( $response_body_text, true );

        if ( $response_code === 200 && isset( $decoded_response['candidates'][0]['content']['parts'][0]['text'] ) ) {
            $poll_json_string = $decoded_response['candidates'][0]['content']['parts'][0]['text'];
            $poll_json_data = json_decode( $poll_json_string, true );

            if ( $poll_json_data && isset( $poll_json_data['question'] ) && isset( $poll_json_data['options'] ) && is_array( $poll_json_data['options'] ) && count( $poll_json_data['options'] ) === 3 ) {
                $sanitized_poll_data = [
                    'question' => sanitize_text_field($poll_json_data['question']),
                    'options' => array_map('sanitize_text_field', $poll_json_data['options'])
                ];
                update_post_meta( $post_id, self::META_KEY_POLL_DATA, wp_json_encode($sanitized_poll_data) );
                update_post_meta( $post_id, self::META_KEY_POLL_VOTES, array_fill(0, count($sanitized_poll_data['options']), 0) );
                delete_post_meta( $post_id, self::META_KEY_POLL_ERROR );
            } else {
                $error_msg = 'Poll API response format error. Expected JSON with question and 3 options. Received: ' . esc_html(substr($poll_json_string, 0, 200));
                update_post_meta( $post_id, self::META_KEY_POLL_ERROR, $error_msg );
                delete_post_meta( $post_id, self::META_KEY_POLL_DATA );
                delete_post_meta( $post_id, self::META_KEY_POLL_VOTES );
            }
        } elseif (isset($decoded_response['promptFeedback']['blockReason'])) {
            $error_message = 'Poll generation blocked. Reason: ' . $decoded_response['promptFeedback']['blockReason'];
            update_post_meta( $post_id, self::META_KEY_POLL_ERROR, $error_message );
            delete_post_meta( $post_id, self::META_KEY_POLL_DATA );
            delete_post_meta( $post_id, self::META_KEY_POLL_VOTES );
        } else {
            $error_message = 'Poll API error (' . esc_html($response_code) . '): ';
            $error_message .= isset( $decoded_response['error']['message'] ) ? esc_html( $decoded_response['error']['message'] ) : 'Unexpected response. Body: '. esc_html(substr($response_body_text, 0, 200));
            update_post_meta( $post_id, self::META_KEY_POLL_ERROR, $error_message );
            delete_post_meta( $post_id, self::META_KEY_POLL_DATA );
            delete_post_meta( $post_id, self::META_KEY_POLL_VOTES );
        }
    }
}