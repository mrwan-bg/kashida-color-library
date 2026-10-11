<?php
/*
 * Plugin Name: Kashida Color Library
 * Description: A lightweight WordPress plugin to organize, display, and manage color collections.
 * Version: 2.6.8
 * Author: Kashida Media Services
 * Author URI: https://kashida.ly/
 * License: GPL-2.0-or-later
 * Text Domain: kashida-color-library
 */
if (!defined('ABSPATH')) exit;

define('KASHIDA_COLORS_SHOWCASE_VERSION','2.6.8');
define('KASHIDA_COLORS_SHOWCASE_DIR',plugin_dir_path(__FILE__));
define('KASHIDA_COLORS_SHOWCASE_URL',plugin_dir_url(__FILE__));


function kashida_colors_showcase_register_color_group_post_type(){
    register_post_type('kashida_color_group',[
        'labels'=>[
            'name'=>kashida_colors_showcase_t('groups_name','Color Groups'),
            'singular_name'=>kashida_colors_showcase_t('group_singular','Color Group'),
            'add_new'=>kashida_colors_showcase_t('add_new','Add Group'),
            'add_new_item'=>kashida_colors_showcase_t('add_new_item','Add New Group'),
            'edit_item'=>kashida_colors_showcase_t('edit_item','Edit Group'),
            'new_item'=>kashida_colors_showcase_t('new_item','New Group'),
            'view_item'=>kashida_colors_showcase_t('view_item','View Group'),
            'search_items'=>kashida_colors_showcase_t('search_items','Search Groups'),
            'not_found'=>kashida_colors_showcase_t('not_found','No groups found'),
            'menu_name'=>kashida_colors_showcase_t('menu_name','Kashida Color Library')
        ],
        'public'=>true,
        'publicly_queryable'=>true,
        'has_archive'=>false,
        'query_var'=>true,
        'menu_icon'=>'dashicons-art',
        'menu_position'=>25,
        'supports'=>['title','editor','thumbnail','excerpt'],
        'rewrite'=>['slug'=>'kashida-color-groups','with_front'=>false,'feeds'=>false],
        'show_in_rest'=>false
    ]);
}

function kashida_colors_showcase_activate_plugin(){
    kashida_colors_showcase_register_color_group_post_type();
    flush_rewrite_rules(false);
}
function kashida_colors_showcase_deactivate_plugin(){
    flush_rewrite_rules(false);
}
register_activation_hook(__FILE__,'kashida_colors_showcase_activate_plugin');
register_deactivation_hook(__FILE__,'kashida_colors_showcase_deactivate_plugin');


/**
 * Migrate legacy identifiers to the plugin's unique namespace without losing existing groups or settings.
 * This runs once during init before the new post type is registered.
 */
function kashida_colors_showcase_migrate_legacy_schema(){
    if (get_option('kashida_colors_showcase_schema_version') === '1') {
        return;
    }

    $old_type = 'kcs_color_group';
    $new_type = 'kashida_color_group';
    $ids = get_posts(array(
        'post_type' => array($old_type, $new_type),
        'post_status' => 'any',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'orderby' => 'ID',
        'order' => 'ASC',
        'suppress_filters' => true,
    ));
    $failed = false;

    $meta_keys = array(
        '_kcs_group_code' => '_kashida_colors_showcase_group_code',
        '_kcs_active' => '_kashida_colors_showcase_active',
        '_kcs_attribute_label' => '_kashida_colors_showcase_attribute_label',
        '_kcs_attribute_options' => '_kashida_colors_showcase_attribute_options',
        '_kcs_colors' => '_kashida_colors_showcase_colors',
        '_kcs_desktop' => '_kashida_colors_showcase_desktop',
        '_kcs_tablet' => '_kashida_colors_showcase_tablet',
        '_kcs_mobile' => '_kashida_colors_showcase_mobile',
        '_kcs_show_name' => '_kashida_colors_showcase_show_name',
        '_kcs_show_code' => '_kashida_colors_showcase_show_code',
        '_kcs_show_hex' => '_kashida_colors_showcase_show_hex',
        '_kcs_show_rgb' => '_kashida_colors_showcase_show_rgb',
        '_kcs_show_hsl' => '_kashida_colors_showcase_show_hsl',
        '_kcs_show_attribute' => '_kashida_colors_showcase_show_attribute',
        '_kcs_show_image' => '_kashida_colors_showcase_show_image',
        '_kcs_color_shape' => '_kashida_colors_showcase_color_shape',
    );

    foreach ($ids as $post_id) {
        $post_id = (int) $post_id;
        $post = get_post($post_id);
        if (!$post) {
            $failed = true;
            continue;
        }

        if ($post->post_type === $old_type) {
            $updated_post = wp_update_post(array('ID' => $post_id, 'post_type' => $new_type), true);
            if (is_wp_error($updated_post)) {
                $failed = true;
                continue;
            }
        }

        foreach ($meta_keys as $old_key => $new_key) {
            $old_values = get_post_meta($post_id, $old_key, false);
            if (!$old_values) {
                continue;
            }
            if (!metadata_exists('post', $post_id, $new_key)) {
                foreach ($old_values as $old_value) {
                    add_post_meta($post_id, $new_key, $old_value);
                }
            }
            delete_post_meta($post_id, $old_key);
        }
        clean_post_cache($post_id);
    }

    $legacy_options = array(
        'kcs_group_identity_version' => 'kashida_colors_showcase_group_identity_version',
        'kcs_rewrite_version' => 'kashida_colors_showcase_rewrite_version',
    );
    foreach ($legacy_options as $old_option => $new_option) {
        $old_value = get_option($old_option, null);
        if ($old_value !== null && get_option($new_option, null) === null) {
            update_option($new_option, $old_value, false);
        }
        if ($old_value !== null) {
            delete_option($old_option);
        }
    }

    if (!$failed) {
        update_option('kashida_colors_showcase_schema_version', '1', false);
    }
}
add_action('init', 'kashida_colors_showcase_migrate_legacy_schema', 1);

// Import security limits.
define('KASHIDA_COLORS_SHOWCASE_IMPORT_MAX_CSV_BYTES',5 * 1024 * 1024);
define('KASHIDA_COLORS_SHOWCASE_IMPORT_MAX_XLSX_BYTES',10 * 1024 * 1024);
define('KASHIDA_COLORS_SHOWCASE_IMPORT_MAX_ZIP_BYTES',25 * 1024 * 1024);
define('KASHIDA_COLORS_SHOWCASE_IMPORT_MAX_ZIP_UNCOMPRESSED',100 * 1024 * 1024);
define('KASHIDA_COLORS_SHOWCASE_IMPORT_MAX_ZIP_FILES',10000);
define('KASHIDA_COLORS_SHOWCASE_IMPORT_MAX_COLORS',10000);
define('KASHIDA_COLORS_SHOWCASE_IMPORT_MAX_ROWS',10001);
define('KASHIDA_COLORS_SHOWCASE_IMPORT_MAX_COLUMNS',32);
define('KASHIDA_COLORS_SHOWCASE_IMPORT_MAX_CELL_BYTES',4096);
define('KASHIDA_COLORS_SHOWCASE_IMPORT_MAX_IMAGE_BYTES',5 * 1024 * 1024);


