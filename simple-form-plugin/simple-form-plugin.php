<?php
/**
 * Plugin Name: Simple Form Plugin
 * Description: Verwaltet mehrere Formulare, speichert Nachrichten und versendet E-Mails per SMTP.
 * Version: 1.0.0
 * Author: Example Author
 * Text Domain: simple-form-plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

require_once plugin_dir_path( __FILE__ ) . 'includes/class-simple-form-plugin.php';

\SimpleFormPlugin\Plugin::instance();
