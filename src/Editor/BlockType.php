<?php

namespace WackFoundation\Editor;

use WP_Block_Editor_Context;

/**
 * Block type controller for the block editor
 *
 * This class provides functionality for controlling which Gutenberg blocks
 * are available in the WordPress block editor. It defines a set of default
 * core blocks and uses the 'allowed_block_types' filter to allow customization
 * of the allowed blocks for specific post types or contexts.
 *
 * To find the list of all the core blocks that can be filtered, see:
 * https://developer.wordpress.org/block-editor/reference-guides/core-blocks/
 *
 * You can get the list of actually registered blocks by running
 * `wp.blocks.getBlockTypes()` in the browser console on the block editor page.
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
 *     ]
 * ));
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
     * Constructor
     *
     * Registers the filter hook that restricts available blocks in the editor.
     */
    public function __construct()
    {
        add_filter('allowed_block_types_all', [$this, 'filterAllowedBlockTypes'], 10, 2);
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
}
