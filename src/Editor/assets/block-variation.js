/**
 * Block Variation Disabler
 *
 * Disables block variations that are not in the enabled list.
 * Gets all registered variations dynamically and disables those not specified
 * in the PHP-provided config, eliminating the need for hardcoded variation lists.
 *
 * Supports controlling variations for multiple block types.
 */

wp.domReady(() => {
  // Get the enabled variations per block type from the PHP-provided config
  const enabledVariationsMap =
    window.blockVariationDisablerConfig?.enabledVariations || {};

  // If no block types are configured, do nothing
  if (Object.keys(enabledVariationsMap).length === 0) {
    return;
  }

  // Process each block type
  for (const [blockType, enabledVariations] of Object.entries(
    enabledVariationsMap
  )) {
    // Get all registered variations for this block type
    const allVariations = wp.blocks.getBlockVariations(blockType) || [];

    if (enabledVariations.length === 0) {
      // If no variations are enabled for this block, disable all variations
      for (const variation of allVariations) {
        wp.blocks.unregisterBlockVariation(blockType, variation.name);
      }
    } else {
      // Disable variations that are not in the enabled list
      for (const variation of allVariations) {
        if (!enabledVariations.includes(variation.name)) {
          wp.blocks.unregisterBlockVariation(blockType, variation.name);
        }
      }
    }
  }
});
