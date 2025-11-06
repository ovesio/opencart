<?php
// Heading
$_['heading_title']    = 'Ovesio [<a target="_blank" href="https://ovesio.com">ovesio.com</a>]';

// Text
$_['text_extension']   = 'Extensions';
$_['text_success']     = 'Success: You have modified Ovesio AI module!';
$_['text_edit']        = 'Edit Ovesio AI Module';
$_['text_system_language']        = 'System Language';
$_['text_iso2_language']        = 'ISO2 Language';
$_['text_translate_status'] = 'Translate';
$_['text_translate_status_helper'] = 'Will be automatically ignored if is Catalog Language';
$_['text_translate_from'] = 'Translate From';
$_['text_language_association'] = 'Language Association';
$_['text_language_translations'] = 'Language Translation';
$_['text_translated_fields'] = 'Translated Fields';
$_['text_products'] = 'Products';
$_['text_categories'] = 'Categories';
$_['text_product'] = 'Product';
$_['text_category'] = 'Category';

$_['text_name'] = 'Name';
$_['text_description'] = 'Description';
$_['text_attributes'] = 'Attributes';
$_['text_tag'] = 'Tags';
$_['text_meta_title'] = 'Meta Title';
$_['text_meta_description'] = 'Meta Description';
$_['text_meta_keyword'] = 'Meta Keywords';
$_['text_groups_and_attributes'] = 'Groups and Attributes';
$_['text_options'] = 'Options';
$_['text_others'] = 'Others';
$_['text_enabled'] = 'Enabled';
$_['text_disabled'] = 'Disabled';
$_['text_yes'] = 'Yes';
$_['text_no'] = 'No';
$_['text_translate_feeds'] = 'Translate feeds';
$_['text_translation_callback'] = 'Callback url';
$_['text_translation_callback_helper'] = '* This is the URL where translations/descriptions will be send by Ovesio.com. The URL must be reachable from outside.';
$_['text_cronjob'] = 'Cronjob';
$_['text_cronjob_helper'] = '* Each time the cron runs, it will process only 40 resources at a time.';
$_['text_content_generator_info'] = 'Changing the options in this section may affect translations already made with Ovesio. If you re-generate descriptions for products or categories that have already been translated, they will need to be translated again.';
$_['text_translate_after_content_generator_info'] = 'Since you have enabled automatic description and/or metatags generator, resource translation will occur after finishing the rest of the operations, according to the settings you chose.';
$_['text_metatags_generator_info'] = 'Since you have enabled automatic description generation, metatags generator will occur after the description is generated';
$_['text_only_for_action'] = 'Generate Metatags';
$_['text_only_for_generated_content'] = 'Only for resources with generated description';
$_['text_only_for_all'] = 'For all resources';
$_['text_one_time_only'] = 'One time only';
$_['text_on_each_update'] = 'On each resource update';
$_['text_other_translations'] = '* in order to translate products you need to translate groups, attributes & options as well';
$_['text_product_name_warning'] = 'Warning: To prevent the insertion of empty product names when other keys are resolved (eg.: Description, Meta), the flow will ignore products that do not have a translated name in related language. Uncheck this option only if you assume that the names of the products will be translated by other means.';
$_['text_category_name_warning'] = 'Warning: To prevent the insertion of empty category names when other keys are resolved (eg.: Description, Meta), the flow will ignore categories that do not have a translated name in related language. Uncheck this option only if you assume that the names of the categories will be translated by other means.';
$_['text_warning_no_language'] = 'Attention, no language has been selected for translation';

$_['text_content_generator'] = 'AI Content Generator';
$_['text_seo_generator']     = 'AI SEO MetaTags Generator';
$_['text_translate']         = 'Translate Settings';

// Entry
$_['entry_status']     = 'Status';
$_['entry_token']     = 'API Token';
$_['entry_api']     = 'API Url';
$_['entry_token_helper']     = 'API Token is found in Ovesio.com platform, in Settings menu';
$_['entry_catalog_language']     = 'Catalog Language';
// $_['entry_live_translate']     = 'Live translate';
// $_['help_live_translate']     = 'Ensure that you request a new translation each time a resource is edited. This approach keeps your content up-to-date across all languages. If this feature is disabled, you can still translate your content by setting up a translation feed on Ovesio.com. The feed URLs are listed under "Translate feeds."';
// $_['entry_live_description']     = 'Live description';
// $_['help_live_description']     = 'Ensure that you request a new description creating each time a resource is edited. This approach keeps your content up-to-date across all languages. If this feature is disabled, you can still auto generate descriptions by setting up a cron job(check cronjob section)';
$_['entry_send_stock_0'] = 'Include products out of stock(quantity <= 0)';
$_['entry_send_disabled'] = 'Include disabled products and categories';
$_['entry_generate_product_description'] = 'Generate description for products';
$_['entry_generate_category_description'] = 'Generate description for categories';
$_['entry_minimum_description_length_product'] = 'Ignore product descriptions larger than X characters';
$_['entry_minimum_description_length_category'] = 'Ignore category descriptions larger than X characters';
$_['entry_create_a_new_description'] = 'Create a new description';
$_['entry_create_a_new_translation'] = 'Create a new translation';
$_['entry_metatags_product'] = 'Generate MetaTags for products';
$_['entry_metatags_send_stock_0'] = 'Include products out of stock(quantity <= 0)';
$_['entry_metatags_send_disabled'] = 'Include disabled products and categories';
$_['entry_metatags_category'] = 'Generate MetaTags for categories';

