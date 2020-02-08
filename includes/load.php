<?php 

$plugin_path = get_noptin_include_dir();

// Admin.
require_once get_noptin_include_dir('admin/class-noptin-admin.php');
require_once get_noptin_include_dir('admin/class-noptin-vue.php');
require_once get_noptin_include_dir('admin/class-noptin-settings.php');
require_once get_noptin_include_dir('admin/class-noptin-form-editor.php');

// Email campaigns.
require_once $this->admin_path . 'class-noptin-email-campaigns-admin.php';
require_once $this->admin_path . 'class-noptin-email-newsletters-table.php';
require_once $this->admin_path . 'class-noptin-email-automations-table.php';

// Bg handlers.
require_once get_noptin_include_dir('class-noptin-async-request.php');
require_once get_noptin_include_dir('class-noptin-background-process.php');
require_once get_noptin_include_dir('class-noptin-new-post-notify.php');
require_once get_noptin_include_dir('class-noptin-mailer.php');
require_once get_noptin_include_dir('class-noptin-background-mailer.php');

// Forms.
require_once get_noptin_include_dir('class-noptin-form.php');
require_once get_noptin_include_dir('class-noptin-post-types.php');
require_once get_noptin_include_dir('class-noptin-popups.php');
require_once get_noptin_include_dir('class-noptin-inpost.php');
require_once get_noptin_include_dir('class-noptin-sidebar.php');
require_once get_noptin_include_dir('class-noptin-widget.php');

// Misc.
require_once get_noptin_include_dir('class-noptin-page.php');
require_once get_noptin_include_dir('class-noptin-intergrations.php');
require_once get_noptin_include_dir('class-noptin-ajax.php');