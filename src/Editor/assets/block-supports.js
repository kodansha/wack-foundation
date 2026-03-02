/**
 * Disable specific block supports in the block editor
 *
 * This script overrides the supports settings for blocks specified in
 * blockSupportsConfig, which is passed from PHP via wp_localize_script().
 *
 * Block supports control which editor UI features are exposed for a block,
 * such as alignment, borders, colors, and typography controls.
 *
 * The blocks.registerBlockType filter runs at block registration time, so
 * wp.domReady is not needed here.
 *
 * Design note — top-level keys only:
 * Only top-level support keys are handled. When a top-level key whose value
 * is an object (e.g., 'color', 'typography') is disabled by setting it to
 * false, all of its nested sub-controls are also disabled. For granular
 * control of nested support properties (e.g., keeping 'color' but disabling
 * only gradients), add a separate blocks.registerBlockType filter with higher
 * priority after this one.
 *
 * Reference: Block supports
 * https://developer.wordpress.org/block-editor/reference-guides/block-api/block-supports/
 */

const disabledSupports = blockSupportsConfig?.disabledSupports || {};

wp.hooks.addFilter(
    "blocks.registerBlockType",
    "wack/disable-block-supports",
    (settings, name) => {
        const keys = disabledSupports[name];
        if (!keys?.length) {
            return settings;
        }

        return {
            ...settings,
            supports: {
                ...settings.supports,
                // Set each disabled support key to false.
                // Keys with object values (e.g., color: { gradient: true }) are
                // collapsed to false, disabling all nested controls at once.
                ...Object.fromEntries(keys.map((key) => [key, false])),
            },
        };
    },
);
