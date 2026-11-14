<?php

namespace Hizzle\Noptin\Abilities;

defined( 'ABSPATH' ) || exit;

/** Registers Noptin's WordPress abilities. */
class Main {

	/** Connect the module to the Abilities API registration hooks. */
	public static function init() {
		add_action( 'wp_abilities_api_categories_init', array( __CLASS__, 'register_category' ) );
		add_action( 'wp_abilities_api_init', array( Subscribers::class, 'register' ) );
	}

	/** Register the category shared by Noptin abilities. */
	public static function register_category() {
		wp_register_ability_category(
			'noptin',
			array(
				'label'       => 'Noptin',
				'description' => 'Manage newsletter data in Noptin.',
			)
		);
	}
}
