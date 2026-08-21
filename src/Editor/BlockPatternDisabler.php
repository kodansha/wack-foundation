<?php

namespace WackFoundation\Editor;

use WP_Block_Patterns_Registry;

/**
 * Block pattern disabler for the block editor
 *
 * Removes block patterns from the block editor. Patterns insert markup that may
 * contain block types outside the allowed list (see BlockType), so they are
 * disabled by default.
 *
 * Patterns reach the editor through the REST endpoint
 * (wp/v2/block-patterns/patterns), which reads the pattern registry at request
 * time. Simply hiding the UI is therefore not enough, and registration is
 * stopped in three steps:
 *
 * 1. remove_theme_support('core-block-patterns') : core patterns (core/query-* etc.)
 * 2. should_load_remote_block_patterns filter    : remote patterns from the
 *                                                  wordpress.org pattern directory
 * 3. unregister on 'init'                        : anything else registered by
 *                                                  themes or plugins
 *
 * In addition, a stylesheet hides the "Patterns" tab in the inserter. WordPress
 * core always renders the tab even when no pattern is registered, and user
 * patterns (wp_block posts) are loaded separately via REST, so the tab is
 * removed as a whole.
 *
 * By default patterns are disabled. Child themes can keep them enabled by using
 * the 'wack_block_pattern_disabled' filter.
 *
 * Example usage:
 * <code>
 * <?php
 * // Instantiate to enable the feature (disables patterns by default)
 * new BlockPatternDisabler();
 *
 * // To keep patterns enabled in a child theme
 * add_filter('wack_block_pattern_disabled', fn() => false);
 * ?>
 * </code>
 *
 * Note: hiding the inserter tab relies on WordPress internal CSS classes and
 * DOM structure, so it may stop working after a WordPress version upgrade.
 * Verified on WordPress 7.1.
 */
class BlockPatternDisabler
{
    use Trait\AssetUrlTrait;

    /**
     * Style handle and file name
     */
    private const string STYLE_HANDLE = 'block-pattern-disabler';
    private const string STYLE_FILE = 'block-pattern-disabler.css';

    /**
     * Check if block patterns should be disabled
     *
     * Applies the 'wack_block_pattern_disabled' filter to allow customization.
     *
     * @return bool True if patterns should be disabled, false otherwise
     */
    protected function isDisabled(): bool
    {
        /**
         * Filter whether to disable block patterns
         *
         * @param bool $disabled Whether to disable block patterns. Default true.
         */
        return apply_filters('wack_block_pattern_disabled', true);
    }

    /**
     * Constructor
     *
     * Registers the hooks that stop pattern registration and hide the
     * "Patterns" tab in the inserter.
     */
    public function __construct()
    {
        add_action('after_setup_theme', [$this, 'removeCorePatternSupport']);
        add_filter('should_load_remote_block_patterns', [$this, 'filterShouldLoadRemotePatterns']);
        add_action('init', [$this, 'unregisterAllPatterns'], PHP_INT_MAX);
        add_action('enqueue_block_editor_assets', [$this, 'enqueueStyles']);
    }

    /**
     * Remove theme support for core block patterns
     *
     * Stops the registration of the patterns bundled with WordPress core
     * (core/query-*, core/navigation-overlay-* etc.). This also stops the
     * core and featured patterns coming from the pattern directory, which
     * are only loaded while this theme support is present.
     *
     * @return void
     */
    public function removeCorePatternSupport(): void
    {
        if (!$this->isDisabled()) {
            return;
        }

        remove_theme_support('core-block-patterns');
    }

    /**
     * Filter callback for remote block pattern loading
     *
     * Stops patterns from being fetched from the wordpress.org pattern
     * directory when patterns are disabled.
     *
     * @param bool $should_load Whether remote patterns should be loaded.
     * @return bool False when patterns are disabled, the original value otherwise
     */
    public function filterShouldLoadRemotePatterns(bool $should_load): bool
    {
        return $this->isDisabled() ? false : $should_load;
    }

    /**
     * Unregister every registered block pattern
     *
     * Runs at the very end of 'init' so that patterns registered by themes
     * (patterns directory) and plugins are covered as well. Core and remote
     * patterns are already stopped at this point, so the registry is usually
     * empty and this is a no-op.
     *
     * @return void
     */
    public function unregisterAllPatterns(): void
    {
        if (!$this->isDisabled()) {
            return;
        }

        foreach (WP_Block_Patterns_Registry::get_instance()->get_all_registered() as $pattern) {
            unregister_block_pattern($pattern['name']);
        }
    }

    /**
     * Enqueue the CSS file that hides the "Patterns" tab in the inserter
     *
     * Loads the stylesheet only when patterns are disabled.
     *
     * @return void
     */
    public function enqueueStyles(): void
    {
        if (!$this->isDisabled()) {
            return;
        }

        $this->enqueueAssetSafely(
            self::STYLE_HANDLE,
            self::STYLE_FILE,
            [],
            'style',
        );
    }
}
