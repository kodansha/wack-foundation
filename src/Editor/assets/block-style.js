/**
 * Disable specific block styles in the block editor
 *
 * This script unregisters the block styles specified in the blockStyleDisablerConfig
 * object, which is passed from PHP via wp_localize_script().
 *
 * Block styles allow different visual variations of blocks. For example:
 * - core/button: outline
 * - core/image: rounded
 * - core/quote: plain
 * - core/table: stripes
 *
 * Note: Default styles (isDefault: true) cannot be unregistered and will
 * remain available even if specified in the disabled list.
 *
 * Reference: Block style variations
 * https://developer.wordpress.org/block-editor/reference-guides/block-api/block-styles/
 */
wp.domReady(() => {
    // Get the disabled styles configuration from PHP
    const disabledStyles = blockStyleDisablerConfig?.disabledStyles || {};

    // Unregister each disabled style for each block
    Object.entries(disabledStyles).forEach(([blockName, styleNames]) => {
        styleNames.forEach((styleName) => {
            wp.blocks.unregisterBlockStyle(blockName, styleName);
        });
    });
});