function kashida_colors_showcase_translation_strings(){
    static $strings = null;
    if ($strings !== null) return $strings;
    $strings = [
        'groups_name'=>'Color Groups',
        'group_singular'=>'Color Group',
        'add_new'=>'Add Group',
        'add_new_item'=>'Add New Group',
        'edit_item'=>'Edit Group',
        'new_item'=>'New Group',
        'view_item'=>'View Group',
        'search_items'=>'Search Groups',
        'not_found'=>'No groups found',
        'menu_name'=>'Kashida Color Library',
        'group_code_column'=>'Group Code',
        'shortcode_column'=>'Shortcode',
        'view_column'=>'View',
        'copy'=>'Copy',
        'copied'=>'Copied',
        'view_group'=>'View Group',
        'preview_page_title'=>'Color Group Preview',
        'preview_menu'=>'Color Group Preview',
        'not_allowed'=>'You are not allowed to view this page.',
        'group_not_found'=>'Group not found.',
        'not_allowed_group'=>'You are not allowed to view this group.',
        'preview_group_label'=>'View Group: ',
        'preview_description'=>'Preview the colors as they will appear on the website.',
        'edit_group'=>'Edit Group',
        'help'=>'Help',
        'shortcode_label'=>'Shortcode',
        'no_colors_group'=>'There are no colors in this group yet.',
        'settings'=>'Settings',
        'settings_title'=>'Kashida Color Library — Settings',
        'settings_intro'=>'No operational settings are currently required. Use the Help page to manage color groups, imports, and the Shortcode.',
        'settings_help'=>'Open Help',
        'plugin_info'=>'Plugin Information',
        'plugin_name'=>'Kashida Color Library',
        'plugin_name_label'=>'Plugin Name',
        'version'=>'Version',
        'author'=>'Author',
        'website'=>'Website',
        'help_title'=>'Kashida Color Library Help',
        'help_intro'=>'Guide to managing color groups, entering and importing color data, defining a custom attribute for each group, preparing the color table, and using the Shortcode to display colors on your website.',
        'help_create_group'=>'1. Create a Group',
        'help_create_1'=>'Open Kashida Color Library → Add Group.',
        'help_create_2'=>'Enter the group name and optionally add a description and featured image.',
        'help_create_3'=>'Save the group; a fixed code such as KCS-0001 will be generated automatically.',
        'help_code_note'=>'The group code does not depend on the group name and does not change when the name changes. It is used in the Shortcode and URL.',
        'help_manual_colors'=>'2. Add Colors Manually',
        'help_manual_1'=>'Open the required group.',
        'help_manual_2'=>'In the Color Group box, click Add New Color.',
        'help_manual_3'=>'Enter the color name, code, HEX value, optional attribute value, and sample image if needed.',
        'help_manual_4'=>'Save the group.',
        'help_import'=>'3. Import Colors and Supporting Files',
        'help_import_intro'=>'Colors can be imported from CSV or XLSX, and ZIP can be used when sample images need to be included with the data file.',
        'column'=>'Column',
        'type'=>'Type',
        'description'=>'Description',
        'example'=>'Example',
        'attribute_values'=>'Attribute values',
        'help_import_zip'=>'When using ZIP, place a CSV or XLSX file inside the archive. PNG/JPG/WebP/GIF images can be placed at the same level. The plugin matches image names to the color name or color code.',
        'download_csv'=>'Download CSV Template',
        'download_xlsx'=>'Download XLSX Template',
        'download_zip'=>'Download ZIP Template with Images',
        'help_table'=>'4. Prepare the Color Table',
        'help_table_intro'=>'The first row must contain column headers, and each following row represents one color. Do not merge cells or add extra headings above the first row.',
        'help_table_note'=>'In CSV, use commas as the column separator. In XLSX, the first worksheet is used. HEX values must be valid, and sample_image may be left empty when no image is available.',
        'help_shortcode'=>'5. Shortcode',
        'help_shortcode_intro'=>'Copy the Shortcode from the Shortcode column in the groups table or from the group settings, then paste it into a page or post.',
        'help_shortcode_options'=>'Shortcode Options',
        'attribute'=>'Attribute',
        'default_value'=>'Default Value',
        'function'=>'Function',
        'help_color_search'=>'6. Find a Nearby Color',
        'help_color_search_intro'=>'When search is enabled, the color wheel is displayed. Choose a color from the wheel and click Find Nearest. The plugin displays the nearest colors in the same color family, ordered by proximity.',
        'help_color_search_note'=>'The color-wheel search is independent of text search. Clear the selected color to return to all results.',
        'help_front_features'=>'7. Display Features',
        'help_feature_1'=>'Search by name, code, or HEX.',
        'help_feature_2'=>'Filter by the group attribute when an attribute is configured.',
        'help_feature_3'=>'Similar colors.',
        'help_feature_4'=>'Select multiple colors, then copy, print, or share them.',
        'help_translation'=>'8. Translation with Polylang',
        'help_translation_intro'=>'All interface strings are prepared for translation with Polylang. You can translate each label, button, field, and message from Languages → String translations.',
        'help_errors'=>'9. Common Errors',
        'help_error_1'=>'Group not found: make sure you are using the correct group code, such as KCS-0001.',
        'help_error_2'=>'The import contains no valid colors: check name and hex in the file.',
        'help_error_3'=>'ZIP images do not appear: make sure the image filename matches the color name or code and uses a supported extension.',
        'help_error_4'=>'Do not use the group name in the Shortcode; use the group code.',
        'activate_group'=>'Activate Group',
        'group_code'=>'Group Code',
        'group_code_fixed'=>'This code is fixed and does not change when the group name changes.',
        'import_formats'=>'CSV or XLSX. Columns: name, code, hex, attribute, attribute_label, sample_image.',
        'sample_image_hint'=>'sample_image: optional image URL. A filename can also be matched when importing ZIP.',
        'choose_import_file'=>'Choose CSV, XLSX, or ZIP',
        'import_colors'=>'Import Colors',
        'importing'=>'Importing…',
        'import_success'=>'Imported',
        'colors_word'=>'colors. The page will refresh…',
        'import_failed'=>'Import failed',
        'color'=>'Color',
        'color_name'=>'Color Name',
        'color_code'=>'Color Code',
        'attribute_label'=>'Attribute',
        'attribute_name_label'=>'Attribute Name',
        'attribute_options'=>'Attribute Options',
        'attribute_options_help'=>'Define the values you want to offer when entering colors. You can add or remove options at any time.',
        'help_attribute_examples'=>'For example, use Surface with Gloss, Satin, and Matte; Material with Cotton, Linen, and Silk; or Texture with Smooth and Brushed. The feature is not limited to paint or finishing types.',
        'attribute_none'=>'No Attribute',
        'add_option'=>'Add Option',
        'remove_option'=>'Remove',
        'attribute_placeholder'=>'e.g. Surface, Material, Texture',
        'attribute_value_placeholder'=>'e.g. Matte, Gloss, Cotton',
        'attribute'=>'Attribute',
        'attribute_label_text'=>'Attribute Label',
        'delete'=>'Delete',
        'upload_sample'=>'Upload Sample Image',
        'remove_image'=>'Remove Image',
        'choose_sample'=>'Choose Sample Image',
        'select'=>'Select',
        'add_color'=>'Add New Color',
        'colors_group_box'=>'Color Group Colors',
        'group_settings_box'=>'Group Settings',
        'import_colors_box'=>'Import Colors',
        'security_help'=>'10. Security and Import Limits',
        'security_help_intro'=>'Imports are secured with permission and file validation. Current limits: CSV up to 5MB, XLSX up to 10MB, ZIP up to 25MB, up to 10,000 colors, image validation, unsafe ZIP path protection, and abnormal compression-ratio protection.',
        'security_b1'=>'Import requires permission to edit the group itself.',
        'security_b2'=>'Unsupported extensions and files whose content does not match their extension are rejected.',
        'security_b3'=>'ZIP files are checked for unsafe paths, oversized files, and abnormal compression ratios.',
        'security_b4'=>'Image files are validated to ensure they are real supported images.',
        'security_b5'=>'Color data is sanitized and validated before saving.',
        'security_not_allowed'=>'You are not allowed to perform this import.',
        'security_group'=>'You are not allowed to edit this group.',
        'security_no_file'=>'No file was selected.',
        'security_bad_upload'=>'The import file is invalid.',
        'security_upload_failed'=>'The import file upload failed.',
        'security_upload_source'=>'The import file source is invalid.',
        'security_file_type'=>'Only CSV, XLSX, or ZIP files are supported.',
        'security_file_size'=>'The import file exceeds the allowed size.',
        'security_file_mime'=>'The file type does not match its extension.',
        'security_zip_unavailable'=>'ZipArchive is not available on the server.',
        'security_zip_open'=>'The ZIP file could not be opened.',
        'security_zip_path'=>'The ZIP contains an unsafe file path.',
        'security_zip_bomb'=>'The uncompressed ZIP size exceeds the allowed limit.',
        'security_zip_ratio'=>'The ZIP was rejected because of an abnormal compression ratio.',
        'security_zip_files'=>'The number of files inside the ZIP exceeds the allowed limit.',
        'security_zip_entry_size'=>'A file inside the ZIP exceeds the allowed size.',
        'security_headers'=>'The table must contain at least the name and hex columns.',
        'security_rows'=>'The number of rows exceeds the allowed limit.',
        'security_columns'=>'The number of columns exceeds the allowed limit.',
        'security_cell'=>'A cell is larger than the allowed size.',
        'security_colors_limit'=>'The number of colors exceeds the allowed limit.',
        'security_images_limit'=>'The number of images inside the ZIP exceeds the allowed limit.',
        'security_image_type'=>'A file inside the ZIP is not a supported image.',
        'security_image_size'=>'An image inside the ZIP exceeds the allowed size.',
        'security_image_read'=>'A ZIP image could not be read.',
        'security_image_check'=>'The image type could not be verified.',
        'security_xlsx_sheet'=>'The first worksheet could not be found.',
        'security_xlsx_size'=>'The XLSX worksheet exceeds the allowed size.',
        'security_xlsx_strings'=>'The number of strings inside the XLSX exceeds the allowed limit.',
        'security_xlsx_parse'=>'The XLSX file could not be read.',
        'security_extract'=>'The data file could not be safely extracted.',
        'security_zip_data'=>'No safe CSV or XLSX file was found inside the ZIP.',
        'security_data_size'=>'The data file inside the ZIP exceeds the allowed size.',
        'security_temp'=>'The ZIP file could not be safely prepared.',
        'security_no_colors'=>'No valid colors were found.',
        'search'=>'Search',
        'security_open_csv'=>'Unable to open CSV.','security_invalid_group'=>'Invalid group.',
        'selected'=>'Selected',
        'selected_count'=>'Selected color',
        'selected_count_plural'=>'Selected colors',
        'show_selected'=>'Show Selected',
        'search_placeholder'=>'Search by color name or code…',
        'all_attributes'=>'All Attributes',
        'similar'=>'Similar Colors',
        'empty'=>'Select some colors first.',
        'print'=>'Print',
        'share'=>'Share',
        'no_results'=>'No matching results.',
        'close'=>'Close',
        'selected_colors'=>'Selected Colors',
        'similar_to'=>'Similar colors to',
        'color_wheel'=>'Color Wheel',
        'color_palette'=>'Color Palette',
        'choose_color'=>'Choose a Color',
        'find_nearest'=>'Find Nearest',
        'clear_color'=>'Clear Color',
        'nearest_results'=>'Nearest Colors',
        'nearest_hint'=>'Choose a color from the wheel to display the nearest colors in the same color family.',
        'no_nearest'=>'No nearby colors were found in this color family.',
        'copy_color'=>'Copy Color',
        
        'group_required'=>'Please select a group.',
        'slug_url'=>'Slug URL',
        'attribute_values_inline'=>'attribute: any value you choose. attribute_label: optional label for the attribute, such as Surface, Material, or Finish.',
        'hex'=>'HEX',
        'shortcode_generator'=>'Shortcode Generator',
        'shortcode_generator_intro'=>'Generate a responsive shortcode for one or more color groups.',
        'select_groups'=>'Select Color Groups',
        'desktop_columns'=>'Desktop Columns',
        'tablet_columns'=>'Tablet Columns',
        'mobile_columns'=>'Mobile Columns',
        'generated_shortcode'=>'Generated Shortcode',
        'copy_shortcode'=>'Copy Shortcode',
        'shortcode_copied'=>'Shortcode copied.',
        'responsive_columns'=>'Responsive Columns',
        'choose_groups_hint'=>'Choose one or more groups. Their colors will appear together in the selected order.',
        'view_on_site'=>'View on Site',
        'open_group'=>'Open Group',
        'group_page_intro'=>'Browse this color collection on the website.',
        'no_groups_selected'=>'Please select at least one color group.',
        'show_group_title'=>'Show Group Title',
        'single_group_page'=>'Color Group Page',
        'front_page_not_found'=>'Color group page not found.',
        'group_url'=>'Group URL',
        'sort'=>'Sort',
        'sort_name_asc'=>'Name A–Z',
        'sort_name_desc'=>'Name Z–A',
        'sort_code_asc'=>'Code A–Z',
        'sort_attribute'=>'Attribute',
        'sort_hex'=>'HEX',
        'copy_hex'=>'Copy HEX',
        'copy_rgb'=>'Copy RGB',
        'copied_value'=>'Copied',
        'rgb'=>'RGB',
        'hsl'=>'HSL',
        'show_name'=>'Show Color Name',
        'show_code'=>'Show Color Code',
        'show_hex'=>'Show HEX',
        'show_rgb'=>'Show RGB',
        'show_hsl'=>'Show HSL',
        'show_attribute'=>'Show Attribute',
        'show_image'=>'Show Sample Image',
        'display_options'=>'Display Options',
        'responsive_columns_group'=>'Group Display Columns',
        'export'=>'Export',
        'export_csv'=>'Export CSV',
        'export_xlsx'=>'Export XLSX',
        'duplicate'=>'Duplicate',
        'duplicate_group'=>'Duplicate Group',
        'duplicate_created'=>'Group duplicated as a draft.',
        'import_preview'=>'Preview Import',
        'import_preview_title'=>'Import Preview',
        'import_preview_intro'=>'Review the file before importing it.',
        'valid_colors'=>'Valid colors',
        'duplicate_rows'=>'Duplicate rows',
        'existing_codes'=>'Existing codes',
        'import_mode'=>'Import Mode',
        'replace_all'=>'Replace all existing colors',
        'append_skip'=>'Append and skip existing codes',
        'append_update'=>'Append and update existing codes',
        'preview'=>'Preview',
        'confirm_import'=>'Confirm Import',
        'import_preview_failed'=>'Unable to preview the import.',
        'no_file'=>'Please choose a file first.',
        'show_hex_default'=>'Show HEX by default',
        'group_columns_help'=>'Set the default responsive columns for this group. Shortcode values override these defaults.',
        'display_options_help'=>'Choose which color information appears on the website for this group.',
        'help_display'=>'6. Display, Search, Sort, and Export',
        'help_display_intro'=>'Each group can define its responsive columns and choose which color fields are shown. Website visitors can search and sort colors, and administrators can export a group as CSV or XLSX.',
        'help_export'=>'Export a group from the group editor using Export CSV or Export XLSX. The exported columns are compatible with the import template.',
        'help_import_preview'=>'Choose Preview Import first. The plugin validates the file and reports valid rows, duplicates, and codes already present in the group before you confirm the import.',
        'help_duplicate_modes'=>'Replace all removes the current color list. Append and skip keeps existing codes unchanged. Append and update replaces matching codes and adds new ones.',
        'help_copy_values'=>'Click HEX or RGB on a color card to copy the value. RGB and HSL are calculated automatically from HEX.',
        'help_shortcodes_columns'=>'A group stores default Desktop, Tablet, and Mobile columns. Shortcode attributes desktop, tablet, and mobile override the group defaults when provided.',
        'help_search_extended'=>'Search also matches RGB, HSL, and Attribute values.',
        'help_sort_extended'=>'Sort by name, code, attribute, or HEX.',
        'help_import_preview_title'=>'9. Import Preview and Duplicate Handling',
        'help_polylang_title'=>'10. Translation with Polylang',
        'help_common_errors_title'=>'11. Common Errors',
        'collection_listing'=>'Collection Listing',
        'collection_listing_intro'=>'Display your published color groups as a responsive collection list.',
        'collection_shape'=>'Collection Shape',
        'shape_rectangle'=>'Rectangle',
        'shape_square'=>'Square',
        'shape_circle'=>'Circle',
        'shape_compact'=>'Compact',
        'shape_help'=>'Choose how each collection card is shaped in the Collection Listing shortcode. Compact uses a dense horizontal card layout.',
        'collection_options'=>'Collection Options',
        'color_shape'=>'Color Display Shape',
        'color_shape_help'=>'Choose the default shape for color cards in this group. The Shortcode can override it.',
        'group_shortcode_generator'=>'Group Shortcode Generator',
        'group_shortcode_generator_help'=>'Generate a shortcode for this group and choose the color shape and responsive columns.',
                'show_count'=>'Show Color Count',
        'show_description'=>'Show Description',
        'show_featured_image'=>'Show Featured Image',
        'collection_shortcode'=>'Collection Shortcode',
        'published'=>'Published',
        'draft'=>'Draft',
        'status'=>'Status',
        'colors_count'=>'%s colors',
        'view_collection'=>'View Collection',
        'collection_required'=>'No published color groups are available.',
        'help_collections_title'=>'2.5. Collection Listing',
        'help_collections_intro'=>'Use the Collection Listing shortcode to display published color groups. Draft groups are not shown on the public collection listing. Each collection can optionally use its featured image as a cover at the top of the card.',
        'help_collection_shortcode'=>'Collection Shortcode',
        'help_group_shape'=>'A group can use Rectangle, Square, Circle, or Compact color cards. The group setting is the default; the Shortcode can override it with shape="rectangle", shape="square", shape="circle", or shape="compact".',
        'help_status'=>'Group status follows the standard WordPress Draft/Published workflow. Only published groups are publicly accessible and included in Collection Listing.',
        'help_collection_shape'=>'The Collection Listing shortcode supports rectangle, square, circle, and compact card shapes.',
        'help_page_title'=>'Group Page',
        'help_table_text'=>'Text',
        'help_table_color_name'=>'Color Name',
        'help_table_commercial_code'=>'Commercial Color Code',
        'help_table_color_value'=>'Color Value',
        'help_table_optional_attribute'=>'Optional value for the group attribute',
        'help_table_attribute_label'=>'Optional label for the attribute; the first non-empty value is used for the group',
        'help_table_sample_image'=>'Sample image; can be matched from ZIP',
        'help_table_image_type'=>'Image URL / Filename',
        'help_required'=>'Required',
        'help_group_defaults'=>'Group defaults',
        'help_true'=>'true',
        'help_show_group_title'=>'Show group title.',
        'help_show_search'=>'Show search and color-family search.',
        'help_show_filter'=>'Show the group attribute filter when configured.',
        'help_show_sort'=>'Show sorting controls.',
        'help_show_similar'=>'Show similar colors.',
        'help_one_group_or_many'=>'One group code or comma-separated group codes.',
        'help_responsive_columns'=>'Responsive columns. Explicit shortcode values override group settings.',
        'help_color_card_shape'=>'Color card shape: rectangle, square, circle, or compact.',
        'help_show_color_name'=>'Show color name.',
        'help_show_color_code'=>'Show color code.',
        'help_show_copy_hex'=>'Show and copy HEX.',
        'help_show_copy_rgb'=>'Show and copy RGB calculated from HEX.',
        'help_show_hsl'=>'Show HSL calculated from HEX.',
        'help_show_attribute_value'=>'Show the group attribute value.',
        'help_show_image_overlay'=>'Show the sample image overlay.',
        'help_page_intro'=>'The public group page uses the color shape, responsive columns, display options, attribute filter, search, and sorting settings.',
    ];
    return $strings;
}
function kashida_colors_showcase_register_polylang_strings(){
    if(!function_exists('pll_register_string')) return;
    foreach(kashida_colors_showcase_translation_strings() as $key=>$default){ pll_register_string('kashida_colors_showcase_'.$key,$default,'Kashida Color Library',false); }
}
add_action('init','kashida_colors_showcase_register_polylang_strings',5);
function kashida_colors_showcase_t($key,$default=''){
    $strings = kashida_colors_showcase_translation_strings();
    $source = $strings[$key] ?? $default;
    if (function_exists('pll__')) {
        $translated = pll__($source);
        if ($translated !== $source) { return $translated; }
    }
    if ($key === 'menu_name') {
        return _x('Kashida Color Library', 'kcs_menu_name', 'kashida-color-library');
    }
    switch ($key) {
        case 'groups_name':
            return __('Color Groups', 'kashida-color-library');
        case 'group_singular':
            return __('Color Group', 'kashida-color-library');
        case 'add_new':
            return __('Add Group', 'kashida-color-library');
        case 'add_new_item':
            return __('Add New Group', 'kashida-color-library');
        case 'edit_item':
        case 'edit_group':
            return __('Edit Group', 'kashida-color-library');
        case 'new_item':
            return __('New Group', 'kashida-color-library');
        case 'view_item':
        case 'view_group':
            return __('View Group', 'kashida-color-library');
        case 'search_items':
            return __('Search Groups', 'kashida-color-library');
        case 'not_found':
            return __('No groups found', 'kashida-color-library');
        case 'group_code_column':
        case 'group_code':
            return __('Group Code', 'kashida-color-library');
        case 'shortcode_column':
        case 'shortcode_label':
            return __('Shortcode', 'kashida-color-library');
        case 'view_column':
            return __('View', 'kashida-color-library');
        case 'copy':
            return __('Copy', 'kashida-color-library');
        case 'copied':
        case 'copied_value':
            return __('Copied', 'kashida-color-library');
        case 'preview_page_title':
        case 'preview_menu':
            return __('Color Group Preview', 'kashida-color-library');
        case 'not_allowed':
            return __('You are not allowed to view this page.', 'kashida-color-library');
        case 'group_not_found':
            return __('Group not found.', 'kashida-color-library');
        case 'not_allowed_group':
            return __('You are not allowed to view this group.', 'kashida-color-library');
        case 'preview_group_label':
            return __('View Group: ', 'kashida-color-library');
        case 'preview_description':
            return __('Preview the colors as they will appear on the website.', 'kashida-color-library');
        case 'help':
            return __('Help', 'kashida-color-library');
        case 'no_colors_group':
            return __('There are no colors in this group yet.', 'kashida-color-library');
        case 'settings':
            return __('Settings', 'kashida-color-library');
        case 'settings_title':
            return __('Kashida Color Library — Settings', 'kashida-color-library');
        case 'settings_intro':
            return __('No operational settings are currently required. Use the Help page to manage color groups, imports, and the Shortcode.', 'kashida-color-library');
        case 'settings_help':
            return __('Open Help', 'kashida-color-library');
        case 'plugin_info':
            return __('Plugin Information', 'kashida-color-library');
        case 'plugin_name':
            return __('Kashida Color Library', 'kashida-color-library');
        case 'plugin_name_label':
            return __('Plugin Name', 'kashida-color-library');
        case 'version':
            return __('Version', 'kashida-color-library');
        case 'author':
            return __('Author', 'kashida-color-library');
        case 'website':
            return __('Website', 'kashida-color-library');
        case 'help_title':
            return __('Kashida Color Library Help', 'kashida-color-library');
        case 'help_intro':
            return __('Guide to managing color groups, entering and importing color data, defining a custom attribute for each group, preparing the color table, and using the Shortcode to display colors on your website.', 'kashida-color-library');
        case 'help_create_group':
            return __('1. Create a Group', 'kashida-color-library');
        case 'help_create_1':
            return __('Open Kashida Color Library → Add Group.', 'kashida-color-library');
        case 'help_create_2':
            return __('Enter the group name and optionally add a description and featured image.', 'kashida-color-library');
        case 'help_create_3':
            return __('Save the group; a fixed code such as KCS-0001 will be generated automatically.', 'kashida-color-library');
        case 'help_code_note':
            return __('The group code does not depend on the group name and does not change when the name changes. It is used in the Shortcode and URL.', 'kashida-color-library');
        case 'help_manual_colors':
            return __('2. Add Colors Manually', 'kashida-color-library');
        case 'help_manual_1':
            return __('Open the required group.', 'kashida-color-library');
        case 'help_manual_2':
            return __('In the Color Group box, click Add New Color.', 'kashida-color-library');
        case 'help_manual_3':
            return __('Enter the color name, code, HEX value, optional attribute value, and sample image if needed.', 'kashida-color-library');
        case 'help_manual_4':
            return __('Save the group.', 'kashida-color-library');
        case 'help_import':
            return __('3. Import Colors and Supporting Files', 'kashida-color-library');
        case 'help_import_intro':
            return __('Colors can be imported from CSV or XLSX, and ZIP can be used when sample images need to be included with the data file.', 'kashida-color-library');
        case 'column':
            return __('Column', 'kashida-color-library');
        case 'type':
            return __('Type', 'kashida-color-library');
        case 'description':
            return __('Description', 'kashida-color-library');
        case 'example':
            return __('Example', 'kashida-color-library');
        case 'attribute_values':
            return __('Attribute values', 'kashida-color-library');
        case 'help_import_zip':
            return __('When using ZIP, place a CSV or XLSX file inside the archive. PNG/JPG/WebP/GIF images can be placed at the same level. The plugin matches image names to the color name or color code.', 'kashida-color-library');
        case 'download_csv':
            return __('Download CSV Template', 'kashida-color-library');
        case 'download_xlsx':
            return __('Download XLSX Template', 'kashida-color-library');
        case 'download_zip':
            return __('Download ZIP Template with Images', 'kashida-color-library');
        case 'help_table':
            return __('4. Prepare the Color Table', 'kashida-color-library');
        case 'help_table_intro':
            return __('The first row must contain column headers, and each following row represents one color. Do not merge cells or add extra headings above the first row.', 'kashida-color-library');
        case 'help_table_note':
            return __('In CSV, use commas as the column separator. In XLSX, the first worksheet is used. HEX values must be valid, and sample_image may be left empty when no image is available.', 'kashida-color-library');
        case 'help_shortcode':
            return __('5. Shortcode', 'kashida-color-library');
        case 'help_shortcode_intro':
            return __('Copy the Shortcode from the Shortcode column in the groups table or from the group settings, then paste it into a page or post.', 'kashida-color-library');
        case 'help_shortcode_options':
            return __('Shortcode Options', 'kashida-color-library');
        case 'attribute':
        case 'attribute_label':
        case 'sort_attribute':
            return __('Attribute', 'kashida-color-library');
        case 'default_value':
            return __('Default Value', 'kashida-color-library');
        case 'function':
            return __('Function', 'kashida-color-library');
        case 'help_color_search':
            return __('6. Find a Nearby Color', 'kashida-color-library');
        case 'help_color_search_intro':
            return __('When search is enabled, the color wheel is displayed. Choose a color from the wheel and click Find Nearest. The plugin displays the nearest colors in the same color family, ordered by proximity.', 'kashida-color-library');
        case 'help_color_search_note':
            return __('The color-wheel search is independent of text search. Clear the selected color to return to all results.', 'kashida-color-library');
        case 'help_front_features':
            return __('7. Display Features', 'kashida-color-library');
        case 'help_feature_1':
            return __('Search by name, code, or HEX.', 'kashida-color-library');
        case 'help_feature_2':
            return __('Filter by the group attribute when an attribute is configured.', 'kashida-color-library');
        case 'help_feature_3':
            return __('Similar colors.', 'kashida-color-library');
        case 'help_feature_4':
            return __('Select multiple colors, then copy, print, or share them.', 'kashida-color-library');
        case 'help_translation':
            return __('8. Translation with Polylang', 'kashida-color-library');
        case 'help_translation_intro':
            return __('All interface strings are prepared for translation with Polylang. You can translate each label, button, field, and message from Languages → String translations.', 'kashida-color-library');
        case 'help_errors':
            return __('9. Common Errors', 'kashida-color-library');
        case 'help_error_1':
            return __('Group not found: make sure you are using the correct group code, such as KCS-0001.', 'kashida-color-library');
        case 'help_error_2':
            return __('The import contains no valid colors: check name and hex in the file.', 'kashida-color-library');
        case 'help_error_3':
            return __('ZIP images do not appear: make sure the image filename matches the color name or code and uses a supported extension.', 'kashida-color-library');
        case 'help_error_4':
            return __('Do not use the group name in the Shortcode; use the group code.', 'kashida-color-library');
        case 'activate_group':
            return __('Activate Group', 'kashida-color-library');
        case 'group_code_fixed':
            return __('This code is fixed and does not change when the group name changes.', 'kashida-color-library');
        case 'import_formats':
            return __('CSV or XLSX. Columns: name, code, hex, attribute, attribute_label, sample_image.', 'kashida-color-library');
        case 'sample_image_hint':
            return __('sample_image: optional image URL. A filename can also be matched when importing ZIP.', 'kashida-color-library');
        case 'choose_import_file':
            return __('Choose CSV, XLSX, or ZIP', 'kashida-color-library');
        case 'import_colors':
        case 'import_colors_box':
            return __('Import Colors', 'kashida-color-library');
        case 'importing':
            return __('Importing…', 'kashida-color-library');
        case 'import_success':
            return __('Imported', 'kashida-color-library');
        case 'colors_word':
            return __('colors. The page will refresh…', 'kashida-color-library');
        case 'import_failed':
            return __('Import failed', 'kashida-color-library');
        case 'color':
            return __('Color', 'kashida-color-library');
        case 'color_name':
        case 'help_table_color_name':
            return __('Color Name', 'kashida-color-library');
        case 'color_code':
            return __('Color Code', 'kashida-color-library');
        case 'attribute_name_label':
            return __('Attribute Name', 'kashida-color-library');
        case 'attribute_options':
            return __('Attribute Options', 'kashida-color-library');
        case 'attribute_options_help':
            return __('Define the values you want to offer when entering colors. You can add or remove options at any time.', 'kashida-color-library');
        case 'help_attribute_examples':
            return __('For example, use Surface with Gloss, Satin, and Matte; Material with Cotton, Linen, and Silk; or Texture with Smooth and Brushed. The feature is not limited to paint or finishing types.', 'kashida-color-library');
        case 'attribute_none':
            return __('No Attribute', 'kashida-color-library');
        case 'add_option':
            return __('Add Option', 'kashida-color-library');
        case 'remove_option':
            return __('Remove', 'kashida-color-library');
        case 'attribute_placeholder':
            return __('e.g. Surface, Material, Texture', 'kashida-color-library');
        case 'attribute_value_placeholder':
            return __('e.g. Matte, Gloss, Cotton', 'kashida-color-library');
        case 'attribute_label_text':
            return __('Attribute Label', 'kashida-color-library');
        case 'delete':
            return __('Delete', 'kashida-color-library');
        case 'upload_sample':
            return __('Upload Sample Image', 'kashida-color-library');
        case 'remove_image':
            return __('Remove Image', 'kashida-color-library');
        case 'choose_sample':
            return __('Choose Sample Image', 'kashida-color-library');
        case 'select':
            return __('Select', 'kashida-color-library');
        case 'add_color':
            return __('Add New Color', 'kashida-color-library');
        case 'colors_group_box':
            return __('Color Group Colors', 'kashida-color-library');
        case 'group_settings_box':
            return __('Group Settings', 'kashida-color-library');
        case 'security_help':
            return __('10. Security and Import Limits', 'kashida-color-library');
        case 'security_help_intro':
            return __('Imports are secured with permission and file validation. Current limits: CSV up to 5MB, XLSX up to 10MB, ZIP up to 25MB, up to 10,000 colors, image validation, unsafe ZIP path protection, and abnormal compression-ratio protection.', 'kashida-color-library');
        case 'security_b1':
            return __('Import requires permission to edit the group itself.', 'kashida-color-library');
        case 'security_b2':
            return __('Unsupported extensions and files whose content does not match their extension are rejected.', 'kashida-color-library');
        case 'security_b3':
            return __('ZIP files are checked for unsafe paths, oversized files, and abnormal compression ratios.', 'kashida-color-library');
        case 'security_b4':
            return __('Image files are validated to ensure they are real supported images.', 'kashida-color-library');
        case 'security_b5':
            return __('Color data is sanitized and validated before saving.', 'kashida-color-library');
        case 'security_not_allowed':
            return __('You are not allowed to perform this import.', 'kashida-color-library');
        case 'security_group':
            return __('You are not allowed to edit this group.', 'kashida-color-library');
        case 'security_no_file':
            return __('No file was selected.', 'kashida-color-library');
        case 'security_bad_upload':
            return __('The import file is invalid.', 'kashida-color-library');
        case 'security_upload_failed':
            return __('The import file upload failed.', 'kashida-color-library');
        case 'security_upload_source':
            return __('The import file source is invalid.', 'kashida-color-library');
        case 'security_file_type':
            return __('Only CSV, XLSX, or ZIP files are supported.', 'kashida-color-library');
        case 'security_file_size':
            return __('The import file exceeds the allowed size.', 'kashida-color-library');
        case 'security_file_mime':
            return __('The file type does not match its extension.', 'kashida-color-library');
        case 'security_zip_unavailable':
            return __('ZipArchive is not available on the server.', 'kashida-color-library');
        case 'security_zip_open':
            return __('The ZIP file could not be opened.', 'kashida-color-library');
        case 'security_zip_path':
            return __('The ZIP contains an unsafe file path.', 'kashida-color-library');
        case 'security_zip_bomb':
            return __('The uncompressed ZIP size exceeds the allowed limit.', 'kashida-color-library');
        case 'security_zip_ratio':
            return __('The ZIP was rejected because of an abnormal compression ratio.', 'kashida-color-library');
        case 'security_zip_files':
            return __('The number of files inside the ZIP exceeds the allowed limit.', 'kashida-color-library');
        case 'security_zip_entry_size':
            return __('A file inside the ZIP exceeds the allowed size.', 'kashida-color-library');
        case 'security_headers':
            return __('The table must contain at least the name and hex columns.', 'kashida-color-library');
        case 'security_rows':
            return __('The number of rows exceeds the allowed limit.', 'kashida-color-library');
        case 'security_columns':
            return __('The number of columns exceeds the allowed limit.', 'kashida-color-library');
        case 'security_cell':
            return __('A cell is larger than the allowed size.', 'kashida-color-library');
        case 'security_colors_limit':
            return __('The number of colors exceeds the allowed limit.', 'kashida-color-library');
        case 'security_images_limit':
            return __('The number of images inside the ZIP exceeds the allowed limit.', 'kashida-color-library');
        case 'security_image_type':
            return __('A file inside the ZIP is not a supported image.', 'kashida-color-library');
        case 'security_image_size':
            return __('An image inside the ZIP exceeds the allowed size.', 'kashida-color-library');
        case 'security_image_read':
            return __('A ZIP image could not be read.', 'kashida-color-library');
        case 'security_image_check':
            return __('The image type could not be verified.', 'kashida-color-library');
        case 'security_xlsx_sheet':
            return __('The first worksheet could not be found.', 'kashida-color-library');
        case 'security_xlsx_size':
            return __('The XLSX worksheet exceeds the allowed size.', 'kashida-color-library');
        case 'security_xlsx_strings':
            return __('The number of strings inside the XLSX exceeds the allowed limit.', 'kashida-color-library');
        case 'security_xlsx_parse':
            return __('The XLSX file could not be read.', 'kashida-color-library');
        case 'security_extract':
            return __('The data file could not be safely extracted.', 'kashida-color-library');
        case 'security_zip_data':
            return __('No safe CSV or XLSX file was found inside the ZIP.', 'kashida-color-library');
        case 'security_data_size':
            return __('The data file inside the ZIP exceeds the allowed size.', 'kashida-color-library');
        case 'security_temp':
            return __('The ZIP file could not be safely prepared.', 'kashida-color-library');
        case 'security_no_colors':
            return __('No valid colors were found.', 'kashida-color-library');
        case 'search':
            return __('Search', 'kashida-color-library');
        case 'security_open_csv':
            return __('Unable to open CSV.', 'kashida-color-library');
        case 'security_invalid_group':
            return __('Invalid group.', 'kashida-color-library');
        case 'selected':
            return __('Selected', 'kashida-color-library');
        case 'selected_count':
            return __('Selected color', 'kashida-color-library');
        case 'selected_count_plural':
            return __('Selected colors', 'kashida-color-library');
        case 'show_selected':
            return __('Show Selected', 'kashida-color-library');
        case 'search_placeholder':
            return __('Search by color name or code…', 'kashida-color-library');
        case 'all_attributes':
            return __('All Attributes', 'kashida-color-library');
        case 'similar':
            return __('Similar Colors', 'kashida-color-library');
        case 'empty':
            return __('Select some colors first.', 'kashida-color-library');
        case 'print':
            return __('Print', 'kashida-color-library');
        case 'share':
            return __('Share', 'kashida-color-library');
        case 'no_results':
            return __('No matching results.', 'kashida-color-library');
        case 'close':
            return __('Close', 'kashida-color-library');
        case 'selected_colors':
            return __('Selected Colors', 'kashida-color-library');
        case 'similar_to':
            return __('Similar colors to', 'kashida-color-library');
        case 'color_wheel':
            return __('Color Wheel', 'kashida-color-library');
        case 'color_palette':
            return __('Color Palette', 'kashida-color-library');
        case 'choose_color':
            return __('Choose a Color', 'kashida-color-library');
        case 'find_nearest':
            return __('Find Nearest', 'kashida-color-library');
        case 'clear_color':
            return __('Clear Color', 'kashida-color-library');
        case 'nearest_results':
            return __('Nearest Colors', 'kashida-color-library');
        case 'nearest_hint':
            return __('Choose a color from the wheel to display the nearest colors in the same color family.', 'kashida-color-library');
        case 'no_nearest':
            return __('No nearby colors were found in this color family.', 'kashida-color-library');
        case 'copy_color':
            return __('Copy Color', 'kashida-color-library');
        case 'group_required':
            return __('Please select a group.', 'kashida-color-library');
        case 'slug_url':
            return __('Slug URL', 'kashida-color-library');
        case 'attribute_values_inline':
            return __('attribute: any value you choose. attribute_label: optional label for the attribute, such as Surface, Material, or Finish.', 'kashida-color-library');
        case 'hex':
        case 'sort_hex':
            return __('HEX', 'kashida-color-library');
        case 'shortcode_generator':
            return __('Shortcode Generator', 'kashida-color-library');
        case 'shortcode_generator_intro':
            return __('Generate a responsive shortcode for one or more color groups.', 'kashida-color-library');
        case 'select_groups':
            return __('Select Color Groups', 'kashida-color-library');
        case 'desktop_columns':
            return __('Desktop Columns', 'kashida-color-library');
        case 'tablet_columns':
            return __('Tablet Columns', 'kashida-color-library');
        case 'mobile_columns':
            return __('Mobile Columns', 'kashida-color-library');
        case 'generated_shortcode':
            return __('Generated Shortcode', 'kashida-color-library');
        case 'copy_shortcode':
            return __('Copy Shortcode', 'kashida-color-library');
        case 'shortcode_copied':
            return __('Shortcode copied.', 'kashida-color-library');
        case 'responsive_columns':
            return __('Responsive Columns', 'kashida-color-library');
        case 'choose_groups_hint':
            return __('Choose one or more groups. Their colors will appear together in the selected order.', 'kashida-color-library');
        case 'view_on_site':
            return __('View on Site', 'kashida-color-library');
        case 'open_group':
            return __('Open Group', 'kashida-color-library');
        case 'group_page_intro':
            return __('Browse this color collection on the website.', 'kashida-color-library');
        case 'no_groups_selected':
            return __('Please select at least one color group.', 'kashida-color-library');
        case 'show_group_title':
            return __('Show Group Title', 'kashida-color-library');
        case 'single_group_page':
            return __('Color Group Page', 'kashida-color-library');
        case 'front_page_not_found':
            return __('Color group page not found.', 'kashida-color-library');
        case 'group_url':
            return __('Group URL', 'kashida-color-library');
        case 'sort':
            return __('Sort', 'kashida-color-library');
        case 'sort_name_asc':
            return __('Name A–Z', 'kashida-color-library');
        case 'sort_name_desc':
            return __('Name Z–A', 'kashida-color-library');
        case 'sort_code_asc':
            return __('Code A–Z', 'kashida-color-library');
        case 'copy_hex':
            return __('Copy HEX', 'kashida-color-library');
        case 'copy_rgb':
            return __('Copy RGB', 'kashida-color-library');
        case 'rgb':
            return __('RGB', 'kashida-color-library');
        case 'hsl':
            return __('HSL', 'kashida-color-library');
        case 'show_name':
            return __('Show Color Name', 'kashida-color-library');
        case 'show_code':
            return __('Show Color Code', 'kashida-color-library');
        case 'show_hex':
            return __('Show HEX', 'kashida-color-library');
        case 'show_rgb':
            return __('Show RGB', 'kashida-color-library');
        case 'show_hsl':
            return __('Show HSL', 'kashida-color-library');
        case 'show_attribute':
            return __('Show Attribute', 'kashida-color-library');
        case 'show_image':
            return __('Show Sample Image', 'kashida-color-library');
        case 'display_options':
            return __('Display Options', 'kashida-color-library');
        case 'responsive_columns_group':
            return __('Group Display Columns', 'kashida-color-library');
        case 'export':
            return __('Export', 'kashida-color-library');
        case 'export_csv':
            return __('Export CSV', 'kashida-color-library');
        case 'export_xlsx':
            return __('Export XLSX', 'kashida-color-library');
        case 'duplicate':
            return __('Duplicate', 'kashida-color-library');
        case 'duplicate_group':
            return __('Duplicate Group', 'kashida-color-library');
        case 'duplicate_created':
            return __('Group duplicated as a draft.', 'kashida-color-library');
        case 'import_preview':
            return __('Preview Import', 'kashida-color-library');
        case 'import_preview_title':
            return __('Import Preview', 'kashida-color-library');
        case 'import_preview_intro':
            return __('Review the file before importing it.', 'kashida-color-library');
        case 'valid_colors':
            return __('Valid colors', 'kashida-color-library');
        case 'duplicate_rows':
            return __('Duplicate rows', 'kashida-color-library');
        case 'existing_codes':
            return __('Existing codes', 'kashida-color-library');
        case 'import_mode':
            return __('Import Mode', 'kashida-color-library');
        case 'replace_all':
            return __('Replace all existing colors', 'kashida-color-library');
        case 'append_skip':
            return __('Append and skip existing codes', 'kashida-color-library');
        case 'append_update':
            return __('Append and update existing codes', 'kashida-color-library');
        case 'preview':
            return __('Preview', 'kashida-color-library');
        case 'confirm_import':
            return __('Confirm Import', 'kashida-color-library');
        case 'import_preview_failed':
            return __('Unable to preview the import.', 'kashida-color-library');
        case 'no_file':
            return __('Please choose a file first.', 'kashida-color-library');
        case 'show_hex_default':
            return __('Show HEX by default', 'kashida-color-library');
        case 'group_columns_help':
            return __('Set the default responsive columns for this group. Shortcode values override these defaults.', 'kashida-color-library');
        case 'display_options_help':
            return __('Choose which color information appears on the website for this group.', 'kashida-color-library');
        case 'help_display':
            return __('6. Display, Search, Sort, and Export', 'kashida-color-library');
        case 'help_display_intro':
            return __('Each group can define its responsive columns and choose which color fields are shown. Website visitors can search and sort colors, and administrators can export a group as CSV or XLSX.', 'kashida-color-library');
        case 'help_export':
            return __('Export a group from the group editor using Export CSV or Export XLSX. The exported columns are compatible with the import template.', 'kashida-color-library');
        case 'help_import_preview':
            return __('Choose Preview Import first. The plugin validates the file and reports valid rows, duplicates, and codes already present in the group before you confirm the import.', 'kashida-color-library');
        case 'help_duplicate_modes':
            return __('Replace all removes the current color list. Append and skip keeps existing codes unchanged. Append and update replaces matching codes and adds new ones.', 'kashida-color-library');
        case 'help_copy_values':
            return __('Click HEX or RGB on a color card to copy the value. RGB and HSL are calculated automatically from HEX.', 'kashida-color-library');
        case 'help_shortcodes_columns':
            return __('A group stores default Desktop, Tablet, and Mobile columns. Shortcode attributes desktop, tablet, and mobile override the group defaults when provided.', 'kashida-color-library');
        case 'help_search_extended':
            return __('Search also matches RGB, HSL, and Attribute values.', 'kashida-color-library');
        case 'help_sort_extended':
            return __('Sort by name, code, attribute, or HEX.', 'kashida-color-library');
        case 'help_import_preview_title':
            return __('9. Import Preview and Duplicate Handling', 'kashida-color-library');
        case 'help_polylang_title':
            return __('10. Translation with Polylang', 'kashida-color-library');
        case 'help_common_errors_title':
            return __('11. Common Errors', 'kashida-color-library');
        case 'collection_listing':
            return __('Collection Listing', 'kashida-color-library');
        case 'collection_listing_intro':
            return __('Display your published color groups as a responsive collection list.', 'kashida-color-library');
        case 'collection_shape':
            return __('Collection Shape', 'kashida-color-library');
        case 'shape_rectangle':
            return __('Rectangle', 'kashida-color-library');
        case 'shape_square':
            return __('Square', 'kashida-color-library');
        case 'shape_circle':
            return __('Circle', 'kashida-color-library');
        case 'shape_compact':
            return __('Compact', 'kashida-color-library');
        case 'shape_help':
            return __('Choose how each collection card is shaped in the Collection Listing shortcode. Compact uses a dense horizontal card layout.', 'kashida-color-library');
        case 'collection_options':
            return __('Collection Options', 'kashida-color-library');
        case 'color_shape':
            return __('Color Display Shape', 'kashida-color-library');
        case 'color_shape_help':
            return __('Choose the default shape for color cards in this group. The Shortcode can override it.', 'kashida-color-library');
        case 'group_shortcode_generator':
            return __('Group Shortcode Generator', 'kashida-color-library');
        case 'group_shortcode_generator_help':
            return __('Generate a shortcode for this group and choose the color shape and responsive columns.', 'kashida-color-library');
        case 'show_count':
            return __('Show Color Count', 'kashida-color-library');
        case 'show_description':
            return __('Show Description', 'kashida-color-library');
        case 'show_featured_image':
            return __('Show Featured Image', 'kashida-color-library');
        case 'collection_shortcode':
        case 'help_collection_shortcode':
            return __('Collection Shortcode', 'kashida-color-library');
        case 'published':
            return __('Published', 'kashida-color-library');
        case 'draft':
            return __('Draft', 'kashida-color-library');
        case 'status':
            return __('Status', 'kashida-color-library');
        case 'colors_count':
            // translators: %s is the localized number of colors in a collection.
            return __('%s colors', 'kashida-color-library');
        case 'view_collection':
            return __('View Collection', 'kashida-color-library');
        case 'collection_required':
            return __('No published color groups are available.', 'kashida-color-library');
        case 'help_collections_title':
            return __('2.5. Collection Listing', 'kashida-color-library');
        case 'help_collections_intro':
            return __('Use the Collection Listing shortcode to display published color groups. Draft groups are not shown on the public collection listing. Each collection can optionally use its featured image as a cover at the top of the card.', 'kashida-color-library');
        case 'help_group_shape':
            return __('A group can use Rectangle, Square, Circle, or Compact color cards. The group setting is the default; the Shortcode can override it with shape="rectangle", shape="square", shape="circle", or shape="compact".', 'kashida-color-library');
        case 'help_status':
            return __('Group status follows the standard WordPress Draft/Published workflow. Only published groups are publicly accessible and included in Collection Listing.', 'kashida-color-library');
        case 'help_collection_shape':
            return __('The Collection Listing shortcode supports rectangle, square, circle, and compact card shapes.', 'kashida-color-library');
        case 'help_page_title':
            return __('Group Page', 'kashida-color-library');
        case 'help_table_text':
            return __('Text', 'kashida-color-library');
        case 'help_table_commercial_code':
            return __('Commercial Color Code', 'kashida-color-library');
        case 'help_table_color_value':
            return __('Color Value', 'kashida-color-library');
        case 'help_table_optional_attribute':
            return __('Optional value for the group attribute', 'kashida-color-library');
        case 'help_table_attribute_label':
            return __('Optional label for the attribute; the first non-empty value is used for the group', 'kashida-color-library');
        case 'help_table_sample_image':
            return __('Sample image; can be matched from ZIP', 'kashida-color-library');
        case 'help_table_image_type':
            return __('Image URL / Filename', 'kashida-color-library');
        case 'help_required':
            return __('Required', 'kashida-color-library');
        case 'help_group_defaults':
            return __('Group defaults', 'kashida-color-library');
        case 'help_true':
            return __('true', 'kashida-color-library');
        case 'help_show_group_title':
            return __('Show group title.', 'kashida-color-library');
        case 'help_show_search':
            return __('Show search and color-family search.', 'kashida-color-library');
        case 'help_show_filter':
            return __('Show the group attribute filter when configured.', 'kashida-color-library');
        case 'help_show_sort':
            return __('Show sorting controls.', 'kashida-color-library');
        case 'help_show_similar':
            return __('Show similar colors.', 'kashida-color-library');
        case 'help_one_group_or_many':
            return __('One group code or comma-separated group codes.', 'kashida-color-library');
        case 'help_responsive_columns':
            return __('Responsive columns. Explicit shortcode values override group settings.', 'kashida-color-library');
        case 'help_color_card_shape':
            return __('Color card shape: rectangle, square, circle, or compact.', 'kashida-color-library');
        case 'help_show_color_name':
            return __('Show color name.', 'kashida-color-library');
        case 'help_show_color_code':
            return __('Show color code.', 'kashida-color-library');
        case 'help_show_copy_hex':
            return __('Show and copy HEX.', 'kashida-color-library');
        case 'help_show_copy_rgb':
            return __('Show and copy RGB calculated from HEX.', 'kashida-color-library');
        case 'help_show_hsl':
            return __('Show HSL calculated from HEX.', 'kashida-color-library');
        case 'help_show_attribute_value':
            return __('Show the group attribute value.', 'kashida-color-library');
        case 'help_show_image_overlay':
            return __('Show the sample image overlay.', 'kashida-color-library');
        case 'help_page_intro':
            return __('The public group page uses the color shape, responsive columns, display options, attribute filter, search, and sorting settings.', 'kashida-color-library');
        default:
            return $default !== '' ? $default : $source;
    }
}
function kashida_colors_showcase_group_code($post_id){
    $post_id=absint($post_id);
    if(!$post_id) return '';
    $code=get_post_meta($post_id,'_kashida_colors_showcase_group_code',true);
    if(!$code){
        $code='KCS-'.str_pad((string)$post_id,4,'0',STR_PAD_LEFT);
        update_post_meta($post_id,'_kashida_colors_showcase_group_code',$code);
    }
    return $code;
}
function kashida_colors_showcase_group_slug($post_id){
    return sanitize_title(kashida_colors_showcase_group_code($post_id));
}
function kashida_colors_showcase_find_group($value){
    $value=trim((string)$value);
    if($value==='') return null;
    $slug=sanitize_title($value);
    $post=get_page_by_path($slug,OBJECT,'kashida_color_group');
    if($post) return $post;
    $q=new WP_Query(['post_type'=>'kashida_color_group','post_status'=>'any','posts_per_page'=>1,'fields'=>'all','meta_query'=>[['key'=>'_kashida_colors_showcase_group_code','value'=>$value,'compare'=>'=']]]);
    return $q->have_posts() ? $q->posts[0] : null;
}
function kashida_colors_showcase_migrate_group_identities(){
    if(!is_admin() || get_option('kashida_colors_showcase_group_identity_version')===KASHIDA_COLORS_SHOWCASE_VERSION) return;
    $ids=get_posts(['post_type'=>'kashida_color_group','post_status'=>'any','posts_per_page'=>-1,'fields'=>'ids','no_found_rows'=>true]);
    $changed=false;
    foreach($ids as $id){
        $code=kashida_colors_showcase_group_code($id);
        $slug=sanitize_title($code);
        if(get_post_field('post_name',$id)!==$slug){
            remove_action('save_post_kashida_color_group','kashida_colors_showcase_save_group_data',10);
            wp_update_post(['ID'=>$id,'post_name'=>$slug]);
            add_action('save_post_kashida_color_group','kashida_colors_showcase_save_group_data',10,1);
            $changed=true;
        }
    }
    update_option('kashida_colors_showcase_group_identity_version',KASHIDA_COLORS_SHOWCASE_VERSION,false);
    if($changed) flush_rewrite_rules(false);
}
function kashida_colors_showcase_refresh_rewrite_rules_for_version(){
    $option='kashida_colors_showcase_rewrite_version';
    if(get_option($option)!==KASHIDA_COLORS_SHOWCASE_VERSION){
        flush_rewrite_rules(false);
        update_option($option,KASHIDA_COLORS_SHOWCASE_VERSION,false);
    }
}
add_action('init','kashida_colors_showcase_refresh_rewrite_rules_for_version',30);

