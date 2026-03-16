/**
 * Block editor UI customization workaround
 *
 * All detection and conditional logic lives here. CSS only targets the
 * custom `wack-ui-hidden` class that this script applies.
 *
 * Per-feature flags are read from window.wackUiWorkaroundConfig, which is
 * injected by PHP before this script loads. Each flag corresponds to a
 * WordPress filter that child themes can use to disable specific features.
 *
 * Features and their config keys:
 * - heading-toolbar    : hide "Text alignment", "Bold", "Link" in heading toolbar;
 *                        disable "Align" support via blocks.registerBlockType filter
 * - separator-toolbar  : disable "Align" support; hide button as fallback if needed
 * - image-toolbar      : hide "Align", "Link", "Crop", "Add caption" group in toolbar;
 *                        disable "Align" support via blocks.registerBlockType filter
 * - image-sidebar      : hide "Settings" panel (alt text, aspect ratio, width, height…)
 * - status-visibility  : hide "Password protection" and "Stick to the top of the blog"
 * - view-options-devices: hide device selection (Desktop / Tablet / Mobile) in View Options
 * - preview-button     : hide preview dropdown and/or View/Preview links by post status
 *
 * Note: aria-label selectors used to locate elements are Japanese locale strings.
 * They will not match on non-Japanese WordPress installations.
 * Relies on WordPress internal CSS classes and DOM structure,
 * so this may stop working after a WordPress version upgrade.
 * Verified on WordPress 6.9.3.
 */

const config = window.wackUiWorkaroundConfig ?? {};
const CLASS_HIDDEN = "wack-ui-hidden";

// ============================================================
// Block filter: disable "Align" support per block type
// ============================================================

const alignDisabledBlocks = [
  config["heading-toolbar"] && "core/heading",
  config["separator-toolbar"] && "core/separator",
  config["image-toolbar"] && "core/image",
].filter(Boolean);

if (alignDisabledBlocks.length > 0) {
  wp.hooks.addFilter(
    "blocks.registerBlockType",
    "wack-foundation/ui-customization-workaround",
    (settings, name) => {
      if (!alignDisabledBlocks.includes(name)) return settings;
      return { ...settings, supports: { ...settings.supports, align: false } };
    },
  );
}

// ============================================================
// Block selection: mark toolbar and sidebar elements
// ============================================================

/**
 * Add wack-ui-hidden to an element if not already hidden.
 * Idempotent: classList.add is a no-op when the class is already present,
 * so calling this repeatedly is safe.
 *
 * @param {Element|null|undefined} el
 */
function hide(el) {
  el?.classList.add(CLASS_HIDDEN);
}

/**
 * Remove wack-ui-hidden from an element.
 * Called when a block is deselected to clean up any previously added classes,
 * in case the element persists in the DOM (e.g. due to re-rendering).
 *
 * @param {Element|null|undefined} el
 */
function show(el) {
  el?.classList.remove(CLASS_HIDDEN);
}

/**
 * Mark or unmark heading toolbar controls.
 * Targets "Text alignment" button and the "Bold / Link" group.
 * Elements are located via aria-label (Japanese locale).
 *
 * @param {boolean} isSelected
 */
function applyHeadingToolbar(isSelected) {
  const toolbar = document.querySelector(".block-editor-block-toolbar");

  // "Text alignment" button (aria-label: Japanese)
  const textAlignBtn = toolbar?.querySelector('[aria-label="テキストの配置"]');
  isSelected ? hide(textAlignBtn) : show(textAlignBtn);

  // "Bold / Link" group: locate via the bold button, then mark its parent group
  const boldGroup = toolbar
    ?.querySelector('[aria-label="太字"]')
    ?.closest(".components-toolbar-group");
  isSelected ? hide(boldGroup) : show(boldGroup);
}

/**
 * Mark or unmark separator toolbar controls.
 * Targets the "Align" group as a CSS fallback in case the block filter
 * did not suppress the button.
 *
 * @param {boolean} isSelected
 */
function applySeparatorToolbar(isSelected) {
  const toolbar = document.querySelector(".block-editor-block-toolbar");

  // "Align" group (aria-label: Japanese) — fallback when JS filter has no effect
  const alignGroup = toolbar
    ?.querySelector('[aria-label="配置"]')
    ?.closest(".components-toolbar-group");
  isSelected ? hide(alignGroup) : show(alignGroup);
}

/**
 * Mark or unmark image block toolbar and sidebar controls.
 *
 * Toolbar: "Align" is removed from DOM by the block filter, so "Crop"
 * is used as the stable anchor to locate the group.
 *
 * Sidebar: "Settings" panel identified via the alt text textarea.
 *
 * @param {boolean} isSelected
 */
