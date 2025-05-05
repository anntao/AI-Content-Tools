// assets/js/public-poll.js
jQuery(document).ready(function($) {

    // Event delegation for dynamically added forms if needed,
    // but for shortcode output on page load, direct binding is fine.
    $(document).on('submit', 'form[id^="ai-poll-form-"]', function(event) {
        event.preventDefault();

        var $form = $(this);
        var $container = $form.closest('.ai-poll-container');
        var postId = $container.data('postid');
        var $feedbackDiv = $form.siblings('.ai-poll-feedback');
        var $submitButton = $form.find('.ai-poll-vote-button');

        var selectedOption = $form.find('input[name="ai_poll_option"]:checked').val();
        var nonce = $form.find('#_ai_poll_nonce').val();

        if (typeof selectedOption === 'undefined') {
            $feedbackDiv.text('Please select an option.').fadeIn().delay(3000).fadeOut();
            return;
        }

        $submitButton.prop('disabled', true).text('Voting...');
        $feedbackDiv.hide().empty();

        $.ajax({
            url: aiPollData.ajax_url, // Localized from PHP
            type: 'POST',
            data: {
                action: 'ai_submit_poll_vote', // WordPress AJAX action
                post_id: postId,
                selected_option: selectedOption,
                _ai_poll_nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    // Replace the form container's content with the new results HTML
                    $container.fadeOut(200, function() {
                        $(this).html(response.data.html).fadeIn(200);
                        // If a specific message is provided (e.g. already voted)
                        if (response.data.message && response.data.already_voted) {
                            // Could prepend this message if desired, or just show results
                            // For now, results HTML includes the data.
                        }
                    });
                } else {
                    $feedbackDiv.text('Error: ' + (response.data.message || 'Could not submit vote.')).fadeIn();
                    $submitButton.prop('disabled', false).text('Vote');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                $feedbackDiv.text('AJAX Error: ' + textStatus + ' - ' + errorThrown).fadeIn();
                $submitButton.prop('disabled', false).text('Vote');
                console.error("AI Poll AJAX Error:", textStatus, errorThrown, jqXHR.responseText);
            }
        });
    });
});