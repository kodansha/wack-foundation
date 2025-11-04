<?php

namespace WackFoundation\PostType;

/**
 * Base abstract class for custom post type registration
 *
 * A wrapper class to handle register_post_type API arguments in a type-safe manner.
 * Child classes define the minimum requirements such as post type name, label, position, and icon,
 * and can adjust supports, taxonomies, and extra_args as needed.
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
 *     public function __construct(int $menu_position)
 *     {
 *         $this->menu_position = $menu_position;
 *         $this->menu_icon = 'dashicons-media-document';
 *         $this->supports = ['title', 'editor', 'thumbnail', 'excerpt'];
 *         $this->taxonomies = ['genre', 'post_tag'];
 *         $this->extra_args['exclude_from_search'] = false;
 *     }
 *
 *     protected function createLabels(): array
 *     {
 *         $label = static::postTypeLabel();
 *         return [
 *             'name' => $label,
 *             'add_new' => __('Add New', 'my-theme'),
 *             'add_new_item' => sprintf(__('Add New %s', 'my-theme'), $label),
 *             // ... other labels
 *         ];
 *     }
 * }
 *
 * // Register the custom post type
 * new ArticlePostType(21)->register();
 * ?>
 * </code>
 */
abstract class BasePostType
{
    /** @var string Admin menu icon (Dashicons, etc.) */
    protected string $menu_icon = '';

    /** @var int Menu position (around 20 for after Posts/Media) */
    protected int $menu_position = 20;

    /** @var array Supported features */
    protected array $supports = ['title', 'editor'];

    /** @var bool Whether the post type is publicly accessible */
    protected bool $public = true;

    /** @var bool Whether to show admin UI */
    protected bool $show_ui = true;

    /** @var bool Whether the post type is publicly queryable */
    protected bool $publicly_queryable = true;

    /** @var bool Whether to show in REST API */
    protected bool $show_in_rest = true;

    /** @var bool Whether to use query var */
    protected bool $query_var = true;

    /** @var bool|string Archive slug */
    protected bool|string $has_archive = true;

    /** @var bool|array Rewrite rules */
    protected bool|array $rewrite = true;

    /** @var string Base capability type (post, page, etc.) */
    protected string $capability_type = 'post';

    /** @var array Associated taxonomies */
    protected array $taxonomies = [];

    /** @var array Additional arguments to pass to register_post_type */
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
     * Return the label displayed in the menu
     *
     * By default, returns the same value as postTypeLabel().
     * Override this method only if you need to use a different label
     * (e.g., when the label is too long for the dashboard sidebar menu).
     *
     * @return string Menu label for the post type
     */
    public static function postTypeMenuLabel(): string
    {
        return static::postTypeLabel();
    }

    /**
     * Constructor
     *
     * Initialize the post type properties such as menu_position, menu_icon,
     * supports, taxonomies, and extra_args.
     *
     * @param int $menu_position The position in the menu order this post type should appear
     */
    abstract public function __construct(int $menu_position);

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
     * Child classes can adjust supports, taxonomies, and extra_args in the constructor.
     *
     * @return array Arguments array for register_post_type()
     */
    protected function buildArgs(): array
    {
        $base = [
            'labels' => $this->createLabels(),
            'menu_icon' => $this->menu_icon,
            'menu_position' => $this->menu_position,
            'supports' => $this->supports,
            'public' => $this->public,
            'show_ui' => $this->show_ui,
            'publicly_queryable' => $this->publicly_queryable,
            'show_in_rest' => $this->show_in_rest,
            'has_archive' => $this->has_archive,
            'rewrite' => $this->rewrite,
            'capability_type' => $this->capability_type,
            'taxonomies' => $this->taxonomies,
        ];

        // Merge/override values specified in extra_args by the user
        return array_merge($base, $this->extra_args);
    }

    /**
     * Generate various labels
     *
     * Override this method in child classes to use the appropriate text domain.
     * The text domain must be specified as a string literal so that translation tools
     * can recognize the strings.
     *
     * Example:
     * <code>
     * <?php
     * protected function createLabels(): array
     * {
     *     $label = static::postTypeLabel();
     *     return [
     *         'name' => $label,
     *         'menu_name' => static::postTypeMenuLabel(),
     *         'add_new' => __('Add New', 'your-textdomain'),
     *         'add_new_item' => sprintf(__('Add New %s', 'your-textdomain'), $label),
     *         // ... other labels
     *     ];
     * }
     * ?>
     * </code>
     *
     * @return array Array of labels
     */
    protected function createLabels(): array
    {
        $post_type_label = static::postTypeLabel();

        // Default implementation (no translation)
        // It is recommended to override in child classes with a specified text domain
        return [
            'name' => $post_type_label,
            'menu_name' => static::postTypeMenuLabel(),
        ];
    }
}
