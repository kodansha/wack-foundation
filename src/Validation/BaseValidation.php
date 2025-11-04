<?php

namespace WackFoundation\Validation;

use ReflectionClass;

/**
 * Base abstract class for implementing custom block editor validation
 *
 * This class provides a foundation for adding custom validation logic to the WordPress
 * block editor for specific post types. It automatically loads and configures validation
 * JavaScript files that can enforce content rules and requirements.
 *
 * ## File Structure Convention
 *
 * When extending this class, you must follow this file structure:
 * ```
 * YourValidation.php           # Your validation class
 * assets/
 *   └── validation.js          # Your validation script (required)
 * ```
 *
 * The validation script must be named exactly `validation.js` and placed in an `assets`
 * directory adjacent to your validation class file.
 *
 * ## JavaScript Module Support
 *
 * The validation script is automatically loaded as an ES6 module (`type="module"`),
 * allowing you to use import/export statements in your JavaScript code.
 *
 * ## Using the lockEditor Utility
 *
 * The framework provides a `lockEditor` utility function to easily implement validation
 * that prevents publishing when requirements are not met. Import it from the Util directory.
 *
 * ## Example Usage
 *
 * PHP class:
 * ```php
 * class PostValidation extends BaseValidation
 * {
 *     protected function getTargetPostType(): string
 *     {
 *         return 'post';
 *     }
 * }
 *
 * // Initialize validation
 * new PostValidation();
 * ```
 *
 * JavaScript validation.js with lockEditor utility:
 * ```javascript
 * import { lockEditor } from '/app/themes/wack-foundation/src/Validation/assets/validation-lock-utility.js';
 *
 * // Validate post title
 * const validateTitle = () => {
 *     const title = wp.data.select('core/editor').getEditedPostAttribute('title') || '';
 *
 *     lockEditor(
 *         title.length <= 0,
 *         'title-missing-lock',
 *         'Please enter a title'
 *     );
 * };
 *
 * // Validate categories
 * const validateCategories = () => {
 *     const categories = wp.data.select('core/editor').getEditedPostAttribute('categories') || [];
 *
 *     lockEditor(
 *         categories.length === 0,
 *         'category-missing-lock',
 *         'Please select a category'
 *     );
 *
 *     lockEditor(
 *         categories.length > 1,
 *         'category-multiple-lock',
 *         'Please select only one category'
 *     );
 * };
 *
 * // Validate featured image
 * const validateFeaturedImage = () => {
 *     const featuredMedia = wp.data.select('core/editor').getEditedPostAttribute('featured_media');
 *
 *     lockEditor(
 *         featuredMedia === 0,
 *         'featured-image-missing-lock',
 *         'Please set a featured image'
 *     );
 * };
 *
 * // Run validation on page load and on every change
 * wp.domReady(() => {
 *     const validate = () => {
 *         validateTitle();
 *         validateCategories();
 *         validateFeaturedImage();
 *     };
 *
 *     validate();
 *     wp.data.subscribe(validate);
 * });
 * ```
 */
abstract class BaseValidation
{
    /**
     * Flag to track if lock-editor utility has been enqueued
     *
     * This static variable ensures that the lock-editor.js utility is only
     * enqueued once, even when multiple validation classes are instantiated.
     *
     * @var bool
     */
    private static bool $lockEditorEnqueued = false;

    /**
     * Constructor
     *
     * Registers the necessary WordPress hooks for loading validation scripts
     * and configuring them as ES6 modules.
     */
    public function __construct()
    {
        add_action('enqueue_block_editor_assets', [$this, 'loadValidationScript']);
        add_filter('script_loader_tag', [$this, 'setTypeToModule'], 10, 3);
    }

    /**
     * Load the validation script for the target post type
     *
     * This method automatically locates and enqueues the validation.js file from the
     * assets directory adjacent to the child class file. The script is only loaded
     * when editing the target post type.
     *
     * Also, enqueues the shared lock-editor.js utility (once per page load) that
     * provides the lockEditor() function for validation scripts.
     *
     * The script is enqueued with dependencies on:
     * - wp-dom-ready: For DOM manipulation after editor is ready
     * - wp-data: For accessing WordPress data stores
     *
     * @return void
     */
    public function loadValidationScript(): void
    {
        $post_type = get_post_type();

        // Only load validation script for the target post type
        if ($post_type !== $this->getTargetPostType()) {
            return;
        }

        // Enqueue lock-editor utility once (shared across all validations)
        $this->enqueueLockEditorUtility();

        // Use reflection to locate the validation.js file relative to the child class
        // This allows each validation class to have its own validation script
        $reflection = new ReflectionClass(static::class);
        $base_path = dirname($reflection->getFileName());
        $script_file_path = $base_path . '/assets/validation.js';

        wp_enqueue_script(
            $this->scriptName(),
            get_stylesheet_directory_uri() . str_replace(get_stylesheet_directory(), '', $script_file_path),
            ['wp-dom-ready', 'wp-data'],
            filemtime($script_file_path),
            true,
        );
    }

    /**
     * Enqueue the validation lock utility script
     *
     * This method ensures that the validation-lock-utility.js is only enqueued once,
     * even when multiple validation classes are used. The utility provides the
     * lockEditor() function that validation scripts can import.
     *
     * @return void
     */
    private function enqueueLockEditorUtility(): void
    {
        // Skip if already enqueued
        if (self::$lockEditorEnqueued) {
            return;
        }

        $lock_editor_path = __DIR__ . '/assets/validation-lock-utility.js';

        wp_enqueue_script(
            'validation-lock-utility',
            get_template_directory_uri() . str_replace(get_template_directory(), '', $lock_editor_path),
            ['wp-dom-ready', 'wp-data'],
            filemtime($lock_editor_path),
            true,
        );

        self::$lockEditorEnqueued = true;
    }

    /**
     * Add type="module" attribute to the validation script tag
     *
     * This filter callback modifies the script tag to support ES6 modules,
     * allowing the use of import/export statements in the validation JavaScript.
     * Applies to both the validation lock utility and individual validation scripts.
     *
     * @param string $tag    The script tag HTML
     * @param string $handle The script handle
     * @param string $src    The script source URL
     * @return string Modified script tag with type="module" or original tag
     */
    public function setTypeToModule(string $tag, string $handle, string $src): string
    {
        // Apply type="module" to validation lock utility and validation scripts
        if ($handle === 'validation-lock-utility' || $handle === $this->scriptName()) {
            return '<script type="module" src="' . esc_url($src) . '"></script>';
        }

        return $tag;
    }

    /**
     * Generate the script handle name for the validation script
     *
     * Creates a unique handle name following the convention: "validation-{post_type}".
     * This handle is used when enqueuing the script and when filtering the script tag.
     *
     * @return string Script handle in the format "validation-{post_type}"
     */
    public function scriptName(): string
    {
        return 'validation-' . $this->getTargetPostType();
    }

    /**
     * Get the target post type for this validation
     *
     * Child classes must implement this method to specify which post type
     * should have this validation applied.
     *
     * @return string Post type slug (e.g., 'post', 'page', 'custom_post_type')
     */
    abstract protected function getTargetPostType(): string;
}
