<?php

namespace WackFoundation\Taxonomy;

/**
 * Base abstract class for custom taxonomy registration
 *
 * A wrapper class to handle register_taxonomy API arguments in a type-safe manner.
 * Child classes define the minimum requirements such as taxonomy key and label,
 * and can adjust hierarchical, visibility, and extra_args as needed.
 *
 * Reference: https://developer.wordpress.org/reference/functions/register_taxonomy/
 *
 * Example usage:
 * <code>
 * <?php
 * class GenreTaxonomy extends BaseTaxonomy
 * {
 *     public static function taxonomyKey(): string
 *     {
 *         return 'genre';
 *     }
 *
 *     public static function taxonomyLabel(): string
 *     {
 *         return 'Genre';
 *     }
 *
 *     public function __construct()
 *     {
 *         $this->hierarchical = true;
 *         $this->rewrite = ['slug' => 'genres'];
 *         $this->extra_args['show_in_nav_menus'] = true;
 *     }
 *
 *     protected function createLabels(): array
 *     {
 *         $label = static::taxonomyLabel();
 *         return [
 *             'name' => $label,
 *             'singular_name' => $label,
 *             'add_new_item' => sprintf(__('Add New %s', 'my-theme'), $label),
 *             // ... other labels
 *         ];
 *     }
 * }
 *
 * // Register the custom taxonomy for specific post types
 * new GenreTaxonomy()->register(['post', 'article']);
 * ?>
 * </code>
 */
abstract class BaseTaxonomy
{
    /** @var bool Whether the taxonomy is hierarchical (like categories) or flat (like tags) */
    protected bool $hierarchical = false;

    /** @var bool Whether the taxonomy is publicly accessible */
    protected bool $public = true;

    /** @var bool Whether to show in REST API */
    protected bool $show_in_rest = true;

    /** @var bool|array Rewrite rules */
    protected bool|array $rewrite = true;

    /** @var array Additional arguments to pass to register_taxonomy */
    protected array $extra_args = [];

    /**
     * Return the taxonomy key
     *
     * This key is used as the taxonomy identifier in WordPress.
     * It should be lowercase and use underscores for spaces.
     *
     * @return string Taxonomy key (e.g., 'genre', 'product_category')
     */
    abstract public static function taxonomyKey(): string;

    /**
     * Return the taxonomy label
     *
     * This label is used as the default display name for the taxonomy.
     *
     * @return string Taxonomy label (e.g., 'Genre', 'Product Category')
     */
    abstract public static function taxonomyLabel(): string;

    /**
     * Constructor
     *
     * Initialize the taxonomy properties such as hierarchical, rewrite,
     * and extra_args.
     */
    abstract public function __construct();

    /**
     * Register the custom taxonomy
     *
     * Hooks into the 'init' action to register the taxonomy with WordPress
     * for the specified object types.
     *
     * @param array $associated_to Array of object types to register the taxonomy for (e.g., ['post', 'article'])
     * @return void
     */
    public function register(array $associated_to): void
    {
        add_action('init', function () use ($associated_to) {
            register_taxonomy(static::taxonomyKey(), $associated_to, $this->buildArgs());
        });
    }

    /**
     * Build the arguments array to pass to register_taxonomy
     *
     * Combines base configuration with custom settings from child classes.
     * Child classes can adjust hierarchical, rewrite, and extra_args in the constructor.
     *
     * @return array Arguments array for register_taxonomy()
     */
    protected function buildArgs(): array
    {
        $base = [
            'labels' => $this->createLabels(),
            'hierarchical' => $this->hierarchical,
            'public' => $this->public,
            'show_in_rest' => $this->show_in_rest,
            'rewrite' => $this->rewrite,
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
     *     $label = static::taxonomyLabel();
     *     return [
     *         'name' => $label,
     *         'singular_name' => $label,
     *         'all_items' => sprintf(__('All %s', 'your-textdomain'), $label),
     *         'edit_item' => sprintf(__('Edit %s', 'your-textdomain'), $label),
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
        $label = static::taxonomyLabel();

        // Default implementation (no translation)
        // It is recommended to override in child classes with a specified text domain
        return [
            'name' => $label,
            'singular_name' => $label,
            'menu_name' => $label,
        ];
    }
}
