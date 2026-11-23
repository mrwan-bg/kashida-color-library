# Changelog

All notable changes to Kashida Color Library are documented here.

## [2.7.0] - 2026-11-23

### Added
- Added public, read-only REST API endpoints for compatible integrations, including Kashida Paint Visualizer.
- Added a group-list endpoint that returns published and enabled color groups in alphabetical title order.
- Added a group-colors endpoint with pagination and search by color name, commercial code, and HEX value.
- Added normalized API output containing only color name, code, HEX, and custom attribute fields.

### Improved
- Preserved the original color order in API responses.
- Normalized valid HEX values and excluded rows with invalid HEX values from API responses.
- Restricted API visibility to published, enabled groups.
- Kept sample-image URLs, attachment IDs, and administrative metadata out of API responses.
- Kept the integration API optional; the Color Library remains fully usable as a standalone plugin.

### Compatibility
- The API uses GET-only routes and does not provide catalog write operations.
- The group-colors endpoint defaults to 100 colors per page and supports up to 200 per page.

## [2.6.14] - 2026-10-22

- Redesigned the Help page to match the Settings page layout.
- Expanded Help and Settings pages to use the available WordPress admin content width.
- Improved alignment, spacing, and responsive behavior.
- Refined RTL and LTR layout support for Arabic and English.
- Improved presentation of import templates, tables, and shortcode examples.

## [2.6.11] - 2026-10-12

- Corrected translation documentation and clarified the primary shortcode.
- Updated plugin and readme version metadata.
- Updated the WordPress.org readme changelog for the release.
- Preserved repository development assets.

## [2.6.8] - 2026-10-11

- Prepared the GitHub repository package and documentation for version 2.6.8.
- Documented color groups, responsive display styles, search, filters, sorting, and color tools.
- Documented CSV, XLSX, and ZIP import workflows.
- Added screenshots and repository presentation assets.
