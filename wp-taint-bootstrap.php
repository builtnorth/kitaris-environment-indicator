<?php
/**
 * Constants wp-taint cannot otherwise see (plugin files gate on ABSPATH).
 * Parsed for defines only — never reported as findings.
 *
 * @package KitarisEnvironmentIndicator
 */

define( 'ABSPATH', '/tmp/wp-taint-abspath/' );
define( 'WPINC', 'wp-includes' );
define( 'WP_CONTENT_DIR', '/tmp/wp-taint-abspath/wp-content' );
