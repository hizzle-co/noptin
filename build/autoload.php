<?php

/**
 * Main loader.
 *
 * @since   3.3.0
 * @package Noptin
 */

namespace Hizzle\Noptin;

defined( 'ABSPATH' ) || exit;

$folders = scandir( __DIR__ );
foreach ( $folders as $folder ) {
    if ( '.' !== $folder && '..' !== $folder && is_dir( __DIR__ . '/' . $folder ) ) {
        if ( file_exists( wp_normalize_path( __DIR__ . '/' . $folder . '/Main.php' ) ) ) {
            $class = __NAMESPACE__ . '\\' . $folder . '\\Main';
            if ( class_exists( $class ) && method_exists( $class, 'init' ) ) {
                $class::init();
            }
        }
    }
}