add_action('init','kashida_colors_showcase_register_color_group_post_type',5);
add_action('init','kashida_colors_showcase_migrate_group_identities',20);

add_filter('single_template',function($template){
    if(is_singular('kashida_color_group')){
        $plugin_template=KASHIDA_COLORS_SHOWCASE_DIR.'templates/single-kashida_color_group.php';
        if(file_exists($plugin_template)) return $plugin_template;
    }
    return $template;
});

add_filter('post_row_actions',function($actions,$post){
    if($post->post_type==='kashida_color_group' && current_user_can('edit_post',$post->ID)){
        $url=wp_nonce_url(admin_url('admin-post.php?action=kashida_colors_showcase_duplicate_group&post_id='.$post->ID),'kashida_colors_showcase_duplicate_group_'.$post->ID);
        $actions['kcs_duplicate']='<a href="'.esc_url($url).'">'.esc_html(kashida_colors_showcase_t('duplicate','Duplicate')).'</a>';
    }
    return $actions;
},10,2);
add_action('admin_post_kashida_colors_showcase_duplicate_group',function(){
    $id=absint(wp_unslash($_GET['post_id']??0));check_admin_referer('kashida_colors_showcase_duplicate_group_'.$id);if(!$id||get_post_type($id)!=='kashida_color_group'||!current_user_can('edit_post',$id))wp_die(esc_html(kashida_colors_showcase_t('not_allowed','You are not allowed to perform this action.')));
    $src=get_post($id);$new=wp_insert_post(['post_type'=>'kashida_color_group','post_status'=>'draft','post_title'=>$src->post_title.' — Copy','post_content'=>$src->post_content,'post_excerpt'=>$src->post_excerpt],true);if(is_wp_error($new))wp_die(esc_html($new->get_error_message()));
    foreach(['_kashida_colors_showcase_active','_kashida_colors_showcase_attribute_label','_kashida_colors_showcase_attribute_options','_kashida_colors_showcase_colors','_kashida_colors_showcase_desktop','_kashida_colors_showcase_tablet','_kashida_colors_showcase_mobile','_kashida_colors_showcase_show_name','_kashida_colors_showcase_show_code','_kashida_colors_showcase_show_hex','_kashida_colors_showcase_show_rgb','_kashida_colors_showcase_show_hsl','_kashida_colors_showcase_show_attribute','_kashida_colors_showcase_show_image','_kashida_colors_showcase_color_shape'] as $meta){$v=get_post_meta($id,$meta,true);if($v!==''&&$v!==[]){update_post_meta($new,$meta,$v);}}
    if($thumb=get_post_thumbnail_id($id))set_post_thumbnail($new,$thumb);
    wp_safe_redirect(admin_url('post.php?post='.$new.'&action=edit&kcs_duplicated=1'));exit;
});

add_filter('manage_kashida_color_group_posts_columns',function($columns){
    $new=[];
    foreach($columns as $key=>$label){
        $new[$key]=$label;
        if($key==='title'){
            $new['kashida_colors_showcase_group_code']=kashida_colors_showcase_t('group_code_column','Group Code');
            $new['kashida_colors_showcase_shortcode']=kashida_colors_showcase_t('shortcode_column','Shortcode');
            $new['kcs_view']=kashida_colors_showcase_t('view_column','View');
        }
    }
    return $new;
});
add_action('manage_kashida_color_group_posts_custom_column',function($column,$post_id){
    if($column==='kashida_colors_showcase_group_code'){
        echo '<code style="direction:ltr;display:inline-block;user-select:all">'.esc_html(kashida_colors_showcase_group_code($post_id)).'</code>';
    }
    if($column==='kashida_colors_showcase_shortcode'){
        $short='[paint_colors group="'.kashida_colors_showcase_group_code($post_id).'"]';
        echo '<span style="display:inline-flex;align-items:center;gap:6px;direction:ltr"><code class="kcs-admin-shortcode-code" style="user-select:all">'.esc_html($short).'</code><button type="button" class="button button-small kcs-copy-admin-shortcode" data-shortcode="'.esc_attr($short).'">'.esc_html(kashida_colors_showcase_t('copy','Copy')).'</button></span>';
    }
    if($column==='kcs_view'){
        $url=get_permalink($post_id);
        echo '<a class="button" href="'.esc_url($url).'" target="_blank" rel="noopener noreferrer">'.esc_html(kashida_colors_showcase_t('view_on_site','View on Site')).'</a>';
    }
},10,2);
add_action('admin_footer-edit.php',function(){
    $screen=get_current_screen(); if(!$screen || $screen->post_type!=='kashida_color_group') return;
    ?><script>(function(){function fallbackCopy(text){var ta=document.createElement('textarea');ta.value=text;ta.setAttribute('readonly','');ta.style.position='fixed';ta.style.top='-9999px';ta.style.left='-9999px';document.body.appendChild(ta);ta.focus();ta.select();var ok=false;try{ok=document.execCommand('copy');}catch(e){}ta.remove();return ok;}window.kashidaColorsShowcaseCopyText=function(text){text=String(text||'');if(!text)return Promise.resolve(false);if(navigator.clipboard&&typeof navigator.clipboard.writeText==='function'&&window.isSecureContext){return navigator.clipboard.writeText(text).then(function(){return true;}).catch(function(){return fallbackCopy(text);});}return Promise.resolve(fallbackCopy(text));};document.querySelectorAll('.kcs-copy-admin-shortcode').forEach(function(b){b.addEventListener('click',function(){var s=b.getAttribute('data-shortcode')||'';var done=function(){var old=b.textContent;b.textContent='<?php echo esc_js(kashida_colors_showcase_t('copied','Copied')); ?>';setTimeout(function(){b.textContent=old},1200)};window.kashidaColorsShowcaseCopyText(s).then(done);});});})();</script><?php
});

add_action('admin_menu',function(){
    add_submenu_page(
        'edit.php?post_type=kashida_color_group',
        kashida_colors_showcase_t('help','Help'),
        kashida_colors_showcase_t('help','Help'),
        'edit_posts',
        'kashida-colors-help',
        'kashida_colors_showcase_help_page'
    );
});

add_action('admin_menu',function(){
    add_submenu_page(
        'edit.php?post_type=kashida_color_group',
        kashida_colors_showcase_t('settings_title','Kashida Color Library — Settings'),
        kashida_colors_showcase_t('settings','Settings'),
        'manage_options',
        'kashida-colors-settings',
        'kashida_colors_showcase_settings_page'
    );
});

add_action('admin_menu',function(){
    global $submenu;
    $parent='edit.php?post_type=kashida_color_group';
    if(empty($submenu[$parent]) || !is_array($submenu[$parent])) return;
    $desired=[$parent,'post-new.php?post_type=kashida_color_group','kashida-colors-settings','kashida-colors-help'];
    $map=[];
    foreach($submenu[$parent] as $item){if(isset($item[2]))$map[$item[2]]=$item;}
    $ordered=[];
    foreach($desired as $slug){if(isset($map[$slug])){$ordered[]=$map[$slug];unset($map[$slug]);}}
    foreach($map as $item)$ordered[]=$item;
    $submenu[$parent]=$ordered;
},99);


