<?php
/** Isolated queue checks: no WordPress database, scheduling, or delivery. */
define('ABSPATH', __DIR__);
/** In-memory fixture used only by this isolated regression. */
class WP_Error {
    private $message;
    /** Fixture implementation for the isolated regression. */
    public function __construct($code, $message) { $this->message = $message; }
    /** Fixture implementation for the isolated regression. */
    public function get_error_message() { return $this->message; }
}
/** Fixture implementation for the isolated regression. */
function is_wp_error($value) { return $value instanceof WP_Error; }
/** Fixture implementation for the isolated regression. */
function esc_html($value) { return htmlspecialchars($value); }
/** Fixture implementation for the isolated regression. */
function noptin_email_sending_limit_reached() { return false; }
/** Fixture implementation for the isolated regression. */
function get_option($key) { return 0; }
$queue = $queries = $pauses = array();
/** Fixture implementation for the isolated regression. */
function get_posts($args) {
    global $queue, $queries;
    $queries[] = $args;
    return array_slice(array_values(array_diff(array_keys($queue), $args['exclude'])), 0, $args['posts_per_page']);
}
/** Fixture implementation for the isolated regression. */
function noptin_get_email_campaign_object($id) { return $GLOBALS['queue'][$id]; }
/** Fixture implementation for the isolated regression. */
function noptin_pause_email_campaign($id, $message) { $GLOBALS['pauses'][$id] = $message; }
/** In-memory fixture used only by this isolated regression. */
class SelectionCampaign {
    public $id;
    public $eligible;
    public $sender;
    /** Fixture implementation for the isolated regression. */
    public function __construct($id, $eligible = true, $sender = 'fixture') {
        $this->id = $id;
        $this->eligible = $eligible;
        $this->sender = $sender;
    }
    /** Fixture implementation for the isolated regression. */
    public function can_send($return_error) { return $this->eligible; }
    /** Fixture implementation for the isolated regression. */
    public function get_sender() { return $this->sender; }
}
require dirname( __DIR__, 3 ) . '/build/Emails/Bulk/Main.php';
use Hizzle\Noptin\Emails\Bulk\Main;
Main::$senders['fixture'] = new stdClass();
$count = 0;
$assert = static function ($condition, $message) use (&$count) {
    if (!$condition) throw new RuntimeException($message);
    ++$count;
};
// More than a batch of invalid/deferred entries before the oldest eligible one.
for ($id = 1; $id <= 8; $id++) $queue[$id] = new SelectionCampaign($id);
$queue[1]->eligible = new WP_Error('invalid', 'Missing subject');
$queue[2]->sender = 'unsupported';
foreach (array(3, 4, 5) as $id) $queue[$id]->eligible = false;
$selection = Main::get_pending_campaign_selection();
$assert(6 === $selection['campaign']->id, 'Selection must scan past the first batch');
$assert(array(1, 2) === array_keys($selection['errors']), 'Only invalid configurations should produce preparation errors');
$assert(array() === $pauses, 'Read-only selection must not pause campaigns');
$assert(2 === count($queries) && array(1, 2, 3, 4, 5) === $queries[1]['exclude'], 'Batch iteration must preserve prior exclusions');
$assert(array('date'=>'ASC', 'ID'=>'ASC') === $queries[0]['orderby'], 'Sending and polling need stable oldest-first ordering');
$campaign = Main::prepare_pending_campaign();
$assert(6 === $campaign->id, 'Preparation must select the same campaign as polling');
$assert('Missing subject' === $pauses[1] && false !== strpos($pauses[2], 'unsupported'), 'Preparation must retain error messages and pause invalid senders');
$assert(!isset($pauses[3]), 'A deferred campaign must not be paused');
$selection = Main::get_pending_campaign_selection(array(6));
$assert(7 === $selection['campaign']->id, 'Explicit exclusions must survive batching');
$pauses = array();
$queue = array(1=>new SelectionCampaign(1, new WP_Error('invalid', 'Invalid final entry')));
$selection = Main::get_pending_campaign_selection();
$assert(false === $selection['campaign'] && isset($selection['errors'][1]) && !$pauses, 'An invalid-only queue must remain unchanged during inspection');
$assert(false === Main::get_foreground_recovery_data() && !$pauses, 'Recovery checks during polling must not prepare or pause invalid campaigns');
$assert(false === Main::prepare_pending_campaign() && isset($pauses[1]), 'Preparation must apply errors even when no campaign is sendable');
$queue = array();
$assert(array('campaign'=>false, 'errors'=>array()) === Main::get_pending_campaign_selection(), 'An empty queue must have no candidate or errors');
echo "Passed $count bulk campaign selection checks.\n";
