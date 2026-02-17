<?php

namespace WackFoundation\Editor;

use WP_Block_Editor_Context;

/**
 * Block type controller for the block editor
 *
 * This class provides functionality for controlling which Gutenberg blocks
 * are available in the WordPress block editor. It defines a set of default
 * core blocks and uses the WordPress core 'allowed_block_types_all' filter internally and
 * a custom 'wack_block_type_enabled_types' filter to allow customization
 * of the allowed blocks for specific post types or contexts.
 *
 * To find the list of all the core blocks that can be filtered, see:
 * https://developer.wordpress.org/block-editor/reference-guides/core-blocks/
 *
 * Primary inspection (PHP):
 * You can retrieve all registered block types server-side (e.g. from a CLI command,
 * mu-plugin diagnostics, or within a debugging endpoint) using:
 *
 * ```php
 * $registry = \WP_Block_Type_Registry::get_instance();
 * $all_blocks = $registry->get_all_registered(); // array of WP_Block_Type objects keyed by name
 * foreach ($all_blocks as $name => $block) {
 *     // $name example: 'core/paragraph'
 *     // $block->title, $block->category, $block->supports, etc.
 * }
 * ```
 *
 * Secondary (browser console): `wp.blocks.getBlockTypes()`
 * Use the browser console only for quick ad‑hoc inspection; prefer PHP for automation,
 * reporting and CI assertions.
 *
 * Example usage:
 * <code>
 * <?php
 * // Instantiate to enable the feature
 * new BlockType();
 *
 * // Use filter to customize allowed block types
 * // The default block types are passed as the first parameter
 * add_filter('wack_block_type_enabled_types', fn($default_blocks) => array_merge(
 *     $default_blocks,
 *     [
 *         'core/table',
 *         'core/video',
 *         'core/gallery',
 *         'core/embed', // Enable embed block
 *     ]
 * ));
 *
 * // Note: Some blocks have variations (e.g., core/embed, core/paragraph)
 * // All block variations are disabled by default. Use wack_block_enabled_variations
 * // filter to enable specific variations. See BlockVariation class for details.
 * add_filter('wack_block_enabled_variations', fn() => [
 *     'core/embed' => ['youtube', 'vimeo'], // Enable specific embed providers
 *     'core/paragraph' => ['stretchy-paragraph'], // Enable stretchy paragraph
 * ]);
 * ?>
 * </code>
 */
class BlockType
{
    /**
     * Default allowed block types
     *
     * A minimal set of essential core blocks that are allowed by default.
     * These include basic content blocks like paragraphs, headings, images,
     * and lists.
     *
     * @var string[]
     */
    public const array DEFAULT_ALLOWED_BLOCK_TYPES = [
        'core/heading',
        'core/image',
        'core/list',
        'core/list-item',
        'core/paragraph',
    ];

    /**
     * Default heading level options for core/heading block
     *
     * @var int[]
     */
    public const array DEFAULT_HEADING_LEVEL_OPTIONS = [1, 2, 3, 4, 5, 6];

    /**
     * Get the list of allowed block types
     *
     * Applies the 'wack_block_type_enabled_types' filter to allow customization.
     * The filter receives DEFAULT_ALLOWED_BLOCK_TYPES as the first parameter.
     *
     * @return string[] Array of block type names (e.g., 'core/paragraph', 'custom/block')
     */
    protected function getAllowedBlockTypes(): array
    {
        /**
         * Filter the enabled block types
         *
         * @param string[] $block_types Array of default block type names.
         */
        return apply_filters('wack_block_type_enabled_types', self::DEFAULT_ALLOWED_BLOCK_TYPES);
    }

    /**
     * Get heading level options for core/heading block
     *
     * Applies the 'wack_block_type_heading_level_options' filter to allow customization.
     *
     * @return int[] Array of heading levels (e.g., [2, 3])
     */
    protected function getHeadingLevelOptions(): array
    {
        /**
         * Filter the heading level options for core/heading block
         *
         * @param int[] $level_options Array of heading levels.
         */
        return apply_filters('wack_block_type_heading_level_options', self::DEFAULT_HEADING_LEVEL_OPTIONS);
    }

    /**
     * Constructor
     *
     * Registers the filter hook that restricts available blocks in the editor.
     */
    public function __construct()
    {
        add_filter('allowed_block_types_all', [$this, 'filterAllowedBlockTypes'], 10, 2);
        add_filter('register_block_type_args', [$this, 'filterBlockTypeArgs'], 10, 2);
    }

    /**
     * Filter callback for allowed block types
     *
     * This method is called by WordPress when determining which blocks
     * should be available in the block editor.
     *
     * @param bool|string[] $allowed_block_types Array of block type slugs, or boolean to enable/disable all. Default true (all registered block types supported)
     * @param WP_Block_Editor_Context $block_editor_context The current block editor context.
     * @return string[] Array of allowed block type names
     */
    public function filterAllowedBlockTypes(bool|array $allowed_block_types, WP_Block_Editor_Context $block_editor_context): array
    {
        return $this->getAllowedBlockTypes();
    }

    /**
     * Filter callback for block type registration args
     *
     * Sets level options for core/heading block via custom filter.
     *
     * @param array<string, mixed> $args Block type args.
     * @param string $block_type Block type name.
     * @return array<string, mixed> Filtered block type args.
     */
    public function filterBlockTypeArgs(array $args, string $block_type): array
    {
        if ($block_type !== 'core/heading') {
            return $args;
        }

        $args['attributes']['levelOptions'] = array_merge(
            $args['attributes']['levelOptions'] ?? [],
            ['default' => $this->getHeadingLevelOptions()],
        );

        return $args;
    }
}
