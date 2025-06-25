/**
 * Admin-specific JavaScript for the Straico Integration plugin.
 *
 * @since      1.0.0
 * @package    Straico_Integration
 * @subpackage Straico_Integration/admin/js
 */

(function($) {
    'use strict';

    /**
     * Modal Management
     */
    const Modal = {
        /**
         * Initialize modal functionality.
         */
        init: function() {
            // Close modals when clicking close button
            $('.straico-modal-close').on('click', function() {
                $(this).closest('.straico-modal').hide();
            });

            // Close modals when clicking outside
            $('.straico-modal').on('click', function(e) {
                if (e.target === this) {
                    $(this).hide();
                }
            });

            // Prevent modal content clicks from closing modal
            $('.straico-modal-content').on('click', function(e) {
                e.stopPropagation();
            });
        },

        /**
         * Show a modal.
         * @param {string} modalId - The ID of the modal to show.
         */
        show: function(modalId) {
            $(`#${modalId}`).show();
        },

        /**
         * Hide a modal.
         * @param {string} modalId - The ID of the modal to hide.
         */
        hide: function(modalId) {
            $(`#${modalId}`).hide();
        }
    };

    /**
     * Form Management
     */
    const Form = {
        /**
         * Initialize form functionality.
         */
        init: function() {
            // Handle form submission
            $('.straico-form').on('submit', function(e) {
                e.preventDefault();
                Form.handleSubmit($(this));
            });

            // Handle form reset
            $('.straico-form button[type="reset"]').on('click', function() {
                var $form = $(this).closest('form');
                $form.find('.straico-error').hide();
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

            // Reset state
            $error.hide();
            $submit.prop('disabled', true);
            if ($reset.length) {
                $reset.prop('disabled', true);
            }
            $loading.show();

            // Submit form via AJAX
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: new FormData($form[0]),
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        if (response.data.redirect) {
                            window.location.href = response.data.redirect;
                        } else if (response.data.reload) {
                            location.reload();
                        } else if (response.data.html) {
                            Form.handleSuccess($form, response.data.html);
                        }
                    } else {
                        Form.handleError($form, response.data.message);
                    }
                },
                error: function() {
                    Form.handleError($form, straicoAdmin.strings.error);
                }
            });
        },

        /**
         * Handle successful form submission.
         * @param {jQuery} $form - The form jQuery object.
         * @param {string} html - The HTML content to display.
         */
        handleSuccess: function($form, html) {
            var $response = $('.straico-response');
            var $responseContent = $('.straico-response-content');

            $form.hide();
            $responseContent.html(html);
            $response.show();
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
        }
    };

    /**
     * File Upload Management
     */
    const FileUpload = {
        /**
         * Initialize file upload functionality.
         */
        init: function() {
            // Handle add file button
            // Ensure previous handlers are removed before adding a new one to prevent multiple bindings
            $(document).off('click', '.straico-add-file').on('click', '.straico-add-file', function() {
                FileUpload.addFileInput($(this).closest('.straico-file-uploads'));
            });

            // Handle remove file button (delegated, so typically doesn't need .off() unless re-init is an issue)
            // For consistency and to be safe if init is called multiple times:
            $(document).off('click', '.straico-remove-file').on('click', '.straico-remove-file', function() {
                FileUpload.removeFileInput($(this));
            });
        },

        /**
         * Add a new file input.
         * @param {jQuery} $container - The file uploads container.
         */
        addFileInput: function($container) {
            var fileCount = $container.find('.straico-file-upload').length;

            if (fileCount < 4) {
                var $newUpload = $('<div class="straico-file-upload"></div>');
                $newUpload.append('<input type="file" name="rag_files[]">');
                $newUpload.append('<button type="button" class="button straico-remove-file">' + straicoAdmin.strings.remove + '</button>');
                $container.append($newUpload);

                if (fileCount + 1 >= 4) {
                    $('.straico-add-file').prop('disabled', true);
                }
            }
        },

        /**
         * Remove a file input.
         * @param {jQuery} $button - The remove button jQuery object.
         */
        removeFileInput: function($button) {
            $button.closest('.straico-file-upload').remove();
            $('.straico-add-file').prop('disabled', false);
        }
    };

    /**
     * Confirmation Management
     */
    const Confirm = {
        /**
         * Initialize confirmation functionality.
         */
        init: function() {
            // Handle delete confirmation
            $('.straico-delete-button').on('click', function(e) {
                e.preventDefault();
                if (confirm(straicoAdmin.strings.confirmDelete)) {
                    window.location.href = $(this).attr('href');
                }
            });
        }
    };

    /**
     * Copy to Clipboard
     */
    const Clipboard = {
        /**
         * Initialize clipboard functionality.
         */
        init: function() {
            $('.straico-copy-button').on('click', function() {
                Clipboard.copy($(this));
            });
        },

        /**
         * Copy text to clipboard.
         * @param {jQuery} $button - The copy button jQuery object.
         */
        copy: function($button) {
            var text = $button.data('copy');
            var $temp = $('<input>');
            $('body').append($temp);
            $temp.val(text).select();
            document.execCommand('copy');
            $temp.remove();

            var originalText = $button.text();
            $button.text(straicoAdmin.strings.copied);
            setTimeout(function() {
                $button.text(originalText);
            }, 1000);
        }
    };

    /**
     * Initialize all functionality on document ready.
     */
    $(function() {
        Modal.init();
        Form.init();
        FileUpload.init();
        Confirm.init();
        Clipboard.init();
    });

})(jQuery);
