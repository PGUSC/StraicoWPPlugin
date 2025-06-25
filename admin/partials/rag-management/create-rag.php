<?php
/**
 * Admin create RAG page template.
 *
 * This template displays the form for creating a new RAG in the WordPress admin area.
 *
 * @since      1.0.0
 * @package    Straico_Integration
 * @subpackage Straico_Integration/admin/partials/rag-management
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get RAG API instance for chunking methods
$rag_api = new Straico_RAG_API();
$chunking_methods = $rag_api->get_chunking_methods();
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="straico-create-rag">
        <form method="post" enctype="multipart/form-data" class="straico-rag-form">
            <?php wp_nonce_field('straico_create_rag', 'straico_nonce'); ?>
            <input type="hidden" name="action" value="straico_create_rag">

            <div class="straico-form-section">
                <h2><?php _e('Basic Information', 'straico-integration'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="rag_name">
                                <?php _e('Name', 'straico-integration'); ?>
                                <span class="required">*</span>
                            </label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="rag_name" 
                                   name="rag_name" 
                                   class="regular-text" 
                                   required
                            >
                            <p class="description">
                                <?php _e('Enter a name for this RAG.', 'straico-integration'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="rag_description">
                                <?php _e('Description', 'straico-integration'); ?>
                                <span class="required">*</span>
                            </label>
                        </th>
                        <td>
                            <textarea id="rag_description" 
                                      name="rag_description" 
                                      class="large-text" 
                                      rows="3" 
                                      required
                            ></textarea>
                            <p class="description">
                                <?php _e('Describe the purpose of this RAG.', 'straico-integration'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="straico-form-section">
                <h2><?php _e('File Upload', 'straico-integration'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="rag_files">
                                <?php _e('Files', 'straico-integration'); ?>
                                <span class="required">*</span>
                            </label>
                        </th>
                        <td>
                            <div class="straico-file-uploads">
                                <div class="straico-file-upload">
                                    <input type="file" 
                                           id="rag_files"
                                           name="rag_files[]" 
                                           accept=".pdf,.docx,.csv,.txt,.xlsx,.py"
                                           required
                                    >
                                    <button type="button" class="button straico-add-file">
                                        <?php _e('Add Another File', 'straico-integration'); ?>
                                    </button>
                                </div>
                            </div>
                            <p class="description">
                                <?php
                                printf(
                                    /* translators: %s: maximum file size */
                                    __('Upload up to 4 files (max %s each). Supported formats: PDF, DOCX, CSV, TXT, XLSX, PY', 'straico-integration'),
                                    size_format(25 * MB_IN_BYTES)
                                );
                                ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="straico-form-section">
                <h2><?php _e('Chunking Options', 'straico-integration'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="chunking_method">
                                <?php _e('Chunking Method', 'straico-integration'); ?>
                            </label>
                        </th>
                        <td>
                            <select id="chunking_method" name="chunking_method">
                                <?php foreach ($chunking_methods as $method => $info) : ?>
                                    <option value="<?php echo esc_attr($method); ?>">
                                        <?php echo esc_html($info['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description straico-method-description"></p>
                        </td>
                    </tr>
                </table>

                <?php foreach ($chunking_methods as $method => $info) : ?>
                    <div class="straico-method-options" data-method="<?php echo esc_attr($method); ?>" style="display: none;">
                        <table class="form-table">
                            <?php foreach ($info['parameters'] as $param => $config) : ?>
                                <tr>
                                    <th scope="row">
                                        <label for="<?php echo esc_attr("{$method}_{$param}"); ?>">
                                            <?php echo esc_html(ucwords(str_replace('_', ' ', $param))); ?>
                                        </label>
                                    </th>
                                    <td>
                                        <?php if ($config['type'] === 'number') : ?>
                                            <input type="number" 
                                                   id="<?php echo esc_attr("{$method}_{$param}"); ?>"
                                                   name="<?php echo esc_attr("chunking_options[{$param}]"); ?>"
                                                   value="<?php echo esc_attr($config['default']); ?>"
                                                   class="small-text"
                                            >
                                        <?php elseif ($config['type'] === 'string' && isset($config['options'])) : ?>
                                            <select id="<?php echo esc_attr("{$method}_{$param}"); ?>"
                                                    name="<?php echo esc_attr("chunking_options[{$param}]"); ?>"
                                            >
                                                <?php foreach ($config['options'] as $option) : ?>
                                                    <option value="<?php echo esc_attr($option); ?>"
                                                            <?php selected($option, $config['default']); ?>
                                                    >
                                                        <?php echo esc_html(ucwords(str_replace('_', ' ', $option))); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        <?php elseif ($config['type'] === 'array') : ?>
                                            <textarea id="<?php echo esc_attr("{$method}_{$param}"); ?>"
                                                      name="<?php echo esc_attr("chunking_options[{$param}]"); ?>"
                                                      class="large-text code"
                                                      rows="3"
                                            ><?php echo esc_textarea(implode("\n", $config['default'])); ?></textarea>
                                        <?php else : ?>
                                            <input type="text" 
                                                   id="<?php echo esc_attr("{$method}_{$param}"); ?>"
                                                   name="<?php echo esc_attr("chunking_options[{$param}]"); ?>"
                                                   value="<?php 
                                                   if ($param === 'separator' && $method === 'fixed_size') {
                                                       echo '\\n';
                                                   } else {
                                                       echo esc_attr($config['default']);
                                                   }
                                                   ?>"
                                                   class="regular-text"
                                            >
                                        <?php endif; ?>
                                        <p class="description">
                                            <?php 
                                            echo esc_html($config['description']);
                                            if ($param === 'separator' && $method === 'fixed_size') {
                                                echo ' ' . esc_html__('Only one separator is allowed, but it may be multiple characters long (e.g., "\\n", ". ", "\\n\\n").', 'straico-integration');
                                            }
                                            ?>
                                        </p>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="straico-form-actions">
                <button type="submit" class="button button-primary">
                    <?php _e('Create RAG', 'straico-integration'); ?>
                </button>
                <div class="straico-loading" style="display: none;">
                    <?php _e('Creating RAG... This may take a few minutes.', 'straico-integration'); ?>
                </div>
                <div class="straico-error" style="display: none;"></div>
            </div>
        </form>
    </div>
</div>

<style>
.straico-create-rag {
    margin-top: 1em;
}

.straico-form-section {
    margin: 2em 0;
    padding: 1em;
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.straico-form-section h2 {
    margin-top: 0;
    padding-bottom: 0.5em;
    border-bottom: 1px solid #eee;
}

.required {
    color: #dc3232;
}

.straico-file-uploads {
    margin-bottom: 1em;
}

.straico-file-upload {
    display: flex;
    align-items: center;
    gap: 1em;
    margin-bottom: 0.5em;
}

.straico-file-upload .button {
    flex-shrink: 0;
}

.straico-form-actions {
    margin: 2em 0;
}

.straico-loading,
.straico-error {
    margin-top: 1em;
    padding: 1em;
    background: #f8f9fa;
    border-radius: 4px;
}

.straico-error {
    color: #dc3232;
    background: #f8d7da;
}

.straico-progress {
    margin-top: 1em;
    display: none;
}

.straico-progress-bar {
    height: 20px;
    background-color: #f0f0f1;
    border-radius: 10px;
    overflow: hidden;
}

.straico-progress-bar-fill {
    height: 100%;
    background-color: #2271b1;
    width: 0;
    transition: width 0.3s ease;
}

.straico-progress-text {
    text-align: center;
    margin-top: 0.5em;
    font-size: 12px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Store chunking methods data
    var chunkingMethods = <?php echo wp_json_encode($chunking_methods); ?>;

    // Update chunking method description and options
    function updateChunkingMethod() {
        var method = $('#chunking_method').val();
        var methodInfo = chunkingMethods[method];

        // Update description
        $('.straico-method-description').text(methodInfo.description);

        // Show/hide options and set default values
        $('.straico-method-options').hide();
        var $methodOptions = $('.straico-method-options[data-method="' + method + '"]');
        $methodOptions.show();

        // Set default values for the selected method's parameters
        Object.entries(methodInfo.parameters).forEach(function([param, config]) {
            var $input = $methodOptions.find('#' + method + '_' + param);
            if (method === 'recursive' && param === 'separators') {
                // For recursive chunking's separators, show each separator on a new line
                if (config.type === 'array' && Array.isArray(config.default)) {
                    $input.val(config.default.join('\n'));
                }
            } else if (method === 'fixed_size' && param === 'separator') {
                // For fixed_size chunking's separator
                $input.val('\\n');
            } else if (config.default !== undefined) {
                // For all other parameters
                $input.val(config.default);
            }
        });
    }

    // Initialize chunking method display
    updateChunkingMethod();

    // Handle chunking method change
    $('#chunking_method').on('change', updateChunkingMethod);

    // Handle file upload buttons
    $('.straico-add-file').on('click', function() {
        var $uploads = $('.straico-file-uploads');
        var fileCount = $uploads.find('.straico-file-upload').length;

        if (fileCount < 4) {
            var $newUpload = $('<div class="straico-file-upload"></div>');
            var newFileId = 'rag_files_' + (fileCount + 1);
            $newUpload.append('<input type="file" id="' + newFileId + '" name="rag_files[]" accept=".pdf,.docx,.csv,.txt,.xlsx,.py">');
            $newUpload.append('<button type="button" class="button straico-remove-file"><?php _e('Remove', 'straico-integration'); ?></button>');
            $uploads.append($newUpload);

            if (fileCount + 1 >= 4) {
                $('.straico-add-file').prop('disabled', true);
            }
        }
    });

    // Handle file removal
    $(document).on('click', '.straico-remove-file', function() {
        $(this).closest('.straico-file-upload').remove();
        $('.straico-add-file').prop('disabled', false);
    });

    // Handle form submission
    $('.straico-rag-form').on('submit', function(e) {
        e.preventDefault();

        var $form = $(this);
        var $submit = $form.find('button[type="submit"]');
        var $loading = $form.find('.straico-loading');
        var $error = $form.find('.straico-error');
        var $progress = $form.find('.straico-progress');

        // Validate required fields
        var name = $('#rag_name').val().trim();
        var description = $('#rag_description').val().trim();
        var files = $('input[type="file"][name="rag_files[]"]').get().filter(function(input) {
            return input.files.length > 0;
        });

        if (!name || !description) {
            $error.text('<?php _e('Please fill in all required fields.', 'straico-integration'); ?>').show();
            return;
        }

        if (name.indexOf(' ') !== -1) {
            $error.text('<?php _e('RAG name cannot contain spaces.', 'straico-integration'); ?>').show();
            return;
        }

        if (files.length === 0) {
            $error.text('<?php _e('Please upload at least one file.', 'straico-integration'); ?>').show();
            return;
        }

        // Check if files are still loading
        var totalSize = 0;
        var maxFileSize = <?php echo wp_max_upload_size(); ?>; // WordPress max upload size
        var filesReady = true;

        for (var i = 0; i < files.length; i++) {
            var file = files[i].files[0];
            totalSize += file.size;
            
            // Check if file is still loading
            if (file.size === 0) {
                filesReady = false;
                break;
            }
            
            // Check individual file size
            if (file.size > maxFileSize) {
                $error.text('<?php _e('One or more files exceed the maximum file size limit.', 'straico-integration'); ?>').show();
                return;
            }
        }

        // Check if files are still loading
        if (!filesReady) {
            $error.text('<?php _e('Please wait for files to finish loading.', 'straico-integration'); ?>').show();
            return;
        }

        // Reset state
        $error.hide();
        $submit.prop('disabled', true);
        $loading.show();
        $progress.show();

        // Show upload progress
        var $progressBar = $('<div class="straico-progress-bar"><div class="straico-progress-bar-fill"></div></div>');
        var $progressText = $('<div class="straico-progress-text"></div>');
        $progress.empty().append($progressBar).append($progressText);

        // Create FormData object and explicitly append form data
        var formData = new FormData();
        
        // Add WordPress action and security nonce
        formData.append('action', 'straico_create_rag');
        var nonce = $('#straico_nonce').val();
        if (!nonce) {
            $error.text('<?php _e('Security check failed. Please refresh the page and try again.', 'straico-integration'); ?>').show();
            return;
        }
        formData.append('straico_nonce', nonce);
        
        // Add form data
        formData.append('rag_name', name);
        formData.append('rag_description', description);
        
        // Log form data for debugging
        console.log('Form Data:', {
            name: name,
            description: description,
            files_count: files.length,
            nonce: nonce ? 'present' : 'missing'
        });
        
        // Append only the files that were actually selected
        files.forEach(function(fileInput, index) {
            var file = fileInput.files[0];
            formData.append('rag_files[]', file);
            console.log('File ' + (index + 1) + ':', {
                name: file.name,
                size: file.size,
                type: file.type
            });
        });

        // Add chunking method
        var chunkingMethod = $('#chunking_method').val();
        formData.append('chunking_method', chunkingMethod);
        var $methodOptions = $('.straico-method-options[data-method="' + chunkingMethod + '"]');

        // Handle method-specific parameters
        switch (chunkingMethod) {
                    case 'fixed_size':
                        // Add chunk_size
                        var chunkSize = $methodOptions.find('input[name="chunking_options[chunk_size]"]').val();
                        formData.append('chunk_size', parseInt(chunkSize) || 1000);

                        // Add chunk_overlap
                        var chunkOverlap = $methodOptions.find('input[name="chunking_options[chunk_overlap]"]').val();
                        formData.append('chunk_overlap', parseInt(chunkOverlap) || 50);

                        // Add single separator
                        var separator = $methodOptions.find('input[name="chunking_options[separator]"]').val();
                        formData.append('separator', (!separator || separator === '\\n') ? '\n' : separator);

                        console.log('Fixed Size Options:', {
                            chunk_size: parseInt(chunkSize) || 1000,
                            chunk_overlap: parseInt(chunkOverlap) || 50,
                            separator: (!separator || separator === '\\n') ? '\n' : separator
                        });
                        break;

                    case 'recursive':
                        // Add chunk_size
                        var chunkSize = $methodOptions.find('input[name="chunking_options[chunk_size]"]').val();
                        formData.append('chunk_size', parseInt(chunkSize) || 1000);

                        // Add chunk_overlap
                        var chunkOverlap = $methodOptions.find('input[name="chunking_options[chunk_overlap]"]').val();
                        formData.append('chunk_overlap', parseInt(chunkOverlap) || 50);

                        // Add separators array
                        var separatorsText = $methodOptions.find('textarea[name="chunking_options[separators]"]').val();
                        var separators = separatorsText ? 
                            separatorsText.split('\n').filter(Boolean).map(s => s.trim()) : 
                            ["\n\n", "\n", ". "];
                        formData.append('separators', JSON.stringify(separators));

                        console.log('Recursive Options:', {
                            chunk_size: parseInt(chunkSize) || 1000,
                            chunk_overlap: parseInt(chunkOverlap) || 50,
                            separators: separators
                        });
                        break;

                    case 'markdown':
                    case 'python':
                        // Add chunk_size
                        var chunkSize = $methodOptions.find('input[name="chunking_options[chunk_size]"]').val();
                        formData.append('chunk_size', parseInt(chunkSize) || 1000);

                        // Add chunk_overlap
                        var chunkOverlap = $methodOptions.find('input[name="chunking_options[chunk_overlap]"]').val();
                        formData.append('chunk_overlap', parseInt(chunkOverlap) || 50);

                        console.log(chunkingMethod + ' Options:', {
                            chunk_size: parseInt(chunkSize) || 1000,
                            chunk_overlap: parseInt(chunkOverlap) || 50
                        });
                        break;

                    case 'semantic':
                        // Add breakpoint_threshold_type
                        var thresholdType = $methodOptions.find('select[name="chunking_options[breakpoint_threshold_type]"]').val();
                        if (thresholdType) {
                            formData.append('breakpoint_threshold_type', thresholdType);
                        }

                        // Add buffer_size
                        var bufferSize = $methodOptions.find('input[name="chunking_options[buffer_size]"]').val();
                        formData.append('buffer_size', parseInt(bufferSize) || 100);

                        console.log('Semantic Options:', {
                            breakpoint_threshold_type: thresholdType,
                            buffer_size: parseInt(bufferSize) || 100
                        });
                        break;
                }

                // Log final FormData for debugging
                console.log('Final FormData entries:');
                for (var pair of formData.entries()) {
                    if (pair[0] === 'rag_files[]') {
                        console.log(pair[0], pair[1].name);
                    } else {
                        console.log(pair[0], pair[1]);
                    }
                }

                // Submit form via AJAX with upload progress tracking
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            timeout: 300000, // 5 minute timeout for large files
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(evt) {
                    if (evt.lengthComputable) {
                        var percentComplete = (evt.loaded / evt.total) * 100;
                        $progressBar.find('.straico-progress-bar-fill').css('width', percentComplete + '%');
                        $progressText.text('Upload progress: ' + Math.round(percentComplete) + '%');
                    }
                }, false);
                return xhr;
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = '<?php echo esc_url(admin_url('admin.php?page=straico-rag-management')); ?>';
                } else {
                    $error.text(response.data.message).show();
                    $submit.prop('disabled', false);
                    $loading.hide();
                }
            },
            error: function(xhr, status, error) {
                var errorMessage = '<?php _e('An error occurred. Please try again.', 'straico-integration'); ?>';
                if (status === 'timeout') {
                    errorMessage = '<?php _e('The Straico servers are still processing your files. Large or complex files can take considerable time to process. Please give the servers up to 30 minutes to finish processing your files and then check the RAG Management page to see if it is ready for use.', 'straico-integration'); ?>';
                    // Redirect to RAG management page after showing message
                    setTimeout(function() {
                        window.location.href = '<?php echo esc_url(admin_url('admin.php?page=straico-rag-management')); ?>';
                    }, 10000); // Wait 10 seconds so user can read the message
                }
                $error.text(errorMessage).show();
                $submit.prop('disabled', false);
                $loading.hide();
                $progress.hide();
            }
        });
    });
});
</script>
