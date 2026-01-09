<?php

namespace WackFoundation\Editor;

/**
 * Block variation controller for the block editor
 *
 * This class provides functionality for controlling which block variations
 * are available in the WordPress block editor. Use the 'wack_block_enabled_variations'
 * filter to specify which variations to enable for each block type.
 *
 * The actual disabling is performed by a JavaScript file that will be enqueued
 * in the block editor. For most variations, JavaScript is used to unregister them.
 * However, some variations (like the generic URL embed block 'url' variation) cannot
 * be disabled via JavaScript, so CSS is used to hide them from the block inserter
 * when not enabled.
 *
 * **Default behavior for blocks with variations:**
 * WordPress core blocks that have variations registered default to having all
 * variations disabled (empty array) unless explicitly configured in the filter.
 * This provides consistent behavior across all variation-enabled blocks.
 *
 * **Blocks with variations (18 blocks):**
 *
 * | Block | Available Variations |
 * |-------|----------------------|
 * | core/categories | terms, categories |
 * | core/columns | one-column-full, two-columns-equal, two-columns-one-third-two-thirds, two-columns-two-thirds-one-third, three-columns-equal, three-columns-wider-center |
 * | core/cover | cover |
 * | core/embed | twitter, youtube, facebook, instagram, wordpress, soundcloud, spotify, flickr, vimeo, animoto, cloudup, collegehumor, crowdsignal, dailymotion, imgur, issuu, kickstarter, mixcloud, pocket-casts, reddit, reverbnation, scribd, smugmug, speaker-deck, tiktok, ted, tumblr, videopress, wordpress-tv, amazon-kindle, pinterest, wolfram-cloud, bluesky |
 * | core/group | group, group-row, group-stack, group-grid |
 * | core/heading | heading, stretchy-heading |
 * | core/navigation-link | post, page, category, tag, photo_book, back_number, back_number_category, cover_talent |
 * | core/paragraph | paragraph, stretchy-paragraph |
 * | core/post-date | post-date, post-date-modified |
 * | core/post-navigation-link | post-previous, post-next |
 * | core/post-terms | category, post_tag, back_number_category, cover_talent |
 * | core/post-time-to-read | time-to-read, word-count |
 * | core/query | title-date, title-excerpt, title-date-excerpt, image-date-title |
 * | core/query-title | archive-title, search-title, post-type-label |
 * | core/search | default |
 * | core/social-link | wordpress, fivehundredpx, amazon, bandcamp, behance, bluesky, chain, codepen, deviantart, discord, dribbble, dropbox, etsy, facebook, feed, flickr, foursquare, goodreads, google, github, gravatar, instagram, lastfm, linkedin, mail, mastodon, meetup, medium, patreon, pinterest, pocket, reddit, skype, snapchat, soundcloud, spotify, telegram, threads, tiktok, tumblr, twitch, twitter, vimeo, vk, whatsapp, x, yelp, youtube |
 * | core/template-part | area_header, area_footer |
 * | core/terms-query | name, name-count |
 *
 * To inspect available variations in the browser console on the block editor page:
 * ```javascript
 * wp.blocks.getBlockVariations('core/embed')
 * // Or to see all blocks with variations:
 * wp.blocks.getBlockTypes()
 *   .map(b => ({name: b.name, variations: wp.blocks.getBlockVariations(b.name) || []}))
 *   .filter(b => b.variations.length > 0)
 * ```
 *
 * Example usage:
 * <code>
 * <?php
 * // Instantiate to enable the feature
 * new BlockVariation();
 *
 * // Use filter to specify enabled variations per block type
 * add_filter('wack_block_enabled_variations', function($variations) {
 *     return [
 *         'core/embed' => ['youtube'],
 *         'core/columns' => [
 *             'two-columns-equal',
 *             'three-columns-equal',
 *         ],
 *         'core/post-date' => ['post-date', 'post-date-modified'],
 *         'core/navigation-link' => ['post', 'page', 'category'],
 *         // Add more block types and their enabled variations as needed
 *     ];
 * });
 * ?>
 * </code>
 */
class BlockVariation
{
    use Trait\AssetUrlTrait;

    /**
     * Script handle and file name
     */
    private const string SCRIPT_HANDLE = 'block-variation';
    private const string SCRIPT_FILE = 'block-variation.js';

