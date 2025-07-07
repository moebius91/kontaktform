<?php
namespace SimpleFormPlugin;

class Plugin {
    private static $instance;
    public $db_version = '1.0';
    public $table_name;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'simple_form_messages';

        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
        add_shortcode( 'simple_form', array( $this, 'render_form_shortcode' ) );
        add_action( 'admin_post_nopriv_simple_form_submit', array( $this, 'handle_form_submit' ) );
        add_action( 'admin_post_simple_form_submit', array( $this, 'handle_form_submit' ) );
        add_action( 'init', array( $this, 'register_block' ) );
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE {$this->table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            form_id bigint(20) unsigned NOT NULL,
            email varchar(255) NOT NULL,
            message longtext NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
        add_option( 'simple_form_db_version', $this->db_version );
    }

    public function register_admin_menu() {
        add_menu_page( 'Simple Forms', 'Simple Forms', 'manage_options', 'simple-form-plugin', array( $this, 'settings_page' ) );
    }

    public function settings_page() {
        echo '<div class="wrap"><h1>Simple Form Plugin</h1><p>Formularverwaltung folgt.</p></div>';
    }

    public function render_form_shortcode( $atts ) {
        $atts = shortcode_atts( array( 'id' => 0 ), $atts );
        ob_start();
        include plugin_dir_path( __DIR__ ) . 'templates/form.php';
        return ob_get_clean();
    }

    public function handle_form_submit() {
        if ( ! isset( $_POST['simple_form_nonce'] ) || ! wp_verify_nonce( $_POST['simple_form_nonce'], 'simple_form_submit' ) ) {
            wp_die( 'Nonce check failed' );
        }
        global $wpdb;
        $email   = sanitize_email( $_POST['email'] );
        $message = sanitize_textarea_field( $_POST['message'] );
        $wpdb->insert( $this->table_name, array(
            'form_id'    => absint( $_POST['form_id'] ),
            'email'      => $email,
            'message'    => $message,
            'created_at' => current_time( 'mysql' ),
        ) );

        $headers = array( 'Content-Type: text/plain; charset=UTF-8' );
        if ( ! empty( $_POST['send_html'] ) ) {
            $headers = array( 'Content-Type: text/html; charset=UTF-8' );
        }
        $subject = 'Neue Nachricht';
        $body    = $message;
        wp_mail( get_option( 'admin_email' ), $subject, $body, $headers );

        wp_redirect( wp_get_referer() );
        exit;
    }

    public function register_block() {
        if ( ! function_exists( 'register_block_type' ) ) {
            return;
        }
        wp_register_script( 'simple-form-block', plugins_url( '../blocks/index.js', __FILE__ ), array( 'wp-blocks', 'wp-element', 'wp-editor' ), '1.0', true );
        wp_register_style( 'simple-form-block-editor', plugins_url( '../blocks/editor.css', __FILE__ ), array( 'wp-edit-blocks' ) );
        wp_register_style( 'simple-form-block', plugins_url( '../blocks/style.css', __FILE__ ), array() );
        register_block_type( 'simple-form/block', array(
            'editor_script' => 'simple-form-block',
            'editor_style'  => 'simple-form-block-editor',
            'style'         => 'simple-form-block',
            'render_callback' => array( $this, 'render_form_block' ),
        ) );
    }

    public function render_form_block( $attributes ) {
        ob_start();
        include plugin_dir_path( __DIR__ ) . 'templates/form.php';
        return ob_get_clean();
    }
}
