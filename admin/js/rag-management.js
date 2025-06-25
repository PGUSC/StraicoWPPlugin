/**
 * RAG Management specific JavaScript.
 *
 * @since      1.0.0
 * @package    Straico_Integration
 * @subpackage Straico_Integration/admin/js
 */

(function($) {
    'use strict';

    /**
     * RAG Management functionality.
     */
    const RAGManagement = {
        /**
         * Initialize RAG management functionality.
         */
        init: function() {
            this.initViewDetails();
            this.initDeleteRAG();
        },

        /**
         * Initialize RAG details view functionality.
         */
        initViewDetails: function() {
            $('.straico-view-details').on('click', function() {
                var ragId = $(this).data('rag-id');
                var $modal = $('#straico-rag-details-modal');
                var $content = $modal.find('.straico-details-content');
                var $loading = $modal.find('.straico-loading');
                var $error = $modal.find('.straico-error');

                // Reset modal state
                $content.hide();
                $error.hide();
                $loading.show();
                $modal.show();

                // Fetch RAG details
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'straico_get_rag_details',
                        nonce: straicoAdmin.nonce,
                        rag_id: ragId
                    },
                    success: function(response) {
                        $loading.hide();
                        if (response.success) {
                            $content.html(response.data.html).show();
                        } else {
                            $error.text(response.data.message).show();
                        }
                    },
                    error: function() {
                        $loading.hide();
                        $error.text(straicoAdmin.strings.error).show();
                    }
                });
            });
        },

        /**
         * Initialize RAG deletion functionality.
         */
        initDeleteRAG: function() {
            $('.straico-delete-rag').on('click', function() {
                var ragId = $(this).data('rag-id');
                var ragName = $(this).data('rag-name');
                var $modal = $('#straico-delete-confirm-modal');
                var $message = $modal.find('.straico-delete-message');
                var $loading = $modal.find('.straico-loading');
                var $error = $modal.find('.straico-error');
                var $actions = $modal.find('.straico-modal-actions');

                // Set confirmation message
                $message.html(
                    straicoAdmin.strings.confirmDelete.replace('{name}', '<strong>' + ragName + '</strong>')
                );

                // Reset modal state
                $error.hide();
                $loading.hide();
                $actions.show();
                $modal.show();

                // Store RAG ID for deletion
                $modal.data('rag-id', ragId);
            });

            // Handle delete confirmation
            $('.straico-confirm-delete').on('click', function() {
                var $modal = $('#straico-delete-confirm-modal');
                var ragId = $modal.data('rag-id');
                var $loading = $modal.find('.straico-loading');
                var $error = $modal.find('.straico-error');
                var $actions = $modal.find('.straico-modal-actions');

                // Reset state
                $error.hide();
                $actions.hide();
                $loading.show();

                // Delete RAG
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'straico_delete_rag',
                        nonce: straicoAdmin.nonce,
                        rag_id: ragId
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            $loading.hide();
                            $error.text(response.data.message).show();
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
        }
    };

    // Initialize on document ready
    $(function() {
        RAGManagement.init();
    });

})(jQuery);
