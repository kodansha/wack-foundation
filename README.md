# WACK Foundation Theme

- [WACK Foundation Theme](#wack-foundation-theme)
  - [Overview](#overview)
  - [Purpose \& Design Philosophy](#purpose--design-philosophy)
    - [Key Principles](#key-principles)
  - [Installation](#installation)
    - [Installing via Composer](#installing-via-composer)
    - [Using as a Parent Theme](#using-as-a-parent-theme)
    - [Setting Up PSR-4 Autoloading](#setting-up-psr-4-autoloading)
  - [Features](#features)
    - [Appearance](#appearance)
      - [Admin Favicon](#admin-favicon)
    - [Comment](#comment)
      - [Comment Disabler](#comment-disabler)
    - [Dashboard](#dashboard)
      - [Dashboard Disabler](#dashboard-disabler)
        - [`wack_dashboard_redirect_url`](#wack_dashboard_redirect_url)
        - [`wack_dashboard_allowed_capabilities`](#wack_dashboard_allowed_capabilities)
    - [Editor](#editor)
      - [Block Type Controller](#block-type-controller)
        - [`wack_block_type_enabled_types`](#wack_block_type_enabled_types)
      - [Block Style Manager](#block-style-manager)
        - [`wack_block_style_enabled_styles`](#wack_block_style_enabled_styles)
      - [Format Controller](#format-controller)
        - [`wack_text_format_enabled_types`](#wack_text_format_enabled_types)
      - [Block Variation Manager](#block-variation-manager)
        - [`wack_block_enabled_variations`](#wack_block_enabled_variations)
      - [Content Editor Disabler](#content-editor-disabler)
        - [`wack_content_editor_disabled_post_types`](#wack_content_editor_disabled_post_types)
      - [Quick Edit Disabler](#quick-edit-disabler)
        - [`wack_quick_edit_enabled_post_types`](#wack_quick_edit_enabled_post_types)
    - [Media](#media)
      - [Image Size Control](#image-size-control)
        - [`wack_image_size_control_custom_sizes`](#wack_image_size_control_custom_sizes)
      - [Media Filename Normalizer](#media-filename-normalizer)
        - [`media_filename_generator`](#media_filename_generator)
    - [Security](#security)
      - [REST API Controller](#rest-api-controller)
        - [`wack_rest_api_namespace_whitelist`](#wack_rest_api_namespace_whitelist)
        - [`wack_rest_api_forbidden_routes`](#wack_rest_api_forbidden_routes)
      - [XML-RPC Disabler](#xml-rpc-disabler)
    - [PostType \& Taxonomy Base Classes](#posttype--taxonomy-base-classes)
      - [BasePostType](#baseposttype)
      - [BaseTaxonomy](#basetaxonomy)
    - [Validation](#validation)
      - [BaseValidation](#basevalidation)
  - [Killer Pads Users](#killer-pads-users)

## Overview

WACK Foundation is a battle-tested parent theme designed specifically for headless WordPress deployments. It provides a curated set of features that disable unnecessary functionality by default while offering granular control through WordPress filters to re-enable features as needed.

By using this as a parent theme, you gain:
- **Security hardening** out of the box (XML-RPC disabled, REST API access control, etc.)
- **Block editor optimization** for headless workflows (reduced block types, disabled Quick Edit, etc.)
- **Filter-based extensibility** for all features
- **Zero configuration** required for sensible defaults

## Purpose & Design Philosophy

This theme is built for **headless WordPress** setups where:
- Content is managed in WordPress but rendered on a separate frontend
- The block editor (Gutenberg) is used for structured content authoring
- Security and performance are prioritized over convenience features
- Child themes extend functionality through filters rather than overriding templates

### Key Principles

1. **Deny by default, allow by exception**: Features are disabled unless explicitly enabled
2. **Filter-driven configuration**: All behavior is customizable via WordPress filters (except abstract base classes like `BasePostType`, `BaseTaxonomy`, and `BaseValidation`, which require class inheritance)
3. **Minimal footprint**: No bloat, no legacy compatibility, no frontend assets
4. **Developer-friendly**: Clear APIs, comprehensive documentation, predictable behavior

## Installation

### Installing via Composer

This theme is available only through Composer.

**Installation steps:**

1. Require the theme package:

```bash
composer require kodansha/wack-foundation
```

2. The theme will be installed to `web/app/themes/wack-foundation` (when using Bedrock) or `wp-content/themes/wack-foundation` (standard WordPress).

3. Activate the theme or use it as a parent theme for your child theme.

### Using as a Parent Theme

To use WACK Foundation as a parent theme, specify it in your child theme's `style.css`:

```css
/*
Theme Name: Your Child Theme Name
Template: wack-foundation
Version: 1.0.0
*/
```

The `Template` field must match the directory name of the WACK Foundation theme.

### Setting Up PSR-4 Autoloading

To use abstract base classes like `BasePostType`, `BaseTaxonomy`, and `BaseValidation`, you'll need to set up PSR-4 autoloading for your child theme.

**For Bedrock projects:**

Add your theme namespace to `composer.json`:

```json
{
  "autoload": {
    "psr-4": {
      "MyTheme\\": "web/app/themes/my-theme/src/"
    }
  }
}
```

After updating `composer.json`, regenerate the autoloader:

```bash
composer dump-autoload
```

**Directory structure:**

Organize your classes following PSR-4 conventions:

```
web/app/themes/my-theme/  (or wp-content/themes/my-theme/)
├── src/
│   ├── PostTypes/
│   │   ├── AuthorPostType.php      (MyTheme\PostTypes\AuthorPostType)
│   │   └── ProductPostType.php     (MyTheme\PostTypes\ProductPostType)
│   ├── Taxonomies/
│   │   └── GenreTaxonomy.php       (MyTheme\Taxonomies\GenreTaxonomy)
│   └── Validations/
│       └── PostValidation.php      (MyTheme\Validations\PostValidation)
├── functions.php
└── style.css
```

**Example class file** (`src/PostTypes/AuthorPostType.php`):

```php
<?php
namespace MyTheme\PostTypes;

use WackFoundation\PostType\BasePostType;

class AuthorPostType extends BasePostType
{
    public static function postTypeName(): string
    {
        return 'author';
    }

    public static function postTypeLabel(): string
    {
        return 'Author';
    }

    public function __construct()
    {
        $this->menu_icon = 'dashicons-admin-users';
        $this->menu_position = 5;
        $this->extra_args = [
            'supports' => ['title', 'editor', 'thumbnail'],
        ];
    }
}
```

**Register in functions.php:**

```php
<?php
// functions.php
new MyTheme\PostTypes\AuthorPostType()->register();
new MyTheme\Taxonomies\GenreTaxonomy()->register(['author']);
new MyTheme\Validations\PostValidation();
```

## Features

### Appearance

#### Admin Favicon

Automatically sets custom favicons for the WordPress admin dashboard and login page by detecting favicon files in your theme's root directory.

**Supported files** (priority order):
1. `favicon.ico` - ICO format (recommended for compatibility)
2. `favicon.png` - PNG format (modern browsers)
3. `favicon.svg` - SVG format (scalable vector graphics)

**How it works:**
- Place a favicon file (`favicon.ico`, `favicon.png`, or `favicon.svg`) in your theme's root directory
- The class automatically detects and outputs the appropriate `<link>` tag
- MIME types are auto-detected from file extension
- Child themes inherit and can override parent theme favicons

**No configuration needed** - just add the file to your theme directory.

---

### Comment

#### Comment Disabler

Completely disables comments and trackbacks functionality for headless WordPress.

**What it disables:**
- Comment support for all public post types
- Comment-related admin UI (menus, dashboard modules, profile shortcuts)
- REST API comment endpoints (`/wp/v2/comments`, etc.)
- XML-RPC comment methods (`wp.newComment`, `wp.editComment`, etc.)
- Comment widgets and admin bar items

**What it preserves:**
- Logged-in users with `edit_posts` capability retain full access (for Gutenberg)
- Frontend template rendering (this is admin/API-only)

**No filters available** - comments are always disabled by this theme. This feature cannot be turned off.

---

### Dashboard

#### Dashboard Disabler

Disables the WordPress admin dashboard (`index.php`) and redirects users to a more useful admin page.

**Default behavior:**
- Redirects all users to the posts list (`edit.php`)
- Removes the "Dashboard" menu item from the admin sidebar

**Filters:**

##### `wack_dashboard_redirect_url`

Customize the redirect destination URL.

```php
<?php
// Redirect to pages list instead of posts
add_filter('wack_dashboard_redirect_url', fn() => 'edit.php?post_type=page');

// Redirect to a custom page
add_filter('wack_dashboard_redirect_url', fn() => 'admin.php?page=my-custom-page');
```

**Parameters:**
- (none) - Returns a string URL path relative to `admin_url()`

**Default:** `'edit.php'` (posts list)

##### `wack_dashboard_allowed_capabilities`

Allow specific user capabilities to access the dashboard.

```php
<?php
// Allow administrators to see the dashboard
add_filter('wack_dashboard_allowed_capabilities', function($capabilities) {
    $capabilities[] = 'manage_options';
    return $capabilities;
});

// Allow editors and admins
add_filter('wack_dashboard_allowed_capabilities', function($capabilities) {
    $capabilities[] = 'manage_options';
    $capabilities[] = 'edit_others_posts';
    return $capabilities;
});
```

**Parameters:**
- `array $capabilities` - Array of capability strings

**Default:** `[]` (no users can access dashboard)

---

### Editor

#### Block Type Controller

Controls which Gutenberg blocks are available in the block editor. Uses a minimal whitelist approach.

**Default allowed blocks:**
- `core/heading`
- `core/image`
- `core/list`
- `core/list-item`
- `core/paragraph`

All other blocks (including media, embeds, widgets, etc.) are **disabled by default**.

**Important:** Some of the default blocks have variations (e.g., `core/paragraph` has stretchy-paragraph). All block variations are **disabled by default** even if the block itself is enabled. You must use the `wack_block_enabled_variations` filter to selectively enable specific variations. See [Block Variation Manager](#block-variation-manager) for details.

Example: Enable core/embed block and YouTube embeds:
```php
// First, enable the embed block
add_filter('wack_block_type_enabled_types', fn($blocks) => array_merge($blocks, [
    'core/embed',
]));

// Then, enable specific embed variations
add_filter('wack_block_enabled_variations', fn() => [
    'core/embed' => ['youtube'],
]);
```

**Filter:**

##### `wack_block_type_enabled_types`

Extend the list of allowed block types.

```php
<?php
// Add additional core blocks
add_filter('wack_block_type_enabled_types', fn($default_blocks) => array_merge($default_blocks, [
    'core/table',
    'core/video',
    'core/gallery',
    'core/quote',
    'core/code',
]));

// Replace entirely (not recommended)
add_filter('wack_block_type_enabled_types', fn() => [
    'core/paragraph',
    'core/heading',
    'my-custom/block',
]);
```

**Parameters:**
- `array $default_blocks` - Array of default block type names

**Default:** `BlockType::DEFAULT_ALLOWED_BLOCK_TYPES`

**Block type reference:**
Primary (PHP – suitable for CLI, diagnostics, CI):
```php
$registry = \WP_Block_Type_Registry::get_instance();
$all_blocks = $registry->get_all_registered(); // array keyed by block name
foreach ($all_blocks as $name => $block) {
    // $name example: 'core/paragraph'
    // Access meta: $block->title, $block->category, $block->supports
}
```
Secondary (browser console quick lookup): `wp.blocks.getBlockTypes()`
Reference docs: https://developer.wordpress.org/block-editor/reference-guides/core-blocks/

---

#### Block Style Manager

Controls which block styles are available for core and custom blocks. Disables non-default styles by default.

**Default behavior:**
- Only "default" block styles are enabled
- Non-default styles (like "outline" button, "rounded" image, etc.) are disabled

**Filter:**

##### `wack_block_style_enabled_styles`

Specify which block styles should be available. Format: associative array mapping block names to arrays of style names.

```php
<?php
// Enable specific non-default styles
add_filter('wack_block_style_enabled_styles', fn() => [
    'core/button' => ['outline'],
    'core/quote' => ['plain'],
    'core/image' => ['rounded'],
    'core/separator' => ['wide', 'dots'],
]);
```

**Parameters:**
- `array<string, string[]> $styles` - Associative array of block styles

**Default:** `[]` (all non-default styles disabled, only default styles available)

**Block style reference:**
Default styles (like `core/button:fill`, `core/image:default`) are always available and cannot be disabled.

Primary (PHP – enumerating styles):
```php
$registry = \WP_Block_Type_Registry::get_instance();
$styles = [];
foreach ($registry->get_all_registered() as $block) {
    if (!empty($block->styles)) {
        $styles[$block->name] = $block->styles; // each style array has keys like 'name', 'label', 'isDefault'
    }
}
// $styles maps block name => array of style meta arrays
```
Secondary (browser console quick lookup):
`wp.blocks.getBlockTypes().filter(b => b.styles?.length).map(b => ({block: b.name, styles: b.styles}))`

---

#### Format Controller

Controls which text formatting options are available in the Rich Text toolbar (bold, italic, link, etc.).

**Default behavior:**
- **All** text formats are disabled by default (empty array)
- Use the filter to enable specific formats

**Filter:**

##### `wack_text_format_enabled_types`

Specify which text formats should be enabled.

```php
<?php
// Enable basic formatting
add_filter('wack_text_format_enabled_types', fn() => [
    'core/bold',
    'core/italic',
    'core/link',
]);

// Enable extended formatting
add_filter('wack_text_format_enabled_types', fn() => [
    'core/bold',
    'core/code',
    'core/italic',
    'core/link',
    'core/strikethrough',
    'core/underline',
]);

// Enable all available formats
add_filter('wack_text_format_enabled_types', fn() => [
    'core/bold',
    'core/code',
    'core/italic',
    'core/link',
    'core/strikethrough',
    'core/subscript',
    'core/superscript',
    'core/text-color',
    'core/underline',
]);
```

**Parameters:**
- `array $formats` - Array of format type identifiers

**Default:** `[]` (all formats disabled)

**Format type reference:**
- Full list: https://github.com/WordPress/gutenberg/tree/trunk/packages/format-library
- Runtime inspection: Run `wp.data.select('core/rich-text').getFormatTypes()` in browser console

---

#### Block Variation Manager

Controls which block variations are available in the block editor. This can be used to restrict variations for any block type, such as embed blocks (YouTube, Twitter, Vimeo, etc.).

**Default behavior:**
- **All WordPress core blocks with variations** default to having all variations disabled (empty array) unless explicitly configured
- This provides consistent behavior across all variation-enabled blocks
- Prevents unwanted variations (like WordPress 6.9+ stretchy variations) from being enabled by default

**Blocks with variations:**

The following 18 blocks have variations registered and will default to having all variations disabled. To enable specific variations, use the filter with the exact variation names listed below.

| Block Name | Available Variation Names |
|:-----------|:--------------------------|
| `core/categories` | `terms`, `categories` |
| `core/columns` | `one-column-full`, `two-columns-equal`, `two-columns-one-third-two-thirds`, `two-columns-two-thirds-one-third`, `three-columns-equal`, `three-columns-wider-center` |
| `core/cover` | `cover` |
| `core/embed` | `twitter`, `youtube`, `facebook`, `instagram`, `wordpress`, `soundcloud`, `spotify`, `flickr`, `vimeo`, `animoto`, `cloudup`, `collegehumor`, `crowdsignal`, `dailymotion`, `imgur`, `issuu`, `kickstarter`, `mixcloud`, `pocket-casts`, `reddit`, `reverbnation`, `scribd`, `smugmug`, `speaker-deck`, `tiktok`, `ted`, `tumblr`, `videopress`, `wordpress-tv`, `amazon-kindle`, `pinterest`, `wolfram-cloud`, `bluesky` |
| `core/group` | `group`, `group-row`, `group-stack`, `group-grid` |
| `core/heading` | `heading`, `stretchy-heading` |
| `core/navigation-link` | `post`, `page`, `category`, `tag`, `photo_book`, `back_number`, `back_number_category`, `cover_talent` |
| `core/paragraph` | `paragraph`, `stretchy-paragraph` |
| `core/post-date` | `post-date`, `post-date-modified` |
| `core/post-navigation-link` | `post-previous`, `post-next` |
| `core/post-terms` | `category`, `post_tag`, `back_number_category`, `cover_talent` |
| `core/post-time-to-read` | `time-to-read`, `word-count` |
| `core/query` | `title-date`, `title-excerpt`, `title-date-excerpt`, `image-date-title` |
| `core/query-title` | `archive-title`, `search-title`, `post-type-label` |
| `core/search` | `default` |
| `core/social-link` | `wordpress`, `fivehundredpx`, `amazon`, `bandcamp`, `behance`, `bluesky`, `chain`, `codepen`, `deviantart`, `discord`, `dribbble`, `dropbox`, `etsy`, `facebook`, `feed`, `flickr`, `foursquare`, `goodreads`, `google`, `github`, `gravatar`, `instagram`, `lastfm`, `linkedin`, `mail`, `mastodon`, `meetup`, `medium`, `patreon`, `pinterest`, `pocket`, `reddit`, `skype`, `snapchat`, `soundcloud`, `spotify`, `telegram`, `threads`, `tiktok`, `tumblr`, `twitch`, `twitter`, `vimeo`, `vk`, `whatsapp`, `x`, `yelp`, `youtube` |
| `core/template-part` | `area_header`, `area_footer` |
| `core/terms-query` | `name`, `name-count` |

**Note:** Some variation names (like `photo_book`, `back_number`, etc.) are specific to this project's custom post types and taxonomies.

**Filter:**

##### `wack_block_enabled_variations`

Specify which variations should be available for each block type.

```php
<?php
// Enable YouTube embeds only
add_filter('wack_block_enabled_variations', fn() => [
    'core/embed' => ['youtube'],
]);

// Enable specific column layouts
add_filter('wack_block_enabled_variations', fn() => [
    'core/columns' => [
        'two-columns-equal',               // 50-50 layout
        'three-columns-equal',             // 33-33-33 layout
        'two-columns-one-third-two-thirds', // 33-66 layout
    ],
]);

// Enable specific query variations
add_filter('wack_block_enabled_variations', fn() => [
    'core/query' => [
        'title-date',         // Title and date layout
        'title-excerpt',      // Title and excerpt layout
    ],
]);

// Control variations for multiple block types
add_filter('wack_block_enabled_variations', fn() => [
    'core/embed' => ['youtube'],
    'core/columns' => ['two-columns-equal', 'three-columns-equal'],
    'core/post-date' => ['post-date', 'post-date-modified'],
    'core/navigation-link' => ['post', 'page', 'category'],
    // Other blocks default to [] (all variations disabled)
]);
```

**Parameters:**
- `array<string, array> $variations` - Map of block types to their enabled variation slugs

**Default:** `[]` (no blocks configured; all 18 blocks with variations default to empty arrays)

**Runtime inspection:**
To see all available variations for a specific block, run the following in the browser console:
```javascript
wp.blocks.getBlockVariations('core/embed')

// Or to see all blocks with variations:
wp.blocks.getBlockTypes()
  .map(b => ({name: b.name, variations: wp.blocks.getBlockVariations(b.name) || []}))
  .filter(b => b.variations.length > 0)
```

---

#### Content Editor Disabler

Hides the content editor (title and content fields) for specific post types where structured content is managed entirely through ACF or custom fields.

**Default behavior:**
- No post types have the editor disabled (empty array)

**Why not remove 'editor' support?**
While you can disable the content editor by removing `'editor'` from a post type's `supports` array, this prevents the Gutenberg UI from loading entirely. This class allows you to hide the content editor while maintaining the block editor interface, ensuring a consistent UI across all post types.

**Filter:**

##### `wack_content_editor_disabled_post_types`

Specify which post types should have their content editor hidden.

```php
<?php
// Hide content editor for 'author' and 'product' post types
add_filter('wack_content_editor_disabled_post_types', fn() => [
    'author',
    'product',
]);

// Hide for a single post type
add_filter('wack_content_editor_disabled_post_types', fn() => ['landing-page']);
```

**Parameters:**
- `array $post_types` - Array of post type slugs

**Default:** `[]` (no post types affected)

**How it works:**
- Loads a CSS file that hides the editor interface
- Only loads on the specified post types
- Content is still stored in the database; only the UI is hidden
- Preserves the Gutenberg block editor UI for consistency

---

#### Quick Edit Disabler

Disables the "Quick Edit" inline editing functionality in WordPress admin post lists.

**Default behavior:**
- Quick Edit is **disabled for all post types** by default

**Filter:**

##### `wack_quick_edit_enabled_post_types`

Specify which post types should have Quick Edit enabled.

```php
<?php
// Enable Quick Edit for posts and pages
add_filter('wack_quick_edit_enabled_post_types', fn() => [
    'post',
    'page',
]);

// Enable for custom post type
add_filter('wack_quick_edit_enabled_post_types', fn($types) => array_merge(
    $types,
    ['product', 'event']
));
```

**Parameters:**
- `array $post_types` - Array of post type slugs where Quick Edit should be enabled

**Default:** `[]` (Quick Edit disabled for all post types)

**Why disable Quick Edit?**
- Prevents inconsistent data when using structured content (ACF, custom fields)
- Forces editors to use the full editor where validation rules apply
- Reduces accidental edits in headless workflows

---

### Media

#### Image Size Control

Controls which WordPress image sizes are automatically generated when images are uploaded. Uses a whitelist approach.

**Default behavior:**
- **All** WordPress default image sizes are disabled (thumbnail, medium, large, etc.)
- **All** auto-generated sizes are disabled
- Big image auto-resize threshold is disabled

**Filter:**

##### `wack_image_size_control_custom_sizes`

Define custom image sizes to generate.

```php
<?php
// Define custom image sizes
add_filter('wack_image_size_control_custom_sizes', fn() => [
    // Format: 'size-name' => [width, height, crop]
    // All parameters are optional except width

    // Fixed size with crop
    'card-thumbnail' => [400, 300, true],

    // Fixed size without crop (default)
    'hero-banner' => [1200, 600],

    // Width only (height auto-calculated)
    'content-width' => [800],

    // Width and height, no crop
    'gallery-large' => [1024, 768, false],

    // Width only with soft crop
    'post-thumbnail' => [600, 0, false],
]);
```

**Parameters:**
- `array $sizes` - Associative array mapping size names to `[width, height, crop]` arrays
  - `width` (int, required): Image width in pixels
  - `height` (int, optional): Image height in pixels (0 = auto)
  - `crop` (bool, optional): Whether to crop to exact dimensions (default: `false`)

**Default:** `[]` (no sizes generated)

**Size format:**
- Array values: `[width]`, `[width, height]`, or `[width, height, crop]`
- Crop can be `true` (center crop) or `false` (proportional resize)
- Height of `0` means auto-calculate based on aspect ratio

**What's disabled:**
- WordPress default sizes: `thumbnail`, `medium`, `medium_large`, `large`
- Theme-defined sizes via `add_image_size()`
- Big image threshold (no auto-resize of large originals)

---

#### Media Filename Normalizer

Automatically generates clean, unique filenames for uploaded media files using UUIDv7.

**Default behavior:**
- Original filenames are replaced with UUIDv7 identifiers
- File extensions are preserved
- Filenames are URL-safe and collision-resistant

**Example:**
- `my photo (1).jpg` → `01933b6e-8f12-7890-abcd-ef1234567890.jpg`
- `スクリーンショット 2024.png` → `01933b6e-9a3c-7def-1234-567890abcdef.png`

**Filter:**

##### `media_filename_generator`

Provide a custom filename generation function.

```php
<?php
// Use timestamp with random string
add_filter('media_filename_generator', fn($default, $original, $ext) =>
    date('Y-m-d-His') . '-' . wp_generate_password(8, false), 10, 3);

// Use sanitized original filename (not recommended for security)
add_filter('media_filename_generator', fn($default, $original, $ext) =>
    sanitize_file_name($original), 10, 3);

// Custom format with prefix
add_filter('media_filename_generator', fn($default, $original, $ext) =>
    'media-' . uniqid() . '-' . time(), 10, 3);
```

**Parameters:**
- `string $default_filename` - The default UUIDv7-based filename (without extension)
- `string $original_filename` - The original uploaded filename (without extension)
- `string $extension` - The file extension (e.g., `'jpg'`, `'png'`)

**Returns:** `string` - New filename (without extension)

**Default:** UUIDv7 identifier

**Why UUIDv7?**
- Time-ordered for better database indexing
- Globally unique (collision-resistant)
- URL-safe characters only
- No personal information leakage
- Consistent length and format

---

### Security

#### REST API Controller

Controls access to WordPress REST API endpoints using namespace whitelisting and route blacklisting.

**Default behavior:**
- **All** REST API access is denied by default (empty whitelist)
- Logged-in users with `edit_posts` capability have **full access** (required for Gutenberg)

**Security model:**
1. Check user capability → Allow if `edit_posts` (admin/editor users bypass all restrictions)
2. Check route blacklist → Deny if route is explicitly forbidden
3. Check namespace whitelist → Allow if namespace is whitelisted
4. Default deny → Reject all other requests

**Filters:**

##### `wack_rest_api_namespace_whitelist`

Add namespaces that should be publicly accessible.

```php
<?php
// Allow WordPress core API for public posts/pages
add_filter('wack_rest_api_namespace_whitelist', function($namespaces) {
    $namespaces[] = 'wp/v2';
    return $namespaces;
});

// Allow custom plugin API
add_filter('wack_rest_api_namespace_whitelist', function($namespaces) {
    $namespaces[] = 'my-plugin/v1';
    $namespaces[] = 'wc/v3'; // WooCommerce
    return $namespaces;
});
```

**Parameters:**
- `array $namespaces` - Array of namespace prefixes (e.g., `'wp/v2'`, `'my-plugin/v1'`)

**Default:** `[]` (no public access)

**How namespaces work:**
- Namespaces are matched by prefix: `'wp/v2'` matches `/wp/v2/posts`, `/wp/v2/pages`, etc.
- Leading slash is optional: `'wp/v2'` and `'/wp/v2'` are equivalent

##### `wack_rest_api_forbidden_routes`

Block specific routes even if their namespace is whitelisted.

```php
<?php
// Block user enumeration
add_filter('wack_rest_api_forbidden_routes', function($routes) {
    $routes[] = '/wp/v2/users';
    return $routes;
});

// Block multiple sensitive endpoints
add_filter('wack_rest_api_forbidden_routes', function($routes) {
    return array_merge($routes, [
        '/wp/v2/users',
        '/wp/v2/plugins',
        '/wp/v2/themes',
        '/wp/v2/settings',
    ]);
});
```

**Parameters:**
- `array $routes` - Array of route paths to block

**Default:** `[]` (no routes explicitly forbidden)

**Recommended blacklist for security:**
```php
add_filter('wack_rest_api_forbidden_routes', fn($routes) => array_merge($routes, [
    '/wp/v2/users',               // User enumeration
    '/wp/v2/plugins',             // Plugin disclosure
    '/wp/v2/themes',              // Theme disclosure
    '/wp/v2/settings',            // Site settings
    '/wp/v2/comments',            // Comments (if disabled)
]));
```

**Gutenberg compatibility:**
- Logged-in users with `edit_posts` capability bypass all restrictions
- This ensures the block editor works without additional configuration
- Public API access requires explicit whitelisting

---

#### XML-RPC Disabler

Completely disables XML-RPC functionality to prevent legacy API attacks.

**What it disables:**
- XML-RPC API endpoint (`xmlrpc.php`)
- All XML-RPC methods
- X-Pingback header
- Pingback functionality

**Security benefits:**
- Prevents brute force attacks via `system.multicall`
- Blocks DDoS attacks via pingback
- Reduces attack surface for headless WordPress

**No filters available** - XML-RPC is completely disabled. If you need XML-RPC for legacy integrations, do not instantiate this class.

**Note:** XML-RPC is a legacy API and is not needed for modern WordPress usage. The REST API and Application Passwords provide better alternatives.

---

### PostType & Taxonomy Base Classes

#### BasePostType

Abstract base class for registering custom post types with sensible defaults for headless WordPress.

**Features:**
- Simplified post type registration with minimal boilerplate
- Headless-friendly defaults (no frontend templates)
- Automatic REST API exposure
- Support for Gutenberg editor
- Automatic label generation with locale support (English/Japanese)

**Basic Usage:**
```php
<?php
namespace MyTheme\PostTypes;

use WackFoundation\PostType\BasePostType;

class AuthorPostType extends BasePostType
{
    public static function postTypeName(): string
    {
        return 'author';
    }

    public static function postTypeLabel(): string
    {
        return '著者'; // Or 'Author' for English
    }

    public function __construct()
    {
        $this->menu_position = 21;
        $this->menu_icon = 'dashicons-admin-users';
        $this->extra_args = [
            'supports' => ['title', 'editor', 'thumbnail'],
        ];
    }
}

// Register the post type
new AuthorPostType()->register();
```

**Label Generation:**
Labels are automatically generated based on the site's locale:
- Japanese locale (`ja*`): Uses Japanese label templates (e.g., "新規追加", "編集")
- Other locales: Uses English label templates (e.g., "Add New", "Edit")

The post type label from `postTypeLabel()` is automatically inserted into the templates.

**Customizing Labels:**
Override the `createLabels()` method in child classes. Use `buildLabelsFromTemplates()` for partial customization:
```php
<?php
protected function createLabels(): array
{
    $labels = $this->buildLabelsFromTemplates(static::postTypeLabel());
    $labels['add_new'] = 'Create New'; // Override specific label
    $labels['edit_item'] = 'Modify';
    return $labels;
}
```

**Required Methods:**
- `postTypeName()`: Return the post type slug (e.g., 'product', 'author')
- `postTypeLabel()`: Return the singular label (e.g., 'Product', '商品')
- `__construct()`: Initialize properties (menu_position, menu_icon, extra_args, etc.)

**Customizable Properties:**
- `$menu_icon`: Dashicon class or custom URL (default: `null`)
- `$menu_position`: Admin menu position (default: `20`)
- `$public`: Public visibility (default: `true`)
- `$publicly_queryable`: Publicly queryable (default: `true`)
- `$show_ui`: Show admin UI (default: `true`)
- `$show_in_rest`: REST API enabled (default: `true`)
- `$has_archive`: Enable archive (default: `true`)
- `$extra_args`: Additional `register_post_type()` arguments (default: `[]`)
  - Common usage: `supports`, `taxonomies`, `rewrite`, `capability_type`, etc.

---

#### BaseTaxonomy

Abstract base class for registering custom taxonomies with sensible defaults for headless WordPress.

**Features:**
- Simplified taxonomy registration with minimal boilerplate
- Headless-friendly defaults
- REST API exposure enabled by default
- Hierarchical (category-style) by default
- Automatic label generation with locale support (English/Japanese)

**Basic Usage:**
```php
<?php
namespace MyTheme\Taxonomies;

use WackFoundation\Taxonomy\BaseTaxonomy;

class GenreTaxonomy extends BaseTaxonomy
{
    public static function taxonomyKey(): string
    {
        return 'genre';
    }

    public static function taxonomyLabel(): string
    {
        return 'ジャンル'; // Or 'Genre' for English
    }

    public function __construct()
    {
        $this->extra_args = [
            'rewrite' => ['slug' => 'genres'],
            'show_in_nav_menus' => true,
        ];
    }
}

// Register the taxonomy for specific post types
new GenreTaxonomy()->register(['post', 'article']);
```

**Non-hierarchical (tag-style) taxonomy:**
```php
<?php
class TagTaxonomy extends BaseTaxonomy
{
    public static function taxonomyKey(): string
    {
        return 'custom_tag';
    }

    public static function taxonomyLabel(): string
    {
        return 'カスタムタグ';
    }

    public function __construct()
    {
        // Override to make it non-hierarchical (tag-style)
        $this->hierarchical = false;

        $this->extra_args = [
            'rewrite' => ['slug' => 'tags'],
            'show_tagcloud' => true,
        ];
    }
}
```

**Label Generation:**
Labels are automatically generated based on the site's locale and taxonomy type:
- **Hierarchical taxonomies**: Use category-style labels
  - Japanese: "カテゴリー一覧", "カテゴリーを追加", etc.
  - English: "All Categories", "Add Category", etc.
- **Non-hierarchical taxonomies**: Use tag-style labels
  - Japanese: "すべてのタグ", "タグを追加", "人気のタグ", etc.
  - English: "All Tags", "Add Tag", "Popular Tags", etc.

The taxonomy label from `taxonomyLabel()` is automatically inserted into the templates.

**Customizing Labels:**
Override the `createLabels()` method in child classes. Use `buildLabelsFromTemplates()` for partial customization:
```php
<?php
protected function createLabels(): array
{
    $labels = $this->buildLabelsFromTemplates(static::taxonomyLabel());
    $labels['add_new_item'] = 'Create New'; // Override specific label
    $labels['search_items'] = 'Find';
    return $labels;
}
```

**Required Methods:**
- `taxonomyKey()`: Return the taxonomy slug (e.g., 'genre', 'product_tag')
- `taxonomyLabel()`: Return the singular label (e.g., 'Genre', 'ジャンル')
- `__construct()`: Initialize properties (hierarchical, extra_args, etc.)

**Customizable Properties:**
- `$hierarchical`: Whether hierarchical (category-style) or flat (tag-style) (default: `true`)
- `$show_in_rest`: REST API enabled (default: `true`)
- `$extra_args`: Additional `register_taxonomy()` arguments (default: `[]`)
  - Common usage: `rewrite`, `show_in_nav_menus`, `show_admin_column`, `show_tagcloud`, etc.

---

### Validation

#### BaseValidation

Abstract base class for implementing custom editor validation in Gutenberg.

**Purpose:**
Enforce content quality rules at the editor level (e.g., require featured image, enforce title length, validate custom fields).

**Features:**
- JavaScript-based validation in the block editor
- Custom error messages
- Pre-publish checks
- Integration with Gutenberg publish panel

**Usage:**
```php
<?php
namespace MyTheme\Validations;

use WackFoundation\Validation\BaseValidation;

class PostValidation extends BaseValidation
{
    protected string $handle = 'post-validation';
    protected string $script_file = 'validation.js';

    protected array $target_post_types = [
        'post',
        'article',
    ];

    protected function getScriptData(): array
    {
        return [
            'requiredFields' => ['title', 'excerpt', 'featured_image'],
            'minTitleLength' => 10,
            'maxTitleLength' => 60,
        ];
    }
}

// Register validation
new PostValidation();
```

**JavaScript validation example:**
```javascript
// validation.js
import { lockEditor } from '/app/themes/wack-foundation/src/Validation/assets/validation-lock-utility.js'; // Change path as needed

/**
 * Validate post title length
 */
const validateTitle = () => {
    const title = wp.data.select('core/editor').getEditedPostAttribute('title') || '';
    const config = validationConfig; // Passed from PHP via wp_localize_script

    lockEditor(
        title.length < config.minTitleLength,
        'title-length-lock',
        `Title must be at least ${config.minTitleLength} characters`
    );
};

/**
 * Validate featured image
 */
const validateFeaturedImage = () => {
    const featuredMedia = wp.data.select('core/editor').getEditedPostAttribute('featured_media');

    lockEditor(
        !featuredMedia,
        'featured-image-lock',
        'Featured image is required'
    );
};

/**
 * Run all validation checks
 */
const validate = () => {
    validateTitle();
    validateFeaturedImage();
};

/**
 * Initialize validation
 */
wp.domReady(() => {
    validate();
    wp.data.subscribe(validate);
});
```

**Methods to override:**
- `getTargetPostTypes()`: Define which post types to validate
- `getScriptData()`: Pass configuration to JavaScript
- `getScriptDependencies()`: Define JavaScript dependencies

**When to use:**
- Enforce required fields (featured image, excerpt, etc.)
- Validate title/content length
- Check custom field values
- Prevent publishing of incomplete content

## Killer Pads Users

If you previously relied on the Killer Pads project (https://github.com/kodansha/killer-pads), this parent theme fully replaces its intended optimization scope (dashboard reduction, editor hardening, security tightening). Do NOT install or activate Killer Pads when using WACK Foundation as a parent theme; running both would duplicate or conflict on the same WordPress hooks.

Not included here (you must implement separately if needed):
- Post revision limiting or disabling
- Autosave disabling

Those concerns are intentionally left out to avoid enforcing irreversible editorial constraints. Add them in a child theme or a small mu‑plugin if your workflow demands it.

Summary:
- Killer Pads: not required
- Revisions & autosave: unmanaged — handle manually if you need stricter control