// Text
$_['text_connect'] = 'Connect';
$_['text_activity_list'] = 'Lista de activități';
$_['text_disconnect_confirm'] = 'This will disable the module. Are you sure?';
$_['text_default_language_helper'] = 'Default language of the online store/platform';
$_['text_cron_info'] = 'CRON Information';
$_['text_cron_url'] = 'CRON URL';
$_['text_cron_command'] = 'CRON Command';
$_['text_cron_interval'] = 'Recommended Interval';
$_['text_cron_interval_helper'] = 'For optimal data synchronization';
$_['text_edit_configuration'] = 'Edit Configuration';
$_['text_generate_content_for'] = 'Generate description for';
$_['text_generate_content_sumary'] = '%s (%s), having description less then %s characters';
$_['text_generate_seo_sumary'] = '%s (%s)';
$_['text_translate_sumary'] = '%s (%s): %s';
$_['text_including_disabled'] = 'including disabled';
$_['text_excluding_disabled'] = 'only enabled';
$_['text_generate_content_min_length'] = 'Generate descriptions only for resources that don\'t have a description, or have a description smaller than';
$_['text_characters'] = 'characters';
$_['text_generate_content_live_update'] = 'Generate a new description when resource data has been modified';
$_['text_generate_content_live_update_helper'] = 'If left unchecked, the description will be generated only once';
$_['text_include_disabled_resources'] = 'Include disabled resources';
$_['text_workflow_used'] = 'Workflow used';

// SEO Form texts
$_['text_generate_seo_for'] = 'Generate Meta Tags for';
$_['text_generate_seo_live_update'] = 'Generate Meta Tags when resource is updated';
$_['text_generate_seo_live_update_helper'] = 'If left unchecked, Meta Tags will be generated only once';

// Translate Form texts
$_['text_translated_fields'] = 'Translated Fields';
$_['text_language_translation'] = 'Language Translation';
$_['text_system_language'] = 'System Language';
$_['text_ovesio_language'] = 'Ovesio Language';
$_['text_translate'] = 'Translate';
$_['text_translate_from'] = 'Translate From';
$_['text_select_source_language'] = 'Select source language';
$_['text_please_select_matching_language'] = 'Please select a matching language';

// Activity Log texts
$_['text_all_status'] = 'All Status';
$_['text_all_types'] = 'All Types';
$_['text_all_activities'] = 'All Activities';
$_['text_success'] = 'Success';
$_['text_pending'] = 'Pending';
$_['text_error'] = 'Error';
$_['text_warning'] = 'Warning';
$_['text_processing'] = 'Processing';
$_['text_completed'] = 'Completed';
$_['text_failed'] = 'Failed';
$_['text_update_status'] = 'Update Status';
$_['text_generate_description'] = 'Generate Description';
$_['text_generate_seo'] = 'Generate SEO';
$_['text_translate'] = 'Translate';
$_['text_resource_type'] = 'Resource Type';
$_['text_resource_id'] = 'Resource ID';
$_['text_resource_name'] = 'Resource Name';
$_['text_activity_type'] = 'Activity Type';
$_['text_date_range'] = 'Date Range';
$_['text_date_from'] = 'Date From';
$_['text_date_to'] = 'Date To';
$_['text_sort_by'] = 'Sort By';
$_['text_items_per_page'] = 'Items Per Page';
$_['text_filter_by_errors'] = 'Filter by Errors';
$_['text_with_errors_only'] = 'With errors only';
$_['text_without_errors'] = 'Without errors';
$_['text_custom'] = 'Custom';
$_['text_last_7_days'] = 'Last 7 days';
$_['text_last_30_days'] = 'Last 30 days';
$_['text_this_month'] = 'This month';
$_['text_last_month'] = 'Last month';
$_['text_updated_desc'] = 'Updated (Newest first)';
$_['text_updated_asc'] = 'Updated (Oldest first)';
$_['text_created_desc'] = 'Created (Newest first)';
$_['text_created_asc'] = 'Created (Oldest first)';
$_['text_status_desc'] = 'Status';
$_['text_status'] = 'Status';
$_['text_view_resource'] = 'View Resource';
$_['text_view_request'] = 'View Request';
$_['text_view_response'] = 'View Response';
$_['text_view_error'] = 'View Error';
$_['text_retry'] = 'Retry';
$_['text_start'] = 'Start';
$_['text_refresh'] = 'Refresh';
$_['text_apply_filters'] = 'Apply Filters';
$_['text_clear_filters'] = 'Clear All Filters';
$_['text_go_to_settings'] = 'Go to Settings';
$_['text_filtering'] = 'Filtering';
$_['text_refreshing'] = 'Refreshing';

