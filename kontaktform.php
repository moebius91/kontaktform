<?php
/**
 * Plugin Name: KontaktForm
 * Plugin URI: https://nikoothersen.de
 * Description: Verwaltung mehrerer Formulare, speichert Nachrichten und versendet E-Mails per SMTP.
 * Version: 1.0.0
 * Author: Jan-Nikolas Othersen
 * Author URI: https://nikoothersen.de
 * Text Domain: kontaktform
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

require_once plugin_dir_path( __FILE__ ) . 'includes/class-kontaktform.php';

\KontaktForm\Plugin::instance();
