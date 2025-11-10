<?php

namespace WackFoundation\PostType;

/**
 * Base abstract class for custom post type registration
 *
 * A wrapper class to handle register_post_type API arguments in a type-safe manner.
 * Child classes define the minimum requirements such as post type name and label,
 * and can configure all WordPress post type registration options.
 *
 * This class only defines properties whose recommended defaults differ from WordPress core.
 * Other WordPress arguments should be set via the $extra_args array.
 *
 * Properties with non-core defaults:
 * - $public: true (WordPress core: false)
 * - $publicly_queryable: true (WordPress core: null)
 * - $show_ui: true (WordPress core: null)
 * - $menu_position: 20 (WordPress core: null)
 * - $has_archive: true (WordPress core: false)
 * - $show_in_rest: true (WordPress core: false)
 *
 * Reference: https://developer.wordpress.org/reference/functions/register_post_type/
 *
 * Example usage:
 * <code>
 * <?php
 * class ArticlePostType extends BasePostType
 * {
 *     public static function postTypeName(): string
 *     {
 *         return 'article';
 *     }
 *
 *     public static function postTypeLabel(): string
 *     {
 *         return 'Article';
 *     }
 *
 *     public function __construct()
 *     {
 *         $this->menu_position = 21;
 *         $this->menu_icon = 'dashicons-media-document';
 *         $this->extra_args = [
 *             'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
 *             'taxonomies' => ['genre', 'post_tag'],
 *         ];
 *     }
 * }
 *
 * // Register the custom post type
 * new ArticlePostType()->register();
 * ?>
 * </code>
 */
abstract class BasePostType
{
    /** @var bool Whether a post type is intended for use publicly (WordPress core default: false) */
    protected bool $public = true;

    /** @var bool|null Whether queries can be performed on the front end (WordPress core default: null) */
    protected ?bool $publicly_queryable = true;

    /** @var bool|null Whether to generate and allow a UI for managing this post type in the admin (WordPress core default: null) */
    protected ?bool $show_ui = true;

    /** @var int|null The position in the menu order the post type should appear (WordPress core default: null) */
    protected ?int $menu_position = 20;

    /** @var string|null The URL or reference to the icon to be used for this menu */
    protected ?string $menu_icon = null;

    /** @var bool|string Whether there should be post type archives, or if a string, the archive slug to use (WordPress core default: false) */
    protected bool|string $has_archive = true;

    /** @var bool Whether this post type should appear in the REST API (WordPress core default: false) */
    protected bool $show_in_rest = true;

    /**
     * Additional arguments to pass to register_post_type
     *
     * This array can contain any WordPress post type registration arguments
     * that match WordPress core defaults or require custom values.
     *
     * Common arguments include: description, hierarchical, exclude_from_search,
     * show_in_menu, show_in_nav_menus, show_in_admin_bar, capability_type,
     * map_meta_cap, register_meta_box_cb, taxonomies, query_var,
     * can_export, delete_with_user, template, template_lock, rewrite, supports,
     * rest_base, rest_namespace, rest_controller_class, and version-specific arguments.
     *
     * @var array
     */
    protected array $extra_args = [];

    /**
     * Return the post type name
     *
     * This name is used as the post type identifier in WordPress.
     * It should be lowercase and use underscores for spaces.
     *
     * @return string Post type name (e.g., 'article', 'product')
     */
    abstract public static function postTypeName(): string;

    /**
     * Return the post type label
     *
     * This label is used as the default display name for the post type.
     *
     * @return string Post type label (e.g., 'Article', 'Product')
     */
    abstract public static function postTypeLabel(): string;

    /**
     * Constructor
     *
     * Initialize the post type properties such as menu_position, menu_icon,
     * and extra_args.
     */
    abstract public function __construct();

    /**
     * Register the custom post type
     *
     * Hooks into the 'init' action to register the post type with WordPress.
     *
     * @return void
     */
    public function register(): void
    {
        add_action('init', function () {
            register_post_type(static::postTypeName(), $this->buildArgs());
        });
    }

    /**
     * Build the arguments array to pass to register_post_type
     *
     * Combines base configuration with custom settings from child classes.
     *
     * @return array Arguments array for register_post_type()
     */
    protected function buildArgs(): array
    {
        $base = [
            'labels' => $this->createLabels(),
            'public' => $this->public,
            'publicly_queryable' => $this->publicly_queryable,
            'show_ui' => $this->show_ui,
            'menu_position' => $this->menu_position,
            'menu_icon' => $this->menu_icon,
            'has_archive' => $this->has_archive,
            'show_in_rest' => $this->show_in_rest,
        ];

        // Merge with extra_args, allowing override
        return array_merge($base, $this->extra_args);
    }

    /**
     * Generate various labels
     *
     * Automatically generates labels using locale-based templates.
     * Override this method in child classes for complete customization.
     * Use buildLabelsFromTemplates() for partial customization.
     *
     * @return array Array of labels
     */
    protected function createLabels(): array
    {
        return $this->buildLabelsFromTemplates(static::postTypeLabel());
    }

    /**
     * Build labels from templates
     *
     * Helper method to construct labels from templates based on site locale.
     * Useful when overriding createLabels() but still wanting to use templates.
     *
     * - Japanese locale (ja, ja_JP): Uses PostTypeLabelTemplates::TEMPLATES_JA
     * - Other locales: Uses PostTypeLabelTemplates::TEMPLATES_EN
     *
     * Templates use sprintf-style placeholders replaced with the post type label.
     *
     * Example:
     * <code>
     * <?php
     * protected function createLabels(): array
     * {
     *     $labels = $this->buildLabelsFromTemplates(static::postTypeLabel());
     *     $labels['add_new'] = 'Create New'; // Override specific label
     *     return $labels;
     * }
     * ?>
     * </code>
     *
     * @param string $label The post type label to use in templates
     * @return array Array of labels
     */
    protected function buildLabelsFromTemplates(string $label): array
    {
        // Select templates based on locale
        $locale = get_locale();
        $is_japanese = in_array($locale, ['ja', 'ja_JP'], true) || str_starts_with($locale, 'ja_');
        $templates = $is_japanese
            ? PostTypeLabelTemplates::TEMPLATES_JA
            : PostTypeLabelTemplates::TEMPLATES_EN;

        $labels = [];

        // For English, some labels use lowercase for natural language flow
        // e.g., "No posts found." instead of "No Posts found."
        // This matches WordPress core behavior for consistent UX
        if (! $is_japanese) {
            $label_lower = mb_strtolower($label);

            foreach ($templates as $key => $template) {
                $use_lowercase = in_array($key, [
                    'not_found',
                    'not_found_in_trash',
                    'insert_into_item',
                    'uploaded_to_this_item',
                    'filter_items_list',
                ], true);

                $labels[$key] = sprintf($template, $use_lowercase ? $label_lower : $label);
            }
        } else {
            // For Japanese, simply apply the label to all templates
            foreach ($templates as $key => $template) {
                $labels[$key] = sprintf($template, $label);
            }
        }

        return $labels;
    }
}
