<?php
/**
 * View popups
 *
 *
 * @since             1.0.0
 *
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    die;
}

?>
<div class="noptin-popup-designer noptin-list">
    <div class="noptin-list-inner">
    <header class="noptin-list-header">
        <div class="noptin-list-action">
            <button class="noptin-add-button">Add New</button>
        </div>
        <div class="noptin-list-filter"><input type="search" placeholder="Search Forms"><div>
    </header>
    <main class="content">
        <table class="noptin-list-table">
    <thead>
        <tr><th class="sortable th-title" data-sort="title">Title</th>
            <th class="th-shortcode">Status</th>
            <th class="sortable th-created" data-sort="created_at">Date Created</th>
        </tr>
    </thead>
    <tbody class="forms-collection">
        <tr>
            <td><a href="admin.php?page=ninja-forms&amp;form_id=1">Contact Me</a></td>
            <td>Active</td>
            <td>05/23/19 10:06 AM</td>
        </tr>
    </tbody>
</table>
</main>
</div>
</div>