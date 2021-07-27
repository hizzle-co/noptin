<?php
/**
 * Noptin Automation Rule
 *
 */

defined( 'ABSPATH' ) || exit;
/**
 * This class represents a single Noptin automation rule.
 *
 * @link https://noptin.com/guide/automation-rules/
 * @see Noptin_Automation_Rules
 * @see Noptin_Automation_Rules_Table
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
        
        foreach ( get_object_vars( $data ) as $key => $var ) {
            if ( property_exists( $this, $key ) ) {
                $this->$key = $this->make_bool( maybe_unserialize( $var ) );
            }
        }

        // Fill defaults.
        $trigger = noptin()->automation_rules->get_trigger( $this->trigger_id );

        if ( ! empty( $trigger ) && is_array( $trigger->get_settings() ) ) {

            foreach ( $trigger->get_settings() as $key => $args ) {
                if ( ! isset( $this->trigger_settings[ $key ] ) && isset( $args['default'] ) ) {
                    $this->trigger_settings[ $key ] = $args['default'];
                }
            }

        }

        $action = noptin()->automation_rules->get_action( $this->action_id );

        if ( ! empty( $action ) && is_array( $action->get_settings() ) ) {

            foreach ( $action->get_settings() as $key => $args ) {
                if ( ! isset( $this->action_settings[ $key ] ) && isset( $args['default'] ) ) {
                    $this->action_settings[ $key ] = $args['default'];
                }
            }

        }

    }

    /**
	 * Converts bool strings to their bool counterparts.
	 *
	 * @since  1.3.0
	 *
	 * @param mixed $val The val to make boolean.
	 */
	public function make_bool( $val ) {

        if ( is_scalar( $val ) ) {

            // Make true.
            if ( 'true' === $val ) {
                $val = true;
            }

            // Make false.
            if ( 'false' === $val ) {
                $val = false;
            }

            return $val;

        }

        if ( is_array( $val ) ) {
            return map_deep( $val, array( $this, 'make_bool' ) );
        }

        return $val;

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
            wp_cache_set( $rule->id, $rule, 'noptin_automation_rules', 10 );
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
