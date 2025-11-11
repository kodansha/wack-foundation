<?php

namespace WackFoundation\Taxonomy;

/**
 * Category-style (hierarchical) taxonomy label templates
 *
 * Provides default label templates for hierarchical custom taxonomies in multiple languages.
 * These templates match WordPress core's 'category' taxonomy labels.
 */
class CategoryLabelTemplates
{
    /**
     * Hierarchical taxonomy label templates (English)
     *
     * Based on WordPress core's default labels for the 'category' taxonomy.
     * Each template uses sprintf-style placeholders where %s is replaced with the taxonomy label.
     */
    public const array TEMPLATES_EN = [
        'name' => '%s',
        'singular_name' => '%s',
        'menu_name' => '%s',
        'all_items' => 'All %s',
        'edit_item' => 'Edit %s',
        'view_item' => 'View %s',
        'update_item' => 'Update %s',
        'add_new_item' => 'Add %s',
        'new_item_name' => 'New %s Name',
        'parent_item' => 'Parent %s',
        'parent_item_colon' => 'Parent %s:',
        'search_items' => 'Search %s',
        'popular_items' => null,
        'separate_items_with_commas' => null,
        'add_or_remove_items' => null,
        'choose_from_most_used' => null,
        'not_found' => 'No %s found.',
        'no_terms' => 'No %s',
        'filter_by_item' => 'Filter by %s',
        'items_list_navigation' => '%s list navigation',
        'items_list' => '%s list',
        'most_used' => 'Most Used',
        'back_to_items' => '&larr; Go to %s',
        'item_link' => '%s Link',
        'item_link_description' => 'A link to a %s.',
    ];

    /**
     * Hierarchical taxonomy label templates (Japanese)
     *
     * Based on WordPress core's Japanese translation for the 'category' taxonomy.
     * Each template uses sprintf-style placeholders where %s is replaced with the taxonomy label.
     */
    public const array TEMPLATES_JA = [
        'name' => '%s',
        'singular_name' => '%s',
        'menu_name' => '%s',
        'all_items' => '%s一覧',
        'edit_item' => '%sを編集',
        'view_item' => '%sを表示',
        'update_item' => '%sを更新',
        'add_new_item' => '%sを追加',
        'new_item_name' => '新規%s名',
        'parent_item' => '親%s',
        'parent_item_colon' => '親%s:',
        'search_items' => '%sを検索',
        'popular_items' => null,
        'separate_items_with_commas' => null,
        'add_or_remove_items' => null,
        'choose_from_most_used' => null,
        'not_found' => '%sが見つかりませんでした。',
        'no_terms' => '%sなし',
        'filter_by_item' => '%sで絞り込む',
        'items_list_navigation' => '%sリストナビゲーション',
        'items_list' => '%sリスト',
        'most_used' => 'よく使うもの',
        'back_to_items' => '&larr; %sへ移動',
        'item_link' => '%sリンク',
        'item_link_description' => '%sへのリンク。',
    ];
}
