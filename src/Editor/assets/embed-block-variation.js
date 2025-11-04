/**
 * Embed Block Variation Disabler
 *
 * Disables embed block variations that are not in the enabled list.
 * Gets all registered variations dynamically and disables those not specified
 * in the PHP-provided config, eliminating the need for hardcoded variation lists.
 */

wp.domReady(() => {
  // Get the enabled variations from the PHP-provided config
  const enabledVariations =
    window.embedBlockVariationDisablerConfig?.enabledVariations || [];

  if (enabledVariations.length === 0) {
    // If no variations are enabled, disable all variations
    const allVariations = wp.blocks.getBlockVariations("core/embed") || [];
    for (const variation of allVariations) {
      wp.blocks.unregisterBlockVariation("core/embed", variation.name);
    }
    return;
  }

  // Get all registered embed block variations
  const allVariations = wp.blocks.getBlockVariations("core/embed") || [];

  // Disable variations that are not in the enabled list
  for (const variation of allVariations) {
    if (!enabledVariations.includes(variation.name)) {
      wp.blocks.unregisterBlockVariation("core/embed", variation.name);
    }
  }
});
