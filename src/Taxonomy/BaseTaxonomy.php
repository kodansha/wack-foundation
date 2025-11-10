<?php

namespace WackFoundation\Taxonomy;

/**
 * Base abstract class for custom taxonomy registration
 *
 * A wrapper class to handle register_taxonomy API arguments in a type-safe manner.
 * Child classes define the minimum requirements such as taxonomy key and label,
 * and can configure all WordPress taxonomy registration options.
 *
 * This class only defines properties whose recommended defaults differ from WordPress core.
 * Other WordPress arguments should be set via the $extra_args array.
 *
 * Properties with non-core defaults:
 * - $show_in_rest: true (WordPress core: false)
 * - $hierarchical: true (WordPress core: false)
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
 *         $this->extra_args = [
 *             'rewrite' => ['slug' => 'genres'],
 *             'show_in_nav_menus' => true,
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
    /** @var bool Whether to show in REST API (WordPress core default: false) */
    protected bool $show_in_rest = true;

    /** @var bool Whether the taxonomy is hierarchical (WordPress core default: false) */
    protected bool $hierarchical = true;

    /**
     * Additional arguments to pass to register_taxonomy
     *
     * This array can contain any WordPress taxonomy registration arguments
     * that match WordPress core defaults or require custom values.
     *
     * Common arguments include: description, public, publicly_queryable,
     * show_ui, show_in_menu, show_in_nav_menus, show_in_quick_edit, show_admin_column,
     * meta_box_cb, meta_box_sanitize_cb, capabilities, rewrite, query_var, update_count_callback,
     * show_tagcloud, show_in_graphql, and version-specific arguments.
     *
     * @var array
     */
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
     * Initialize the taxonomy properties.
     * Override $hierarchical to false for non-hierarchical (tag-style) taxonomies.
     * Set $extra_args for additional WordPress taxonomy registration options.
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
     *
     * @return array Arguments array for register_taxonomy()
     */
    protected function buildArgs(): array
    {
        $base = [
            'labels' => $this->createLabels(),
            'hierarchical' => $this->hierarchical,
            'show_in_rest' => $this->show_in_rest,
        ];

        // Merge with extra_args, allowing override
        return array_merge($base, $this->extra_args);
    }

    /**
     * Generate various labels
     *
     * Automatically generates labels using locale and hierarchy-based templates.
     * Override this method in child classes for complete customization.
     * Use buildLabelsFromTemplates() for partial customization.
     *
     * @return array Array of labels
     */
    protected function createLabels(): array
    {
        return $this->buildLabelsFromTemplates(static::taxonomyLabel());
    }

    /**
     * Build labels from templates
     *
     * Helper method to construct labels from templates based on site locale and taxonomy type.
     * Useful when overriding createLabels() but still wanting to use templates.
     *
     * - Hierarchical taxonomies use category-style templates
     * - Non-hierarchical taxonomies use tag-style templates
     * - Japanese locale (ja, ja_JP): Uses CategoryLabelTemplates/TagLabelTemplates::TEMPLATES_JA
     * - Other locales: Uses CategoryLabelTemplates/TagLabelTemplates::TEMPLATES_EN
     *
     * Templates use sprintf-style placeholders replaced with the taxonomy label.
     *
     * Example:
     * <code>
     * <?php
     * protected function createLabels(): array
     * {
     *     $labels = $this->buildLabelsFromTemplates(static::taxonomyLabel());
     *     $labels['add_new_item'] = 'Create New'; // Override specific label
     *     return $labels;
     * }
     * ?>
     * </code>
     *
     * @param string $label The taxonomy label to use in templates
     * @return array Array of labels
     */
    protected function buildLabelsFromTemplates(string $label): array
    {
        // Select templates based on locale and hierarchy
        $locale = get_locale();
        $is_japanese = in_array($locale, ['ja', 'ja_JP'], true) || str_starts_with($locale, 'ja_');

        if ($this->hierarchical) {
            $templates = $is_japanese
                ? CategoryLabelTemplates::TEMPLATES_JA
                : CategoryLabelTemplates::TEMPLATES_EN;
        } else {
            $templates = $is_japanese
                ? TagLabelTemplates::TEMPLATES_JA
                : TagLabelTemplates::TEMPLATES_EN;
        }

        $labels = [];

        // For English, some labels use lowercase for natural language flow
        // e.g., "No categories found." instead of "No Categories found."
        // This matches WordPress core behavior for consistent UX
        if (! $is_japanese) {
            $label_lower = mb_strtolower($label);

            foreach ($templates as $key => $template) {
                if ($template === null) {
                    continue;
                }

                $use_lowercase = in_array($key, [
                    'not_found',
                    'separate_items_with_commas',
                    'add_or_remove_items',
                    'choose_from_most_used',
                ], true);

                $labels[$key] = sprintf($template, $use_lowercase ? $label_lower : $label);
            }
        } else {
            // For Japanese, simply apply the label to all templates
            foreach ($templates as $key => $template) {
                if ($template === null) {
                    continue;
                }
                $labels[$key] = sprintf($template, $label);
            }
        }

        return $labels;
    }
}
