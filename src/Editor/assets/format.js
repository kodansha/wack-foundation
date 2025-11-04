/**
 * Text Format Type Disabler
 *
 * Disables text format types that are not in the enabled list.
 * Gets all registered format types dynamically and disables those not specified
 * in the PHP-provided config, eliminating the need for hardcoded format lists.
 *
 * Reference: List of all available formats
 * https://github.com/WordPress/gutenberg/tree/trunk/packages/format-library
 */
wp.domReady(() => {
  // Get the enabled formats from the PHP-provided config
  const enabledFormats = formatDisablerConfig?.enabledFormats || [];

  if (enabledFormats.length === 0) {
    // If no formats are enabled, disable all formats
    const allFormats = wp.data.select("core/rich-text").getFormatTypes() || [];
    for (const format of allFormats) {
      wp.richText.unregisterFormatType(format.name);
    }
    return;
  }

  // Get all registered format types
  const allFormats = wp.data.select("core/rich-text").getFormatTypes() || [];

  // Disable formats that are not in the enabled list
  for (const format of allFormats) {
    if (!enabledFormats.includes(format.name)) {
      wp.richText.unregisterFormatType(format.name);
    }
  }
});
