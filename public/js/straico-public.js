/**
 * Public-facing JavaScript for the Straico Integration plugin.
 *
 * @since      1.0.0
 * @package    Straico_Integration
 * @subpackage Straico_Integration/public/js
 */

(function($) {
    'use strict';

    /**
     * Prompt Form Management
     */
    const PromptForm = {
        /**
         * Initialize prompt form functionality.
         */
        init: function() {
            // Handle form submission
            $('.straico-prompt-form').on('submit', function(e) {
                e.preventDefault();
                PromptForm.handleSubmit($(this));
            });

            // Handle form reset
            $('.straico-prompt-form button[type="reset"]').on('click', function() {
                var $form = $(this).closest('form');
                PromptForm.resetForm($form);
            });

            // Handle new prompt button
            $('.straico-reset-prompt').on('click', function() {
                var $container = $(this).closest('.straico-prompt-container');
                PromptForm.resetPrompt($container);
            });
        },

        /**
         * Handle form submission.
         * @param {jQuery} $form - The form jQuery object.
         */
        handleSubmit: function($form) {
            var $submit = $form.find('button[type="submit"]');
            var $reset = $form.find('button[type="reset"]');
            var $loading = $form.find('.straico-loading');
            var $error = $form.find('.straico-error');
            var $response = $form.closest('.straico-prompt-container').find('.straico-response');
            var $responseContent = $response.find('.straico-response-content');

            // Reset state
            $error.hide();
            $submit.prop('disabled', true);
            if ($reset.length) {
                $reset.prop('disabled', true);
            }
            $loading.show();
            $response.hide();
            $responseContent.empty();

            // Submit form via AJAX
            $.ajax({
                url: straico_public.ajaxurl,
                type: 'POST',
                data: $form.serialize(),
                success: function(response) {
                    if (response.success) {
                        PromptForm.handleSuccess($form, response.data);
                    } else {
                        PromptForm.handleError($form, response.data.message);
                    }
                },
                error: function() {
                    PromptForm.handleError($form, straico_public.strings.error);
                }
            });
        },

        /**
         * Handle successful form submission.
         * @param {jQuery} $form - The form jQuery object.
         * @param {Object} data - The response data.
         */
        handleSuccess: function($form, data) {
            var $container = $form.closest('.straico-prompt-container');
            var $response = $container.find('.straico-response');
            var $responseContent = $response.find('.straico-response-content');
            var html = '';

            // Add answer
            html += '<div class="straico-response-answer">' + data.answer + '</div>';

            // Add references if available
            if (data.references && data.references.length > 0) {
                html += '<div class="straico-response-references">';
                html += '<h3>' + straico_public.strings.references + '</h3>';
                data.references.forEach(function(ref) {
                    html += '<div class="straico-reference">';
                    html += '<div class="straico-reference-content">' + ref.content + '</div>';
                    if (ref.page) {
                        html += '<div class="straico-reference-page">';
                        html += straico_public.strings.page + ': ' + ref.page;
                        html += '</div>';
                    }
                    html += '</div>';
                });
                html += '</div>';
            }

            // Add cost information
            if (data.coins_used) {
                html += '<div class="straico-response-cost">';
                html += straico_public.strings.cost + ': ' + data.coins_used.toFixed(2) + ' ' + straico_public.strings.coins;
                html += '</div>';
            }

            $responseContent.html(html);
            $form.hide();
            $response.show();

            // Scroll to response
            $('html, body').animate({
                scrollTop: $response.offset().top - 50
            }, 500);
        },

        /**
         * Handle form submission error.
         * @param {jQuery} $form - The form jQuery object.
         * @param {string} message - The error message to display.
         */
        handleError: function($form, message) {
            var $submit = $form.find('button[type="submit"]');
            var $reset = $form.find('button[type="reset"]');
            var $loading = $form.find('.straico-loading');
            var $error = $form.find('.straico-error');

            $error.text(message).show();
            $submit.prop('disabled', false);
            if ($reset.length) {
                $reset.prop('disabled', false);
            }
            $loading.hide();

            // Scroll to error
            $('html, body').animate({
                scrollTop: $error.offset().top - 50
            }, 500);
        },

        /**
         * Reset a form.
         * @param {jQuery} $form - The form jQuery object.
         */
        resetForm: function($form) {
            $form[0].reset();
            $form.find('.straico-error').hide();
            $form.find('button').prop('disabled', false);
            $form.find('.straico-loading').hide();
        },

        /**
         * Reset prompt container to initial state.
         * @param {jQuery} $container - The prompt container jQuery object.
         */
        resetPrompt: function($container) {
            var $form = $container.find('.straico-prompt-form');
            var $response = $container.find('.straico-response');

            PromptForm.resetForm($form);
            $form.show();
            $response.hide();

            // Scroll to form
            $('html, body').animate({
                scrollTop: $form.offset().top - 50
            }, 500);
        }
    };

    /**
     * Accessibility Enhancements
     */
    const Accessibility = {
        /**
         * Initialize accessibility features.
         */
        init: function() {
            // Add ARIA labels
            $('.straico-prompt-input textarea').attr('aria-label', straico_public.strings.promptLabel);
            $('.straico-submit-button').attr('aria-label', straico_public.strings.submitLabel);
            $('.straico-reset-button').attr('aria-label', straico_public.strings.resetLabel);

            // Handle loading state announcements
            $('.straico-prompt-form').on('submit', function() {
                var $loading = $(this).find('.straico-loading');
                $loading.attr('role', 'status').attr('aria-live', 'polite');
            });

            // Handle error announcements
            $('.straico-error').attr('role', 'alert').attr('aria-live', 'assertive');

            // Handle response announcements
            $('.straico-response-content').attr('role', 'region').attr('aria-live', 'polite');
        }
    };

    /**
     * Initialize all functionality on document ready.
     */
    $(function() {
        PromptForm.init();
        Accessibility.init();
    });

})(jQuery);
