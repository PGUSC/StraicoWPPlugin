/**
 * JavaScript functionality for prompt completion pages.
 *
 * @since      1.0.0
 * @package    Straico_Integration
 * @subpackage Straico_Integration/admin/js
 */

jQuery(document).ready(function($) {
    // Handle model selection limit
    $('.straico-model-selection input[type="checkbox"]').on('change', function() {
        var checked = $('.straico-model-selection input[type="checkbox"]:checked').length;
        if (checked >= 4) {
            $('.straico-model-selection input[type="checkbox"]:not(:checked)').prop('disabled', true);
        } else {
            $('.straico-model-selection input[type="checkbox"]').prop('disabled', false);
        }
    });

    // Handle form submission
    $('.straico-prompt-form').on('submit', async function(e) {
        e.preventDefault();

        // Validate model selection
        if ($('.straico-model-selection input[type="checkbox"]:checked').length === 0) {
            alert(straicoAdmin.strings.selectModel || 'Please select at least one model.');
            return;
        }

        var $form = $(this);
        var $submit = $form.find('button[type="submit"]');
        var $reset = $form.find('button[type="reset"]');
        var $loading = $form.find('.straico-loading');
        var $error = $form.find('.straico-error');
        var $response = $('.straico-response');
        var $responseContent = $('.straico-response-content');

        // Reset state
        $error.hide();
        $submit.prop('disabled', true);
        $reset.prop('disabled', true);
        $loading.show();
        $response.hide();
        $responseContent.empty();

        try {
            // Handle file upload first if a file is present
            let fileUrls = [];
            var fileInput = $form.find('input[name="file"]')[0];
            if (fileInput && fileInput.files.length > 0) {
                console.log('File detected, uploading first...');
                
                // Create FormData for file upload
                var fileFormData = new FormData();
                fileFormData.append('action', 'straico_upload_file');
                fileFormData.append('straico_nonce', $form.find('input[name="straico_nonce"]').val());
                // Preserve original filename when appending to FormData
                const file = fileInput.files[0];
                fileFormData.append('file', file, file.name);

                // Upload file
                try {
                    const uploadResponse = await $.ajax({
                        url: straicoAdmin.ajaxurl,
                        type: 'POST',
                        data: fileFormData,
                        processData: false,
                        contentType: false
                    });

                    console.log('File upload response:', uploadResponse);

                    if (uploadResponse.success && uploadResponse.data && uploadResponse.data.url) {
                        fileUrls.push(uploadResponse.data.url);
                        console.log('File uploaded successfully:', uploadResponse.data.url);
                    } else {
                        throw new Error(uploadResponse.data.message || 'File upload failed');
                    }
                } catch (uploadError) {
                    console.error('File upload error:', uploadError);
                    throw new Error('File upload failed: ' + (uploadError.message || 'Unknown error'));
                }
            }

            // Create FormData for prompt completion
            var promptFormData = new FormData();
            
            // Add required fields
            promptFormData.append('action', 'straico_prompt_completion');
            promptFormData.append('straico_nonce', $form.find('input[name="straico_nonce"]').val());
            
            // Append each selected model individually to create an array in FormData
            $form.find('input[name="models[]"]:checked').each(function() {
                promptFormData.append('models[]', $(this).val());
            });
            
            // Add message
            promptFormData.append('message', $form.find('textarea[name="message"]').val());
            
            // Add optional YouTube URL if valid
            var youtubeUrl = $form.find('input[name="youtube_url"]').val();
            if (youtubeUrl && youtubeUrl.match(/^https?:\/\/(www\.)?youtube\.com\/watch\?v=[\w-]+$/)) {
                promptFormData.append('youtube_url', youtubeUrl);
            }

            // Add file URLs if we have any
            if (fileUrls.length > 0) {
                fileUrls.forEach(url => {
                    promptFormData.append('file_urls[]', url);
                });
            }

            // Only add display_transcripts if checked
            if ($form.find('input[name="display_transcripts"]').is(':checked')) {
                promptFormData.append('display_transcripts', '1');
            }
            
            // Add temperature if provided
            var temperature = $form.find('input[name="temperature"]').val();
            if (temperature) {
                promptFormData.append('temperature', temperature);
            }
            
            // Add max_tokens if provided
            var maxTokens = $form.find('input[name="max_tokens"]').val();
            if (maxTokens) {
                promptFormData.append('max_tokens', maxTokens);
            }

            // Log the form data for debugging
            console.log('Prompt completion form data being sent:');
            for (var pair of promptFormData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }

            // Submit prompt completion request
            const response = await $.ajax({
                url: straicoAdmin.ajaxurl,
                type: 'POST',
                data: promptFormData,
                processData: false,
                contentType: false
            });

            console.log('API Response:', response);
            
            if (response.success) {
                // Format and display response
                var html = '';

                // Add model responses
                $.each(response.data.completions, function(model, data) {
                    console.log('Processing model response:', model, data);
                    
                    html += '<div class="straico-model-response">';
                    html += '<div class="straico-model-name">' + model + '</div>';
                    html += '<div class="straico-model-completion">' + data.completion.choices[0].message.content + '</div>';
                    html += '<div class="straico-model-stats">';
                    html += straicoAdmin.strings.input + ': ' + data.words.input + ' ' + straicoAdmin.strings.words + ' ';
                    html += '(' + straicoAdmin.strings.cost + ': ' + data.price.input.toFixed(2) + ' ' + straicoAdmin.strings.coins + ')<br>';
                    html += straicoAdmin.strings.output + ': ' + data.words.output + ' ' + straicoAdmin.strings.words + ' ';
                    html += '(' + straicoAdmin.strings.cost + ': ' + data.price.output.toFixed(2) + ' ' + straicoAdmin.strings.coins + ')';
                    html += '</div>';
                    html += '</div>';
                });

                // Add transcripts if enabled
                if (response.data.transcripts && response.data.transcripts.length > 0) {
                    html += '<div class="straico-transcripts">';
                    html += '<h3>' + straicoAdmin.strings.transcripts + '</h3>';
                    $.each(response.data.transcripts, function(i, transcript) {
                        html += '<div class="straico-transcript">';
                        html += '<div class="straico-transcript-name">' + transcript.name + '</div>';
                        html += '<div class="straico-transcript-content">' + transcript.text + '</div>';
                        html += '</div>';
                    });
                    html += '</div>';
                }

                // Add overall stats
                html += '<div class="straico-overall-stats">';
                html += '<strong>' + straicoAdmin.strings.totalUsage + ':</strong><br>';
                html += straicoAdmin.strings.input + ': ' + response.data.overall_words.input + ' ' + straicoAdmin.strings.words + ' ';
                html += '(' + straicoAdmin.strings.cost + ': ' + response.data.overall_price.input.toFixed(2) + ' ' + straicoAdmin.strings.coins + ')<br>';
                html += straicoAdmin.strings.output + ': ' + response.data.overall_words.output + ' ' + straicoAdmin.strings.words + ' ';
                html += '(' + straicoAdmin.strings.cost + ': ' + response.data.overall_price.output.toFixed(2) + ' ' + straicoAdmin.strings.coins + ')<br>';
                html += straicoAdmin.strings.totalCost + ': ' + response.data.overall_price.total.toFixed(2) + ' ' + straicoAdmin.strings.coins;
                html += '</div>';

                $responseContent.html(html);
                $response.show();
                $form.hide();
            } else {
                throw new Error(response.data.message || straicoAdmin.strings.error);
            }
        } catch (error) {
            console.error('Error:', error);
            $error.text(error.message || straicoAdmin.strings.error).show();
            $submit.prop('disabled', false);
            $reset.prop('disabled', false);
        } finally {
            $loading.hide();
        }
    });

    // Handle form reset
    $('.straico-prompt-form button[type="reset"]').on('click', function() {
        var $form = $(this).closest('form');
        $form.find('.straico-error').hide();
        $('.straico-model-selection input[type="checkbox"]').prop('disabled', false);
    });

    // Handle new prompt button
    $('.straico-reset-prompt').on('click', function() {
        $('.straico-prompt-form').show().trigger('reset');
        $('.straico-response').hide();
        $('.straico-error').hide();
        $('.straico-model-selection input[type="checkbox"]').prop('disabled', false);
    });

    // Validate YouTube URL
    $('#youtube_url').on('change', function() {
        var url = $(this).val();
        if (url && !url.match(/^https?:\/\/(www\.)?youtube\.com\/watch\?v=[\w-]+$/)) {
            alert(straicoAdmin.strings.invalidYouTubeUrl || 'Please enter a valid YouTube video URL.');
            $(this).val('');
        }
    });
});
