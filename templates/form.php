<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
    <input type="hidden" name="action" value="kontaktform_submit">
    <input type="hidden" name="form_id" value="<?php echo esc_attr( isset( $atts['id'] ) ? $atts['id'] : 0 ); ?>">
    <?php wp_nonce_field( 'kontaktform_submit', 'kontaktform_nonce' ); ?>
    <p>
        <label for="kontaktform-email">Email</label><br>
        <input type="email" id="kontaktform-email" name="email" required>
    </p>
    <p>
        <label for="kontaktform-message">Nachricht</label><br>
        <textarea id="kontaktform-message" name="message" rows="5" required></textarea>
    </p>
    <p>
        <label>
            <input type="checkbox" name="send_html" value="1"> HTML-Antwort senden
        </label>
    </p>
    <p>
        <button type="submit">Senden</button>
    </p>
</form>