function applyImageElements(isSelected) {
  const toolbar = document.querySelector(".block-editor-block-toolbar");
  const inspector = document.querySelector(".block-editor-block-inspector");

  if (config["image-toolbar"]) {
    // "Crop" button (aria-label: Japanese) is always present after align is removed
    const cropGroup = toolbar
      ?.querySelector('[aria-label="切り抜き"]')
      ?.closest(".components-toolbar-group");
    isSelected ? hide(cropGroup) : show(cropGroup);
  }

  if (config["image-sidebar"]) {
    // "Settings" panel: identified by the alt text textarea inside it
    const settingsPanel = inspector
      ?.querySelector(".components-textarea-control")
      ?.closest(".components-tools-panel");
    isSelected ? hide(settingsPanel) : show(settingsPanel);
  }
}

// Subscribe to block selection changes and update toolbar/sidebar marks.
// Tracked via prevBlockName to avoid redundant DOM work on unrelated store updates.
// requestAnimationFrame defers DOM queries until React has committed the toolbar.
let prevBlockName = null;
let pendingRafId = null;

wp.data.subscribe(() => {
  const blockName =
    wp.data.select("core/block-editor").getSelectedBlock()?.name ?? null;

  if (blockName === prevBlockName) return;
  prevBlockName = blockName;

  if (pendingRafId !== null) cancelAnimationFrame(pendingRafId);
  pendingRafId = requestAnimationFrame(() => {
    if (config["heading-toolbar"]) {
      applyHeadingToolbar(blockName === "core/heading");
    }
    if (config["separator-toolbar"]) {
      applySeparatorToolbar(blockName === "core/separator");
    }
    if (config["image-toolbar"] || config["image-sidebar"]) {
      applyImageElements(blockName === "core/image");
    }
  });
});

// ============================================================
// DOM observation: mark popup and dropdown menu elements
// ============================================================

/**
 * Mark Status & Visibility popup items and View Options devices group.
 * These elements are only in the DOM when their respective popups are open,
 * so a MutationObserver is used to catch them when they appear.
 *
 * The `:not(.wack-ui-hidden)` selector prevents triggering repeated mutations
 * by only selecting elements that have not yet been hidden.
 */
function applyDynamicElements() {
  if (config["status-visibility"]) {
    // "Password protection" fieldset
    hide(
      document.querySelector(
        `.editor-change-status__password-fieldset:not(.${CLASS_HIDDEN})`,
      ),
    );

    // "Stick to the top of the blog" checkbox control
    hide(
      document.querySelector(
        `.editor-post-sticky__checkbox-control:not(.${CLASS_HIDDEN})`,
      ),
    );
  }

  if (config["view-options-devices"]) {
    // Device selection group inside the View Options dropdown.
    // Anchored on .editor-preview-dropdown__button-external (language-independent).
    const externalBtn = document.querySelector(
      `.editor-preview-dropdown__button-external:not(.${CLASS_HIDDEN})`,
    );
    if (externalBtn) {
      const radioGroup = externalBtn
        .closest(".components-dropdown-menu__menu")
        ?.querySelector('[role="menuitemradio"]')
        ?.closest(".components-menu-group");
      hide(radioGroup);
    }
  }
}

wp.domReady(() => {
  applyDynamicElements();

  // Observe the entire body for newly added nodes (popup, dropdown renders)
  const observer = new MutationObserver(applyDynamicElements);
  observer.observe(document.body, { childList: true, subtree: true });
});

// ============================================================
// Post status: show / hide preview dropdown and View/Preview links
// ============================================================

wp.domReady(() => {
  if (!config["preview-button"]) return;
  if (!wp.data?.select("core/editor")) return;

  let prevStatus = null;

  const applyPreviewButtonVisibility = () => {
    const status = wp.data
      .select("core/editor")
      .getCurrentPostAttribute("status");

    if (status === prevStatus) return;
    prevStatus = status;

    // Published: hide the preview dropdown (device preview is irrelevant)
    // Private:   hide both the preview dropdown and the View/Preview header links
    const previewDropdown = document.querySelector(".editor-preview-dropdown");
    previewDropdown?.classList.toggle(
      CLASS_HIDDEN,
      status === "publish" || status === "private",
    );

    document
      .querySelectorAll(".editor-header__settings > a.components-button")
      .forEach((link) => {
        link.classList.toggle(CLASS_HIDDEN, status === "private");
      });
  };

  applyPreviewButtonVisibility();
  wp.data.subscribe(applyPreviewButtonVisibility);
});
