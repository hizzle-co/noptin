<?php

/**
 * Main actions class.
 *
 * @since   3.1.0
 * @package Noptin
 */

namespace Hizzle\Noptin\Automation_Rules\Actions;

defined( 'ABSPATH' ) || exit;

/**
 * Main actions class.
 */
class Main {

    /**
     * @var Action[] List of all registered actions.
     */
    public static $actions = array();

    /**
     * Registers an action.
     *
     * @param Action $action The action to register.
     */
    public static function add( $action ) {
        if ( isset( self::$actions[ $action->get_id() ] ) ) {
			return _doing_it_wrong( __METHOD__, 'Action with id ' . esc_html( $action->get_id() ) . ' already exists', '3.0.0' );
		}

		self::$actions[ $action->get_id() ] = $action;
    }

    /**
     * Retrieves a registered action.
     *
     * @param string $action_id The action's unique id.
     * @return Action|null
     */
    public static function get( $action_id ) {
        if ( ! is_scalar( $action_id ) || empty( $action_id ) ) {
            return null;
        }

        if ( isset( self::$actions[ $action_id ] ) ) {
            return self::$actions[ $action_id ];
        }

        foreach ( self::$actions as $action ) {
            if ( isset( $action->alias ) && $action->alias === $action_id ) {
                return $action;
            }
        }

        return null;
    }

    /**
     * Returns all registered actions.
     * @return Action[]
     */
    public static function all() {
        return self::$actions;
    }

    /**
     * Checks if an action is registered.
     */
    public static function exists( $action_id ) {
        return self::get( $action_id ) !== null;
    }
}
