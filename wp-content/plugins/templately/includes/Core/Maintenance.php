<?php

namespace Templately\Core;

class Maintenance {
	/**
	 * Init Maintenance
	 *
	 * @since 2.0.1
	 * @return void
	 */
	public static function init(){
		register_activation_hook( TEMPLATELY_PLUGIN_BASENAME, [ __CLASS__, 'activation' ] );
		register_uninstall_hook( TEMPLATELY_PLUGIN_BASENAME, [ __CLASS__, 'uninstall' ] );
	}

	/**
	 * Runs on activation
	 *
	 * @since 2.0.1
	 * @return void
	 */
	public static function activation(){

	}

	/**
	 * Runs on uninstallation.
	 *
	 * @since 2.0.1
	 * @return void
	 */
	public static function uninstall(){

	}
}
