<?php
/**
 * Removes the plugin's single option on uninstall.
 *
 * @package KitarisEnvironmentIndicator
 */

defined('WP_UNINSTALL_PLUGIN') || exit;

delete_option('kitaris_environment_indicator_settings');
