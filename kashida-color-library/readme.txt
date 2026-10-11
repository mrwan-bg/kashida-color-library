=== Kashida Color Library ===
Contributors: mrwanbg
Tags: colors, color palette, color swatches, color picker, shortcode
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 2.6.8
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Author URI: https://kashida.ly/

Manage color groups and display responsive color swatches with search, filters, imports, copy, and flexible layouts.

== Description ==
Kashida Color Library is a lightweight WordPress plugin for creating and displaying reusable color collections.

Each Color Group can contain:
* Color name and commercial code
* HEX, RGB, and HSL values
* A customizable group attribute such as Surface, Material, Texture, or Finish
* Optional sample images
* Responsive Desktop, Tablet, and Mobile column settings
* Rectangle, Square, Circle, or Compact card layouts

The public display includes optional text search, attribute filtering, sorting, nearby-color search, color selection, copying, printing, and sharing.

Administrators can import colors from CSV, XLSX, or ZIP files, preview imports, choose duplicate handling, duplicate groups as drafts, and export group data as CSV or XLSX.

Each Color Group receives a fixed code such as `KCS-0001`. The code does not depend on the group name and remains stable when the name changes.

The plugin does not require a third-party API or external service for its core functionality.

== Main Shortcode ==
`[kashida_colors_showcase group="KCS-0001"]` (recommended)

Legacy shortcode `[paint_colors]` remains available for backward compatibility.

The shortcode name is retained for compatibility with earlier versions. The plugin itself is generic and is not limited to paint products.

== Collection Listing ==
Use the Collection Listing shortcode to display published Color Groups:

`[kashida_collections]`

Examples:

`[kashida_collections desktop="5" tablet="3" mobile="2" shape="square"]`

`[kashida_collections groups="KCS-0001,KCS-0002" show_description="true"]`

Only published Color Groups are included in the public collection listing.

== Shortcode Attributes ==
`group` — One Color Group code.

`groups` — Comma-separated Color Group codes for multiple groups.

`desktop` — Desktop columns; overrides the group default.

`tablet` — Tablet columns; overrides the group default.

`mobile` — Mobile columns; overrides the group default.

`columns` — Legacy/general column value used as a fallback for Desktop.

`shape` — `rectangle`, `square`, `circle`, or `compact`.

`show_name` — Show or hide the color name.

`show_code` — Show or hide the color code.

`show_hex` — Show or hide HEX and its copy control.

`show_rgb` — Show or hide RGB and its copy control.

`show_hsl` — Show or hide HSL.

`show_attribute` — Show or hide the configured group attribute value.

`show_image` — Show or hide the sample image overlay.

`show_finish` — Legacy alias for `show_attribute`.

`title` — Show or hide the group title and description. Default: `true`.

`search` — Show or hide text and color-family search. Default: `true`.

`filter` — Show or hide the group attribute filter. Default: `true`.

`sort` — Show or hide sorting controls. Default: `true`.

`similar` — Show or hide similar-color controls. Default: `true`.

== Examples ==
`[paint_colors group="KCS-0001" shape="rectangle"]`

`[paint_colors group="KCS-0001" shape="circle"]`

`[paint_colors group="KCS-0001" shape="compact"]`

`[paint_colors groups="KCS-0001,KCS-0002" desktop="6" tablet="4" mobile="2"]`

`[paint_colors group="KCS-0001" search="false" filter="false" sort="false"]`

`[paint_colors group="KCS-0001" show_code="false" show_hex="true" show_rgb="true" show_hsl="true"]`

`[paint_colors group="KCS-0001" similar="false"]`

== Import Files ==
CSV and XLSX files use these columns:

`name, code, hex, attribute, attribute_label, sample_image`

`name` and `hex` are required.

`attribute` can contain any group-specific value.

`attribute_label` is an optional label for that attribute, such as Surface, Material, Texture, or Finish.

`sample_image` can contain an image URL. In ZIP imports, image filenames can also match the color name or color code.

ZIP imports can contain one CSV or XLSX data file plus PNG, JPG, WebP, or GIF sample images.

== Import Security ==
Imports are validated before processing. The plugin applies limits to upload size, row count, column count, cell size, color count, image size, ZIP contents, ZIP paths, and ZIP compression ratios.

Color values are sanitized and HEX values are validated before they are saved.

ZIP images are checked as actual supported image files before being added to the Media Library.

Import and export actions require WordPress permissions and nonces.

== Search and Display ==
Visitors can search by color name, code, HEX, RGB, HSL, and attribute. They can filter by the configured group attribute and sort by name, code, attribute, or HEX.

The color wheel can find nearby colors within the same color family and rank results by color proximity.

Selected colors can be copied, printed, or shared from the front-end interface.

