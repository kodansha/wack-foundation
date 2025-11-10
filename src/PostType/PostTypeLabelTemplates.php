<?php

namespace WackFoundation\PostType;

/**
 * Post type label templates
 *
 * Provides default label templates for custom post types in multiple languages.
 * These templates match WordPress core's post type labels.
 */
class PostTypeLabelTemplates
{
    /**
     * Default label templates (English)
     *
     * Based on WordPress core's default labels for the 'post' post type.
     * Each template uses sprintf-style placeholders where %s is replaced with the post type label.
     */
    public const TEMPLATES_EN = [
        'name' => '%s',
        'menu_name' => '%s',
        'add_new' => 'Add',
        'add_new_item' => 'Add %s',
        'edit_item' => 'Edit %s',
        'new_item' => 'New %s',
        'view_item' => 'View %s',
        'view_items' => 'View %s',
        'search_items' => 'Search %s',
        'not_found' => 'No %s found.',
        'not_found_in_trash' => 'No %s found in Trash.',
        'parent_item_colon' => 'Parent %s:',
        'all_items' => 'All %s',
        'archives' => '%s Archives',
        'attributes' => '%s Attributes',
        'insert_into_item' => 'Insert into %s',
        'uploaded_to_this_item' => 'Uploaded to this %s',
        'featured_image' => 'Featured image',
        'set_featured_image' => 'Set featured image',
        'remove_featured_image' => 'Remove featured image',
        'use_featured_image' => 'Use as featured image',
        'filter_items_list' => 'Filter %s list',
        'items_list_navigation' => '%s list navigation',
        'items_list' => '%s list',
        'item_published' => '%s published.',
        'item_published_privately' => '%s published privately.',
        'item_reverted_to_draft' => '%s reverted to draft.',
        'item_scheduled' => '%s scheduled.',
        'item_updated' => '%s updated.',
    ];

    /**
     * Default label templates (Japanese)
     *
     * Based on WordPress core's Japanese translation for the 'post' post type.
     * Each template uses sprintf-style placeholders where %s is replaced with the post type label.
     */
    public const TEMPLATES_JA = [
        'name' => '%s',
        'menu_name' => '%s',
        'add_new' => '追加',
        'add_new_item' => '%sを追加',
        'edit_item' => '%sを編集',
        'new_item' => '新規%s',
        'view_item' => '%sを表示',
        'view_items' => '%s一覧を表示',
        'search_items' => '%sを検索',
        'not_found' => '%sが見つかりませんでした。',
        'not_found_in_trash' => 'ゴミ箱に%sはありません。',
        'parent_item_colon' => '親%s:',
        'all_items' => '%s一覧',
        'archives' => '%sアーカイブ',
        'attributes' => '%sの属性',
        'insert_into_item' => '%sに挿入',
        'uploaded_to_this_item' => 'この%sへのアップロード',
        'featured_image' => 'アイキャッチ画像',
        'set_featured_image' => 'アイキャッチ画像を設定',
        'remove_featured_image' => 'アイキャッチ画像を削除',
        'use_featured_image' => 'アイキャッチ画像として使用',
        'filter_items_list' => '%s一覧を絞り込む',
        'items_list_navigation' => '%sリストナビゲーション',
        'items_list' => '%sリスト',
        'item_published' => '%sを公開しました。',
        'item_published_privately' => '%sを限定公開しました。',
        'item_reverted_to_draft' => '%sを下書きに戻しました。',
        'item_scheduled' => '%sを予約しました。',
        'item_updated' => '%sを更新しました。',
    ];
}
