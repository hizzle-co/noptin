<?php
/** Isolated dispatcher checks; no WordPress, scheduling, or email delivery. */
namespace Hizzle\Noptin\Emails\Bulk {
    /** In-memory fixture used only by this isolated regression. */
    class Main {
        public static $dispatches = 0;
        /** Fixture implementation for the isolated regression. */
        public static function send_pending() {
            ++self::$dispatches;
            $GLOBALS['poll_events'][] = 'send_pending';
        }
    }
}
namespace Hizzle\Noptin\Emails {
    /** In-memory fixture used only by this isolated regression. */
    class Main {
        /** Fixture implementation for the isolated regression. */
        public static function is_staging_email_disabled() { return false; }
    }
}
namespace {
    define('ABSPATH', __DIR__);
    $poll_events = array();
    /** Fixture implementation for the isolated regression. */
    function do_action($name) { $GLOBALS['poll_events'][] = $name; }
    /** Fixture implementation for the isolated regression. */
    function noptin_email_sending_limit_reached() {
        $GLOBALS['poll_events'][] = 'state';
        return false;
    }
    /** Fixture implementation for the isolated regression. */
    function apply_filters($name, $value) {
        return 'noptin_display_cron_notice' === $name ? true : $value;
    }
    /** Fixture implementation for the isolated regression. */
    function get_user_meta() { return false; }
    /** Fixture implementation for the isolated regression. */
    function current_user_can() { return false; }
    /** Fixture implementation for the isolated regression. */
    function get_current_user_id() { return 1; }
    /** Fixture implementation for the isolated regression. */
    function get_noptin_capability() { return 'manage_options'; }
    /** Fixture implementation for the isolated regression. */
    function current_user_can_manage_noptin_campaign_type() { return false; }
    /** Fixture implementation for the isolated regression. */
    function rest_ensure_response($value) { return $value; }
    /** Fixture implementation for the isolated regression. */
    function wp_count_posts($type) {
        if ('noptin-campaign' !== $type) throw new \RuntimeException('Trash count must use campaigns');
        return (object) array('trash' => $GLOBALS['trash_count'] ?? 0);
    }
    require dirname( __DIR__, 3 ) . '/build/Emails/Admin/Browser.php';
    use Hizzle\Noptin\Emails\Admin\Browser;
    use Hizzle\Noptin\Emails\Bulk\Main;
    ob_start();
    require dirname( __DIR__, 3 ) . '/build/Emails/Admin/views/campaigns.php';
    ob_end_clean();
    if ($poll_events || Main::$dispatches) throw new \RuntimeException('Rendering the campaigns mount point must not dispatch work');
    $response = Browser::state();
    if (array('noptin_tasks_run_pending', 'send_pending', 'state') !== $poll_events) throw new \RuntimeException('Polling must run tasks and send once before reading status');
    if (1 !== Main::$dispatches || !isset($response['sendingLimitReached'])) throw new \RuntimeException('Polling must return browser state after dispatch');
    if (false !== $response['hasTrashCampaigns']) throw new \RuntimeException('Empty trash must return boolean false');
    $trash_count = 2;
    $response = Browser::state();
    if (true !== $response['hasTrashCampaigns']) throw new \RuntimeException('Nonempty trash must return boolean true');
    if (2 !== Main::$dispatches) throw new \RuntimeException('Each poll must invoke the sender without additional gates');
    echo "Passed 6 campaign browser dispatch checks.\n";
}