// Additional text keys for activity list
$_['text_all_time'] = 'All Time';
$_['text_today'] = 'Today';
$_['text_yesterday'] = 'Yesterday';
$_['text_custom_range'] = 'Custom Range';
$_['text_to'] = 'to';
$_['text_search_by_id'] = 'Search by ID';
$_['text_search_by_name'] = 'Search by name';
$_['text_show'] = 'Show';
$_['text_all_entries'] = 'All Entries';
$_['text_only_errors'] = 'Only Errors';
$_['text_only_success'] = 'Only Success';
$_['text_only_pending'] = 'Only Pending';
$_['text_name_asc'] = 'Name A-Z';
$_['text_name_desc'] = 'Name Z-A';
$_['text_type_asc'] = 'Type A-Z';
$_['text_actions'] = 'Actions';
$_['text_language'] = 'Language';
$_['text_last_updated'] = 'Last Updated';
$_['text_minutes_ago'] = 'min ago';
$_['text_showing_entries'] = 'Showing';
$_['text_of'] = 'of';
$_['text_entries'] = 'entries';
$_['text_previous'] = 'Previous';
$_['text_next'] = 'Next';
$_['text_activity_log_refreshed'] = 'Activity log refreshed';
$_['text_filters_applied_successfully'] = 'Filters applied successfully';
$_['text_manufacturers'] = 'Manufacturers';
$_['text_information'] = 'Information';
$_['text_no_results_found'] = 'No results found';
$_['text_all_languages'] = 'All Languages';
$_['text_request'] = 'Request';
$_['text_response'] = 'Response';
$_['text_value'] = 'Value';

// Connection messages
$_['text_connection_valid'] = 'Credentials are valid. To complete the connection, please select the default language of the online store and click the connect button again.';
$_['text_connection_error'] = 'A connection error occurred. Please check your credentials and try again.';
$_['text_connection_success'] = 'Connection was successful.';
$_['text_disconnection_success'] = 'Disconnection was successful.';
$_['text_api_check_failed'] = 'API check failed with error:';

// Save messages
$_['text_settings_saved'] = 'Settings have been saved successfully.';
$_['text_seo_settings_saved'] = 'SEO settings have been saved successfully.';
$_['text_translate_settings_saved'] = 'Translation settings have been saved successfully.';

// API errors
$_['text_api_error'] = 'An error occurred while retrieving the necessary information through API';

// Validation errors
// API errors
$_['error_invalid_number'] = 'Error: A valid number greater than 0 must be entered.';

// Success messages for specific forms
$_['text_seo_settings_saved'] = 'SEO settings have been saved successfully.';
$_['text_translate_settings_saved'] = 'Translation settings have been saved successfully.';

// Status tooltips

// Status tooltips
$_['text_tooltip_view_resource'] = 'View Resource';
$_['text_tooltip_view_request'] = 'View Request';
$_['text_tooltip_view_response'] = 'View Response';
$_['text_tooltip_view_error'] = 'View Error';
$_['text_tooltip_update_status'] = 'Update Status';
$_['text_tooltip_retry_translation'] = 'Retry Translation';
$_['text_tooltip_start_translation'] = 'Start Translation';

// Common texts
$_['text_activated'] = 'Activated';
$_['text_included'] = 'Included';
$_['text_excluded'] = 'Excluded';

// Buttons
$_['button_cancel'] = 'Cancel';
$_['button_save'] = 'Save';
$_['button_connect'] = 'Connect';
$_['button_disconnect'] = 'Disconnect';
$_['button_save_changes'] = 'Save Changes';

// Error
$_['error_permission'] = 'Warning: You do not have permission to modify Ovesio AI module!';
$_['error_code'] = 'Language association is required';
$_['error_token'] = 'A valid token is required';
$_['error_from_language'] = 'You cannot translate from the same language';
$_['error_from_language1'] = 'Selected language to translate from is disabled. This must be either activated or the catalog language';
$_['error_warning'] = 'Warning: Please check the form carefully for errors!';