<?php
/**
 * Admin View: Page - Admin Tools > Custom Content
 */

defined( 'ABSPATH' ) || exit;

$table = new Noptin_Custom_Content_Table();
$table->prepare_items();
$table->display();
