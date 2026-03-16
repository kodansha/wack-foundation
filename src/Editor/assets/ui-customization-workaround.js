/**
 * Block editor UI customization workaround
 *
 * Handles editor UI adjustments that cannot be achieved through standard
 * theme customization or parent theme features.
 *
 * [Heading / Separator / Image block toolbars]
 * - "Align": disabled via blocks.registerBlockType filter (supports.align = false)
 * - "Text alignment", "Bold", "Link": body class toggled via wp.data.subscribe;
 *   CSS targets those classes to hide the controls
 *
 * [Preview / View button]
 * - Monitors post status via wp.data and sets data-custom-post-status on body.
 *   CSS selectors use this attribute to show/hide preview-related buttons per status.
 *
 * Note: relies on WordPress internal CSS classes and DOM structure,
 * so this may stop working after a WordPress version upgrade.
 * Verified on WordPress 6.9.3.
 */

// Blocks for which the "Align" toolbar button is disabled
const ALIGN_DISABLED_BLOCKS = ["core/heading", "core/separator", "core/image"];

wp.hooks.addFilter(
  "blocks.registerBlockType",
  "wack-foundation/ui-customization-workaround",
  function (settings, name) {
    if (!ALIGN_DISABLED_BLOCKS.includes(name)) {
      return settings;
    }

    return {
      ...settings,
      supports: {
        ...settings.supports,
        align: false,
      },
    };
  },
);

// Toggle body classes on block selection to drive CSS-based control hiding
wp.data.subscribe(function () {
  const selectedBlock = wp.data.select("core/block-editor").getSelectedBlock();
  document.body.classList.toggle(
    "wack-heading-selected",
    selectedBlock?.name === "core/heading",
  );
  document.body.classList.toggle(
    "wack-separator-selected",
    selectedBlock?.name === "core/separator",
  );
  document.body.classList.toggle(
    "wack-image-selected",
    selectedBlock?.name === "core/image",
  );
});

// Set data-custom-post-status on body to drive CSS-based preview button visibility
wp.domReady(function () {
  if (!wp.data || !wp.data.select("core/editor")) return;

  const updatePostStatusAttribute = function () {
    // Get the current post status (e.g. 'draft', 'publish', 'private')
    const status = wp.data
      .select("core/editor")
      .getCurrentPostAttribute("status");
    if (status) {
      // Reflect status as a data attribute on body for CSS targeting
      document.body.setAttribute("data-custom-post-status", status);
    }
  };

  updatePostStatusAttribute();

  // Watch for status changes continuously
  wp.data.subscribe(updatePostStatusAttribute);
});
