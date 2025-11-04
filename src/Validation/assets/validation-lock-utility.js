/**
 * @fileoverview Validation lock utility for WordPress block editor
 *
 * This module provides a utility function to manage editor publishing locks based on
 * validation conditions. It integrates with WordPress's post saving system and notice
 * system to prevent publishing when validation requirements are not met.
 *
 * ## Features
 * - Prevents post publishing when validation fails
 * - Displays persistent error notices to users
 * - Automatically manages lock state to avoid duplicate locks/unlocks
 * - Supports multiple independent validation locks with unique handles
 *
 * ## Usage Pattern
 * Import this module in your validation script and call `lockEditor` within a
 * `wp.data.subscribe()` callback to react to editor state changes.
 *
 * @example
 * import { lockEditor } from '/app/themes/wack-foundation/src/Editor/Validation/assets/validation-lock-utility.js';
 *
 * wp.domReady(() => {
 *     const validate = () => {
 *         const title = wp.data.select('core/editor').getEditedPostAttribute('title') || '';
 *         lockEditor(title.length <= 0, 'title-missing-lock', 'Please enter a title');
 *     };
 *
 *     validate();
 *     wp.data.subscribe(validate);
 * });
 *
 * @module validation-lock-utility
 * @requires wp.data
 */

/**
 * Internal state tracker for active locks
 *
 * Maintains a record of which locks are currently active to prevent duplicate
 * lock/unlock operations and unnecessary dispatch calls.
 *
 * @type {Object.<string, boolean>}
 * @private
 */
const locks = [];

/**
 * Lock or unlock the WordPress editor based on a validation condition
 *
 * This function manages editor publishing locks and error notices. When the lock
 * condition is true, it prevents the post from being published and displays an
 * error notice. When the condition becomes false, it automatically unlocks the
 * editor and removes the notice.
 *
 * The function uses internal state tracking to ensure that lock/unlock operations
 * are only performed when the state actually changes, avoiding unnecessary
 * dispatch calls and notice flickering.
 *
 * ## Lock Behavior
 * - When `lock` is `true` and no lock exists: Creates a new lock and displays error notice
 * - When `lock` is `true` and lock already exists: No operation (avoids duplicate locks)
 * - When `lock` is `false` and lock exists: Removes lock and clears error notice
 * - When `lock` is `false` and no lock exists: No operation
 *
 * ## Notice Characteristics
 * - Type: Error (red)
 * - Dismissible: No (user cannot manually close it)
 * - Persistence: Remains until validation condition is resolved
 *
 * @param {boolean} lock - Whether to lock the editor (true) or unlock it (false).
 *                         Typically the result of a validation check expression.
 * @param {string} handle - Unique identifier for this specific lock. Must be unique
 *                          across all validations to avoid conflicts. Recommended
 *                          format: 'feature-condition-lock' (e.g., 'title-missing-lock').
 * @param {string} message - Error message to display in the notice when locked.
 *                           Should clearly describe what the user needs to fix.
 *
 * @example
 * // Validate that a title exists
 * const title = wp.data.select('core/editor').getEditedPostAttribute('title') || '';
 * lockEditor(
 *     title.length <= 0,
 *     'title-missing-lock',
 *     'Please enter a title'
 * );
 *
 * @example
 * // Validate category selection with multiple conditions
 * const categories = wp.data.select('core/editor').getEditedPostAttribute('categories') || [];
 *
 * lockEditor(
 *     categories.length === 0,
 *     'category-missing-lock',
 *     'Please select at least one category'
 * );
 *
 * lockEditor(
 *     categories.length > 3,
 *     'category-too-many-lock',
 *     'Please select no more than 3 categories'
 * );
 *
 * @example
 * // Validate featured image
 * const featuredMedia = wp.data.select('core/editor').getEditedPostAttribute('featured_media');
 * lockEditor(
 *     featuredMedia === 0,
 *     'featured-image-missing-lock',
 *     'Please set a featured image'
 * );
 *
 * @function
 * @exports lockEditor
 */
export const lockEditor = (lock, handle, message) => {
  const { dispatch } = wp.data;

  if (lock) {
    if (!locks[handle]) {
      locks[handle] = true;
      dispatch("core/editor").lockPostSaving(handle);
      dispatch("core/notices").createNotice("error", message, {
        id: handle,
        isDismissible: false,
      });
    }
  } else if (locks[handle]) {
    locks[handle] = false;
    dispatch("core/editor").unlockPostSaving(handle);
    dispatch("core/notices").removeNotice(handle);
  }
};
