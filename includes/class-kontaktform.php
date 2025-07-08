<?php
namespace KontaktForm;

class Plugin {
    private static $instance;
    public $db_version = '1.0';
    public $table_name;
    private $forms_option = 'kontaktform_forms';

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'kontaktform_messages';

        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
        add_shortcode( 'kontaktform', array( $this, 'render_form_shortcode' ) );
        add_action( 'admin_post_nopriv_kontaktform_submit', array( $this, 'handle_form_submit' ) );
        add_action( 'admin_post_kontaktform_submit', array( $this, 'handle_form_submit' ) );
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
        add_option( 'kontaktform_db_version', $this->db_version );
        add_option( $this->forms_option, array() );
    }

    public function register_admin_menu() {
        add_menu_page( 'KontaktForm', 'KontaktForm', 'manage_options', 'kontaktform', array( $this, 'settings_page' ) );
    }

    public function settings_page() {
        if ( isset( $_POST['kontaktform_action'] ) && 'add' === $_POST['kontaktform_action'] ) {
            check_admin_referer( 'kontaktform_add_form' );
            $name = sanitize_text_field( $_POST['form_name'] );
            if ( $name ) {
                $this->add_form( $name );
                echo '<div class="updated"><p>Formular hinzugefügt.</p></div>';
            }
        }

        if ( isset( $_GET['action'], $_GET['form'] ) && 'delete' === $_GET['action'] ) {
            $form_id = absint( $_GET['form'] );
            check_admin_referer( 'kontaktform_delete_form_' . $form_id );
            $this->delete_form( $form_id );
            echo '<div class="updated"><p>Formular gelöscht.</p></div>';
        }

        $forms = $this->get_forms();

        echo '<div class="wrap"><h1>KontaktForm</h1>';

        echo '<h2>Formulare</h2>';
        if ( $forms ) {
            echo '<table class="widefat"><thead><tr><th>Name</th><th>Shortcode</th><th>Aktionen</th></tr></thead><tbody>';
            foreach ( $forms as $id => $form ) {
                $shortcode = '[kontaktform id="' . $id . '"]';
                $delete_url = wp_nonce_url( admin_url( 'admin.php?page=kontaktform&action=delete&form=' . $id ), 'kontaktform_delete_form_' . $id );
                echo '<tr><td>' . esc_html( $form['name'] ) . '</td><td><code>' . esc_html( $shortcode ) . '</code></td><td><a href="' . esc_url( $delete_url ) . '">Löschen</a></td></tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p>Keine Formulare vorhanden.</p>';
        }

        echo '<h2>Neues Formular</h2>';
        echo '<form method="post">';
        echo '<input type="hidden" name="kontaktform_action" value="add">';
        wp_nonce_field( 'kontaktform_add_form' );
        echo '<p><label for="form_name">Name:</label> <input type="text" id="form_name" name="form_name" required></p>';
        echo '<p><input type="submit" class="button button-primary" value="Hinzufügen"></p>';
        echo '</form>';

        echo '</div>';
    }

    private function get_forms() {
        $forms = get_option( $this->forms_option, array() );
        if ( ! is_array( $forms ) ) {
            $forms = array();
        }
        return $forms;
    }

    private function add_form( $name ) {
        $forms        = $this->get_forms();
        $id           = time();
        $forms[ $id ] = array( 'name' => $name );
        update_option( $this->forms_option, $forms );
        return $id;
    }

    private function delete_form( $id ) {
        $forms = $this->get_forms();
        if ( isset( $forms[ $id ] ) ) {
            unset( $forms[ $id ] );
            update_option( $this->forms_option, $forms );
        }
    }

    public function render_form_shortcode( $atts ) {
        $atts = shortcode_atts( array( 'id' => 0 ), $atts );
        $forms = $this->get_forms();
        if ( empty( $forms[ $atts['id'] ] ) ) {
            return '';
        }
        ob_start();
        include plugin_dir_path( __DIR__ ) . 'templates/form.php';
        return ob_get_clean();
    }

    public function handle_form_submit() {
        if ( ! isset( $_POST['kontaktform_nonce'] ) || ! wp_verify_nonce( $_POST['kontaktform_nonce'], 'kontaktform_submit' ) ) {
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
        wp_register_script( 'kontaktform-block', plugins_url( '../blocks/index.js', __FILE__ ), array( 'wp-blocks', 'wp-element', 'wp-editor' ), '1.0', true );
        wp_register_style( 'kontaktform-block-editor', plugins_url( '../blocks/editor.css', __FILE__ ), array( 'wp-edit-blocks' ) );
        wp_register_style( 'kontaktform-block', plugins_url( '../blocks/style.css', __FILE__ ), array() );
        register_block_type( 'kontaktform/block', array(
            'editor_script' => 'kontaktform-block',
            'editor_style'  => 'kontaktform-block-editor',
            'style'         => 'kontaktform-block',
            'render_callback' => array( $this, 'render_form_block' ),
        ) );
    }

    public function render_form_block( $attributes ) {
        ob_start();
        include plugin_dir_path( __DIR__ ) . 'templates/form.php';
        return ob_get_clean();
    }
}
