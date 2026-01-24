<?php

use WackFoundation\Appearance\AdminFavicon;
use WackFoundation\Comment\CommentDisabler;
use WackFoundation\Dashboard\DashboardDisabler;
use WackFoundation\Editor\BlockStyle;
use WackFoundation\Editor\BlockType;
use WackFoundation\Editor\BlockVariation;
use WackFoundation\Editor\ContentEditorDisabler;
use WackFoundation\Editor\Format;
use WackFoundation\Editor\LinkSuggestionDisabler;
use WackFoundation\Editor\QuickEditDisabler;
use WackFoundation\HealthCheck\HealthCheckEndpoint;
use WackFoundation\Media\ImageSizeControl;
use WackFoundation\Media\MediaFilenameNormalizer;
use WackFoundation\Security\RestApiController;
use WackFoundation\Security\XmlRpcDisabler;

//==============================================================================
// Initial Setup
//==============================================================================
add_theme_support('post-thumbnails');

//==============================================================================
// Editor Configuration
//==============================================================================
new ContentEditorDisabler();
new BlockVariation();
new Format();
new LinkSuggestionDisabler();
new BlockStyle();
new BlockType();
new QuickEditDisabler();

//==============================================================================
// Media Configuration
//==============================================================================
new ImageSizeControl();
new MediaFilenameNormalizer();

//==============================================================================
// Comment Configuration
//==============================================================================
new CommentDisabler();

//==============================================================================
// Security Configuration
//==============================================================================
new RestApiController();
new XmlRpcDisabler();

//==============================================================================
// Dashboard Configuration
//==============================================================================
new DashboardDisabler();

//==============================================================================
// Appearance Configuration
//==============================================================================
new AdminFavicon();

//==============================================================================
// Health Check
//==============================================================================
new HealthCheckEndpoint();
