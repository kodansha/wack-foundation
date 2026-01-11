<?php

namespace WackFoundation\Taxonomy;

/**
 * Tag-style (non-hierarchical) taxonomy label templates
 *
 * Provides default label templates for non-hierarchical custom taxonomies in multiple languages.
 * These templates match WordPress core's 'post_tag' taxonomy labels.
 */
class TagLabelTemplates
{
    /**
     * Non-hierarchical taxonomy label templates (English)
     *
     * Based on WordPress core's default labels for the 'post_tag' taxonomy.
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
        'parent_item' => null,
        'parent_item_colon' => null,
        'search_items' => 'Search %s',
        'popular_items' => 'Popular %s',
        'separate_items_with_commas' => 'Separate %s with commas',
        'add_or_remove_items' => 'Add or remove %s',
        'choose_from_most_used' => 'Choose from the most used %s',
        'not_found' => 'No %s found.',
        'no_terms' => 'No %s',
        'filter_by_item' => null,
        'items_list_navigation' => '%s list navigation',
        'items_list' => '%s list',
        'most_used' => 'Most Used',
        'back_to_items' => '&larr; Go to %s',
        'item_link' => '%s Link',
        'item_link_description' => 'A link to a %s.',
    ];

    /**
     * Non-hierarchical taxonomy label templates (Japanese)
     *
     * Based on WordPress core's Japanese translation for the 'post_tag' taxonomy.
     * Each template uses sprintf-style placeholders where %s is replaced with the taxonomy label.
     */
    public const array TEMPLATES_JA = [
        'name' => '%s',
        'singular_name' => '%s',
        'menu_name' => '%s',
        'all_items' => 'すべての%s',
        'edit_item' => '%sを編集',
        'view_item' => '%sを表示',
        'update_item' => '%sを更新',
        'add_new_item' => '%sを追加',
        'new_item_name' => '新規%s名',
        'parent_item' => null,
        'parent_item_colon' => null,
        'search_items' => '%sを検索',
        'popular_items' => '人気の%s',
        'separate_items_with_commas' => '%sが複数ある場合はコンマで区切ってください',
        'add_or_remove_items' => '%sを追加または削除',
        'choose_from_most_used' => 'よく使われている%sから選択',
        'not_found' => '%sが見つかりませんでした。',
        'no_terms' => '%sなし',
        'filter_by_item' => null,
        'items_list_navigation' => '%sリストナビゲーション',
        'items_list' => '%sリスト',
        'most_used' => 'よく使うもの',
        'back_to_items' => '&larr; %sへ戻る',
        'item_link' => '%sリンク',
        'item_link_description' => '%sへのリンク。',
    ];
}