== Administration ==
The admin interface provides:
* Color Groups
* Add Group
* Settings
* Help
* Responsive display settings
* Display-field controls
* Attribute configuration
* Import Preview and duplicate handling
* CSV/XLSX export
* Group duplication as Draft

Group publishing uses the standard WordPress Draft/Published workflow.

== Translation ==
English is the default plugin language.

A bundled Arabic translation is included in:
* `languages/kashida-color-library-ar.po`
* `languages/kashida-color-library-ar.mo`

The plugin is translation-ready and registers its interface strings with Polylang when Polylang is available.

== Compatibility ==
Requires WordPress 5.8 or later.

Tested up to WordPress 7.1.

Requires PHP 8.0 or later.

The plugin uses standard WordPress APIs and does not require a third-party API for its core functionality.

== Installation ==
1. Upload the `kashida-color-library` folder to `/wp-content/plugins/`, or install the ZIP from Plugins → Add New → Upload Plugin.
2. Activate Kashida Color Library from Plugins.
3. Open Color Groups → Add Group.
4. Create a Color Group and add colors manually or import CSV/XLSX/ZIP data.
5. Copy the generated Shortcode and place it in a page or post.

== Frequently Asked Questions ==

= Does the plugin require an API? =
No. The core plugin works locally with WordPress data and does not require an external API.

= Can I use it for products other than paint? =
Yes. The plugin is generic. The group attribute can represent Surface, Material, Texture, Finish, or any other value appropriate to the collection.

= Can I use multiple Color Groups in one Shortcode? =
Yes. Use the `groups` attribute with comma-separated group codes.

= Can I change a Color Group name without changing its Shortcode? =
Yes. The group code is fixed and is independent of the group name.

= Are Draft groups visible on the public website? =
No. Public Shortcodes and Collection Listing only render published groups.

= What happens to existing data when the plugin is updated? =
The plugin performs a one-time migration of existing Color Groups and their metadata to uniquely prefixed identifiers. Existing groups, color data, and public URLs are intended to be retained. Back up the database before updating.

== Changelog ==

= 2.6.4 =
* Renamed the plugin to Kashida Color Library to avoid the restricted-term warning in Plugin Check.
* Fixed the single Color Group template to call the correctly prefixed group-code and shortcode functions.
* Improved circular swatch actions: controls are hidden until hover/focus on pointer devices, while touch devices retain clear, accessible controls.
* Fixed template variable prefix warnings.

= 2.6.3 =
* Fixed circular swatch action buttons so Select and Copy remain fully visible within the circle.
* Replaced direct database migration queries with WordPress APIs.
* Added the required translators comment for the color-count placeholder.
* Prefixed template variables and updated plugin naming and translation domain for directory compatibility.


= 2.6.2 =
* Fixed circular color swatch action buttons so Copy and Select remain visible and accessible.
* Improved touch-device visibility and keyboard focus styling for circular layouts.


= 2.6.2 =
* Migrated custom post type, metadata, options, and admin action identifiers to a distinctive plugin namespace with a one-time compatibility migration.
* Added the fully prefixed `[kashida_colors_showcase]` shortcode while retaining `[paint_colors]` for existing pages.
* Improved uniqueness of globally declared PHP functions and constants.
* Updated AJAX and admin-post action identifiers to use a distinctive plugin prefix.
* Updated localized JavaScript globals and registered script handles to use a distinctive prefix.
* Added literal gettext calls and a POT catalog for WordPress.org translation extraction.
* Retained existing color-group post type and stored metadata identifiers for backward compatibility.

= 2.6.0 =
* Prepared the plugin for WordPress.org submission and release review.
* Hardened public Shortcode output so only published Color Groups are rendered.
* Disabled REST exposure for the Color Group custom post type.
* Updated compatibility metadata and documentation for WordPress 7.1.
* Reviewed import, export, permission, nonce, sanitization, escaping, translation, and ZIP security handling.
* Consolidated the release documentation and current feature set.

= 2.5.9 =
* Improved Shortcode copying with Clipboard API and fallback support.
* Corrected the Color Groups admin menu label.
* Continued responsive Rectangle, Square, Circle, and Compact display support.


= 2.6.5 =
* Improved circular color cards by centering the Select and Copy controls in a vertical stack.
* Kept both controls visible and accessible on desktop and touch devices.

= 2.6.6 =
* Circular color-card action buttons appear on hover or keyboard focus on desktop.
* Kept action buttons visible on touch devices where hover is unavailable.

= 2.6.7 =
* Renamed the plugin to Kashida Color Library to avoid the restricted-term warning in Plugin Check.
* Retained the circular-card hover controls introduced in version 2.6.6.

= 2.6.8 =
* Updated the WordPress admin sidebar menu label to Kashida Color Library.
