<?php

namespace WackFoundation\Editor;

use WP_Block_Type_Registry;

/**
 * Block supports controller for the block editor
 *
 * This class provides functionality for controlling which block supports
 * are available in the WordPress block editor. Block supports are the features
 * that blocks expose in the editor UI, such as alignment, borders, colors,
 * and typography controls.
 *
 * Use the 'wack_block_supports_enabled_supports' filter to specify which
 * top-level support keys to keep enabled for each block you want to restrict.
 * Any non-false support key omitted from the enabled list will be set to false
 * via a JS blocks.registerBlockType filter.
 *
 * Design note — whitelist scope:
 * Unlike BlockStyle (which disables non-listed styles on ALL registered blocks),
 * this class only affects blocks that are explicitly listed in the filter.
 * Blocks not mentioned in the filter are left completely unchanged.
 * This is intentional: disabling supports on unrelated blocks would remove
 * core editor functionality unexpectedly.
 *
 * Design note — top-level keys only:
 * Only top-level support keys are controlled (e.g., 'color', 'typography').
 * Support keys whose values are objects (e.g., color: { gradient: true })
 * will be disabled entirely when their top-level key is omitted from the
 * enabled list. For granular control of nested support properties (e.g.,
 * enabling color but disabling gradients), use an additional
 * blocks.registerBlockType filter directly in JavaScript.
 *
 * Primary inspection (PHP):
 * To retrieve the non-false support keys for a specific block:
 *
 * ```php
 * $block = \WP_Block_Type_Registry::get_instance()->get_registered('core/button');
 * $non_false = array_keys(array_filter(
 *     $block->supports ?? [],
 *     fn($v) => $v !== false && $v !== null,
 * ));
 * // e.g., ['anchor', 'splitting', 'color', 'typography', ...]
 * ```
 *
 * Secondary (browser console quick check):
 * `wp.blocks.getBlockTypes().map(b => ({ block: b.name, supports: Object.keys(b.supports ?? {}).filter(k => b.supports[k] !== false) }))`
 * Prefer the PHP form for automation and test assertions.
 *
 * Example usage:
 * <code>
 * <?php
 * // Instantiate to enable the feature
 * new BlockSupports();
 *
 * // Use filter to specify enabled supports per block.
 * // Only blocks listed here are affected; all other blocks remain unchanged.
 * add_filter('wack_block_supports_enabled_supports', fn() => [
 *     'core/buttons' => [
 *         'anchor',
 *         '__experimentalExposeControlsToChildren',
 *         'color',
 *         'spacing',
 *         'typography',
 *         '__experimentalBorder',
 *         'layout',
 *         'interactivity',
 *         'contentRole',
 *         // 'align' is intentionally omitted to disable wide/full alignment
 *     ],
 *     'core/button' => [
 *         'anchor',
 *         'splitting',
 *         'color',
 *         'typography',
 *         'shadow',
 *         'spacing',
 *         'interactivity',
 *         // '__experimentalBorder' is intentionally omitted to disable the border panel
 *     ],
 * ]);
 * ?>
 * </code>
 */
class BlockSupports
{
    use Trait\AssetUrlTrait;

    /**
     * Script handle and file name
     */
    private const string SCRIPT_HANDLE = 'block-supports';
    private const string SCRIPT_FILE = 'block-supports.js';

    /**
     * Get the map of enabled support keys per block
     *
     * Applies the 'wack_block_supports_enabled_supports' filter to allow
     * child theme customization. Only blocks listed in the returned array
     * will have their supports modified.
     *
     * Format: Associative array mapping block names to arrays of support key strings.
     * Example: ['core/buttons' => ['anchor', 'color', ...], 'core/button' => [...]]
     *
     * @return array<string, string[]> Map of block name to enabled support keys
     */
    protected function getEnabledSupports(): array
    {
        /**
         * Filter the enabled block supports per block
         *
         * Return an associative array mapping block names to arrays of top-level
         * support keys that should remain enabled. Support keys not listed for a
         * given block will be set to false. Blocks not listed are left unchanged.
         *
         * @param array<string, string[]> $supports Map of block name to enabled support keys. Default empty array.
         */
        return apply_filters('wack_block_supports_enabled_supports', []);
    }

    /**
     * Constructor
     *
     * Registers the script that disables the specified block supports.
     */
    public function __construct()
    {
        add_action('enqueue_block_editor_assets', [$this, 'enqueueScript']);
    }

    /**
     * Calculate which supports should be disabled
     *
     * Computes the difference between all non-false support keys (from the block
     * type registry) and the enabled keys (from the filter), for each block
     * that is explicitly listed in the enabled filter.
     *
     * @return array<string, string[]> Map of block name to support keys to set to false
     */
    private function getDisabledSupports(): array
    {
        $enabled = $this->getEnabledSupports();
        if (empty($enabled)) {
            return [];
        }

        $all_non_false = $this->getAllNonFalseBlockSupports();

        $disabled = [];
        foreach ($enabled as $block_name => $enabled_keys) {
            $all_keys = $all_non_false[$block_name] ?? [];
            $disabled_keys = array_values(array_diff($all_keys, $enabled_keys));

            if (!empty($disabled_keys)) {
                $disabled[$block_name] = $disabled_keys;
            }
        }

        return $disabled;
    }

    /**
     * Get all non-false top-level support keys for every registered block
     *
     * Reads block supports from the WP_Block_Type_Registry and collects
     * top-level keys whose value is not false or null. Support keys already
     * set to false in block.json are excluded because they are already disabled
     * and do not need to be targeted.
     *
     * @return array<string, string[]> Map of block name to non-false support keys
     */
    private function getAllNonFalseBlockSupports(): array
    {
        $blocks = WP_Block_Type_Registry::get_instance()->get_all_registered();

        $result = [];
        foreach ($blocks as $block_name => $block) {
            if (empty($block->supports)) {
                continue;
            }

            $non_false_keys = array_values(array_keys(array_filter(
                $block->supports,
                fn($value) => $value !== false && $value !== null,
            )));

            if (!empty($non_false_keys)) {
                $result[$block_name] = $non_false_keys;
            }
        }

        return $result;
    }

    /**
     * Enqueue the JavaScript file to disable block supports
     *
     * Loads the script and passes the disabled supports as inline script data.
     *
     * @return void
     */
    public function enqueueScript(): void
    {
        if (!$this->enqueueAssetSafely(
            self::SCRIPT_HANDLE,
            self::SCRIPT_FILE,
            ['wp-blocks', 'wp-hooks'],
            'script',
        )) {
            return;
        }

        wp_localize_script(
            self::SCRIPT_HANDLE,
            'blockSupportsConfig',
            [
                'disabledSupports' => $this->getDisabledSupports(),
            ],
        );
    }
}
