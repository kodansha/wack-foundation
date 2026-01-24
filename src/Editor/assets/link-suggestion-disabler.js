/**
 * Link Suggestion Disabler
 *
 * Disables link search suggestions in the WordPress block editor's link
 * insertion interface. This prevents users from selecting internal posts/pages,
 * which is useful for headless WordPress installations.
 *
 * The WordPress block editor overwrites the __experimentalFetchLinkSuggestions
 * setting multiple times during initialization, so we need to continuously
 * monitor and re-apply our override.
 *
 * Reference: https://wordpress.org/support/topic/modify-gutenberg-link-dialog-suggestions/
 */
wp.domReady(() => {
  // Return empty array to disable all link suggestions
  const disabledFetchLinkSuggestions = async () => {
    return [];
  };

  // Continuously monitor block editor settings and override when changed
  wp.data.subscribe(() => {
    const blockEditor = wp.data.select("core/block-editor");

    if (blockEditor && blockEditor.getSettings) {
      const blockEditorSettings = blockEditor.getSettings();

      // Update settings if __experimentalFetchLinkSuggestions exists
      // and is not our custom function
      if (
        blockEditorSettings.__experimentalFetchLinkSuggestions &&
        blockEditorSettings.__experimentalFetchLinkSuggestions !==
          disabledFetchLinkSuggestions
      ) {
        wp.data.dispatch("core/block-editor").updateSettings({
          __experimentalFetchLinkSuggestions: disabledFetchLinkSuggestions,
        });
      }
    }
  });
});