function kashida_colors_showcase_settings_page(){
    if(!current_user_can('manage_options')) return;
    $groups=get_posts(['post_type'=>'kashida_color_group','post_status'=>'publish','posts_per_page'=>-1,'orderby'=>'title','order'=>'ASC']);
    $default_desktop=6; $default_tablet=4; $default_mobile=2;
    ?>
    <div class="wrap kcs-settings-page" dir="<?php echo is_rtl()?'rtl':'ltr'; ?>">
      <style>
      .kcs-settings-page{max-width:1180px;margin:22px 20px 40px 0}.kcs-settings-hero{position:relative;overflow:hidden;background:linear-gradient(135deg,#111827 0%,#273449 55%,#3b4758 100%);color:#fff;border-radius:18px;padding:30px 34px;display:flex;align-items:center;justify-content:space-between;gap:24px;box-shadow:0 14px 40px rgba(15,23,42,.16)}.kcs-settings-hero:after{content:'';position:absolute;width:220px;height:220px;border-radius:50%;background:rgba(255,255,255,.06);right:-60px;top:-90px}.kcs-settings-brand{display:flex;align-items:center;gap:18px;position:relative;z-index:1}.kcs-settings-logo{width:62px;height:62px;object-fit:contain;background:#fff;border-radius:14px;padding:8px}.kcs-settings-hero h1{color:#fff;margin:0 0 6px;font-size:27px}.kcs-settings-hero p{margin:0;color:#dbe3ee;max-width:700px;font-size:13px;line-height:1.8}.kcs-settings-grid{display:grid;grid-template-columns:minmax(0,1.55fr) minmax(300px,.75fr);gap:18px;margin-top:18px}.kcs-settings-card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:22px;box-shadow:0 3px 14px rgba(15,23,42,.04)}.kcs-settings-card h2{margin:0 0 7px;font-size:18px;color:#111827}.kcs-settings-card .kcs-card-intro{margin:0 0 18px;color:#64748b;font-size:13px;line-height:1.75}.kcs-shortcode-controls{display:grid;gap:16px}.kcs-generator-options{display:flex;gap:14px;align-items:center;flex-wrap:wrap;padding:12px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:11px}.kcs-generator-options .kcs-field-label{width:100%;margin-bottom:0}.kcs-generator-options label{font-size:12px;color:#374151}.kcs-field-label{font-weight:700;color:#1f2937;font-size:13px;margin-bottom:7px;display:block}.kcs-groups-select{width:100%;min-height:170px;border:1px solid #d1d5db;border-radius:10px;padding:7px;background:#fff}.kcs-columns-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}.kcs-column-field{background:#f8fafc;border:1px solid #e5e7eb;border-radius:11px;padding:12px}.kcs-column-field input{width:100%;margin-top:7px}.kcs-shortcode-output{position:relative;background:#0f172a;color:#e2e8f0;border-radius:11px;padding:15px 48px 15px 15px;direction:ltr;text-align:left;font-family:Consolas,Monaco,monospace;font-size:12px;line-height:1.7;word-break:break-all;min-height:50px}.kcs-shortcode-copy{position:absolute;right:8px;top:8px;border:0;background:#fff;color:#111827;border-radius:7px;padding:7px 10px;cursor:pointer}.kcs-settings-meta{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.kcs-settings-meta>div{background:#f8fafc;border:1px solid #e5e7eb;border-radius:10px;padding:12px}.kcs-settings-meta strong{display:block;margin-bottom:4px;color:#111827;font-size:12px}.kcs-settings-meta span{color:#475569;font-size:12px}.kcs-settings-footer{margin:24px 0 0;padding-top:18px;border-top:1px solid #e5e7eb;color:#64748b;font-size:12px;text-align:center}.kcs-settings-footer a{color:#334155;text-decoration:none;font-weight:700}.kcs-settings-footer img{height:24px;width:auto;vertical-align:middle;margin-right:7px}.kcs-settings-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.14);border-radius:999px;padding:7px 11px;color:#e5e7eb;font-size:11px;position:relative;z-index:1}.kcs-settings-help-link{margin-top:15px;display:inline-flex;align-items:center;gap:6px}.kcs-settings-note{margin-top:12px;padding:11px 13px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;color:#1e40af;font-size:12px;line-height:1.7}.kcs-settings-page .button-primary{border-radius:8px}.kcs-settings-page .description{color:#64748b;font-size:12px}@media(max-width:900px){.kcs-settings-grid{grid-template-columns:1fr}}@media(max-width:650px){.kcs-columns-grid,.kcs-settings-meta{grid-template-columns:1fr}.kcs-settings-hero{padding:24px;align-items:flex-start}.kcs-settings-badge{display:none}}
      </style>
      <div class="kcs-settings-hero">
        <div class="kcs-settings-brand"><img class="kcs-settings-logo" src="<?php echo esc_url(KASHIDA_COLORS_SHOWCASE_URL.'assets/kashida-mark.svg'); ?>" alt="Kashida" width="62" height="62"><div><h1><?php echo esc_html(kashida_colors_showcase_t('settings_title','Kashida Color Library — Settings')); ?></h1><p><?php echo esc_html(kashida_colors_showcase_t('settings_intro','Manage your color display shortcodes and plugin information.')); ?></p></div></div>
        <span class="kcs-settings-badge">Kashida Color Library <?php echo esc_html(KASHIDA_COLORS_SHOWCASE_VERSION); ?></span>
      </div>
      <div class="kcs-settings-grid">
        <div class="kcs-settings-card">
          <h2><?php echo esc_html(kashida_colors_showcase_t('shortcode_generator','Shortcode Generator')); ?></h2>
          <p class="kcs-card-intro"><?php echo esc_html(kashida_colors_showcase_t('shortcode_generator_intro','Generate a responsive shortcode for one or more color groups.')); ?></p>
          <div class="kcs-shortcode-controls">
            <div><label class="kcs-field-label" for="kcs-shortcode-groups"><?php echo esc_html(kashida_colors_showcase_t('select_groups','Select Color Groups')); ?></label><select id="kcs-shortcode-groups" class="kcs-groups-select" multiple><?php foreach($groups as $g): ?><option value="<?php echo esc_attr(kashida_colors_showcase_group_code($g->ID)); ?>"><?php echo esc_html($g->post_title.' — '.kashida_colors_showcase_group_code($g->ID)); ?></option><?php endforeach; ?></select><p class="description"><?php echo esc_html(kashida_colors_showcase_t('choose_groups_hint','Choose one or more groups. Their colors will appear together in the selected order.')); ?></p></div>
            <div><span class="kcs-field-label"><?php echo esc_html(kashida_colors_showcase_t('responsive_columns','Responsive Columns')); ?></span><div class="kcs-columns-grid"><label class="kcs-column-field"><strong><?php echo esc_html(kashida_colors_showcase_t('desktop_columns','Desktop Columns')); ?></strong><input id="kcs-cols-desktop" type="number" min="1" max="12" value="<?php echo esc_attr($default_desktop); ?>"></label><label class="kcs-column-field"><strong><?php echo esc_html(kashida_colors_showcase_t('tablet_columns','Tablet Columns')); ?></strong><input id="kcs-cols-tablet" type="number" min="1" max="8" value="<?php echo esc_attr($default_tablet); ?>"></label><label class="kcs-column-field"><strong><?php echo esc_html(kashida_colors_showcase_t('mobile_columns','Mobile Columns')); ?></strong><input id="kcs-cols-mobile" type="number" min="1" max="6" value="<?php echo esc_attr($default_mobile); ?>"></label></div></div><div><label class="kcs-field-label" for="kcs-collection-shape"><?php echo esc_html(kashida_colors_showcase_t('collection_shape','Collection Shape')); ?></label><select id="kcs-collection-shape" style="width:100%"><option value="rectangle"><?php echo esc_html(kashida_colors_showcase_t('shape_rectangle','Rectangle')); ?></option><option value="square"><?php echo esc_html(kashida_colors_showcase_t('shape_square','Square')); ?></option><option value="circle"><?php echo esc_html(kashida_colors_showcase_t('shape_circle','Circle')); ?></option><option value="compact"><?php echo esc_html(kashida_colors_showcase_t('shape_compact','Compact')); ?></option></select><p class="description"><?php echo esc_html(kashida_colors_showcase_t('shape_help','Choose how each collection card is shaped in the Collection Listing shortcode.')); ?></p></div>
            <div class="kcs-generator-options"><span class="kcs-field-label"><?php echo esc_html(kashida_colors_showcase_t('collection_options','Collection Options')); ?></span><label><input id="kcs-collection-cover" type="checkbox" checked> <?php echo esc_html(kashida_colors_showcase_t('show_featured_image','Show Cover Image')); ?></label><label><input id="kcs-collection-description" type="checkbox"> <?php echo esc_html(kashida_colors_showcase_t('show_description','Show Description')); ?></label><label><input id="kcs-collection-count" type="checkbox" checked> <?php echo esc_html(kashida_colors_showcase_t('show_count','Show Color Count')); ?></label></div>
            <div><span class="kcs-field-label"><?php echo esc_html(kashida_colors_showcase_t('generated_shortcode','Generated Shortcode')); ?></span><div class="kcs-shortcode-output" id="kcs-generated-shortcode"><span class="kcs-shortcode-value" id="kcs-generated-shortcode-value">[kashida_collections desktop="5" tablet="3" mobile="2" shape="rectangle"]</span><button type="button" class="kcs-shortcode-copy" id="kcs-copy-shortcode"><?php echo esc_html(kashida_colors_showcase_t('copy_shortcode','Copy Shortcode')); ?></button></div><div class="kcs-settings-note"><?php echo esc_html(kashida_colors_showcase_t('shortcode_generator_intro','Generate a responsive shortcode for one or more color groups.')); ?></div></div>
          </div>
        </div>
        <div class="kcs-settings-card">
          <h2><?php echo esc_html(kashida_colors_showcase_t('plugin_info','Plugin Information')); ?></h2>
          <div class="kcs-settings-meta"><div><strong><?php echo esc_html(kashida_colors_showcase_t('plugin_name_label','Plugin Name')); ?></strong><span>Kashida Color Library</span></div><div><strong><?php echo esc_html(kashida_colors_showcase_t('version','Version')); ?></strong><span><?php echo esc_html(KASHIDA_COLORS_SHOWCASE_VERSION); ?></span></div><div><strong><?php echo esc_html(kashida_colors_showcase_t('author','Author')); ?></strong><span>Kashida Media Services by Mrwan elGobee</span></div><div><strong><?php echo esc_html(kashida_colors_showcase_t('website','Website')); ?></strong><span><a href="https://kashida.ly/" target="_blank" rel="noopener noreferrer">kashida.ly</a></span></div></div>
          <a class="button button-primary kcs-settings-help-link" href="<?php echo esc_url(add_query_arg(['page'=>'kashida-colors-help'],admin_url('edit.php?post_type=kashida_color_group'))); ?>"><?php echo esc_html(kashida_colors_showcase_t('settings_help','Open Help')); ?></a>
        </div>
      </div>
      <div class="kcs-settings-footer"><img src="<?php echo esc_url(KASHIDA_COLORS_SHOWCASE_URL.'assets/kashida-mark.svg'); ?>" alt="Kashida" width="24" height="24"><a href="https://kashida.ly/" target="_blank" rel="noopener noreferrer">Kashida Media Services by Mrwan elGobee</a></div>
      <script>(function(){var s=document.getElementById('kcs-shortcode-groups'),d=document.getElementById('kcs-cols-desktop'),t=document.getElementById('kcs-cols-tablet'),m=document.getElementById('kcs-cols-mobile'),shape=document.getElementById('kcs-collection-shape'),cover=document.getElementById('kcs-collection-cover'),desc=document.getElementById('kcs-collection-description'),count=document.getElementById('kcs-collection-count'),out=document.getElementById('kcs-generated-shortcode'),valueEl=document.getElementById('kcs-generated-shortcode-value'),copy=document.getElementById('kcs-copy-shortcode');if(!s||!d||!t||!m||!shape||!cover||!desc||!count||!out||!valueEl||!copy)return;function fallbackCopy(text){var ta=document.createElement('textarea');ta.value=text;ta.setAttribute('readonly','');ta.style.position='fixed';ta.style.top='-9999px';ta.style.left='-9999px';document.body.appendChild(ta);ta.focus();ta.select();var ok=false;try{ok=document.execCommand('copy');}catch(e){}ta.remove();return ok;}function copyText(text){text=String(text||'');if(!text)return Promise.resolve(false);if(navigator.clipboard&&typeof navigator.clipboard.writeText==='function'&&window.isSecureContext){return navigator.clipboard.writeText(text).then(function(){return true;}).catch(function(){return fallbackCopy(text);});}return Promise.resolve(fallbackCopy(text));}function build(){var vals=[].slice.call(s.selectedOptions).map(function(o){return o.value}).filter(Boolean);var code='[kashida_collections';if(vals.length)code+=' groups="'+vals.join(',')+'"';code+=' desktop="'+Math.max(1,parseInt(d.value||5,10))+'" tablet="'+Math.max(1,parseInt(t.value||3,10))+'" mobile="'+Math.max(1,parseInt(m.value||2,10))+'" shape="'+(shape.value||'rectangle')+'"';if(!cover.checked)code+=' show_image="false"';if(desc.checked)code+=' show_description="true"';if(!count.checked)code+=' show_count="false"';code+=']';valueEl.textContent=code;out.dataset.value=code;}function boot(){[s,d,t,m,shape,cover,desc,count].forEach(function(x){x.addEventListener('change',build);x.addEventListener('input',build)});copy.addEventListener('click',function(){var v=out.dataset.value||valueEl.textContent||'';copyText(v).then(function(){var old=copy.textContent;copy.textContent='<?php echo esc_js(kashida_colors_showcase_t('shortcode_copied','Shortcode copied.')); ?>';setTimeout(function(){copy.textContent=old},1400);});});build();}boot();})();</script>
    </div>
    <?php
}

add_action('admin_menu',function(){
    add_submenu_page(
        'edit.php?post_type=kashida_color_group',
        kashida_colors_showcase_t('settings_title','Kashida Color Library — Settings'),
        kashida_colors_showcase_t('settings','Settings'),
        'manage_options',
        'kashida-colors-settings',
        'kashida_colors_showcase_settings_page'
    );
});



function kashida_colors_showcase_help_page(){
    if(!current_user_can('edit_posts')) return;
    $examples=['[kashida_collections]','[kashida_collections groups="KCS-0001,KCS-0002" desktop="4" tablet="2" mobile="1" shape="rectangle"]','[kashida_collections desktop="5" tablet="3" mobile="2" shape="square"]','[kashida_collections desktop="4" tablet="2" mobile="2" shape="circle"]','[kashida_collections desktop="5" tablet="3" mobile="2" shape="compact"]','[paint_colors group="KCS-0001" shape="rectangle"]','[paint_colors group="KCS-0001" shape="circle"]','[paint_colors group="KCS-0001" shape="compact"]','[paint_colors groups="KCS-0001,KCS-0002" desktop="6" tablet="4" mobile="2"]','[paint_colors group="KCS-0001" search="false" filter="false" sort="false"]','[paint_colors group="KCS-0001" show_code="false" show_hex="true" show_rgb="true" show_hsl="true"]','[paint_colors group="KCS-0001" similar="false"]','[paint_colors group="KCS-0001" desktop="5" tablet="3" mobile="2" show_image="false" sort="true"]'];
    ?><div class="wrap kcs-help-page" dir="rtl">
    <style>
    .kcs-help-page{max-width:1180px}.kcs-admin-brand-footer{display:flex;align-items:center;justify-content:center;gap:7px;margin:22px 20px 10px;padding-top:12px;border-top:1px solid #e2e8f0;color:#64748b;font-size:12px}.kcs-admin-brand-footer img{width:92px;height:24px;object-fit:contain}.kcs-admin-brand-footer a{color:#475569;text-decoration:none;font-weight:600}.kcs-help-hero{background:linear-gradient(135deg,#111827,#334155);color:#fff;border-radius:14px;padding:28px 30px;margin:20px 0}.kcs-help-hero h1{color:#fff;margin:0 0 8px;font-size:27px}.kcs-help-hero p{margin:0;color:#e5e7eb;font-size:14px;line-height:1.9}.kcs-help-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;margin:18px 0}.kcs-help-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:20px;box-shadow:0 1px 2px rgba(0,0,0,.04)}.kcs-help-card h2{margin:0 0 12px;font-size:18px;color:#111827}.kcs-help-card h3{font-size:14px;margin:18px 0 8px;color:#374151}.kcs-help-card p,.kcs-help-card li{font-size:13px;line-height:1.9;color:#4b5563}.kcs-help-card ul,.kcs-help-card ol{margin:8px 20px 0 0}.kcs-help-code{display:block;direction:ltr;text-align:left;background:#0f172a;color:#e2e8f0;border-radius:8px;padding:12px 14px;margin:8px 0;overflow:auto;font-family:Consolas,Monaco,monospace;font-size:12px;white-space:pre-wrap}.kcs-help-table{width:100%;border-collapse:collapse;margin-top:12px;background:#fff}.kcs-help-table th,.kcs-help-table td{border:1px solid #e5e7eb;padding:10px;text-align:right;vertical-align:top;font-size:12px;line-height:1.7}.kcs-help-table th{background:#f8fafc;color:#111827}.kcs-help-table code{direction:ltr;display:inline-block;background:#f1f5f9;padding:2px 5px;border-radius:4px}.kcs-help-note{background:#fffbeb;border:1px solid #fde68a;color:#78350f;border-radius:9px;padding:12px 14px;font-size:12px;line-height:1.8;margin-top:12px}.kcs-help-ok{background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;border-radius:9px;padding:12px 14px;font-size:12px;line-height:1.8;margin-top:12px}.kcs-help-downloads{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}.kcs-help-downloads a{display:inline-flex;align-items:center;gap:6px}.kcs-help-meta{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;margin-top:10px}.kcs-help-meta div{background:#f8fafc;border:1px solid #e5e7eb;padding:9px 10px;border-radius:8px;font-size:12px}.kcs-help-meta strong{display:block;color:#111827;margin-bottom:3px}.kcs-help-meta span{color:#4b5563}@media(max-width:800px){.kcs-help-grid,.kcs-help-meta{grid-template-columns:1fr}}
    </style>
    <div class="kcs-help-card" style="margin-top:20px;display:flex;align-items:center;gap:18px;flex-wrap:wrap"><div style="width:52px;height:52px;border-radius:10px;background:#fff;border:1px solid #e5e7eb;display:flex;align-items:center;justify-content:center"><img src="<?php echo esc_url(KASHIDA_COLORS_SHOWCASE_URL.'assets/kashida-mark.svg'); ?>" alt="Kashida" width="42" height="42" style="display:block;object-fit:contain"></div><div><h2 style="margin:0 0 6px"><?php echo esc_html(kashida_colors_showcase_t('plugin_info','Plugin Information')); ?></h2><div class="kcs-help-meta"><div><strong><?php echo esc_html(kashida_colors_showcase_t('plugin_name_label','Plugin Name')); ?></strong><span>Kashida Color Library</span></div><div><strong><?php echo esc_html(kashida_colors_showcase_t('version','Version')); ?></strong><span><?php echo esc_html(KASHIDA_COLORS_SHOWCASE_VERSION); ?></span></div><div><strong><?php echo esc_html(kashida_colors_showcase_t('author','Author')); ?></strong><span>Kashida Media Services by Mrwan elGobee</span></div><div><strong><?php echo esc_html(kashida_colors_showcase_t('website','Website')); ?></strong><span><a href="https://kashida.ly/" target="_blank" rel="noopener noreferrer">https://kashida.ly/</a></span></div></div></div></div>
    <div class="kcs-help-hero"><h1><?php echo esc_html(kashida_colors_showcase_t('help_title','Kashida Color Library Help')); ?></h1><p><?php echo esc_html(kashida_colors_showcase_t('help_intro','Guide to managing color groups, entering and importing color data, defining a custom attribute for each group, preparing the color table, and using the Shortcode to display colors on your website.')); ?></p></div>
    <div class="kcs-help-card" style="margin-bottom:16px"><h2><?php echo esc_html(kashida_colors_showcase_t('help_collections_title','2.5. Collection Listing')); ?></h2><p><?php echo esc_html(kashida_colors_showcase_t('help_collections_intro','Use the Collection Listing shortcode to display published color groups. Draft groups are not shown on the public collection listing. Each collection can optionally use its featured image as a cover at the top of the card.')); ?></p><code class="kcs-help-code">[kashida_collections]</code><p><?php echo esc_html(kashida_colors_showcase_t('help_status','Group status follows the standard WordPress Draft/Published workflow. Only published groups are publicly accessible and included in Collection Listing.')); ?></p><p><?php echo esc_html(kashida_colors_showcase_t('help_collection_shape','The Collection Listing shortcode supports rectangle, square, circle, and compact card shapes using shape="rectangle", shape="square", shape="circle", or shape="compact".')); ?></p><code class="kcs-help-code">[kashida_collections desktop="5" tablet="3" mobile="2" shape="square"]</code></div>

    <div class="kcs-help-grid">
      <div class="kcs-help-card"><h2><?php echo esc_html(kashida_colors_showcase_t('help_create_group','1. Create a Group')); ?></h2><ol><li><?php echo esc_html(kashida_colors_showcase_t('help_create_1','Open Kashida Color Library → Add Group.')); ?></li><li><?php echo esc_html(kashida_colors_showcase_t('help_create_2','Enter the group name and optionally add a description and featured image.')); ?></li><li><?php echo esc_html(kashida_colors_showcase_t('help_create_3','Save the group; a fixed code such as KCS-0001 will be generated automatically.')); ?></li></ol><div class="kcs-help-ok"><?php echo esc_html(kashida_colors_showcase_t('help_code_note','The group code does not depend on the group name and does not change when the name changes. It is used in the Shortcode and URL.')); ?></div></div>
      <div class="kcs-help-card"><h2><?php echo esc_html(kashida_colors_showcase_t('help_manual_colors','2. Add Colors Manually')); ?></h2><ol><li><?php echo esc_html(kashida_colors_showcase_t('help_manual_1','Open the required group.')); ?></li><li><?php echo esc_html(kashida_colors_showcase_t('help_manual_2','In the Color Group box, click Add New Color.')); ?></li><li><?php echo esc_html(kashida_colors_showcase_t('help_manual_3','Enter the color name, code, HEX value, optional attribute value, and sample image if needed.')); ?></li><li><?php echo esc_html(kashida_colors_showcase_t('help_manual_4','Save the group.')); ?></li></ol></div>
    </div>
    <div class="kcs-help-card" style="margin-top:16px"><h2><?php echo esc_html(kashida_colors_showcase_t('attribute_options','Attribute Options')); ?></h2><p><?php echo esc_html(kashida_colors_showcase_t('attribute_options_help','Define the values you want to offer when entering colors. You can add or remove options at any time.')); ?></p><p><?php echo esc_html(kashida_colors_showcase_t('help_attribute_examples','For example, use Surface with Gloss, Satin, and Matte; Material with Cotton, Linen, and Silk; or Texture with Smooth and Brushed. The feature is not limited to paint or finishing types.')); ?></p></div>
    <div class="kcs-help-card"><h2><?php echo esc_html(kashida_colors_showcase_t('help_import','3. Import Colors and Supporting Files')); ?></h2><p><?php echo esc_html(kashida_colors_showcase_t('help_import_intro','Colors can be imported from CSV or XLSX, and ZIP can be used when sample images need to be included with the data file.')); ?></p><table class="kcs-help-table"><thead><tr><th><?php echo esc_html(kashida_colors_showcase_t('column','Column')); ?></th><th><?php echo esc_html(kashida_colors_showcase_t('type','Type')); ?></th><th><?php echo esc_html(kashida_colors_showcase_t('description','Description')); ?></th><th><?php echo esc_html(kashida_colors_showcase_t('example','Example')); ?></th></tr></thead><tbody><tr><td><code>name</code></td><td><?php echo esc_html(kashida_colors_showcase_t('help_table_text','Text')); ?></td><td><?php echo esc_html(kashida_colors_showcase_t('help_table_color_name','Color Name')); ?></td><td>Ocean Blue</td></tr><tr><td><code>code</code></td><td><?php echo esc_html(kashida_colors_showcase_t('help_table_text','Text')); ?></td><td><?php echo esc_html(kashida_colors_showcase_t('help_table_commercial_code','Commercial Color Code')); ?></td><td>A-002</td></tr><tr><td><code>hex</code></td><td>HEX</td><td><?php echo esc_html(kashida_colors_showcase_t('help_table_color_value','Color Value')); ?></td><td>#356A91</td></tr><tr><td><code>attribute</code></td><td><?php echo esc_html(kashida_colors_showcase_t('help_table_text','Text')); ?></td><td><?php echo esc_html(kashida_colors_showcase_t('help_table_optional_attribute','Optional value for the group attribute')); ?></td><td>Matte</td></tr><tr><td><code>attribute_label</code></td><td><?php echo esc_html(kashida_colors_showcase_t('help_table_text','Text')); ?></td><td><?php echo esc_html(kashida_colors_showcase_t('help_table_attribute_label','Optional label for the attribute; the first non-empty value is used for the group')); ?></td><td>Surface</td></tr><tr><td><code>sample_image</code></td><td><?php echo esc_html(kashida_colors_showcase_t('help_table_image_type','Image URL / Filename')); ?></td><td><?php echo esc_html(kashida_colors_showcase_t('help_table_sample_image','Sample image; can be matched from ZIP')); ?></td><td>Ocean Blue.png</td></tr></tbody></table><h3><?php echo esc_html(kashida_colors_showcase_t('attribute_values','Attribute values')); ?></h3><p><?php echo esc_html(kashida_colors_showcase_t('attribute_options_help','Define the values you want to offer when entering colors. You can add or remove options at any time.')); ?></p><p><?php echo esc_html(kashida_colors_showcase_t('help_import_zip','When using ZIP, place a CSV or XLSX file inside the archive. PNG/JPG/WebP/GIF images can be placed at the same level. The plugin matches image names to the color name or color code.')); ?></p><div class="kcs-help-downloads"><a class="button button-primary" href="<?php echo esc_url(KASHIDA_COLORS_SHOWCASE_URL.'assets/kashida-colors-template.csv'); ?>" download><?php echo esc_html(kashida_colors_showcase_t('download_csv','Download CSV Template')); ?></a><a class="button" href="<?php echo esc_url(KASHIDA_COLORS_SHOWCASE_URL.'assets/kashida-colors-template.xlsx'); ?>" download><?php echo esc_html(kashida_colors_showcase_t('download_xlsx','Download XLSX Template')); ?></a><a class="button" href="<?php echo esc_url(KASHIDA_COLORS_SHOWCASE_URL.'assets/kashida-colors-import-template.zip'); ?>" download><?php echo esc_html(kashida_colors_showcase_t('download_zip','Download ZIP Template with Images')); ?></a></div></div>
    <div class="kcs-help-card" style="margin-top:16px"><h2><?php echo esc_html(kashida_colors_showcase_t('help_table','4. Prepare the Color Table')); ?></h2><p><?php echo esc_html(kashida_colors_showcase_t('help_table_intro','The first row must contain column headers, and each following row represents one color. Do not merge cells or add extra headings above the first row.')); ?></p><div class="kcs-help-code">name | code | hex | attribute | attribute_label | sample_image
Sun White | A-001 | #F4F1E8 | Matte | Surface | Sun White.png
Ocean Blue | A-002 | #356A91 | Gloss | Surface | Ocean Blue.png
Warm Gray | A-003 | #8A8178 | Brushed | Surface | Warm Gray.png</div><div class="kcs-help-note"><?php echo esc_html(kashida_colors_showcase_t('help_table_note','In CSV, use commas as the column separator. In XLSX, the first worksheet is used. HEX values must be valid, and sample_image may be left empty when no image is available.')); ?></div></div>
    <div class="kcs-help-grid">
      <div class="kcs-help-card"><h2><?php echo esc_html(kashida_colors_showcase_t('help_shortcode','5. Shortcode')); ?></h2><p><?php echo esc_html(kashida_colors_showcase_t('help_shortcode_intro','Copy the Shortcode from the Shortcode column in the groups table or from the group settings, then paste it into a page or post.')); ?></p><?php foreach($examples as $e): ?><code class="kcs-help-code"><?php echo esc_html($e); ?></code><?php endforeach; ?></div>
      <div class="kcs-help-card"><h2><?php echo esc_html(kashida_colors_showcase_t('help_shortcode_options','Shortcode Options')); ?></h2><table class="kcs-help-table"><tr><th><?php echo esc_html(kashida_colors_showcase_t('attribute','Attribute')); ?></th><th><?php echo esc_html(kashida_colors_showcase_t('default_value','Default Value')); ?></th><th><?php echo esc_html(kashida_colors_showcase_t('function','Function')); ?></th></tr><tr><td><code>group</code> / <code>groups</code></td><td><?php echo esc_html(kashida_colors_showcase_t('help_required','Required')); ?></td><td><?php echo esc_html(kashida_colors_showcase_t('help_one_group_or_many','One group code or comma-separated group codes.')); ?></td></tr><tr><td><code>desktop</code> / <code>tablet</code> / <code>mobile</code></td><td><?php echo esc_html(kashida_colors_showcase_t('help_group_defaults','Group defaults')); ?></td><td><?php echo esc_html(kashida_colors_showcase_t('help_responsive_columns','Responsive columns. Explicit shortcode values override group settings.')); ?></td></tr><tr><td><code>shape</code></td><td><?php echo esc_html(kashida_colors_showcase_t('help_group_defaults','Group defaults')); ?></td><td><?php echo esc_html(kashida_colors_showcase_t('help_color_card_shape','Color card shape: rectangle, square, circle, or compact.')); ?></td></tr><tr><td><code>show_name</code></td><td><?php echo esc_html(kashida_colors_showcase_t('help_group_defaults','Group defaults')); ?></td><td><?php echo esc_html(kashida_colors_showcase_t('help_show_color_name','Show color name.')); ?></td></tr><tr><td><code>show_code</code></td><td><?php echo esc_html(kashida_colors_showcase_t('help_group_defaults','Group defaults')); ?></td><td><?php echo esc_html(kashida_colors_showcase_t('help_show_color_code','Show color code.')); ?></td></tr><tr><td><code>show_hex</code></td><td><?php echo esc_html(kashida_colors_showcase_t('help_group_defaults','Group defaults')); ?></td><td><?php echo esc_html(kashida_colors_showcase_t('help_show_copy_hex','Show and copy HEX.')); ?></td></tr><tr><td><code>show_rgb</code></td><td><?php echo esc_html(kashida_colors_showcase_t('help_group_defaults','Group defaults')); ?></td><td><?php echo esc_html(kashida_colors_showcase_t('help_show_copy_rgb','Show and copy RGB calculated from HEX.')); ?></td></tr><tr><td><code>show_hsl</code></td><td><?php echo esc_html(kashida_colors_showcase_t('help_group_defaults','Group defaults')); ?></td><td><?php echo esc_html(kashida_colors_showcase_t('help_show_hsl','Show HSL calculated from HEX.')); ?></td></tr><tr><td><code>show_attribute</code></td><td><?php echo esc_html(kashida_colors_showcase_t('help_group_defaults','Group defaults')); ?></td><td><?php echo esc_html(kashida_colors_showcase_t('help_show_attribute_value','Show the group attribute value.')); ?></td></tr><tr><td><code>show_image</code></td><td><?php echo esc_html(kashida_colors_showcase_t('help_group_defaults','Group defaults')); ?></td><td><?php echo esc_html(kashida_colors_showcase_t('help_show_image_overlay','Show the sample image overlay.')); ?></td></tr><tr><td><code>title</code></td><td><?php echo esc_html(kashida_colors_showcase_t('help_true','true')); ?></td><td><?php echo esc_html(kashida_colors_showcase_t('help_show_group_title','Show group title.')); ?></td></tr><tr><td><code>search</code></td><td><?php echo esc_html(kashida_colors_showcase_t('help_true','true')); ?></td><td><?php echo esc_html(kashida_colors_showcase_t('help_show_search','Show search and color-family search.')); ?></td></tr><tr><td><code>filter</code></td><td><?php echo esc_html(kashida_colors_showcase_t('help_true','true')); ?></td><td><?php echo esc_html(kashida_colors_showcase_t('help_show_filter','Show the group attribute filter when configured.')); ?></td></tr><tr><td><code>sort</code></td><td><?php echo esc_html(kashida_colors_showcase_t('help_true','true')); ?></td><td><?php echo esc_html(kashida_colors_showcase_t('help_show_sort','Show sorting controls.')); ?></td></tr><tr><td><code>similar</code></td><td><?php echo esc_html(kashida_colors_showcase_t('help_true','true')); ?></td><td><?php echo esc_html(kashida_colors_showcase_t('help_show_similar','Show similar colors.')); ?></td></tr></table></div>
    </div>
    <div class="kcs-help-card"><h2><?php echo esc_html(kashida_colors_showcase_t('help_display','6. Display, Search, Sort, and Export')); ?></h2><p><?php echo esc_html(kashida_colors_showcase_t('help_display_intro','Each group can define its responsive columns and choose which color fields are shown. Website visitors can search and sort colors, and administrators can export a group as CSV or XLSX.')); ?></p><ul><li><?php echo esc_html(kashida_colors_showcase_t('help_copy_values','Click HEX or RGB on a color card to copy the value. RGB and HSL are calculated automatically from HEX.')); ?></li><li><?php echo esc_html(kashida_colors_showcase_t('help_shortcodes_columns','A group stores default Desktop, Tablet, and Mobile columns. Shortcode attributes desktop, tablet, and mobile override the group defaults when provided.')); ?></li></ul><div class="kcs-help-ok"><?php echo esc_html(kashida_colors_showcase_t('help_export','Export a group from the group editor using Export CSV or Export XLSX. The exported columns are compatible with the import template.')); ?></div></div>
    <div class="kcs-help-grid"><div class="kcs-help-card"><h2><?php echo esc_html(kashida_colors_showcase_t('help_color_search','7. Find a Nearby Color')); ?></h2><p><?php echo esc_html(kashida_colors_showcase_t('help_color_search_intro','When search is enabled, the color wheel is displayed. Choose a color from the wheel and click Find Nearest. The plugin displays the nearest colors in the same color family, ordered by proximity.')); ?></p><div class="kcs-help-ok"><?php echo esc_html(kashida_colors_showcase_t('help_color_search_note','The color-wheel search is independent of text search. Clear the selected color to return to all results.')); ?></div></div><div class="kcs-help-card"><h2><?php echo esc_html(kashida_colors_showcase_t('help_front_features','8. Display Features')); ?></h2><ul><li><?php echo esc_html(kashida_colors_showcase_t('help_feature_1','Search by name, code, or HEX.')); ?></li><li><?php echo esc_html(kashida_colors_showcase_t('help_search_extended','Search also matches RGB, HSL, and Attribute values.')); ?></li><li><?php echo esc_html(kashida_colors_showcase_t('help_sort_extended','Sort by name, code, attribute, or HEX.')); ?></li><li><?php echo esc_html(kashida_colors_showcase_t('help_feature_2','Filter by the group attribute when an attribute is configured.')); ?></li><li><?php echo esc_html(kashida_colors_showcase_t('help_feature_3','Similar colors.')); ?></li><li><?php echo esc_html(kashida_colors_showcase_t('help_feature_4','Select multiple colors, then copy, print, or share them.')); ?></li></ul></div></div>
    <div class="kcs-help-grid"><div class="kcs-help-card"><h2><?php echo esc_html(kashida_colors_showcase_t('help_import_preview_title','9. Import Preview and Duplicate Handling')); ?></h2><p><?php echo esc_html(kashida_colors_showcase_t('help_import_preview','Choose Preview Import first. The plugin validates the file and reports valid rows, duplicates, and codes already present in the group before you confirm the import.')); ?></p><p><?php echo esc_html(kashida_colors_showcase_t('help_duplicate_modes','Replace all removes the current color list. Append and skip keeps existing codes unchanged. Append and update replaces matching codes and adds new ones.')); ?></p></div><div class="kcs-help-card"><h2><?php echo esc_html(kashida_colors_showcase_t('help_polylang_title','10. Translation with Polylang')); ?></h2><p><?php echo esc_html(kashida_colors_showcase_t('help_translation_intro','All interface strings are prepared for translation with Polylang. You can translate each label, button, field, and message from Languages → String translations.')); ?></p></div></div>
    <div class="kcs-help-card"><h2><?php echo esc_html(kashida_colors_showcase_t('help_common_errors_title','11. Common Errors')); ?></h2><ul><li><?php echo esc_html(kashida_colors_showcase_t('help_error_1','Group not found: make sure you are using the correct group code, such as KCS-0001.')); ?></li><li><?php echo esc_html(kashida_colors_showcase_t('help_error_2','The import contains no valid colors: check name and hex in the file.')); ?></li><li><?php echo esc_html(kashida_colors_showcase_t('help_error_3','ZIP images do not appear: make sure the image filename matches the color name or code and uses a supported extension.')); ?></li><li><?php echo esc_html(kashida_colors_showcase_t('help_error_4','Do not use the group name in the Shortcode; use the group code.')); ?></li></ul></div>
    </div><?php
}

add_action('add_meta_boxes',function(){
    add_meta_box('kashida_colors_showcase_colors_metabox',kashida_colors_showcase_t('colors_group_box','Color Group Colors'),'kashida_colors_showcase_colors_metabox_cb','kashida_color_group','normal','high');
    add_meta_box('kashida_colors_showcase_group_settings',kashida_colors_showcase_t('group_settings_box','Group Settings'),'kashida_colors_showcase_group_settings_cb','kashida_color_group','side','default');
    add_meta_box('kashida_colors_showcase_excel_import',kashida_colors_showcase_t('import_colors_box','Import Colors'),'kashida_colors_showcase_excel_import_cb','kashida_color_group','side','default');
});

function kashida_colors_showcase_group_settings_cb($post){
    wp_nonce_field('kcs_save_group','kcs_group_nonce');
    $active=get_post_meta($post->ID,'_kashida_colors_showcase_active',true); $active=($active==='')?'1':$active;
    $code=kashida_colors_showcase_group_code($post->ID);
    $slug=sanitize_title($code);
    $display=kashida_colors_showcase_group_display_config($post->ID);
    $short='[paint_colors group="'.$code.'"]';
    $attribute_label=get_post_meta($post->ID,'_kashida_colors_showcase_attribute_label',true);
    $options=get_post_meta($post->ID,'_kashida_colors_showcase_attribute_options',true); if(!is_array($options))$options=[];
    ?>
    <p><label style="display:flex;align-items:center;gap:8px;font-weight:600"><input type="checkbox" name="kcs_active" value="1" <?php checked($active,'1');?>> <?php echo esc_html(kashida_colors_showcase_t('activate_group','Activate Group')); ?></label></p>
    <hr style="margin:14px 0">
    <p><label style="font-weight:600;display:block;margin-bottom:6px"><?php echo esc_html(kashida_colors_showcase_t('attribute_name_label','Attribute Name')); ?></label><input type="text" name="kcs_attribute_label" value="<?php echo esc_attr($attribute_label); ?>" placeholder="<?php echo esc_attr(kashida_colors_showcase_t('attribute_placeholder','e.g. Surface, Material, Texture')); ?>" style="width:100%"><span style="display:block;color:#777;font-size:11px;line-height:1.6;margin-top:5px"><?php echo esc_html(kashida_colors_showcase_t('attribute_options_help','Define the values you want to offer when entering colors. You can add or remove options at any time.')); ?></span></p>
    <div><label style="font-weight:600;display:block;margin-bottom:6px"><?php echo esc_html(kashida_colors_showcase_t('attribute_options','Attribute Options')); ?></label><div id="kcs-attribute-options"> <?php foreach($options as $opt): ?><div class="kcs-attribute-option" style="display:flex;gap:6px;margin-bottom:6px"><input type="text" name="kcs_attribute_options[]" value="<?php echo esc_attr($opt); ?>" placeholder="<?php echo esc_attr(kashida_colors_showcase_t('attribute_value_placeholder','e.g. Matte, Gloss, Cotton')); ?>" style="flex:1"><button type="button" class="button kcs-remove-attribute-option">×</button></div><?php endforeach; ?></div><button type="button" class="button" id="kcs-add-attribute-option">+ <?php echo esc_html(kashida_colors_showcase_t('add_option','Add Option')); ?></button></div>
    <script>(function(){var box=document.getElementById('kcs-attribute-options'),add=document.getElementById('kcs-add-attribute-option');if(!box||!add)return;add.addEventListener('click',function(){var d=document.createElement('div');d.className='kcs-attribute-option';d.style.cssText='display:flex;gap:6px;margin-bottom:6px';d.innerHTML='<input type="text" name="kcs_attribute_options[]" placeholder="<?php echo esc_js(kashida_colors_showcase_t('attribute_value_placeholder','e.g. Matte, Gloss, Cotton')); ?>" style="flex:1"><button type="button" class="button kcs-remove-attribute-option">×</button>';box.appendChild(d);});box.addEventListener('click',function(e){if(e.target.classList.contains('kcs-remove-attribute-option'))e.target.parentElement.remove();});})();</script>
    <hr style="margin:14px 0">
    <div><label style="font-weight:600;display:block;margin-bottom:6px"><?php echo esc_html(kashida_colors_showcase_t('responsive_columns_group','Group Display Columns')); ?></label><div style="display:grid;grid-template-columns:repeat(3,1fr);gap:6px"><label style="font-size:11px"><?php echo esc_html(kashida_colors_showcase_t('desktop_columns','Desktop Columns')); ?><input type="number" min="1" max="12" name="kcs_desktop_columns" value="<?php echo esc_attr($display['desktop']); ?>" style="width:100%"></label><label style="font-size:11px"><?php echo esc_html(kashida_colors_showcase_t('tablet_columns','Tablet Columns')); ?><input type="number" min="1" max="8" name="kcs_tablet_columns" value="<?php echo esc_attr($display['tablet']); ?>" style="width:100%"></label><label style="font-size:11px"><?php echo esc_html(kashida_colors_showcase_t('mobile_columns','Mobile Columns')); ?><input type="number" min="1" max="6" name="kcs_mobile_columns" value="<?php echo esc_attr($display['mobile']); ?>" style="width:100%"></label></div><span style="display:block;color:#777;font-size:11px;line-height:1.6;margin-top:5px"><?php echo esc_html(kashida_colors_showcase_t('group_columns_help','Set the default responsive columns for this group. Shortcode values override these defaults.')); ?></span></div>
    <div style="margin-top:12px"><label style="font-weight:600;display:block;margin-bottom:6px"><?php echo esc_html(kashida_colors_showcase_t('color_shape','Color Display Shape')); ?></label><select name="kcs_color_shape" id="kcs-color-shape" style="width:100%"><option value="rectangle" <?php selected($display['shape'],'rectangle'); ?>><?php echo esc_html(kashida_colors_showcase_t('shape_rectangle','Rectangle')); ?></option><option value="square" <?php selected($display['shape'],'square'); ?>><?php echo esc_html(kashida_colors_showcase_t('shape_square','Square')); ?></option><option value="circle" <?php selected($display['shape'],'circle'); ?>><?php echo esc_html(kashida_colors_showcase_t('shape_circle','Circle')); ?></option><option value="compact" <?php selected($display['shape'],'compact'); ?>><?php echo esc_html(kashida_colors_showcase_t('shape_compact','Compact')); ?></option></select><span style="display:block;color:#777;font-size:11px;line-height:1.6;margin-top:5px"><?php echo esc_html(kashida_colors_showcase_t('color_shape_help','Choose the default shape for color cards in this group. The Shortcode can override it.')); ?></span></div>
    <div style="margin-top:14px;padding:12px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:8px"><strong style="display:block;margin-bottom:8px"><?php echo esc_html(kashida_colors_showcase_t('group_shortcode_generator','Group Shortcode Generator')); ?></strong><span style="display:block;color:#64748b;font-size:11px;line-height:1.6;margin-bottom:9px"><?php echo esc_html(kashida_colors_showcase_t('group_shortcode_generator_help','Generate a shortcode for this group and choose the color shape and responsive columns.')); ?></span><div style="display:grid;grid-template-columns:repeat(3,1fr);gap:6px"><label style="font-size:11px"><?php echo esc_html(kashida_colors_showcase_t('desktop_columns','Desktop Columns')); ?><input id="kcs-group-short-desktop" type="number" min="1" max="12" value="<?php echo esc_attr($display['desktop']); ?>" style="width:100%"></label><label style="font-size:11px"><?php echo esc_html(kashida_colors_showcase_t('tablet_columns','Tablet Columns')); ?><input id="kcs-group-short-tablet" type="number" min="1" max="8" value="<?php echo esc_attr($display['tablet']); ?>" style="width:100%"></label><label style="font-size:11px"><?php echo esc_html(kashida_colors_showcase_t('mobile_columns','Mobile Columns')); ?><input id="kcs-group-short-mobile" type="number" min="1" max="6" value="<?php echo esc_attr($display['mobile']); ?>" style="width:100%"></label></div><label style="display:block;font-size:11px;margin-top:9px"><?php echo esc_html(kashida_colors_showcase_t('collection_shape','Color Shape')); ?><select id="kcs-group-short-shape" style="width:100%;margin-top:4px"><option value="rectangle" <?php selected($display['shape'],'rectangle'); ?>><?php echo esc_html(kashida_colors_showcase_t('shape_rectangle','Rectangle')); ?></option><option value="square" <?php selected($display['shape'],'square'); ?>><?php echo esc_html(kashida_colors_showcase_t('shape_square','Square')); ?></option><option value="circle" <?php selected($display['shape'],'circle'); ?>><?php echo esc_html(kashida_colors_showcase_t('shape_circle','Circle')); ?></option><option value="compact" <?php selected($display['shape'],'compact'); ?>><?php echo esc_html(kashida_colors_showcase_t('shape_compact','Compact')); ?></option></select></label><div style="margin-top:9px;padding:8px;background:#fff;border:1px solid #e5e7eb;border-radius:6px"><code id="kcs-group-generated-shortcode" style="display:block;word-break:break-word;direction:ltr"></code><button type="button" class="button" id="kcs-copy-group-shortcode" style="margin-top:7px"><?php echo esc_html(kashida_colors_showcase_t('copy_shortcode','Copy Shortcode')); ?></button></div></div>
    <script>(function(){var d=document.getElementById('kcs-group-short-desktop'),t=document.getElementById('kcs-group-short-tablet'),m=document.getElementById('kcs-group-short-mobile'),sh=document.getElementById('kcs-group-short-shape'),out=document.getElementById('kcs-group-generated-shortcode'),copy=document.getElementById('kcs-copy-group-shortcode');if(!d||!t||!m||!sh||!out)return;var code=<?php echo wp_json_encode($code); ?>;function fallbackCopy(text){var ta=document.createElement('textarea');ta.value=text;ta.setAttribute('readonly','');ta.style.position='fixed';ta.style.top='-9999px';ta.style.left='-9999px';document.body.appendChild(ta);ta.focus();ta.select();var ok=false;try{ok=document.execCommand('copy');}catch(e){}ta.remove();return ok;}function copyText(text){text=String(text||'');if(!text)return Promise.resolve(false);if(navigator.clipboard&&typeof navigator.clipboard.writeText==='function'&&window.isSecureContext){return navigator.clipboard.writeText(text).then(function(){return true;}).catch(function(){return fallbackCopy(text);});}return Promise.resolve(fallbackCopy(text));}function build(){var v='[paint_colors group="'+code+'" shape="'+(sh.value||'rectangle')+'" desktop="'+Math.max(1,parseInt(d.value||6,10))+'" tablet="'+Math.max(1,parseInt(t.value||4,10))+'" mobile="'+Math.max(1,parseInt(m.value||2,10))+'"]';out.textContent=v;out.dataset.value=v;}[d,t,m,sh].forEach(function(x){x.addEventListener('input',build);x.addEventListener('change',build)});if(copy){copy.addEventListener('click',function(){var v=out.dataset.value||out.textContent||'';copyText(v).then(function(){var old=copy.textContent;copy.textContent='<?php echo esc_js(kashida_colors_showcase_t('shortcode_copied','Shortcode copied.')); ?>';setTimeout(function(){copy.textContent=old},1400);});});}build();})();</script>
    <div style="margin-top:12px"><label style="font-weight:600;display:block;margin-bottom:6px"><?php echo esc_html(kashida_colors_showcase_t('display_options','Display Options')); ?></label><div style="display:grid;gap:5px;font-size:12px"><label><input type="checkbox" name="kcs_show_name" value="1" <?php checked($display['show_name'],'1'); ?>> <?php echo esc_html(kashida_colors_showcase_t('show_name','Show Color Name')); ?></label><label><input type="checkbox" name="kcs_show_code" value="1" <?php checked($display['show_code'],'1'); ?>> <?php echo esc_html(kashida_colors_showcase_t('show_code','Show Color Code')); ?></label><label><input type="checkbox" name="kcs_show_hex" value="1" <?php checked($display['show_hex'],'1'); ?>> <?php echo esc_html(kashida_colors_showcase_t('show_hex','Show HEX')); ?></label><label><input type="checkbox" name="kcs_show_rgb" value="1" <?php checked($display['show_rgb'],'1'); ?>> <?php echo esc_html(kashida_colors_showcase_t('show_rgb','Show RGB')); ?></label><label><input type="checkbox" name="kcs_show_hsl" value="1" <?php checked($display['show_hsl'],'1'); ?>> <?php echo esc_html(kashida_colors_showcase_t('show_hsl','Show HSL')); ?></label><label><input type="checkbox" name="kcs_show_attribute" value="1" <?php checked($display['show_attribute'],'1'); ?>> <?php echo esc_html(kashida_colors_showcase_t('show_attribute','Show Attribute')); ?></label><label><input type="checkbox" name="kcs_show_image" value="1" <?php checked($display['show_image'],'1'); ?>> <?php echo esc_html(kashida_colors_showcase_t('show_image','Show Sample Image')); ?></label></div><span style="display:block;color:#777;font-size:11px;line-height:1.6;margin-top:5px"><?php echo esc_html(kashida_colors_showcase_t('display_options_help','Choose which color information appears on the website for this group.')); ?></span></div>
    <div style="display:flex;gap:7px;flex-wrap:wrap;margin-top:12px"><a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=kashida_colors_showcase_export_group&format=csv&post_id='.$post->ID),'kashida_colors_showcase_export_group_'.$post->ID)); ?>"><?php echo esc_html(kashida_colors_showcase_t('export_csv','Export CSV')); ?></a><a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=kashida_colors_showcase_export_group&format=xlsx&post_id='.$post->ID),'kashida_colors_showcase_export_group_'.$post->ID)); ?>"><?php echo esc_html(kashida_colors_showcase_t('export_xlsx','Export XLSX')); ?></a></div>
    <p style="margin-top:14px;padding:10px;background:#f0f4f8;border-radius:6px;font-size:12px;color:#555"><strong><?php echo esc_html(kashida_colors_showcase_t('group_code','Group Code')); ?>:</strong><br><code style="user-select:all;direction:ltr;display:inline-block"><?php echo esc_html($code); ?></code><br><span style="font-size:11px;color:#777"><?php echo esc_html(kashida_colors_showcase_t('group_code_fixed','This code is fixed and does not change when the group name changes.')); ?></span></p>
    <p style="margin-top:8px;padding:10px;background:#f0f4f8;border-radius:6px;font-size:12px;color:#555"><?php echo esc_html(kashida_colors_showcase_t('slug_url','Slug URL')); ?>:<br><code style="user-select:all;direction:ltr;display:inline-block"><?php echo esc_html($slug); ?></code></p>
    <p style="margin-top:8px;padding:10px;background:#fff7ed;border:1px solid #fed7aa;border-radius:6px;font-size:11px;line-height:1.7;color:#7c2d12"><?php echo esc_html(kashida_colors_showcase_t('shortcode_label','Shortcode')); ?><br><code style="user-select:all;direction:ltr;display:inline-block"><?php echo esc_html($short); ?></code></p>
    <?php
}

