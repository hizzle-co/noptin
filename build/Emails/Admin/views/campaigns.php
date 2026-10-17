<?php
defined( 'ABSPATH' ) || exit;
// Run pending tasks.
do_action( 'noptin_tasks_run_pending' );

// Send pending emails.
\Hizzle\Noptin\Emails\Bulk\Main::send_pending();
?>
<div id="noptin-email-campaigns__app"></div>
