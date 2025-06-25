<?php
/**
 * Admin basic prompt completion page template.
 *
 * This template displays the form for submitting basic prompt completions in the WordPress admin area.
 *
 * @since      1.0.0
 * @package    Straico_Integration
 * @subpackage Straico_Integration/admin/partials/prompt-completions
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get models API instance for LLM options
$models_api = new Straico_Models_API();
$models = $models_api->get_models();

// Check if API key is set
if (!$models_api->has_api_key()) {
    ?>
    <div class="notice notice-error">
        <p>
            <?php 
            printf(
                /* translators: %s: settings page URL */
                __('Straico API key is not set. Please configure it in the <a href="%s">settings</a>.', 'straico-integration'),
                admin_url('admin.php?page=straico-settings')
            ); 
            ?>
        </p>
    </div>
    <?php
    return;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="straico-prompt-completion">
        <form method="post" enctype="multipart/form-data" class="straico-prompt-form">
            <?php wp_nonce_field('straico_prompt_completion', 'straico_nonce'); ?>
            <input type="hidden" name="action" value="straico_prompt_completion">

            <div class="straico-form-section">
                <h2><?php _e('Model Selection', 'straico-integration'); ?></h2>
                <?php if (is_wp_error($models)) : ?>
                    <div class="notice notice-error inline">
                        <p><?php echo esc_html($models->get_error_message()); ?></p>
                    </div>
                <?php elseif (!isset($models['data']['chat']) || empty($models['data']['chat'])) : ?>
                    <div class="notice notice-error inline">
                        <p><?php _e('No chat models available. Please try again later.', 'straico-integration'); ?></p>
                    </div>
                <?php else : ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="model_selection">
                                    <?php _e('Models', 'straico-integration'); ?>
                                    <span class="required">*</span>
                                </label>
                            </th>
                            <td>
                                <div class="straico-model-selection">
                                    <?php foreach ($models['data']['chat'] as $model) : ?>
                                        <div class="straico-model-option">
                                            <label>
                                                <input type="checkbox" 
                                                       name="models[]" 
                                                       value="<?php echo esc_attr($model['model']); ?>"
                                                >
                                                <?php echo esc_html($model['name']); ?>
                                                <span class="straico-model-info">
                                                    <?php
                                                    printf(
                                                        /* translators: 1: word limit 2: max output tokens 3: coins per 100 words */
                                                        __('(Limit: %1$s words, Max Output: %2$s tokens, Cost: %3$s coins/100 words)', 'straico-integration'),
                                                        number_format($model['word_limit']),
                                                        number_format($model['max_output']),
                                                        number_format($model['pricing']['coins'], 2)
                                                    );
                                                    ?>
                                                </span>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <p class="description">
                                    <?php _e('Select up to 4 models to compare responses.', 'straico-integration'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                <?php endif; ?>
            </div>

            <div class="straico-form-section">
                <h2><?php _e('Prompt', 'straico-integration'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="prompt_message">
                                <?php _e('Message', 'straico-integration'); ?>
                                <span class="required">*</span>
                            </label>
                        </th>
                        <td>
                            <textarea id="prompt_message" 
                                      name="message" 
                                      class="large-text code" 
                                      rows="5" 
                                      required
                            ></textarea>
                            <p class="description">
                                <?php _e('Enter your prompt message.', 'straico-integration'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="prompt_file">
                                <?php _e('File Attachment', 'straico-integration'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="file" 
                                   id="prompt_file" 
                                   name="file" 
                                   accept=".pdf,.docx,.pptx,.txt,.xlsx,.mp3,.mp4,.html,.csv,.json,.py,.php,.js,.css,.cs,.swift,.kt,.xml,.ts"
                            >
                            <p class="description">
                                <?php
                                printf(
                                    /* translators: %s: maximum file size */
                                    __('Optional. Maximum file size: %s. Supported formats: PDF, DOCX, PPTX, TXT, XLSX, MP3, MP4, HTML, CSV, JSON, PY, PHP, JS, CSS, CS, Swift, KT, XML, TS', 'straico-integration'),
                                    size_format(25 * MB_IN_BYTES)
                                );
                                ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="youtube_url">
                                <?php _e('YouTube URL', 'straico-integration'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="url" 
                                   id="youtube_url" 
                                   name="youtube_url" 
                                   class="regular-text" 
                                   placeholder="https://www.youtube.com/watch?v=..."
                            >
                            <p class="description">
                                <?php _e('Optional. Enter a YouTube video URL.', 'straico-integration'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="straico-form-section">
                <h2><?php _e('Options', 'straico-integration'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="temperature">
                                <?php _e('Temperature', 'straico-integration'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="temperature" 
                                   name="temperature" 
                                   class="small-text" 
                                   value="0.7" 
                                   min="0" 
                                   max="2" 
                                   step="0.1"
                            >
                            <p class="description">
                                <?php _e('Optional. Controls randomness in the response (0-2). Higher values make the output more random.', 'straico-integration'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="max_tokens">
                                <?php _e('Max Tokens', 'straico-integration'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="max_tokens" 
                                   name="max_tokens" 
                                   class="small-text" 
                                   min="1"
                            >
                            <p class="description">
                                <?php _e('Optional. Maximum number of tokens to generate. Leave empty to use model default.', 'straico-integration'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <?php _e('Display Transcripts', 'straico-integration'); ?>
                        </th>
                        <td>
                            <fieldset>
                                <label for="display_transcripts">
                                    <input type="checkbox" 
                                           id="display_transcripts" 
                                           name="display_transcripts" 
                                           value="1"
                                    >
                                    <?php _e('Show file and video transcripts in response', 'straico-integration'); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="straico-form-actions">
                <button type="submit" class="button button-primary" <?php echo (is_wp_error($models) || !isset($models['data']['chat']) || empty($models['data']['chat'])) ? 'disabled' : ''; ?>>
                    <?php _e('Submit Prompt', 'straico-integration'); ?>
                </button>
                <button type="reset" class="button">
                    <?php _e('Reset', 'straico-integration'); ?>
                </button>
                <div class="straico-loading" style="display: none;">
                    <?php _e('Processing prompt...', 'straico-integration'); ?>
                </div>
                <div class="straico-error" style="display: none;"></div>
            </div>
        </form>

        <div class="straico-response" style="display: none;">
            <div class="straico-response-header">
                <h2><?php _e('Response', 'straico-integration'); ?></h2>
                <button type="button" class="button straico-reset-prompt">
                    <?php _e('New Prompt', 'straico-integration'); ?>
                </button>
            </div>
            <div class="straico-response-content"></div>
        </div>
    </div>
</div>