function kashida_colors_showcase_excel_import_cb($post){
    if(!$post->ID) return;
    wp_nonce_field('kcs_ajax_import','kcs_import_nonce');
    ?>
    <p style="font-size:12px;line-height:1.7;color:#4b5563"><?php echo esc_html(kashida_colors_showcase_t('import_formats','CSV or XLSX. Columns: name, code, hex, attribute, attribute_label, sample_image.')); ?></p>
    <p style="font-size:11px;line-height:1.8;color:#6b7280;background:#f9fafb;border:1px solid #e5e7eb;padding:8px;border-radius:6px"><?php echo esc_html(kashida_colors_showcase_t('attribute_values_inline','attribute: any value you choose. attribute_label: optional label for the attribute, such as Surface, Material, or Finish.')); ?><br><?php echo esc_html(kashida_colors_showcase_t('sample_image_hint','sample_image: optional image URL. A filename can also be matched when importing ZIP.')); ?></p>
    <div id="kcs-import-zone" style="border:2px dashed #d1d5db;border-radius:8px;padding:14px;text-align:center;background:#fafafa;cursor:pointer" onclick="document.getElementById('kcs-import-file').click()"><div style="font-size:28px;color:#9ca3af">↑</div><p style="font-size:12px;color:#6b7280;margin:0" id="kcs-import-filename"><?php echo esc_html(kashida_colors_showcase_t('choose_import_file','Choose CSV, XLSX, or ZIP')); ?></p></div>
    <input type="file" id="kcs-import-file" accept=".xlsx,.xls,.csv,.zip" style="display:none">
    <label style="display:block;margin-top:10px;font-size:12px;font-weight:600"><?php echo esc_html(kashida_colors_showcase_t('import_mode','Import Mode')); ?><select id="kcs-import-mode" style="width:100%;margin-top:5px"><option value="replace_all"><?php echo esc_html(kashida_colors_showcase_t('replace_all','Replace all existing colors')); ?></option><option value="append_skip"><?php echo esc_html(kashida_colors_showcase_t('append_skip','Append and skip existing codes')); ?></option><option value="append_update"><?php echo esc_html(kashida_colors_showcase_t('append_update','Append and update existing codes')); ?></option></select></label>
    <div style="display:flex;gap:7px;margin-top:10px"><button type="button" id="kcs-preview-import-btn" class="button" style="flex:1;display:none"><?php echo esc_html(kashida_colors_showcase_t('preview','Preview')); ?></button><button type="button" id="kcs-import-btn" class="button button-primary" style="flex:1;display:none"><?php echo esc_html(kashida_colors_showcase_t('import_colors','Import Colors')); ?></button></div>
    <div id="kcs-import-preview" style="display:none;margin-top:10px;padding:10px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:7px;font-size:12px;line-height:1.8"></div>
    <div id="kcs-import-result" style="margin-top:8px;font-size:12px;display:none"></div>
    <script>(function(){var f=document.getElementById('kcs-import-file'),pb=document.getElementById('kcs-preview-import-btn'),ib=document.getElementById('kcs-import-btn'),z=document.getElementById('kcs-import-zone'),l=document.getElementById('kcs-import-filename'),r=document.getElementById('kcs-import-result'),pv=document.getElementById('kcs-import-preview'),mode=document.getElementById('kcs-import-mode'),previewData=null;if(!f)return;function selected(){return f.files&&f.files[0]}
    f.addEventListener('change',function(){if(selected()){l.textContent=selected().name;pb.style.display='block';ib.style.display='none';pv.style.display='none'}});z.addEventListener('dragover',function(e){e.preventDefault();z.style.borderColor='#3b82f6'});z.addEventListener('drop',function(e){e.preventDefault();if(e.dataTransfer.files[0]){f.files=e.dataTransfer.files;l.textContent=f.files[0].name;pb.style.display='block';ib.style.display='none';pv.style.display='none'}});
    pb.addEventListener('click',function(){if(!selected())return;pb.disabled=true;pb.textContent='<?php echo esc_js(kashida_colors_showcase_t('importing','Importing…')); ?>';var fd=new FormData();fd.append('action','kashida_colors_showcase_preview_import');fd.append('nonce','<?php echo esc_js(wp_create_nonce('kashida_colors_showcase_ajax_import')); ?>');fd.append('post_id','<?php echo (int)$post->ID; ?>');fd.append('kcs_excel_file',selected());fetch(ajaxurl,{method:'POST',body:fd}).then(function(x){return x.json()}).then(function(d){if(!d.success){throw new Error(d.data||'<?php echo esc_js(kashida_colors_showcase_t('import_preview_failed','Unable to preview the import.')); ?>')}previewData=d.data;pv.style.display='block';pv.innerHTML='<strong><?php echo esc_js(kashida_colors_showcase_t('import_preview_title','Import Preview')); ?></strong><br><?php echo esc_js(kashida_colors_showcase_t('valid_colors','Valid colors')); ?>: <b>'+d.data.count+'</b><br><?php echo esc_js(kashida_colors_showcase_t('duplicate_rows','Duplicate rows')); ?>: <b>'+d.data.duplicates+'</b><br><?php echo esc_js(kashida_colors_showcase_t('existing_codes','Existing codes')); ?>: <b>'+d.data.existing+'</b>'+(d.data.attribute_label?'<br><?php echo esc_js(kashida_colors_showcase_t('attribute_label_text','Attribute Label')); ?>: <b>'+d.data.attribute_label+'</b>':'');ib.style.display='block';pb.disabled=false;pb.textContent='<?php echo esc_js(kashida_colors_showcase_t('preview','Preview')); ?>'}).catch(function(e){pv.style.display='block';pv.style.color='#dc2626';pv.textContent='✗ '+e.message;pb.disabled=false;pb.textContent='<?php echo esc_js(kashida_colors_showcase_t('preview','Preview')); ?>'})});
    ib.addEventListener('click',function(){if(!selected()||!previewData)return;ib.disabled=true;ib.textContent='<?php echo esc_js(kashida_colors_showcase_t('importing','Importing…')); ?>';var fd=new FormData();fd.append('action','kashida_colors_showcase_import_excel');fd.append('nonce','<?php echo esc_js(wp_create_nonce('kashida_colors_showcase_ajax_import')); ?>');fd.append('post_id','<?php echo (int)$post->ID; ?>');fd.append('duplicate_mode',mode.value);fd.append('kcs_excel_file',selected());fetch(ajaxurl,{method:'POST',body:fd}).then(function(x){return x.json()}).then(function(d){r.style.display='block';if(d.success){r.style.color='#16a34a';r.textContent='✓ <?php echo esc_js(kashida_colors_showcase_t('import_success','Imported')); ?> '+d.data.count+' <?php echo esc_js(kashida_colors_showcase_t('colors_word','colors. The page will refresh…')); ?>';setTimeout(function(){location.reload()},900)}else{r.style.color='#dc2626';r.textContent='✗ '+(d.data||'<?php echo esc_js(kashida_colors_showcase_t('import_failed','Import failed')); ?>')}ib.disabled=false;ib.textContent='<?php echo esc_js(kashida_colors_showcase_t('confirm_import','Confirm Import')); ?>'}).catch(function(e){r.style.display='block';r.style.color='#dc2626';r.textContent='✗ '+e.message;ib.disabled=false;ib.textContent='<?php echo esc_js(kashida_colors_showcase_t('confirm_import','Confirm Import')); ?>'})});})();</script>
    <?php
}