    /**
     * Style handle and file name for hiding generic URL embed
     */
    private const string STYLE_HANDLE = 'hide-generic-url-embed';
    private const string STYLE_FILE = 'hide-generic-url-embed.css';

    /**
     * WordPress core blocks that have variations
     *
     * These blocks will default to having all variations disabled (empty array)
     * unless explicitly configured via the filter.
     *
     * This list is based on actual variations registered in the WordPress block editor.
     *
     * @var string[]
     */
    private const array BLOCKS_WITH_VARIATIONS = [
        'core/categories',
        'core/columns',
        'core/cover',
        'core/embed',
        'core/group',
        'core/heading',
        'core/navigation-link',
        'core/paragraph',
        'core/post-date',
        'core/post-navigation-link',
        'core/post-terms',
        'core/post-time-to-read',
        'core/query',
        'core/query-title',
        'core/search',
        'core/social-link',
        'core/template-part',
        'core/terms-query',
    ];

    /**
     * Get the map of enabled block variations per block type
     *
     * Applies the 'wack_block_enabled_variations' filter to allow customization.
     *
     * Special handling:
     * - For embed blocks, include 'url' in the array if you want to allow the generic
     *   URL embed block. The 'url' variation is handled differently from other variations
     *   because it cannot be disabled via JavaScript and requires CSS to hide it.
     * - All WordPress core blocks with variations default to having all variations
     *   disabled (empty array) unless explicitly configured. This provides consistent
     *   behavior and prevents unwanted variations from being enabled by default.
     *
     * @return array<string, string[]> Map of block types to their enabled variations
     *                                  e.g., ['core/embed' => ['youtube', 'vimeo', 'url']]
     */
    protected function getEnabledVariations(): array
    {
        /**
         * Filter the block variations to enable per block type
         *
         * @param array<string, string[]> $variations Map of block types to enabled variation names.
         *                                             Default empty array.
         */
        $variations = apply_filters('wack_block_enabled_variations', []);

        // Default all blocks with variations to empty array if not configured
        // This disables all variations by default for consistency
        foreach (self::BLOCKS_WITH_VARIATIONS as $blockType) {
            if (!isset($variations[$blockType])) {
                $variations[$blockType] = [];
            }
        }

        return $variations;
    }

    /**
     * Constructor
     *
     * Registers the script that disables the specified block variations
     * and optionally the CSS to hide the generic URL embed block.
     */
    public function __construct()
    {
        add_action('enqueue_block_editor_assets', [$this, 'enqueueScript']);
        add_action('enqueue_block_editor_assets', [$this, 'enqueueStyle']);
    }

    /**
     * Enqueue the JavaScript file to disable block variations
     *
     * Loads the script and passes the enabled variations as inline script data.
     * JavaScript will handle determining which variations to disable.
     *
     * @return void
     */
    public function enqueueScript(): void
    {
        if (!$this->enqueueAssetSafely(
            self::SCRIPT_HANDLE,
            self::SCRIPT_FILE,
            ['wp-blocks', 'wp-dom-ready'],
            'script',
        )) {
            return;
        }

        // Pass the enabled variations per block type to JavaScript.
        // JavaScript will get all variations for each block and disable those not in this list.
        wp_localize_script(
            self::SCRIPT_HANDLE,
            'blockVariationDisablerConfig',
            [
                'enabledVariations' => $this->getEnabledVariations(),
            ],
        );
    }

    /**
     * Enqueue the CSS file to hide the generic URL embed block
     *
     * The generic URL embed block cannot be disabled via JavaScript like other variations,
     * so we use CSS to hide it from the block inserter when 'url' is not in the enabled variations
     * for 'core/embed'. If 'url' is included in the enabled variations, this CSS will not be loaded.
     *
     * @return void
     */
    public function enqueueStyle(): void
    {
        $enabledVariations = $this->getEnabledVariations();

        // If 'url' is enabled for core/embed, don't hide the generic embed block
        if (
            isset($enabledVariations['core/embed'])
            && in_array('url', $enabledVariations['core/embed'], true)
        ) {
            return;
        }

        // If core/embed is not configured at all, also hide the generic embed
        // (assuming we want to disable all variations by default)
        $this->enqueueAssetSafely(
            self::STYLE_HANDLE,
            self::STYLE_FILE,
        );
    }
}
