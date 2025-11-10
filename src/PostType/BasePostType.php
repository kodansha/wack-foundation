<?php

namespace WackFoundation\PostType;

/**
 * Base abstract class for custom post type registration
 *
 * A wrapper class to handle register_post_type API arguments in a type-safe manner.
 * Child classes define the minimum requirements such as post type name and label,
 * and can configure all WordPress post type registration options.
 *
 * Most properties use common default values for typical use cases, which differ from
 * WordPress core defaults. Properties with non-core defaults are documented accordingly.
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
 *     public function __construct(int $menu_position = 21)
 *     {
 *         $this->menu_position = $menu_position;
 *         $this->menu_icon = 'dashicons-media-document';
 *         $this->supports = ['title', 'editor', 'thumbnail', 'excerpt'];
 *         $this->taxonomies = ['genre', 'post_tag'];
 *         $this->has_archive = true;
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
    /** @var string A short descriptive summary of what the post type is */
    protected string $description = '';

    /** @var bool Whether a post type is intended for use publicly (WordPress core default: false, but true is recommended for most use cases) */
    protected bool $public = true;

    /** @var bool Whether the post type is hierarchical (e.g. page) */
    protected bool $hierarchical = false;

    /** @var bool|null Whether to exclude posts with this post type from front end search results */
    protected ?bool $exclude_from_search = null;

    /** @var bool|null Whether queries can be performed on the front end (WordPress core default: null, but true is recommended for most use cases) */
    protected ?bool $publicly_queryable = true;

    /** @var bool|null Whether this post type is embeddable (WordPress 6.8.0+) */
    protected ?bool $embeddable = null;

    /** @var bool|null Whether to generate and allow a UI for managing this post type in the admin (WordPress core default: null, but true is recommended for most use cases) */
    protected ?bool $show_ui = true;

    /** @var bool|string|null Where to show the post type in the admin menu */
    protected bool|string|null $show_in_menu = null;

    /** @var bool|null Makes this post type available for selection in navigation menus */
    protected ?bool $show_in_nav_menus = null;

    /** @var bool|null Makes this post type available via the admin bar */
    protected ?bool $show_in_admin_bar = null;

    /** @var int|null The position in the menu order the post type should appear (WordPress core default: null, but 20 is recommended for most use cases) */
    protected ?int $menu_position = 20;

    /** @var string|null The URL or reference to the icon to be used for this menu */
    protected ?string $menu_icon = null;

    /** @var string|array The string to use to build the read, edit, and delete capabilities */
    protected string|array $capability_type = 'post';

    /** @var bool Whether to use the internal default meta capability handling */
    protected bool $map_meta_cap = false;

    /** @var array|null Provide a callback function that sets up the meta boxes for the edit form */
    protected ?array $register_meta_box_cb = null;

    /** @var array An array of taxonomy identifiers that will be registered for the post type */
    protected array $taxonomies = [];

    /** @var bool|string Whether there should be post type archives, or if a string, the archive slug to use */
    protected bool|string $has_archive = false;

    /** @var string|bool Sets the query_var key for this post type */
    protected string|bool $query_var = true;

    /** @var bool Whether to allow this post type to be exported */
    protected bool $can_export = true;

    /** @var bool|null Whether to delete posts of this type when deleting a user */
    protected ?bool $delete_with_user = null;

    /** @var array Array of blocks to use as the default initial state for an editor session */
    protected array $template = [];

    /** @var string|false Whether the block template should be locked if $template is set */
    protected string|false $template_lock = false;

    /** @var array|bool Triggers the handling of rewrites for this post type */
    protected array|bool $rewrite = true;

    /** @var array|bool The features supported by the post type */
    protected array|bool $supports = ['title', 'editor'];

    /** @var bool Whether this post type should appear in the REST API (WordPress core default: false, but true is recommended for most use cases) */
    protected bool $show_in_rest = true;

    /** @var string|bool|null The base path for this post type's REST API endpoints */
    protected string|bool|null $rest_base = null;

    /** @var string|bool|null The namespace for this post type's REST API endpoints */
    protected string|bool|null $rest_namespace = null;

    /** @var string|bool|null The controller for this post type's REST API endpoints */
    protected string|bool|null $rest_controller_class = null;

    /** @var string|bool|null The controller for this post type's revisions REST API endpoints (WordPress 6.4.0+) */
    protected string|bool|null $revisions_rest_controller_class = null;

    /** @var string|bool|null The controller for this post type's autosave REST API endpoints (WordPress 6.4.0+) */
    protected string|bool|null $autosave_rest_controller_class = null;

    /** @var bool|null A flag to register the post type REST API controller after its associated autosave / revisions controllers (WordPress 6.4.0+) */
    protected ?bool $late_route_registration = null;

    /**
     * Additional arguments to pass to register_post_type
     *
     * Use this only for:
     * - Future WordPress arguments not yet supported by this class
     * - Plugin-specific custom arguments
     * - Overriding computed values in special cases
     *
     * For standard WordPress arguments, use the dedicated properties instead.
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
     * @param int $menu_position The position in the menu order the post type should appear
     */
    abstract public function __construct(int $menu_position = 20);

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
     * All WordPress post type registration arguments are supported.
     *
     * @return array Arguments array for register_post_type()
     */
    protected function buildArgs(): array
    {
        $base = [
            'labels' => $this->createLabels(),
            'description' => $this->description,
            'public' => $this->public,
            'hierarchical' => $this->hierarchical,
            'exclude_from_search' => $this->exclude_from_search,
            'publicly_queryable' => $this->publicly_queryable,
            'show_ui' => $this->show_ui,
            'show_in_menu' => $this->show_in_menu,
            'show_in_nav_menus' => $this->show_in_nav_menus,
            'show_in_admin_bar' => $this->show_in_admin_bar,
            'menu_position' => $this->menu_position,
            'menu_icon' => $this->menu_icon,
            'capability_type' => $this->capability_type,
            'map_meta_cap' => $this->map_meta_cap,
            'register_meta_box_cb' => $this->register_meta_box_cb,
            'taxonomies' => $this->taxonomies,
            'has_archive' => $this->has_archive,
            'query_var' => $this->query_var,
            'can_export' => $this->can_export,
            'delete_with_user' => $this->delete_with_user,
            'template' => $this->template,
            'template_lock' => $this->template_lock,
            'rewrite' => $this->rewrite,
            'supports' => $this->supports,
            'show_in_rest' => $this->show_in_rest,
            'rest_base' => $this->rest_base,
            'rest_namespace' => $this->rest_namespace,
            'rest_controller_class' => $this->rest_controller_class,
        ];

        // Add WordPress 6.4.0+ properties if available
        if (property_exists($this, 'revisions_rest_controller_class')) {
            $base['revisions_rest_controller_class'] = $this->revisions_rest_controller_class;
        }
        if (property_exists($this, 'autosave_rest_controller_class')) {
            $base['autosave_rest_controller_class'] = $this->autosave_rest_controller_class;
        }
        if (property_exists($this, 'late_route_registration')) {
            $base['late_route_registration'] = $this->late_route_registration;
        }

        // Add WordPress 6.8.0+ properties if available
        if (property_exists($this, 'embeddable')) {
            $base['embeddable'] = $this->embeddable;
        }

        // Merge/override values specified in extra_args by the user
        return array_merge($base, $this->extra_args);
    }

    /**
     * Generate various labels
     *
     * Automatically selects label templates based on site locale:
     * - Japanese locale (ja, ja_JP): Uses PostTypeLabelTemplates::TEMPLATES_JA
     * - Other locales: Uses PostTypeLabelTemplates::TEMPLATES_EN
     *
     * Templates can be overridden via 'wack_post_type_label_templates' filter.
     * Templates use sprintf-style placeholders that are replaced with the post type label.
     *
     * @return array Array of labels
     */
    protected function createLabels(): array
    {
        $label = static::postTypeLabel();

        // Select templates based on locale
        $locale = get_locale();
        $is_japanese = in_array($locale, ['ja', 'ja_JP'], true) || str_starts_with($locale, 'ja_');
        $templates = $is_japanese
            ? PostTypeLabelTemplates::TEMPLATES_JA
            : PostTypeLabelTemplates::TEMPLATES_EN;

        // Allow customization via filter
        $templates = apply_filters('wack_post_type_label_templates', $templates, static::postTypeName());

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