function kashida_colors_showcase_group_display_config($post_id){
    $post_id=absint($post_id);
    $defaults=[
        'desktop'=>6,
        'tablet'=>4,
        'mobile'=>2,
        'show_name'=>'1',
        'show_code'=>'1',
        'show_hex'=>'1',
        'show_rgb'=>'1',
        'show_hsl'=>'1',
        'show_attribute'=>'1',
        'show_image'=>'0',
        'shape'=>'rectangle',
    ];
    if(!$post_id) return $defaults;
    foreach(['desktop','tablet','mobile'] as $key){
        $v=get_post_meta($post_id,'_kashida_colors_showcase_'.$key,true);
        if($v!=='' && is_numeric($v)) $defaults[$key]=(int)$v;
    }
    foreach(['show_name','show_code','show_hex','show_rgb','show_hsl','show_attribute','show_image'] as $key){
        $v=get_post_meta($post_id,'_kashida_colors_showcase_'.$key,true);
        if($v!=='') $defaults[$key]=($v==='1'?'1':'0');
    }
    $defaults['desktop']=max(1,min(12,(int)$defaults['desktop']));
    $defaults['tablet']=max(1,min(8,(int)$defaults['tablet']));
    $defaults['mobile']=max(1,min(6,(int)$defaults['mobile']));
    $shape=get_post_meta($post_id,'_kashida_colors_showcase_color_shape',true);
    $defaults['shape']=in_array($shape,['rectangle','square','circle','compact'],true)?$shape:'rectangle';
    return $defaults;
}

function kashida_colors_showcase_hex_to_rgb($hex){
    $hex=sanitize_hex_color($hex);
    if(!$hex) return null;
    $hex=ltrim($hex,'#');
    if(strlen($hex)===3) $hex=$hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    if(strlen($hex)!==6 || !ctype_xdigit($hex)) return null;
    return [hexdec(substr($hex,0,2)),hexdec(substr($hex,2,2)),hexdec(substr($hex,4,2))];
}

function kashida_colors_showcase_rgb_string($hex){
    $rgb=kashida_colors_showcase_hex_to_rgb($hex);
    return $rgb ? sprintf('rgb(%d, %d, %d)',$rgb[0],$rgb[1],$rgb[2]) : '';
}

function kashida_colors_showcase_hsl_string($hex){
    $rgb=kashida_colors_showcase_hex_to_rgb($hex);
    if(!$rgb) return '';
    [$r,$g,$b]=array_map(static function($v){return $v/255;},$rgb);
    $max=max($r,$g,$b); $min=min($r,$g,$b); $l=($max+$min)/2;
    if($max===$min){$h=0;$s=0;}
    else{
        $d=$max-$min;
        $s=$l>0.5?$d/(2-$max-$min):$d/($max+$min);
        if($max===$r)$h=(($g-$b)/$d)+($g<$b?6:0);
        elseif($max===$g)$h=(($b-$r)/$d)+2;
        else $h=(($r-$g)/$d)+4;
        $h/=6;
    }
    return sprintf('hsl(%d°, %d%%, %d%%)',round($h*360),round($s*100),round($l*100));
}

function kashida_colors_showcase_group_attribute_config($post_id){
    $label=trim((string)get_post_meta($post_id,'_kashida_colors_showcase_attribute_label',true));
    $options=get_post_meta($post_id,'_kashida_colors_showcase_attribute_options',true); if(!is_array($options))$options=[];
    if($label===''){
        $colors=get_post_meta($post_id,'_kashida_colors_showcase_colors',true);
        if(is_array($colors)){
            $legacy=[];
            foreach($colors as $c){$v=trim((string)($c['attribute']??($c['finish']??'')));if($v!==''&&!in_array($v,$legacy,true))$legacy[]=$v;}
            if($legacy){$label='Finish Type';$options=$legacy;update_post_meta($post_id,'_kashida_colors_showcase_attribute_label',$label);update_post_meta($post_id,'_kashida_colors_showcase_attribute_options',$options);}
        }
    }
    return ['label'=>$label,'options'=>$options];
}

function kashida_colors_showcase_render_color_row($i,$c,$attribute_options=[],$attribute_label=''){
    $name=esc_attr($c['name']??'');$code=esc_attr($c['code']??'');$hex=esc_attr($c['hex']??'#FFFFFF');$attribute=esc_attr($c['attribute']??($c['finish']??''));$url=esc_attr($c['sample_url']??'');$id=absint($c['sample_id']??0);
    ?>
    <div class="kcs-color-row">
      <div class="kcs-row-top">
        <div class="kcs-color-picker-field">
          <label><?php echo esc_html(kashida_colors_showcase_t('color','Color')); ?></label>
          <button type="button" class="kcs-color-preview" style="background:<?php echo esc_attr($hex);?>" aria-label="<?php echo esc_attr(kashida_colors_showcase_t('choose_color','Choose a Color')); ?>"></button>
          <input type="hidden" class="kcs-hex-input" name="kcs_colors[<?php echo esc_attr($i);?>][hex]" value="<?php echo esc_attr($hex);?>">
          <div class="kcs-picker-panel" hidden>
            <div class="kcs-picker-head"><strong><?php echo esc_html(kashida_colors_showcase_t('choose_color','Choose a Color')); ?></strong><button type="button" class="kcs-picker-close" aria-label="<?php echo esc_attr(kashida_colors_showcase_t('close','Close')); ?>">×</button></div>
            <div class="kcs-picker-value"><span class="kcs-picker-value-swatch"></span><input type="text" class="kcs-picker-value-code kcs-hex-text" value="<?php echo esc_attr($hex);?>" maxlength="7" inputmode="text" aria-label="HEX"></div>
            <div class="kcs-palette-title"><?php echo esc_html(kashida_colors_showcase_t('color_palette','Color Palette')); ?></div>
            <div class="kcs-color-palette"></div>
            <div class="kcs-custom-row"><span><?php echo esc_html(kashida_colors_showcase_t('hex','HEX')); ?></span><input type="color" class="kcs-custom-color" value="<?php echo esc_attr($hex);?>" aria-label="<?php echo esc_attr(kashida_colors_showcase_t('hex','HEX')); ?>"><button type="button" class="button kcs-picker-close-apply"><?php echo esc_html(kashida_colors_showcase_t('select','Select')); ?></button></div>
          </div>
        </div>
        <div><label><?php echo esc_html(kashida_colors_showcase_t('color_name','Color Name')); ?></label><input type="text" name="kcs_colors[<?php echo esc_attr($i);?>][name]" value="<?php echo esc_attr($name);?>"></div>
        <div><label><?php echo esc_html(kashida_colors_showcase_t('color_code','Color Code')); ?></label><input type="text" name="kcs_colors[<?php echo esc_attr($i);?>][code]" value="<?php echo esc_attr($code);?>"></div>
        <div><label><?php echo esc_html(kashida_colors_showcase_t('hex','HEX')); ?></label><input type="text" class="kcs-hex-text" name="kcs_colors[<?php echo esc_attr($i);?>][hex_text]" value="<?php echo esc_attr($hex);?>" maxlength="7" inputmode="text"></div>
        <div class="kcs-attribute-field"><?php if($attribute_label!==''): ?><label><?php echo esc_html($attribute_label); ?></label><?php endif; ?><select name="kcs_colors[<?php echo esc_attr($i);?>][attribute]" <?php disabled($attribute_label===''); ?>><option value=""><?php echo esc_html(kashida_colors_showcase_t('attribute_none','No Attribute')); ?></option><?php foreach($attribute_options as $v):?><option value="<?php echo esc_attr($v);?>" <?php selected($attribute,$v);?>><?php echo esc_html($v);?></option><?php endforeach; ?><?php if($attribute!==''&&!in_array($attribute,$attribute_options,true)): ?><option value="<?php echo esc_attr($attribute);?>" selected><?php echo esc_html($attribute);?></option><?php endif; ?></select><input type="hidden" name="kcs_colors[<?php echo esc_attr($i);?>][attribute_fallback]" value="<?php echo esc_attr($attribute);?>"></div>
        <button type="button" class="kcs-remove-color" title="<?php echo esc_attr(kashida_colors_showcase_t('delete','Delete')); ?>">×</button>
      </div>
      <div class="kcs-row-bottom"><img src="<?php echo esc_url($url);?>" class="kcs-sample-preview <?php echo $url?'has-image':'';?>" alt="" width="56" height="42"><button type="button" class="button kcs-upload-btn"><?php echo esc_html(kashida_colors_showcase_t('upload_sample','Upload Sample Image')); ?></button><span class="kcs-remove-img <?php echo $url?'visible':'';?>"><?php echo esc_html(kashida_colors_showcase_t('remove_image','Remove Image')); ?></span><input type="hidden" class="kcs-sample-url" name="kcs_colors[<?php echo esc_attr($i);?>][sample_url]" value="<?php echo esc_url($url);?>"><input type="hidden" class="kcs-sample-id" name="kcs_colors[<?php echo esc_attr($i);?>][sample_id]" value="<?php echo esc_attr($id);?>"></div>
    </div>
    <?php
}

function kashida_colors_showcase_admin_color_assets($hook){
    if(!in_array($hook,['post.php','post-new.php'],true)) return;
    $screen=get_current_screen();
    if(!$screen || $screen->post_type!=='kashida_color_group') return;
    wp_enqueue_style('kashida-colors-showcase-admin-colors',KASHIDA_COLORS_SHOWCASE_URL.'assets/kashida-colors-admin.css',[],KASHIDA_COLORS_SHOWCASE_VERSION);
    wp_enqueue_script('kashida-colors-showcase-admin-colors',KASHIDA_COLORS_SHOWCASE_URL.'assets/kashida-colors-admin.js',['jquery'],KASHIDA_COLORS_SHOWCASE_VERSION,true);
    wp_localize_script('kashida-colors-showcase-admin-colors','KashidaColorsShowcaseColorPicker',[
        'chooseSample'=>kashida_colors_showcase_t('choose_sample','Choose Sample Image'),
        'select'=>kashida_colors_showcase_t('select','Select'),
    ]);
}
add_action('admin_enqueue_scripts','kashida_colors_showcase_admin_color_assets');

function kashida_colors_showcase_colors_metabox_cb($post){
    $colors=get_post_meta($post->ID,'_kashida_colors_showcase_colors',true);if(!is_array($colors))$colors=[]; $attribute_config=kashida_colors_showcase_group_attribute_config($post->ID); $attribute_label=$attribute_config['label']; $attribute_options=$attribute_config['options']; wp_enqueue_media();
    ?><div id="kcs-colors-wrap"><div id="kcs-colors-list"><?php foreach($colors as $i=>$c) kashida_colors_showcase_render_color_row($i,$c,$attribute_options,$attribute_label);?></div><button type="button" id="kcs-add-color" class="button button-primary" style="margin-top:12px">+ <?php echo esc_html(kashida_colors_showcase_t('add_color','Add New Color')); ?></button></div>
    <template id="kcs-color-template"><?php kashida_colors_showcase_render_color_row('__INDEX__',['name'=>'','code'=>'','hex'=>'#FFFFFF','attribute'=>'','sample_url'=>'','sample_id'=>''],$attribute_options,$attribute_label);?></template>
<?php
}

function kashida_colors_showcase_export_rows($post_id){
    $colors=get_post_meta($post_id,'_kashida_colors_showcase_colors',true);if(!is_array($colors))$colors=[];$cfg=kashida_colors_showcase_group_attribute_config($post_id);$rows=[];foreach($colors as $c){$hex=sanitize_hex_color($c['hex']??'')?:'#FFFFFF';$rows[]=['name'=>(string)($c['name']??''),'code'=>(string)($c['code']??''),'hex'=>$hex,'attribute'=>(string)($c['attribute']??($c['finish']??'')),'attribute_label'=>$cfg['label'],'sample_image'=>(string)($c['sample_url']??'')];}return $rows;
}
function kashida_colors_showcase_filesystem(){
    require_once ABSPATH . 'wp-admin/includes/file.php';
    global $wp_filesystem;
    if ( ! $wp_filesystem && ! WP_Filesystem(false, false, false) ) {
        return false;
    }
    return $wp_filesystem;
}

function kashida_colors_showcase_export_xlsx_binary($headers,$rows){
    if(!class_exists('ZipArchive'))return false;
    $tmp=wp_tempnam('kcs-export.xlsx');$zip=new ZipArchive();if(!$tmp||$zip->open($tmp,ZipArchive::OVERWRITE)!==true){if($tmp)wp_delete_file($tmp);return false;}
    $esc=function($v){return htmlspecialchars((string)$v,ENT_XML1|ENT_QUOTES,'UTF-8');};
    $shared=[];$lookup=[];$sheetRows=[];$r=1;$all=array_merge([$headers],$rows);
    foreach($all as $row){$cells=[];$col=0;foreach($row as $v){$idx=array_key_exists((string)$v,$lookup)?$lookup[(string)$v]:null;if($idx===null){$idx=count($shared);$shared[]=(string)$v;$lookup[(string)$v]=$idx;}$letters='';$n=$col+1;while($n>0){$rem=($n-1)%26;$letters=chr(65+$rem).$letters;$n=intdiv($n-1,26);} $cells[]='<c r="'.$letters.$r.'" t="s"><v>'.$idx.'</v></c>';$col++;}$sheetRows[]='<row r="'.$r.'">'.implode('',$cells).'</row>';$r++;}
    $lastRow=max(1,$r-1);$sheet='<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><dimension ref="A1:'.$letters.$lastRow.'"/><sheetData>'.implode('',$sheetRows).'</sheetData></worksheet>';
    $sst='<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="'.count($shared).'" uniqueCount="'.count($shared).'">';foreach($shared as $v)$sst.='<si><t>'.$esc($v).'</t></si>';$sst.='</sst>';
    $zip->addFromString('[Content_Types].xml','<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/></Types>');
    $zip->addFromString('_rels/.rels','<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
    $zip->addFromString('xl/workbook.xml','<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Colors" sheetId="1" r:id="rId1"/></sheets></workbook>');
    $zip->addFromString('xl/_rels/workbook.xml.rels','<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/></Relationships>');
    $zip->addFromString('xl/worksheets/sheet1.xml',$sheet);$zip->addFromString('xl/sharedStrings.xml',$sst);$zip->close();$data=file_get_contents($tmp);wp_delete_file($tmp);return $data;
}
function kashida_colors_showcase_csv_line($values){
    $values=array_map(static function($value){return (string)$value;},$values);
    $escaped=array_map(static function($value){return '"'.str_replace('"','""',$value).'"';},$values);
    return implode(',', $escaped)."\r\n";
}
add_action('admin_post_kashida_colors_showcase_export_group',function(){
    $id=absint(wp_unslash($_GET['post_id']??0));check_admin_referer('kashida_colors_showcase_export_group_'.$id);if(!$id||get_post_type($id)!=='kashida_color_group'||!current_user_can('edit_post',$id))wp_die(esc_html(kashida_colors_showcase_t('not_allowed','You are not allowed to perform this action.')));$rows=kashida_colors_showcase_export_rows($id);$headers=['name','code','hex','attribute','attribute_label','sample_image'];$format=sanitize_key($_GET['format']??'csv');$base=sanitize_file_name(get_the_title($id)?:'kashida-colors');
    if($format==='xlsx'){$data=kashida_colors_showcase_export_xlsx_binary($headers,$rows);if($data===false)wp_die(esc_html(kashida_colors_showcase_t('security_zip_unavailable','ZipArchive is not available on the server.')));nocache_headers();header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="'.$base.'.xlsx"');header('Content-Length: '.strlen($data)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Binary XLSX download.
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Binary XLSX download response; escaping would corrupt the file.
    echo $data;exit;}
    nocache_headers();header('Content-Type: text/csv; charset=utf-8');header('Content-Disposition: attachment; filename="'.$base.'.csv"');$csv='';$csv.=kashida_colors_showcase_csv_line($headers);foreach($rows as $row)$csv.=kashida_colors_showcase_csv_line($row); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSV download response.
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSV download response; escaping would corrupt the file.
    echo $csv;exit;
});

function kashida_colors_showcase_import_error($code,$message){ return new WP_Error($code,$message); }

function kashida_colors_showcase_validate_import_upload($file){
    if(!is_array($file) || !isset($file['error'],$file['tmp_name'],$file['name'],$file['size'])) return kashida_colors_showcase_import_error('upload',kashida_colors_showcase_t('security_bad_upload','The import file is invalid.'));
    if((int)$file['error'] !== UPLOAD_ERR_OK) return kashida_colors_showcase_import_error('upload_error',kashida_colors_showcase_t('security_upload_failed','The import file upload failed.'));
    if(empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) return kashida_colors_showcase_import_error('upload_source',kashida_colors_showcase_t('security_upload_source','The import file source is invalid.'));
    $name=sanitize_file_name(wp_unslash($file['name']));
    $ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));
    $allowed=['csv','xlsx','zip'];
    if(!in_array($ext,$allowed,true)) return kashida_colors_showcase_import_error('type',kashida_colors_showcase_t('security_file_type','Only CSV, XLSX, or ZIP files are supported.'));
    $size=(int)$file['size'];
    $limits=['csv'=>KASHIDA_COLORS_SHOWCASE_IMPORT_MAX_CSV_BYTES,'xlsx'=>KASHIDA_COLORS_SHOWCASE_IMPORT_MAX_XLSX_BYTES,'zip'=>KASHIDA_COLORS_SHOWCASE_IMPORT_MAX_ZIP_BYTES];
    if($size<=0 || $size>$limits[$ext]) return kashida_colors_showcase_import_error('size',kashida_colors_showcase_t('security_file_size','The import file exceeds the allowed size.'));
    $ft=wp_check_filetype_and_ext($file['tmp_name'],$name);
    if(!empty($ft['ext']) && strtolower($ft['ext'])!==$ext) return kashida_colors_showcase_import_error('mime',kashida_colors_showcase_t('security_file_mime','The file type does not match its extension.'));
    return ['ext'=>$ext,'name'=>$name,'size'=>$size];
}

function kashida_colors_showcase_validate_text_cell($value){
    $value=(string)$value;
    if(strlen($value)>KASHIDA_COLORS_SHOWCASE_IMPORT_MAX_CELL_BYTES) return '';
    return trim($value);
}

function kashida_colors_showcase_parse_csv($path){
    $filesystem=kashida_colors_showcase_filesystem();
    if(!$filesystem || !$filesystem->exists($path) || !$filesystem->is_readable($path)) return kashida_colors_showcase_import_error('csv',kashida_colors_showcase_t('security_open_csv','Unable to open CSV.'));
    $contents=$filesystem->get_contents($path);
    if($contents===false) return kashida_colors_showcase_import_error('csv',kashida_colors_showcase_t('security_open_csv','Unable to open CSV.'));
    $rows=[];$row_count=0;$column_count=0;
    foreach(preg_split("/\r\n|\r|\n/",$contents) as $line){
        if($line==='' && $row_count===0) continue;
        $r=str_getcsv($line,',');
        $row_count++;
        if($row_count>KASHIDA_COLORS_SHOWCASE_IMPORT_MAX_ROWS) return kashida_colors_showcase_import_error('rows',kashida_colors_showcase_t('security_rows','The number of rows exceeds the allowed limit.'));
        if(count($r)>KASHIDA_COLORS_SHOWCASE_IMPORT_MAX_COLUMNS) return kashida_colors_showcase_import_error('columns',kashida_colors_showcase_t('security_columns','The number of columns exceeds the allowed limit.'));
        foreach($r as $cell){if(strlen((string)$cell)>KASHIDA_COLORS_SHOWCASE_IMPORT_MAX_CELL_BYTES) return kashida_colors_showcase_import_error('cell',kashida_colors_showcase_t('security_cell','A cell is larger than the allowed size.'));}
        $column_count=max($column_count,count($r));
        $rows[]=$r;
    }
    return $rows;
}

