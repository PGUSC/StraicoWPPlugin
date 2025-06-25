/**
 * JavaScript functionality for agent management.
 *
 * @since      1.0.0
 * @package    Straico_Integration
 * @subpackage Straico_Integration/admin/js
 */

jQuery(document).ready(function($) {
    // View agent details
    $('.straico-view-details').on('click', function() {
        var agentId = $(this).data('agent-id');
        var $modal = $('#straico-agent-details-modal');
        var $content = $modal.find('.straico-details-content');
        var $loading = $modal.find('.straico-loading');
        var $error = $modal.find('.straico-error');

        $content.hide();
        $error.hide();
        $loading.show();
        $modal.show();

        $.ajax({
            url: straicoAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'straico_get_agent_details',
                nonce: straicoAdmin.nonce,
                agent_id: agentId
            },
            success: function(response) {
                $loading.hide();
                if (response.success) {
                    $content.html(response.data.html).show();
                } else {
                    $error.text(response.data.message || straicoAdmin.strings.error).show();
                }
            },
            error: function() {
                $loading.hide();
                $error.text(straicoAdmin.strings.error).show();
            }
        });
    });

    // Delete agent
    $('.straico-delete-agent').on('click', function() {
        var agentId = $(this).data('agent-id');
        var agentName = $(this).data('agent-name');
        var $modal = $('#straico-delete-confirm-modal');
        var $message = $modal.find('.straico-delete-message');
        var $loading = $modal.find('.straico-loading');
        var $error = $modal.find('.straico-error');
        var $actions = $modal.find('.straico-modal-actions');

        $message.html(
            straicoAdmin.strings.deleteConfirmPrefix +
            '<strong>' + agentName + '</strong>?' +
            '<br><br>' +
            straicoAdmin.strings.deleteConfirmSuffix
        );

        $error.hide();
        $loading.hide();
        $actions.show();
        $modal.show();

        // Store agent ID for deletion
        $modal.data('agent-id', agentId);
    });

    // Confirm agent deletion
    $('.straico-confirm-delete').on('click', function() {
        var $modal = $('#straico-delete-confirm-modal');
        var agentId = $modal.data('agent-id');
        var $loading = $modal.find('.straico-loading');
        var $error = $modal.find('.straico-error');
        var $actions = $modal.find('.straico-modal-actions');

        $error.hide();
        $actions.hide();
        $loading.show();

        $.ajax({
            url: straicoAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'straico_delete_agent',
                nonce: straicoAdmin.nonce,
                agent_id: agentId
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    $loading.hide();
                    $error.text(response.data.message || straicoAdmin.strings.error).show();
                    $actions.show();
                }
            },
            error: function() {
                $loading.hide();
                $error.text(straicoAdmin.strings.error).show();
                $actions.show();
            }
        });
    });

    // Close modals
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

    // Handle agent form submission with detailed error handling
    $('.straico-agent-form').on('submit', function(e) {
        e.preventDefault();

        var $form = $(this);
        var $submit = $form.find('button[type="submit"]');
        var $loading = $form.find('.straico-loading');
        var $error = $form.find('.straico-error');

        // Reset state
        $error.hide();
        $submit.prop('disabled', true);
        $loading.show();

        // Log form data for debugging
        console.log('Submitting agent form...');

        // Submit form via AJAX
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            success: function(response) {
                console.log('AJAX response:', response);
                if (response.success) {
                    window.location.href = straicoAdmin.agentListUrl;
                } else {
                    $error.html(response.data.message).show();
                    $submit.prop('disabled', false);
                    $loading.hide();
                    console.error('Agent creation failed:', response.data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', {
                    status: status,
                    error: error,
                    xhr: xhr
                });
                
                var errorMessage = straicoAdmin.strings.error;
                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    errorMessage = xhr.responseJSON.data.message;
                }
                
                $error.html(errorMessage).show();
                $submit.prop('disabled', false);
                $loading.hide();
            }
        });
    });

    // Handle tag input formatting
    $('#agent_tags').on('input', function() {
        var value = $(this).val();
        // Allow letters, numbers, commas, spaces, and hyphens
        value = value.replace(/[^a-zA-Z0-9,\s-]/g, '');
        $(this).val(value);
    });

    // Handle RAG connection form submission
    $('.straico-connect-form').on('submit', function(e) {
        e.preventDefault();

        var $form = $(this);
        var $submit = $form.find('button[type="submit"]');
        var $loading = $form.find('.straico-loading');
        var $error = $form.find('.straico-error');

        // Reset state
        $error.hide();
        $submit.prop('disabled', true);
        $loading.show();

        // Log form data for debugging
        console.log('Connecting RAG to Agent...');

        // Submit form via AJAX
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            success: function(response) {
                console.log('AJAX response:', response);
                if (response.success) {
                    window.location.href = straicoAdmin.agentListUrl;
                } else {
                    $error.html(response.data.message || straicoAdmin.strings.error).show();
                    $submit.prop('disabled', false);
                    $loading.hide();
                    console.error('RAG connection failed:', response.data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', {
                    status: status,
                    error: error,
                    xhr: xhr
                });
                
                var errorMessage = straicoAdmin.strings.error;
                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    errorMessage = xhr.responseJSON.data.message;
                }
                
                $error.html(errorMessage).show();
                $submit.prop('disabled', false);
                $loading.hide();
            }
        });
    });
});
