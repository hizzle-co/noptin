<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' )  ) {
	die;
}

/**
 * Represents a single automation rule.
 *
 * @since       1.2.8
 */
class Noptin_Automation_Rule {

    /**
     * The automation rule's ID
     * @var int
     * @since 1.2.8
     */
    public $id = 0;

    /**
     * The automation rule's action id
     * @var string
     * @since 1.2.8
     */
    public $action_id = '';

    /**
     * The automation rule's trigger ID
     * @var string
     * @since 1.2.8
     */
    public $trigger_id = '';

    /**
     * The automation rule's action settings
     * @var array
     * @since 1.2.8
     */
    public $action_settings = array();

    /**
     * The automation rule's trigger settings
     * @var array
     * @since 1.2.8
     */
    public $trigger_settings = array();

    /**
     * The automation rule's status
     * @var int
     * @since 1.2.8
     */
    public $status = 1;

    /**
     * The automation rule's creation time
     * @var string
     * @since 1.2.8
     */
    public $created_at = '0000-00-00 00:00:00';

    /**
     * The automation rule's last update
     * @var string
     * @since 1.2.8
     */
    public $updated_at = '0000-00-00 00:00:00';

    /**
     * The automation rule's run times
     * @var int
     * @since 1.2.8
     */
    public $times_run = 0;

    /**
     * Constructor.
     *
     * @since 1.2.8
     * @var int|stdClass|Noptin_Automation_Rule $rule
     * @return string
     */
    public function __construct( $rule ) {
        
        if ( is_numeric( $rule ) ) {
            $this->init( self::get_rule( $rule ) );
            return;
        }

        $this->init( $rule );
    }

    /**
	 * Sets up object properties.
	 *
	 * @since  1.2.8
	 *
	 * @param object $data Rule DB row object.
	 */
	public function init( $data ) {

        if ( empty( $data ) ) {
            return;
        }

        foreach ( get_object_vars( $data ) as $var ) {
            if ( property_exists( $this, $var ) ) {
                $this->$var = maybe_unserialize( $data->$var );
            }
        }

    }
    
    /**
	 * Retrieves a rule from the database or cache.
	 *
	 * @since  1.2.8
	 *
	 * @param int $id The rule id.
	 */
	public static function get_rule( $id ) {
        global $wpdb;

        $rule  = wp_cache_get( $id, 'noptin_automation_rules' );

        if ( ! empty( $rule ) ) {
            return $rule;
        }

        $table = noptin()->automation_rules->get_table();
		$rule  = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE id = %d LIMIT 1",
				$id
			)
        );
        
        if ( ! empty( $rule ) ) {
            wp_cache_set( $rule->id, $rule, 'noptin_automation_rules', 1 );
        }
        
        return $rule;

    }
    
    /**
	 * Determine whether the rule exists in the database.
	 *
	 * @since 1.2.8
	 *
	 * @return bool True if rule exists in the database, false if not.
	 */
	public function exists() {
		return ! empty( $this->id );
	}

}