function kashida_colors_showcase_safe_zip_open($path,$max_bytes,$max_uncompressed=KASHIDA_COLORS_SHOWCASE_IMPORT_MAX_ZIP_UNCOMPRESSED,$max_files=KASHIDA_COLORS_SHOWCASE_IMPORT_MAX_ZIP_FILES){
    if(!class_exists('ZipArchive')) return kashida_colors_showcase_import_error('zip',kashida_colors_showcase_t('security_zip_unavailable','ZipArchive is not available on the server.'));
    if(!is_readable($path) || filesize($path)>$max_bytes) return kashida_colors_showcase_import_error('zip_size',kashida_colors_showcase_t('security_file_size','The import file exceeds the allowed size.'));
    $z=new ZipArchive();
    if($z->open($path)!==true) return kashida_colors_showcase_import_error('zip_open',kashida_colors_showcase_t('security_zip_open','The ZIP file could not be opened.'));
    if($z->numFiles>$max_files){$z->close();return kashida_colors_showcase_import_error('zip_files',kashida_colors_showcase_t('security_zip_files','The number of files inside the ZIP exceeds the allowed limit.'));}
    $total=0;
    for($i=0;$i<$z->numFiles;$i++){
        $st=$z->statIndex($i);
        $name=(string)($st['name']??'');
        $external=(int)($st['external_attributes']??0);
        $unix_mode=($external >> 16) & 0xF000;
        if($unix_mode===0xA000){$z->close();return kashida_colors_showcase_import_error('zip_symlink',kashida_colors_showcase_t('security_zip_path','The ZIP contains an unsafe file path.'));}
        if($name==='' || str_replace('\\','/',$name)!==ltrim(str_replace('\\','/',$name),'/') || preg_match('#(^|/)\.\.?(/|$)#',$name)){$z->close();return kashida_colors_showcase_import_error('zip_path',kashida_colors_showcase_t('security_zip_path','The ZIP contains an unsafe file path.'));}
        if(isset($st['size'])){
            $size=(int)$st['size'];
            if($size<0 || $size>KASHIDA_COLORS_SHOWCASE_IMPORT_MAX_ZIP_UNCOMPRESSED){$z->close();return kashida_colors_showcase_import_error('zip_entry_size',kashida_colors_showcase_t('security_zip_entry_size','A file inside the ZIP exceeds the allowed size.'));}
            $total += $size;
            if($total>$max_uncompressed){$z->close();return kashida_colors_showcase_import_error('zip_bomb',kashida_colors_showcase_t('security_zip_bomb','The uncompressed ZIP size exceeds the allowed limit.'));}
        }
        if(isset($st['comp_size']) && (int)$st['comp_size']>0 && (int)$st['size']>0){
            $ratio=(float)$st['size']/(float)$st['comp_size'];
            if($ratio>1000){$z->close();return kashida_colors_showcase_import_error('zip_ratio',kashida_colors_showcase_t('security_zip_ratio','The ZIP was rejected because of an abnormal compression ratio.'));}
        }
    }
    return $z;
}

function kashida_colors_showcase_safe_xml_load($xml){
    if(!is_string($xml) || $xml==='') return false;
    if(stripos($xml,'<!DOCTYPE')!==false || stripos($xml,'<!ENTITY')!==false) return false;
    return simplexml_load_string($xml,'SimpleXMLElement',LIBXML_NONET|LIBXML_COMPACT);
}

function kashida_colors_showcase_parse_xlsx($path){
    $z=kashida_colors_showcase_safe_zip_open($path,KASHIDA_COLORS_SHOWCASE_IMPORT_MAX_XLSX_BYTES);
    if(is_wp_error($z)) return $z;
    $shared=[];
    $s=$z->getFromName('xl/sharedStrings.xml');
    if($s!==false){
        if(strlen($s)>KASHIDA_COLORS_SHOWCASE_IMPORT_MAX_ZIP_UNCOMPRESSED){$z->close();return kashida_colors_showcase_import_error('xlsx_size',kashida_colors_showcase_t('security_xlsx_size','The XLSX worksheet exceeds the allowed size.'));}
        $x=kashida_colors_showcase_safe_xml_load($s);
        if($x===false){$z->close();return kashida_colors_showcase_import_error('xml',kashida_colors_showcase_t('security_xlsx_parse','The XLSX file could not be read.'));}
        $shared_count=0;
        foreach($x->si as $si){
            $shared_count++; if($shared_count>KASHIDA_COLORS_SHOWCASE_IMPORT_MAX_ROWS*10){$z->close();return kashida_colors_showcase_import_error('xlsx_strings',kashida_colors_showcase_t('security_xlsx_strings','The number of strings inside the XLSX exceeds the allowed limit.'));}
            $txt=''; if(isset($si->t)) $txt=(string)$si->t; else foreach($si->r as $rr) $txt.=(string)$rr->t;
            if(strlen($txt)>KASHIDA_COLORS_SHOWCASE_IMPORT_MAX_CELL_BYTES) $txt='';
            $shared[]=$txt;
        }
    }
    $sheet=$z->getFromName('xl/worksheets/sheet1.xml');
    if($sheet===false){$z->close();return kashida_colors_showcase_import_error('sheet',kashida_colors_showcase_t('security_xlsx_sheet','The first worksheet could not be found.'));}
    if(strlen($sheet)>KASHIDA_COLORS_SHOWCASE_IMPORT_MAX_ZIP_UNCOMPRESSED){$z->close();return kashida_colors_showcase_import_error('xlsx_sheet_size',kashida_colors_showcase_t('security_xlsx_size','The XLSX worksheet exceeds the allowed size.'));}
    $x=kashida_colors_showcase_safe_xml_load($sheet);
    if($x===false){$z->close();return kashida_colors_showcase_import_error('xml',kashida_colors_showcase_t('security_xlsx_parse','The XLSX file could not be read.'));}
    $rows=[];$row_count=0;
    foreach($x->sheetData->row as $row){
        $row_count++; if($row_count>KASHIDA_COLORS_SHOWCASE_IMPORT_MAX_ROWS){$z->close();return kashida_colors_showcase_import_error('rows',kashida_colors_showcase_t('security_rows','The number of rows exceeds the allowed limit.'));}
        $out=[]; $cell_count=0;
        foreach($row->c as $cell){
            $cell_count++; if($cell_count>KASHIDA_COLORS_SHOWCASE_IMPORT_MAX_COLUMNS){$z->close();return kashida_colors_showcase_import_error('columns',kashida_colors_showcase_t('security_columns','The number of columns exceeds the allowed limit.'));}
            $ref=(string)$cell['r']; preg_match('/([A-Z]+)(\d+)/',$ref,$m); $col=$m[1]??'A'; $idx=0;
            for($i=0;$i<strlen($col);$i++){$idx=$idx*26+(ord($col[$i])-64);}
            $v=(string)$cell->v;
            if((string)$cell['t']==='s') $v=$shared[(int)$v]??'';
            elseif((string)$cell['t']==='inlineStr') $v=(string)$cell->is->t;
            if(strlen($v)>KASHIDA_COLORS_SHOWCASE_IMPORT_MAX_CELL_BYTES) $v='';
            $out[$idx-1]=$v;
        }
        if($out){ksort($out);$rows[]=$out;}
    }
    $z->close(); return $rows;
}

function kashida_colors_showcase_normalize_rows($rows){
    if(empty($rows) || !is_array($rows)) return [];
    $head=[]; foreach($rows[0] as $v){$k=sanitize_key(trim((string)$v)); if($k!=='') $head[]=$k;}
    if(!in_array('name',$head,true) || !in_array('hex',$head,true)) return kashida_colors_showcase_import_error('headers',kashida_colors_showcase_t('security_headers','The table must contain at least the name and hex columns.'));
    $colors=[];$seen_codes=[];$attribute_label='';
    for($i=1;$i<count($rows);$i++){
        if(count($colors)>=KASHIDA_COLORS_SHOWCASE_IMPORT_MAX_COLORS) return kashida_colors_showcase_import_error('colors_limit',kashida_colors_showcase_t('security_colors_limit','The number of colors exceeds the allowed limit.'));
        $r=$rows[$i]; $item=[]; foreach($head as $k=>$key) $item[$key]=isset($r[$k])?kashida_colors_showcase_validate_text_cell($r[$k]):'';
        if($attribute_label==='' && !empty($item['attribute_label'])) $attribute_label=sanitize_text_field($item['attribute_label']);
        if(empty($item['name'])&&empty($item['code'])&&empty($item['hex'])) continue;
        $name=sanitize_text_field($item['name']??''); $code=sanitize_text_field($item['code']??''); $raw_hex=trim((string)($item['hex']??''));
        if($name==='') continue;
        $hex=sanitize_hex_color($raw_hex); if(!$hex) continue;
        $attribute=sanitize_text_field($item['attribute']??''); if($attribute_label==='' && $attribute!=='') $attribute_label='Attribute';
        if($code!==''){ $code_key=strtolower($code); if(isset($seen_codes[$code_key])) continue; $seen_codes[$code_key]=1; }
        $image=esc_url_raw($item['sample_image']??''); if($image!=='' && !wp_http_validate_url($image)) $image='';
        $colors[]=['name'=>$name,'code'=>$code,'hex'=>$hex,'attribute'=>$attribute,'sample_url'=>$image,'sample_id'=>0];
    }
    return ['colors'=>$colors,'attribute_label'=>$attribute_label];
}
function kashida_colors_showcase_zip_image_content($z,$entry){
    $st=$z->statName($entry); if(!$st) return new WP_Error('image_entry',kashida_colors_showcase_t('security_image_read','A ZIP image could not be read.'));
    $size=(int)($st['size']??0); if($size<=0 || $size>KASHIDA_COLORS_SHOWCASE_IMPORT_MAX_IMAGE_BYTES) return new WP_Error('image_size',kashida_colors_showcase_t('security_image_size','An image inside the ZIP exceeds the allowed size.'));
    $data=$z->getFromName($entry); if($data===false || strlen($data)>KASHIDA_COLORS_SHOWCASE_IMPORT_MAX_IMAGE_BYTES) return new WP_Error('image_read',kashida_colors_showcase_t('security_image_read','A ZIP image could not be read.'));
    if(function_exists('getimagesizefromstring')){
        $info=@getimagesizefromstring($data); if(!$info || empty($info['mime']) || !in_array(strtolower($info['mime']),['image/jpeg','image/png','image/webp','image/gif'],true)) return new WP_Error('image_type',kashida_colors_showcase_t('security_image_type','A file inside the ZIP is not a supported image.'));
    } else return new WP_Error('image_check',kashida_colors_showcase_t('security_image_check','The image type could not be verified.'));
    return $data;
}
function kashida_colors_showcase_apply_zip_images($colors,$zip_path){
    if(!class_exists('ZipArchive')) return $colors;
    $z=kashida_colors_showcase_safe_zip_open($zip_path,KASHIDA_COLORS_SHOWCASE_IMPORT_MAX_ZIP_BYTES); if(is_wp_error($z)) return $z;
    $names=[]; $image_count=0;
    for($i=0;$i<$z->numFiles;$i++){
        $n=$z->getNameIndex($i); if(!is_string($n) || strpos($n,'/')!==false && basename($n)!==$n) continue;
        if(preg_match('/\.(jpg|jpeg|png|webp|gif)$/i',$n)){ $image_count++; if($image_count>KASHIDA_COLORS_SHOWCASE_IMPORT_MAX_COLORS){$z->close();return kashida_colors_showcase_import_error('images_limit',kashida_colors_showcase_t('security_images_limit','The number of images inside the ZIP exceeds the allowed limit.'));} $names[strtolower(basename($n))]=$n; }
    }
    foreach($colors as &$c){
        if(!empty($c['sample_url'])) continue;
        $targets=[sanitize_file_name((string)$c['name']),sanitize_file_name((string)$c['code'])];
        foreach($targets as $t){ if($t==='') continue; foreach(['jpg','jpeg','png','webp','gif'] as $ext){$key=strtolower($t.'.'.$ext); if(!isset($names[$key])) continue; $data=kashida_colors_showcase_zip_image_content($z,$names[$key]); if(is_wp_error($data)){ $z->close(); return $data; } $upload=wp_upload_bits(sanitize_file_name($key),null,$data); if(empty($upload['error']) && !empty($upload['url'])){$c['sample_url']=esc_url_raw($upload['url']); break 2;} }}
    }
    unset($c); $z->close(); return $colors;
}

function kashida_colors_showcase_raw_duplicate_count($rows){if(!is_array($rows)||count($rows)<2)return 0;$head=[];foreach($rows[0] as $v)$head[]=sanitize_key(trim((string)$v));$idx=array_search('code',$head,true);if($idx===false)return 0;$seen=[];$dupes=0;for($i=1,$n=count($rows);$i<$n;$i++){if(!isset($rows[$i][$idx]))continue;$code=strtolower(trim((string)$rows[$i][$idx]));if($code!==''&&isset($seen[$code]))$dupes++;if($code!=='')$seen[$code]=1;}return $dupes;}

add_action('wp_ajax_kashida_colors_showcase_preview_import',function(){
    check_ajax_referer('kashida_colors_showcase_ajax_import','nonce');$post_id=absint(wp_unslash($_POST['post_id']??0));if(!$post_id||get_post_type($post_id)!=='kashida_color_group'||!current_user_can('edit_post',$post_id))wp_send_json_error(kashida_colors_showcase_t('security_group','You are not allowed to edit this group.'));if(empty($_FILES['kcs_excel_file']))wp_send_json_error(kashida_colors_showcase_t('security_no_file','No file was selected.'));
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- File upload metadata is validated by kashida_colors_showcase_validate_import_upload().
    $file=$_FILES['kcs_excel_file'];$validated=kashida_colors_showcase_validate_import_upload($file);if(is_wp_error($validated))wp_send_json_error($validated->get_error_message());$ext=$validated['ext'];$rows=null;$tmp='';$cleanup=[];$filesystem=false;
    if($ext==='csv')$rows=kashida_colors_showcase_parse_csv($file['tmp_name']);elseif($ext==='xlsx')$rows=kashida_colors_showcase_parse_xlsx($file['tmp_name']);else{$tmp=wp_tempnam($validated['name']);if(!$tmp || !($filesystem=kashida_colors_showcase_filesystem()) || !$filesystem->move($file['tmp_name'],$tmp,true)){if($tmp)wp_delete_file($tmp);wp_send_json_error(kashida_colors_showcase_t('security_temp','The ZIP file could not be safely prepared.'));}$cleanup[]=$tmp;$z=kashida_colors_showcase_safe_zip_open($tmp,KASHIDA_COLORS_SHOWCASE_IMPORT_MAX_ZIP_BYTES);if(is_wp_error($z)){foreach($cleanup as $f)wp_delete_file($f);wp_send_json_error($z->get_error_message());}$csv=$xlsx=null;for($i=0;$i<$z->numFiles;$i++){$n=$z->getNameIndex($i);if(!$n||strpos($n,'/')!==false)continue;$e=strtolower(pathinfo($n,PATHINFO_EXTENSION));if($e==='xlsx'&&!$xlsx)$xlsx=$n;elseif($e==='csv'&&!$csv)$csv=$n;}if(!$csv&&!$xlsx){$z->close();foreach($cleanup as $f)wp_delete_file($f);wp_send_json_error(kashida_colors_showcase_t('security_zip_data','No safe CSV or XLSX file was found inside the ZIP.'));}$inner=$xlsx?:$csv;$data=$z->getFromName($inner);$z->close();$tmp2=wp_tempnam($inner);if(!$tmp2 || !($filesystem=kashida_colors_showcase_filesystem()) || $data===false || !$filesystem->put_contents($tmp2,$data,FS_CHMOD_FILE)){if($tmp2)wp_delete_file($tmp2);foreach($cleanup as $f)wp_delete_file($f);wp_send_json_error(kashida_colors_showcase_t('security_extract','The data file could not be safely extracted.'));}$cleanup[]=$tmp2;$rows=$xlsx?kashida_colors_showcase_parse_xlsx($tmp2):kashida_colors_showcase_parse_csv($tmp2);foreach($cleanup as $f)wp_delete_file($f);}
    $normalized=is_wp_error($rows)?$rows:kashida_colors_showcase_normalize_rows($rows);if(is_wp_error($normalized))wp_send_json_error($normalized->get_error_message());$colors=$normalized['colors'];$raw_duplicates=kashida_colors_showcase_raw_duplicate_count($rows);$existing=get_post_meta($post_id,'_kashida_colors_showcase_colors',true);if(!is_array($existing))$existing=[];$existing_codes=[];foreach($existing as $c){$code=strtolower(trim((string)($c['code']??'')));if($code!=='')$existing_codes[$code]=1;}$seen=[];$duplicates=0;$existing_count=0;foreach($colors as $c){$code=strtolower(trim((string)($c['code']??'')));if($code!==''&&isset($seen[$code]))$duplicates++;if($code!=='')$seen[$code]=1;if($code!==''&&isset($existing_codes[$code]))$existing_count++;}wp_send_json_success(['count'=>count($colors),'duplicates'=>max($duplicates,$raw_duplicates),'existing'=>$existing_count,'attribute_label'=>$normalized['attribute_label']??'']);
});

add_action('wp_ajax_kashida_colors_showcase_import_excel',function(){
    check_ajax_referer('kashida_colors_showcase_ajax_import','nonce');
    if(!current_user_can('edit_posts')) wp_send_json_error(kashida_colors_showcase_t('security_not_allowed','You are not allowed to perform this import.'));
    $post_id=absint(wp_unslash($_POST['post_id']??0));
    if(!$post_id || get_post_type($post_id)!=='kashida_color_group') wp_send_json_error(kashida_colors_showcase_t('security_invalid_group','Invalid group.'));
    if(!current_user_can('edit_post',$post_id)) wp_send_json_error(kashida_colors_showcase_t('security_group','You are not allowed to edit this group.'));
    if(empty($_FILES['kcs_excel_file'])) wp_send_json_error(kashida_colors_showcase_t('security_no_file','No file was selected.'));
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- File upload metadata is validated by kashida_colors_showcase_validate_import_upload().
    $file=$_FILES['kcs_excel_file'];
    $validated=kashida_colors_showcase_validate_import_upload($file); if(is_wp_error($validated)) wp_send_json_error($validated->get_error_message());
    $ext=$validated['ext']; $rows=null; $colors=null; $cleanup=[]; $filesystem=false;
    if($ext==='csv') $rows=kashida_colors_showcase_parse_csv($file['tmp_name']);
    elseif($ext==='xlsx') $rows=kashida_colors_showcase_parse_xlsx($file['tmp_name']);
    elseif($ext==='zip'){
        $tmp=wp_tempnam($validated['name']);
        if(!$tmp || !($filesystem=kashida_colors_showcase_filesystem()) || !$filesystem->move($file['tmp_name'],$tmp,true)){ if($tmp) wp_delete_file($tmp); wp_send_json_error(kashida_colors_showcase_t('security_temp','The ZIP file could not be safely prepared.')); }
        $cleanup[]=$tmp;
        $z=kashida_colors_showcase_safe_zip_open($tmp,KASHIDA_COLORS_SHOWCASE_IMPORT_MAX_ZIP_BYTES);
        if(is_wp_error($z)){foreach($cleanup as $f)wp_delete_file($f);wp_send_json_error($z->get_error_message());}
        $csv=null;$xlsx=null;
        for($i=0;$i<$z->numFiles;$i++){
            $n=$z->getNameIndex($i); if(!$n || strpos($n,'/')!==false) continue;
            $e=strtolower(pathinfo($n,PATHINFO_EXTENSION));
            if($e==='csv' && !$csv) $csv=$n; elseif($e==='xlsx' && !$xlsx) $xlsx=$n;
        }
        if(!$csv && !$xlsx){$z->close();foreach($cleanup as $f)wp_delete_file($f);wp_send_json_error(kashida_colors_showcase_t('security_zip_data','No safe CSV or XLSX file was found inside the ZIP.'));}
        $inner_name=$xlsx?:$csv; $inner_data=$z->getFromName($inner_name); $z->close();
        if($inner_data===false || strlen($inner_data)>($xlsx?KASHIDA_COLORS_SHOWCASE_IMPORT_MAX_XLSX_BYTES:KASHIDA_COLORS_SHOWCASE_IMPORT_MAX_CSV_BYTES)){foreach($cleanup as $f)wp_delete_file($f);wp_send_json_error(kashida_colors_showcase_t('security_data_size','The data file inside the ZIP exceeds the allowed size.'));}
        $inner=wp_tempnam($inner_name); $filesystem=kashida_colors_showcase_filesystem(); if(!$inner || !$filesystem || !$filesystem->put_contents($inner,$inner_data,FS_CHMOD_FILE)){ if($inner)wp_delete_file($inner);foreach($cleanup as $f)wp_delete_file($f);wp_send_json_error(kashida_colors_showcase_t('security_extract','The data file could not be safely extracted.'));}
        $cleanup[]=$inner;
        $rows=$xlsx?kashida_colors_showcase_parse_xlsx($inner):kashida_colors_showcase_parse_csv($inner);
        $normalized=is_wp_error($rows)?$rows:kashida_colors_showcase_normalize_rows($rows);
        if(!is_wp_error($normalized)){ $colors=$normalized['colors']; if(!empty($normalized['attribute_label'])) update_post_meta($post_id,'_kashida_colors_showcase_attribute_label',sanitize_text_field($normalized['attribute_label'])); $colors=kashida_colors_showcase_apply_zip_images($colors,$tmp); } else $colors=$normalized;
        foreach($cleanup as $f)wp_delete_file($f);
        if(is_wp_error($colors)) wp_send_json_error($colors->get_error_message());
    }
    if($colors===null){ $normalized=is_wp_error($rows)?$rows:kashida_colors_showcase_normalize_rows($rows); if(!is_wp_error($normalized)){ $colors=$normalized['colors']; if(!empty($normalized['attribute_label'])) update_post_meta($post_id,'_kashida_colors_showcase_attribute_label',sanitize_text_field($normalized['attribute_label'])); } else $colors=$normalized; }
    if(is_wp_error($colors)) wp_send_json_error($colors->get_error_message());
    if(!$colors) wp_send_json_error(kashida_colors_showcase_t('security_no_colors','No valid colors were found.'));
    $existing_options=get_post_meta($post_id,'_kashida_colors_showcase_attribute_options',true); if(!is_array($existing_options))$existing_options=[];
    foreach($colors as $ic){$iv=trim((string)($ic['attribute']??''));if($iv!==''&&!in_array($iv,$existing_options,true)&&count($existing_options)<50)$existing_options[]=$iv;}
    if($existing_options) update_post_meta($post_id,'_kashida_colors_showcase_attribute_options',$existing_options);
    $mode=sanitize_key(wp_unslash($_POST['duplicate_mode']??'replace_all'));$current=get_post_meta($post_id,'_kashida_colors_showcase_colors',true);if(!is_array($current))$current=[];
    if($mode==='replace_all'){$final=$colors;}else{
        $index=[];foreach($current as $i=>$c){$code=strtolower(trim((string)($c['code']??'')));if($code!=='')$index[$code]=$i;}
        $final=$current;foreach($colors as $incoming){$code=strtolower(trim((string)($incoming['code']??'')));if($code!==''&&isset($index[$code])){if($mode==='append_update')$final[$index[$code]]=$incoming;continue;} $final[]=$incoming;if($code!=='')$index[$code]=count($final)-1;}
    }
    update_post_meta($post_id,'_kashida_colors_showcase_colors',$final);
    wp_send_json_success(['count'=>count($colors),'final_count'=>count($final)]);
});

