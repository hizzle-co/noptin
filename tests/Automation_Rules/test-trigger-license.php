<?php
/** Runtime licensing coverage for shared trigger validation. */

require_once dirname( __DIR__ ) . '/includes/class-regression-test-case.php';

use Hizzle\Noptin\Automation_Rules\Actions\Main as Actions;
use Hizzle\Noptin\Automation_Rules\Triggers\Main as Triggers;
use Hizzle\Noptin\Tasks\Main as Tasks;

/** Count executions without changing subscriber records or sending email. */
class Noptin_Trigger_License_Probe extends Hizzle\Noptin\Automation_Rules\Actions\Action {
	/** @var int Executions observed in this test. */
	public static $runs = 0;
	/** @inheritdoc */
	public function get_id() { return 'test_trigger_license_probe'; }
	/** @inheritdoc */
	public function get_name() { return 'Trigger license probe'; }
	/** @inheritdoc */
	public function get_description() { return 'Count a harmless automation action.'; }
	/** @inheritdoc */
	public function run( $subject, $rule, $args ) { ++self::$runs; return true; }
}

/** Check trigger entitlements independently of email-only restrictions. */
class Test_Trigger_License extends Noptin_Regression_Test_Case {
	/** @var object|WP_Error Cached license-service response. */
	private $license;
	/** @var bool Whether the site has a saved key. */
	private $has_key = true;

	/** Install isolated license responses without changing the site's saved key. */
	public function set_up() {
		parent::set_up();
		$this->license = (object) array( 'is_active' => true, 'is_active_on_site' => true, 'has_expired' => false );
		add_filter( 'pre_option_noptin_helper_data', function () { return $this->has_key ? array( 'license_key' => 'trigger-runtime-test' ) : array(); }, PHP_INT_MAX );
		add_filter( 'pre_transient_noptin_license_trigger-runtime-test', function () { return $this->license; }, PHP_INT_MAX );
	}

	/** Use the saved key during execution, even when remote verification is unavailable. */
	public function test_license_states_and_free_subscriber_field_triggers() {
		$rule = noptin_get_automation_rule( 0 );
		$rule->set_action_id( 'path' );
		$action = Actions::get( 'path' );
		add_filter( 'noptin_subscriber_filters', static function ( $fields ) {
			$fields['test_segment'] = array( 'label' => 'Test segment', 'options' => array( 'one', 'two' ) );
			return $fields;
		} );
		$collection = Hizzle\Noptin\Objects\Store::get( 'subscriber' );
		$definitions = $collection->get_all_triggers();
		$field_trigger = new Hizzle\Noptin\Objects\Trigger( 'noptin_subscriber_test_segment_changed', $definitions['noptin_subscriber_test_segment_changed'], $collection );
		foreach ( array(
			'active' => (object) array( 'is_active' => true, 'is_active_on_site' => true, 'has_expired' => false ),
			'inactive' => (object) array( 'is_active' => false, 'is_active_on_site' => true, 'has_expired' => false ),
			'expired' => (object) array( 'is_active' => true, 'is_active_on_site' => true, 'has_expired' => true ),
			'other-site' => (object) array( 'is_active' => true, 'is_active_on_site' => false, 'has_expired' => false ),
			'failed-verification' => new WP_Error( 'test_license_unavailable', 'License verification unavailable.' ),
			'missing-key' => (object) array( 'is_active' => true, 'is_active_on_site' => true, 'has_expired' => false ),
		) as $state => $license ) {
			$this->license = $license;
			$this->has_key = 'missing-key' !== $state;
			foreach ( array( 'page_published' => true, 'post_published' => false, 'noptin_subscriber_test_segment_changed' => false, 'noptin_subscriber_added_to_tags' => false, 'noptin_subscriber_removed_from_tags' => false ) as $id => $premium ) {
				$trigger = 'noptin_subscriber_test_segment_changed' === $id ? $field_trigger : Triggers::get( $id );
				$this->assertNotNull( $trigger, $id );
				$this->assertSame( $premium, (bool) $trigger->forcePremium, $id );
				$this->assertSame( ! $premium || $this->has_key, $trigger->is_rule_valid_for_args( $rule, array(), '', $action ), $state . ': ' . $id );
			}
		}
	}

	/** Queued Premium jobs must stop after the saved license is removed locally. */
	public function test_queued_trigger_rechecks_license_at_execution() {
		$registry = Actions::$actions;
		$tasks = array();
		$rule = null;
		$post_id = 0;
		Noptin_Trigger_License_Probe::$runs = 0;
		Actions::add( new Noptin_Trigger_License_Probe() );
		add_filter( 'noptin_saved_task_background_run', '__return_true', PHP_INT_MAX );
		try {
			$author_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
			$post_id = self::factory()->post->create( array( 'post_type' => 'page', 'post_status' => 'draft', 'post_author' => $author_id ) );
			$rule = noptin_get_automation_rule( 0 );
			$rule->set_trigger_id( 'page_published' );
			$rule->set_action_id( 'test_trigger_license_probe' );
			$rule->set_status( true );
			$rule->set_delay( HOUR_IN_SECONDS );
			$this->assertFalse( is_wp_error( $rule->save() ) );
			$trigger = $rule->get_trigger();
			foreach ( array( false, true ) as $expired ) {
				$this->license->has_expired = false;
				$this->has_key = true;
				$args = $trigger->unserialize_trigger_args( array( 'object_id' => $post_id, 'subject_id' => $author_id ) );
				$this->assertTrue( $rule->maybe_run( $args['subject'], $trigger, $rule->get_action(), $args ) );
				$pending = Tasks::query( array( 'hook' => 'noptin_run_automation_rule', 'primary_id' => $rule->get_id(), 'status' => 'pending', 'per_page' => 100 ) );
				$this->assertCount( 1, $pending );
				$task = reset( $pending );
				$tasks[] = $task->get_id();
				$this->license->has_expired = $expired;
				$this->has_key = ! $expired;
				$task->process();
				$this->assertSame( $expired ? 'failed' : 'complete', Tasks::get( $task->get_id() )->get_status(), wp_json_encode( $task->get_logs() ) );
				$this->assertSame( 1, Noptin_Trigger_License_Probe::$runs, 'Locally deactivated queued jobs must not execute the action.' );
			}
		} finally {
			foreach ( $tasks as $id ) { Tasks::get( $id )->delete(); }
			if ( $rule && $rule->exists() ) { $rule->delete(); }
			if ( $post_id ) { wp_delete_post( $post_id, true ); }
			Actions::$actions = $registry;
			Noptin_Trigger_License_Probe::$runs = 0;
		}
	}
}