<?php

use WackFoundation\Appearance\AdminFavicon;
use WackFoundation\Comment\CommentDisabler;
use WackFoundation\Dashboard\DashboardDisabler;
use WackFoundation\Editor\BlockStyle;
use WackFoundation\Editor\BlockType;
use WackFoundation\Editor\ContentEditorDisabler;
use WackFoundation\Editor\EmbedBlockVariation;
use WackFoundation\Editor\Format;
use WackFoundation\Editor\QuickEditDisabler;
use WackFoundation\Media\ImageSizeControl;
use WackFoundation\Media\MediaFilenameNormalizer;
use WackFoundation\Security\RestApiController;
use WackFoundation\Security\XmlRpcDisabler;

//==============================================================================
// 初期設定
//==============================================================================
add_theme_support('post-thumbnails');

//==============================================================================
// エディタ設定
//==============================================================================
new ContentEditorDisabler();
new EmbedBlockVariation();
new Format();
new BlockStyle();
new BlockType();
new QuickEditDisabler();

//==============================================================================
// メディア設定
//==============================================================================
new ImageSizeControl();
new MediaFilenameNormalizer();

//==============================================================================
// コメント設定
//==============================================================================
new CommentDisabler();

//==============================================================================
// セキュリティ設定
//==============================================================================
new RestApiController();
new XmlRpcDisabler();

//==============================================================================
// ダッシュボード設定
//==============================================================================
new DashboardDisabler();

//==============================================================================
// 外観設定
//==============================================================================
new AdminFavicon();

//==============================================================================
// 各種機能の初期化処理（コメントアウト例）
//==============================================================================
// new Hook\MediaUploadHook();

//==============================================================================
// エディターのカスタマイズ系（コメントアウト例）
//==============================================================================
// new Editor\BaseEditorStyle\BaseEditorStyle();
// new Editor\GutenbergAllowedBlockList\GutenbergAllowedBlockList();
// new Editor\GutenbergCustomTextFormat\GutenbergCustomTextFormat();
// new Editor\GutenbergRemoveBlockStyle\GutenbergRemoveBlockStyle();