function kashida_colors_showcase_save_group_data($post_id){
    if(get_post_type($post_id)!=='kashida_color_group') return;
    kashida_colors_showcase_group_code($post_id);
    if(isset($_POST['kcs_group_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['kcs_group_nonce'])),'kcs_save_group') && !(defined('DOING_AUTOSAVE')&&DOING_AUTOSAVE) && current_user_can('edit_post',$post_id)){
        update_post_meta($post_id,'_kashida_colors_showcase_active',isset($_POST['kcs_active'])?'1':'0');
        $display_fields=['desktop_columns'=>['meta'=>'_kashida_colors_showcase_desktop','default'=>6,'max'=>12],'tablet_columns'=>['meta'=>'_kashida_colors_showcase_tablet','default'=>4,'max'=>8],'mobile_columns'=>['meta'=>'_kashida_colors_showcase_mobile','default'=>2,'max'=>6]];
        foreach($display_fields as $field=>$cfg){$v=absint($_POST['kcs_'.$field]??$cfg['default']);$v=max(1,min($cfg['max'],$v));update_post_meta($post_id,$cfg['meta'],(string)$v);}
        foreach(['name','code','hex','rgb','hsl','attribute','image'] as $field){update_post_meta($post_id,'_kashida_colors_showcase_show_'.$field,isset($_POST['kcs_show_'.$field])?'1':'0');}
        $shape=sanitize_key(wp_unslash($_POST['kcs_color_shape']??'rectangle')); update_post_meta($post_id,'_kashida_colors_showcase_color_shape',in_array($shape,['rectangle','square','circle','compact'],true)?$shape:'rectangle');
        $attribute_label=sanitize_text_field(wp_unslash($_POST['kcs_attribute_label']??''));
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Each option is sanitized below.
        $raw_options=isset($_POST['kcs_attribute_options'])&&is_array($_POST['kcs_attribute_options'])?wp_unslash($_POST['kcs_attribute_options']):[];
        $attribute_options=[]; foreach($raw_options as $opt){$opt=sanitize_text_field($opt);if($opt!==''&&!in_array($opt,$attribute_options,true))$attribute_options[]=$opt; if(count($attribute_options)>=50)break;}
        if($attribute_label==='') $attribute_options=[];
        update_post_meta($post_id,'_kashida_colors_showcase_attribute_label',$attribute_label);
        update_post_meta($post_id,'_kashida_colors_showcase_attribute_options',$attribute_options);
        if(isset($_POST['kcs_colors'])&&is_array($_POST['kcs_colors'])){
            $colors=[];
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Each color field is sanitized below.
            foreach(wp_unslash($_POST['kcs_colors']) as $c){
                $hex=sanitize_hex_color($c['hex_text']??'')?:sanitize_hex_color($c['hex']??'')?:'#FFFFFF';
                $attribute=sanitize_text_field($c['attribute']??($c['attribute_fallback']??''));
                if($attribute!==''&&!in_array($attribute,$attribute_options,true)&&count($attribute_options)<50)$attribute_options[]=$attribute;
                $colors[]=['name'=>sanitize_text_field($c['name']??''),'code'=>sanitize_text_field($c['code']??''),'hex'=>$hex,'attribute'=>$attribute,'sample_url'=>esc_url_raw($c['sample_url']??''),'sample_id'=>absint($c['sample_id']??0)];
            }
            update_post_meta($post_id,'_kashida_colors_showcase_attribute_options',$attribute_options);
            update_post_meta($post_id,'_kashida_colors_showcase_colors',$colors);
        }
    }
    $code=kashida_colors_showcase_group_code($post_id); $slug=sanitize_title($code);
    if(get_post_field('post_name',$post_id)!==$slug){
        remove_action('save_post_kashida_color_group','kashida_colors_showcase_save_group_data',10);
        wp_update_post(['ID'=>$post_id,'post_name'=>$slug]);
        add_action('save_post_kashida_color_group','kashida_colors_showcase_save_group_data',10,1);
    }
}
add_action('save_post_kashida_color_group','kashida_colors_showcase_save_group_data',10,1);

function kashida_colors_showcase_enqueue_frontend(){
    static $done=false;if($done)return;$done=true;
    wp_enqueue_style('kashida-colors-showcase-frontend',KASHIDA_COLORS_SHOWCASE_URL.'assets/kashida-colors.css',[],KASHIDA_COLORS_SHOWCASE_VERSION);
    wp_enqueue_script('kashida-colors-showcase-frontend',KASHIDA_COLORS_SHOWCASE_URL.'assets/kashida-colors.js',[],KASHIDA_COLORS_SHOWCASE_VERSION,true);
    wp_localize_script('kashida-colors-showcase-frontend','kashidaColorsShowcaseData',[
        'selectText'=>kashida_colors_showcase_t('select','Select'),'selectedText'=>kashida_colors_showcase_t('selected','Selected'),'selectedCount'=>kashida_colors_showcase_t('selected_count','Selected color'),'selectedCountPlural'=>kashida_colors_showcase_t('selected_count_plural','Selected colors'),'showSelected'=>kashida_colors_showcase_t('show_selected','Show Selected'),'searchPlaceholder'=>kashida_colors_showcase_t('search_placeholder','Search by color name or code…'),'allAttributes'=>kashida_colors_showcase_t('all_attributes','All Attributes'),'attributeLabel'=>kashida_colors_showcase_t('attribute_label','Attribute'),'similar'=>kashida_colors_showcase_t('similar','Similar Colors'),'copied'=>kashida_colors_showcase_t('copied','Copied'),'empty'=>kashida_colors_showcase_t('empty','Select some colors first.'),'print'=>kashida_colors_showcase_t('print','Print'),'share'=>kashida_colors_showcase_t('share','Share'),'copy'=>kashida_colors_showcase_t('copy','Copy'),'noResults'=>kashida_colors_showcase_t('no_results','No matching results.'),'close'=>kashida_colors_showcase_t('close','Close'),'selectedColors'=>kashida_colors_showcase_t('selected_colors','Selected Colors'),'similarTo'=>kashida_colors_showcase_t('similar_to','Similar colors to'),'colorWheel'=>kashida_colors_showcase_t('color_wheel','Color Wheel'),'chooseColor'=>kashida_colors_showcase_t('choose_color','Choose a Color'),'findNearest'=>kashida_colors_showcase_t('find_nearest','Find Nearest'),'clearColor'=>kashida_colors_showcase_t('clear_color','Clear Color'),'nearestResults'=>kashida_colors_showcase_t('nearest_results','Nearest Colors'),'nearestHint'=>kashida_colors_showcase_t('nearest_hint','Choose a color from the wheel to display the nearest colors in the same color family.'),'noNearest'=>kashida_colors_showcase_t('no_nearest','No nearby colors were found in this color family.'),'colorName'=>kashida_colors_showcase_t('color_name','Color Name'),'colorCode'=>kashida_colors_showcase_t('color_code','Color Code')
    ]);
}
function kashida_colors_showcase_render_selection_modal(){static $done=false;if($done)return;$done=true;?><div class="kcs-modal" id="kcs-selection-modal" aria-hidden="true"><div class="kcs-modal-backdrop" data-kcs-close></div><div class="kcs-modal-dialog" role="dialog" aria-modal="true"><div class="kcs-modal-header"><h3 class="kcs-modal-title"><?php echo esc_html(kashida_colors_showcase_t('selected_colors','Selected Colors')); ?></h3><button type="button" class="kcs-modal-close" data-kcs-close aria-label="<?php echo esc_attr(kashida_colors_showcase_t('close','Close')); ?>">×</button></div><div class="kcs-modal-body"><div class="kcs-print-grid" id="kcs-print-grid"></div></div><div class="kcs-modal-actions"><button type="button" class="kcs-action-btn" data-kcs-print><?php echo esc_html(kashida_colors_showcase_t('print','Print')); ?></button><button type="button" class="kcs-action-btn" data-kcs-share><?php echo esc_html(kashida_colors_showcase_t('share','Share')); ?></button></div></div></div><?php }

function kashida_colors_showcase_shortcode($atts){
    $atts=shortcode_atts(['group'=>'','groups'=>'','columns'=>'','desktop'=>'','tablet'=>'','mobile'=>'','show_name'=>'','show_code'=>'','show_hex'=>'','show_rgb'=>'','show_hsl'=>'','show_attribute'=>'','show_image'=>'','show_finish'=>'','shape'=>'','title'=>'true','search'=>'true','filter'=>'true','similar'=>'true','sort'=>'true'],$atts,'paint_colors');
    $requested=$atts['groups']!==''?$atts['groups']:$atts['group']; if($atts['show_attribute']==='') $atts['show_attribute']=$atts['show_finish'];
    if(!$requested) return '<p class="kcs-error">'.esc_html(kashida_colors_showcase_t('group_required','Please select a group.')).'</p>';
    $values=array_filter(array_map('trim',preg_split('/[,|]+/',(string)$requested)));
    $posts=[];$colors=[];$titles=[];$thumb='';
    foreach($values as $value){$post=kashida_colors_showcase_find_group($value);if(!$post || $post->post_status!=='publish')continue;if(get_post_meta($post->ID,'_kashida_colors_showcase_active',true)==='0')continue;$posts[]=$post;$titles[]=$post->post_title;if(!$thumb&&has_post_thumbnail($post->ID))$thumb=get_the_post_thumbnail_url($posts[0]->ID,'thumbnail');$group_colors=get_post_meta($post->ID,'_kashida_colors_showcase_colors',true);if(is_array($group_colors))foreach($group_colors as $c){$c['_group_title']=$post->post_title;$colors[]=$c;}}
    if(!$posts) return '<p class="kcs-error">'.esc_html(kashida_colors_showcase_t('group_not_found','Group not found.')).'</p>';
    if(!$colors) return '<p class="kcs-empty">'.esc_html(kashida_colors_showcase_t('no_colors_group','There are no colors in this group yet.')).'</p>';
    $attribute_explicit=($atts['show_attribute']!=='');$base_display=kashida_colors_showcase_group_display_config($posts[0]->ID);$shape=in_array($atts['shape'],['rectangle','square','circle','compact'],true)?$atts['shape']:$base_display['shape'];$desktop_value=$atts['desktop']!==''?$atts['desktop']:($atts['columns']!==''?$atts['columns']:$base_display['desktop']);$tablet_value=$atts['tablet']!==''?$atts['tablet']:$base_display['tablet'];$mobile_value=$atts['mobile']!==''?$atts['mobile']:$base_display['mobile'];$desktop=max(1,min(12,absint($desktop_value)));$tablet=max(1,min(8,absint($tablet_value)));$mobile=max(1,min(6,absint($mobile_value)));
    foreach(['name','code','hex','rgb','hsl','attribute','image'] as $df){if($atts['show_'.$df]==='')$atts['show_'.$df]=$base_display['show_'.$df];if($atts['show_'.$df]==='true')$atts['show_'.$df]='1';elseif($atts['show_'.$df]==='false')$atts['show_'.$df]='0';}
    if(!$attribute_explicit && $atts['show_finish']!=='')$atts['show_attribute']=($atts['show_finish']==='true'?'1':($atts['show_finish']==='false'?'0':$atts['show_finish']));
    $attribute_label='';$attribute_options=[]; foreach($posts as $p){$cfg=kashida_colors_showcase_group_attribute_config($p->ID);$lab=$cfg['label'];$opts=$cfg['options'];if($lab!==''&&$attribute_label==='')$attribute_label=$lab;if(is_array($opts))foreach($opts as $o){$o=trim((string)$o);if($o!==''&&!in_array($o,$attribute_options,true))$attribute_options[]=$o;}}
    kashida_colors_showcase_enqueue_frontend();kashida_colors_showcase_render_selection_modal();ob_start();
    ?><div class="kcs-group-wrap kcs-color-shape-<?php echo esc_attr($shape); ?>" data-columns="<?php echo (int)$desktop;?>" style="--kcs-cols-desktop:<?php echo (int)$desktop;?>;--kcs-cols-tablet:<?php echo (int)$tablet;?>;--kcs-cols-mobile:<?php echo (int)$mobile;?>;">
    <div class="kcs-group-header-wrap">
    <?php if($atts['title']==='true'): ?><div class="kcs-group-header"><?php if(has_post_thumbnail($posts[0]->ID)): ?><img src="<?php echo esc_url(get_the_post_thumbnail_url($post->ID,'thumbnail'));?>" class="kcs-group-thumb" alt="" width="48" height="48" loading="lazy"><?php endif;?><div><h3 class="kcs-group-title"><?php echo esc_html(count($titles)>1?implode(' / ',$titles):$titles[0]);?></h3><?php if($posts[0]->post_excerpt):?><p class="kcs-group-desc"><?php echo esc_html($posts[0]->post_excerpt);?></p><?php endif;?></div></div><?php endif;?>
    <?php if($atts['search']==='true'||$atts['filter']==='true'||$atts['sort']==='true'): ?><div class="kcs-toolbar">
    <?php if($atts['search']==='true'): ?><div class="kcs-search"><span>⌕</span><input type="search" data-kcs-search placeholder="<?php echo esc_attr(kashida_colors_showcase_t('search_placeholder','Search by color name or code…'));?>" aria-label="<?php echo esc_attr(kashida_colors_showcase_t('search','Search'));?>"></div><button type="button" class="kcs-wheel-toggle" data-kcs-wheel-toggle><?php echo esc_html(kashida_colors_showcase_t('color_wheel','Color Wheel'));?></button><?php endif;?>
    <?php if($atts['filter']==='true' && $attribute_label!==''): ?><select data-kcs-attribute aria-label="<?php echo esc_attr($attribute_label);?>"><option value=""><?php echo esc_html(kashida_colors_showcase_t('all_attributes','All Attributes'));?></option><?php foreach($attribute_options as $v):?><option value="<?php echo esc_attr($v);?>"><?php echo esc_html($v);?></option><?php endforeach;?></select><?php endif;?>
    <?php if($atts['sort']==='true'): ?><select data-kcs-sort aria-label="<?php echo esc_attr(kashida_colors_showcase_t('sort','Sort'));?>"><option value="name_asc"><?php echo esc_html(kashida_colors_showcase_t('sort_name_asc','Name A–Z'));?></option><option value="name_desc"><?php echo esc_html(kashida_colors_showcase_t('sort_name_desc','Name Z–A'));?></option><option value="code_asc"><?php echo esc_html(kashida_colors_showcase_t('sort_code_asc','Code A–Z'));?></option><option value="attribute"><?php echo esc_html(kashida_colors_showcase_t('sort_attribute','Attribute'));?></option><option value="hex"><?php echo esc_html(kashida_colors_showcase_t('sort_hex','HEX'));?></option></select><?php endif;?></div><?php endif;?>
    <?php if($atts['search']==='true'): ?><div class="kcs-wheel-panel" data-kcs-wheel-panel hidden><div class="kcs-wheel-row"><div class="kcs-color-wheel" data-kcs-color-wheel role="slider" tabindex="0" aria-label="<?php echo esc_attr(kashida_colors_showcase_t('color_wheel','Color Wheel'));?>"></div><div class="kcs-wheel-controls"><div class="kcs-wheel-preview" data-kcs-wheel-preview></div><strong data-kcs-wheel-value>#FFFFFF</strong><p><?php echo esc_html(kashida_colors_showcase_t('nearest_hint','Choose a color from the wheel to display the nearest colors in the same color family.'));?></p><div class="kcs-wheel-actions"><button type="button" class="kcs-action-btn" data-kcs-find-nearest><?php echo esc_html(kashida_colors_showcase_t('find_nearest','Find Nearest'));?></button><button type="button" class="kcs-wheel-clear" data-kcs-clear-wheel><?php echo esc_html(kashida_colors_showcase_t('clear_color','Clear Color'));?></button></div></div></div><div class="kcs-nearest-results" data-kcs-nearest-results hidden><div class="kcs-nearest-head"><strong><?php echo esc_html(kashida_colors_showcase_t('nearest_results','Nearest Colors'));?></strong></div><div class="kcs-nearest-grid" data-kcs-nearest-grid></div></div></div><?php endif;?>
    </div>
    <div class="kcs-selection-bar"><span class="kcs-selection-count" data-kcs-selection-count>0 <?php echo esc_html(kashida_colors_showcase_t('selected_count','Selected color'));?></span><button type="button" class="kcs-open-selection" data-kcs-open-selection><?php echo esc_html(kashida_colors_showcase_t('show_selected','Show Selected'));?></button></div>
    <div class="kcs-no-results" data-kcs-no-results style="display:none"><?php echo esc_html(kashida_colors_showcase_t('no_results','No matching results.'));?></div>
    <div class="kcs-colors-grid">
    <?php foreach($colors as $color): $attribute=(string)($color['attribute']??($color['finish']??''));$label=$attribute;$hex=sanitize_hex_color($color['hex']??'')?:'#FFFFFF';$rgb=kashida_colors_showcase_rgb_string($hex);$hsl=kashida_colors_showcase_hsl_string($hex); ?>
    <div class="kcs-color-card" data-color-name="<?php echo esc_attr($color['name']??''); ?>" data-color-code="<?php echo esc_attr($color['code']??''); ?>" data-color-hex="<?php echo esc_attr($hex); ?>" data-color-rgb="<?php echo esc_attr($rgb); ?>" data-color-hsl="<?php echo esc_attr($hsl); ?>" data-color-attribute="<?php echo esc_attr($label); ?>" data-color-image="<?php echo esc_url($color['sample_url']??''); ?>" data-color-group="<?php echo esc_attr($color['_group_title']??''); ?>">
      <div class="kcs-swatch" style="background:<?php echo esc_attr($hex); ?>">
        <?php if($atts['show_image']==='1' && !empty($color['sample_url'])):?><div class="kcs-sample-overlay"><img src="<?php echo esc_url($color['sample_url']); ?>" alt="" loading="lazy"></div><?php endif;?>
        <button type="button" class="kcs-copy-btn" data-hex="<?php echo esc_attr($hex); ?>" data-name="<?php echo esc_attr($color['name']??''); ?>" data-code="<?php echo esc_attr($color['code']??''); ?>" data-attribute="<?php echo esc_attr($label); ?>" data-rgb="<?php echo esc_attr($rgb); ?>" data-hsl="<?php echo esc_attr($hsl); ?>" data-show-name="<?php echo esc_attr($atts['show_name']==='1'?'1':'0'); ?>" data-show-code="<?php echo esc_attr($atts['show_code']==='1'?'1':'0'); ?>" data-show-attribute="<?php echo esc_attr(($atts['show_attribute']==='1' && $attribute_label!=='' && $label!=='')?'1':'0'); ?>" data-show-hex="<?php echo esc_attr($atts['show_hex']==='1'?'1':'0'); ?>" data-show-rgb="<?php echo esc_attr($atts['show_rgb']==='1'?'1':'0'); ?>" data-show-hsl="<?php echo esc_attr($atts['show_hsl']==='1'?'1':'0'); ?>" aria-label="<?php echo esc_attr(kashida_colors_showcase_t('copy_color','Copy Color')); ?>" title="<?php echo esc_attr(kashida_colors_showcase_t('copy','Copy')); ?>">⧉</button>
        <button type="button" class="kcs-select-btn"><?php echo esc_html(kashida_colors_showcase_t('select','Select')); ?></button>
      </div>
      <div class="kcs-color-info">
        <?php if(count($posts)>1 && !empty($color['_group_title'])):?><span class="kcs-color-group-label"><?php echo esc_html($color['_group_title']);?></span><?php endif;?>
        <?php if($atts['show_name']==='1'): ?><span class="kcs-color-name"><?php echo esc_html($color['name']??'');?></span><?php endif;?>
        <?php if($atts['show_code']==='1'&&!empty($color['code'])):?><span class="kcs-color-code"><?php echo esc_html($color['code']);?></span><?php endif;?>
        <?php if($atts['show_attribute']==='1' && $attribute_label!=='' && $label!==''):?><span class="kcs-finish-tag"><?php echo esc_html($label);?></span><?php endif;?>
        <?php if($atts['show_hex']==='1'): ?><button type="button" class="kcs-value-copy" data-copy-value="<?php echo esc_attr($hex); ?>" title="<?php echo esc_attr(kashida_colors_showcase_t('copy_hex','Copy HEX')); ?>"><b>HEX</b><span><?php echo esc_html($hex);?></span></button><?php endif;?>
        <?php if($atts['show_rgb']==='1'): ?><button type="button" class="kcs-value-copy" data-copy-value="<?php echo esc_attr($rgb); ?>" title="<?php echo esc_attr(kashida_colors_showcase_t('copy_rgb','Copy RGB')); ?>"><b>RGB</b><span><?php echo esc_html($rgb);?></span></button><?php endif;?>
        <?php if($atts['show_hsl']==='1'): ?><button type="button" class="kcs-value-copy" data-copy-value="<?php echo esc_attr($hsl); ?>" title="<?php echo esc_attr(kashida_colors_showcase_t('hsl','HSL')); ?>"><b>HSL</b><span><?php echo esc_html($hsl);?></span></button><?php endif;?>
        <?php if($atts['similar']==='true'):?><button type="button" class="kcs-similar-btn"><?php echo esc_html(kashida_colors_showcase_t('similar','Similar Colors'));?></button><?php endif;?>
      </div>
    </div>
    <?php endforeach; ?></div></div><?php return ob_get_clean();
}
function kashida_colors_showcase_collections_shortcode($atts){
    $atts=shortcode_atts(['groups'=>'','desktop'=>'5','tablet'=>'3','mobile'=>'2','shape'=>'rectangle','show_image'=>'true','show_description'=>'false','show_count'=>'true','orderby'=>'title','order'=>'ASC'],$atts,'kashida_collections');
    $desktop=max(1,min(6,absint($atts['desktop'])));$tablet=max(1,min(4,absint($atts['tablet'])));$mobile=max(1,min(2,absint($atts['mobile'])));
    $shape=in_array($atts['shape'],['rectangle','square','circle','compact'],true)?$atts['shape']:'rectangle';
    $order=strtoupper($atts['order'])==='DESC'?'DESC':'ASC';
    $orderby=in_array($atts['orderby'],['title','date'],true)?$atts['orderby']:'title';
    $query=['post_type'=>'kashida_color_group','post_status'=>'publish','posts_per_page'=>-1,'orderby'=>$orderby,'order'=>$order];
    $selected_codes=array_filter(array_map('sanitize_text_field',array_map('trim',explode(',',(string)$atts['groups']))));
    if($selected_codes){$ids=[];foreach($selected_codes as $code){$post=kashida_colors_showcase_find_group($code);if($post&&$post->post_status==='publish')$ids[]=(int)$post->ID;}if(!$ids)return '<p class="kcs-empty">'.esc_html(kashida_colors_showcase_t('collection_required','No published color groups are available.')).'</p>';$query['post__in']=$ids;$query['orderby']='post__in';}
    $groups=get_posts($query);
    if(!$groups)return '<p class="kcs-empty">'.esc_html(kashida_colors_showcase_t('collection_required','No published color groups are available.')).'</p>';
    kashida_colors_showcase_enqueue_frontend();ob_start();
    ?><div class="kcs-collections kcs-collections-shape-<?php echo esc_attr($shape); ?>" style="--kcs-collection-desktop:<?php echo (int)$desktop;?>;--kcs-collection-tablet:<?php echo (int)$tablet;?>;--kcs-collection-mobile:<?php echo (int)$mobile;?>;"><div class="kcs-collections-grid">
    <?php foreach($groups as $g): $colors=get_post_meta($g->ID,'_kashida_colors_showcase_colors',true);$count=is_array($colors)?count($colors):0;$url=get_permalink($g->ID);$excerpt=has_excerpt($g->ID)?get_the_excerpt($g->ID):wp_trim_words(wp_strip_all_tags($g->post_content),18); $has_image=has_post_thumbnail($g->ID); ?>
      <article class="kcs-collection-card<?php echo $has_image?' has-cover':' no-cover'; ?>"><a class="kcs-collection-link" href="<?php echo esc_url($url); ?>">
        <?php if($atts['show_image']==='true'): ?><div class="kcs-collection-cover" aria-hidden="true"><?php if($has_image): echo get_the_post_thumbnail($g->ID,'medium_large',['loading'=>'lazy']); else: ?><span class="kcs-collection-cover-fallback">K</span><?php endif; ?></div><?php endif; ?>
        <div class="kcs-collection-info"><span class="kcs-collection-code"><?php echo esc_html(kashida_colors_showcase_group_code($g->ID)); ?></span><h3><?php echo esc_html($g->post_title); ?></h3><?php if($atts['show_description']==='true'&&$excerpt!==''): ?><p><?php echo esc_html($excerpt); ?></p><?php endif; ?><?php if($atts['show_count']==='true'): ?><span class="kcs-collection-count"><?php echo esc_html(sprintf(kashida_colors_showcase_t('colors_count','%s colors'),number_format_i18n($count))); ?></span><?php endif; ?><span class="kcs-collection-view"><?php echo esc_html(kashida_colors_showcase_t('view_collection','View Collection')); ?> →</span></div>
      </a></article>
    <?php endforeach; ?></div></div><?php return ob_get_clean();
}
add_shortcode('kashida_collections','kashida_colors_showcase_collections_shortcode');
add_shortcode('paint_colors','kashida_colors_showcase_shortcode');
add_shortcode('kashida_colors_showcase','kashida_colors_showcase_shortcode');
add_action('wp_footer',function(){if(is_admin())return;echo '<div id="kcs-global-toast" class="kcs-toast"></div>';});
