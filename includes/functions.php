<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Constants for option names.
define('CFLB_OPTION_NAME', 'cflb_buttons');
define('CFLB_NONCE_ACTION', 'cflb_options');

/**
 * Adds a custom options page to the WordPress admin menu.
 */
function cflb_add_admin_menu()
{
    add_options_page(
        __('Boutons flottants personnalisés', 'custom-floating-buttons'), // Page title.
        __('Boutons flottants', 'custom-floating-buttons'), // Menu title.
        'manage_options', // Capability required to access this menu.
        'custom-floating-buttons', // Menu slug.
        'cflb_render_settings_page' // Function to render the settings page.
    );
}
add_action('admin_menu', 'cflb_add_admin_menu');

/**
 * Renders the settings page for managing custom floating buttons.
 */
function cflb_render_settings_page()
{
    // Check if the current user has the required capability.
    if (!current_user_can('manage_options')) {
        return;
    }

    // Display messages based on query parameters.
    if (isset($_GET['message'])) {
        switch ($_GET['message']) {
            case 'deleted':
                add_settings_error('cflb_messages', 'cflb_message', __('Le bouton a été supprimé avec succès.', 'custom-floating-buttons'), 'updated');
                break;
            case 'updated':
                add_settings_error('cflb_messages', 'cflb_message', __('Le bouton a été mis à jour avec succès.', 'custom-floating-buttons'), 'updated');
                break;
        }
    }

    // Handle form submission for adding or updating a button.
    if (isset($_POST['submit'])) {
        $edit_mode = isset($_GET['action']) && $_GET['action'] === 'edit';
        $button_index = isset($_GET['button']) ? intval($_GET['button']) : 0;
        $buttons = get_option(CFLB_OPTION_NAME, array());

        $button_to_edit = $edit_mode && isset($buttons[$button_index]) ? $buttons[$button_index] : null;

        cflb_handle_form_submission($edit_mode, $button_to_edit);
    }

    // Render the form for adding or editing a button.
    $edit_mode = isset($_GET['action']) && $_GET['action'] === 'edit';
    $button_index = isset($_GET['button']) ? intval($_GET['button']) : 0;
    $buttons = get_option(CFLB_OPTION_NAME, array());
    $button_to_edit = $edit_mode && isset($buttons[$button_index]) ? $buttons[$button_index] : null;

    cflb_render_form($edit_mode, $button_to_edit);
}

/**
 * Handles the form submission for adding or updating a floating button.
 *
 * @param bool       $edit_mode        Indicates if the form is in edit mode.
 * @param array|null $button_to_edit   The button data to edit, if in edit mode.
 */
function cflb_handle_form_submission($edit_mode, $button_to_edit)
{
    // Verify nonce for security.
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], CFLB_NONCE_ACTION)) {
        wp_die(__('Security check failed.', 'custom-floating-buttons'));
    }

    // Sanitize and prepare button data.
    $link = isset($_POST['cflb_link']) ? trim($_POST['cflb_link']) : '';
    $button_data = array(
        'text' => isset($_POST['cflb_text']) ? sanitize_text_field(stripslashes($_POST['cflb_text'])) : '',
        'bg_color' => isset($_POST['cflb_bg_color']) ? sanitize_hex_color($_POST['cflb_bg_color']) : '#ffffff',
        'text_color' => isset($_POST['cflb_text_color']) ? sanitize_hex_color($_POST['cflb_text_color']) : '#000000',
        'position' => isset($_POST['cflb_position']) ? sanitize_text_field($_POST['cflb_position']) : '',
        'icon' => isset($_POST['cflb_icon']) ? sanitize_text_field($_POST['cflb_icon']) : '',
        'link' => empty($link) ? '#' : esc_url_raw($link),
    );

    if ($edit_mode) {
        if (!$button_to_edit) {
            add_settings_error('cflb_messages', 'cflb_message', __('Bouton non trouvé.', 'custom-floating-buttons'), 'error');
            return;
        }

        $button_index = array_search($button_to_edit, get_option(CFLB_OPTION_NAME, array()), true);
        if ($button_index === false) {
            add_settings_error('cflb_messages', 'cflb_message', __('Bouton non trouvé.', 'custom-floating-buttons'), 'error');
            return;
        }

        cflb_update_button($button_index, $button_data);
    } else {
        cflb_save_button($button_data);
    }
}

/**
 * Renders the form for adding or editing a floating button.
 *
 * @param bool       $edit_mode        Indicates if the form is in edit mode.
 * @param array|null $button_to_edit   The button data to edit, if in edit mode.
 */
function cflb_render_form($edit_mode, $button_to_edit)
{
    ?>
    <div class="wrap">
        <h1><?php echo $edit_mode ? esc_html__('Modifier le bouton flottant', 'custom-floating-buttons') : esc_html(get_admin_page_title()); ?>
        </h1>
        <?php settings_errors('cflb_messages'); ?>
        <form method="post">
            <?php wp_nonce_field(CFLB_NONCE_ACTION); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="cflb_text"><?php esc_html_e('Texte du bouton:', 'custom-floating-buttons'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="cflb_text" name="cflb_text" class="regular-text" required
                            value="<?php echo esc_attr($edit_mode && $button_to_edit ? $button_to_edit['text'] : ''); ?>">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label
                            for="cflb_link"><?php esc_html_e('Lien du bouton (facultatif):', 'custom-floating-buttons'); ?></label>
                    </th>
                    <td>
                        <input type="url" id="cflb_link" name="cflb_link" class="regular-text"
                            value="<?php echo esc_attr($edit_mode && $button_to_edit ? $button_to_edit['link'] : ''); ?>">
                        <p class="description">
                            <?php esc_html_e('Laissez vide pour que le bouton redirige vers #', 'custom-floating-buttons'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label
                            for="cflb_bg_color"><?php esc_html_e('Couleur de fond:', 'custom-floating-buttons'); ?></label>
                    </th>
                    <td>
                        <input type="color" id="cflb_bg_color" name="cflb_bg_color" required
                            value="<?php echo esc_attr($edit_mode && $button_to_edit ? $button_to_edit['bg_color'] : '#ffffff'); ?>">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label
                            for="cflb_text_color"><?php esc_html_e('Couleur du texte:', 'custom-floating-buttons'); ?></label>
                    </th>
                    <td>
                        <input type="color" id="cflb_text_color" name="cflb_text_color" required
                            value="<?php echo esc_attr($edit_mode && $button_to_edit ? $button_to_edit['text_color'] : '#000000'); ?>">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="cflb_position"><?php esc_html_e('Position:', 'custom-floating-buttons'); ?></label>
                    </th>
                    <td>
                        <select id="cflb_position" name="cflb_position" required>
                            <option value="">
                                <?php esc_html_e('-- Position --', 'custom-floating-buttons'); ?>
                            </option>
                            <option value="top-left" <?php selected($edit_mode && $button_to_edit ? $button_to_edit['position'] : '', 'top-left'); ?>>
                                <?php esc_html_e('Haut gauche', 'custom-floating-buttons'); ?>
                            </option>
                            <option value="top-right" <?php selected($edit_mode && $button_to_edit ? $button_to_edit['position'] : '', 'top-right'); ?>>
                                <?php esc_html_e('Haut droite', 'custom-floating-buttons'); ?>
                            </option>
                            <option value="bottom-left" <?php selected($edit_mode && $button_to_edit ? $button_to_edit['position'] : '', 'bottom-left'); ?>>
                                <?php esc_html_e('Bas gauche', 'custom-floating-buttons'); ?>
                            </option>
                            <option value="bottom-right" <?php selected($edit_mode && $button_to_edit ? $button_to_edit['position'] : '', 'bottom-right'); ?>>
                                <?php esc_html_e('Bas droite', 'custom-floating-buttons'); ?>
                            </option>
                            <option value="center" <?php selected($edit_mode && $button_to_edit ? $button_to_edit['position'] : '', 'center'); ?>>
                                <?php esc_html_e('Centre', 'custom-floating-buttons'); ?>
                            </option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="cflb_icon"><?php esc_html_e('Icône:', 'custom-floating-buttons'); ?></label>
                    </th>
                    <td>
                        <select id="cflb_icon" name="cflb_icon" required>
                            <option value=""><?php esc_html_e('-- Icône --', 'custom-floating-buttons'); ?>
                            </option>
                            <option value="dashicons-email" <?php selected($edit_mode && $button_to_edit ? $button_to_edit['icon'] : '', 'dashicons-email'); ?>>
                                <?php esc_html_e('Email', 'custom-floating-buttons'); ?>
                            </option>
                            <option value="dashicons-phone" <?php selected($edit_mode && $button_to_edit ? $button_to_edit['icon'] : '', 'dashicons-phone'); ?>>
                                <?php esc_html_e('Téléphone', 'custom-floating-buttons'); ?>
                            </option>
                            <option value="dashicons-location" <?php selected($edit_mode && $button_to_edit ? $button_to_edit['icon'] : '', 'dashicons-location'); ?>>
                                <?php esc_html_e('Localisation', 'custom-floating-buttons'); ?>
                            </option>
                            <option value="dashicons-admin-site" <?php selected($edit_mode && $button_to_edit ? $button_to_edit['icon'] : '', 'dashicons-admin-site'); ?>>
                                <?php esc_html_e('Site web', 'custom-floating-buttons'); ?>
                            </option>
                            <option value="dashicons-share" <?php selected($edit_mode && $button_to_edit ? $button_to_edit['icon'] : '', 'dashicons-share'); ?>>
                                <?php esc_html_e('Partager', 'custom-floating-buttons'); ?>
                            </option>
                        </select>
                    </td>
                </tr>
            </table>
            <?php
            submit_button($edit_mode ? __('Mettre à jour le bouton', 'custom-floating-buttons') : __('Ajouter le bouton', 'custom-floating-buttons'));
            ?>
        </form>
        <?php cflb_display_buttons_table_admin(); ?>
    </div>
    <?php
}

/**
 * Saves a new floating button to the options.
 *
 * @param array $button_data The data of the button to save.
 * @return bool True on success, false on failure.
 */
function cflb_save_button($button_data)
{
    // Validate button text.
    if (empty(trim($button_data['text']))) {
        add_settings_error('cflb_messages', 'cflb_message', __('Vérifiez le texte du bouton.', 'custom-floating-buttons'), 'error');
        return false;
    }

    // Validate button position.
    $valid_positions = ['top-left', 'top-right', 'bottom-left', 'bottom-right', 'center'];
    if (!in_array($button_data['position'], $valid_positions, true)) {
        add_settings_error('cflb_messages', 'cflb_message', __('Position invalide.', 'custom-floating-buttons'), 'error');
        return false;
    }

    // Validate button icon.
    $valid_icons = ['dashicons-email', 'dashicons-phone', 'dashicons-location', 'dashicons-admin-site', 'dashicons-share'];
    if (!in_array($button_data['icon'], $valid_icons, true)) {
        add_settings_error('cflb_messages', 'cflb_message', __('Icône invalide.', 'custom-floating-buttons'), 'error');
        return false;
    }

    // Save button data to options.
    $all_buttons = get_option(CFLB_OPTION_NAME, array());
    $all_buttons[] = $button_data;

    if (!update_option(CFLB_OPTION_NAME, $all_buttons)) {
        error_log('Failed to save floating button: ' . print_r($button_data, true));
        add_settings_error('cflb_messages', 'cflb_message', __('Erreur lors de l\'enregistrement du bouton.', 'custom-floating-buttons'), 'error');
        return false;
    }

    add_settings_error('cflb_messages', 'cflb_message', __('Bouton enregistré avec succès.', 'custom-floating-buttons'), 'updated');
    return true;
}

/**
 * Displays the table of existing floating buttons in the admin area.
 */
function cflb_display_buttons_table_admin()
{
    $buttons = get_option(CFLB_OPTION_NAME, array());
    if (!empty($buttons)) {
        ?>
        <h2><?php esc_html_e('Liste des boutons flottants', 'custom-floating-buttons'); ?></h2>
        <?php
        foreach ($buttons as $index => $button) {
            ?>
            <div class="cflb-preview-row" style="margin-bottom: 20px;">
                <a href="<?php echo esc_url($button['link']); ?>" class="cflb-button-preview"
                    style="background-color: <?php echo esc_attr($button['bg_color']); ?>; color: <?php echo esc_attr($button['text_color']); ?>; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
                    <span class="dashicons <?php echo esc_attr($button['icon']); ?>"></span>
                    <?php echo esc_html($button['text']); ?>
                </a>
                <p><?php echo sprintf(esc_html__('Lien: %s', 'custom-floating-buttons'), $button['link'] === '#' ? esc_html__('Aucun lien', 'custom-floating-buttons') : esc_url($button['link'])); ?>
                </p>
                <p><?php echo sprintf(esc_html__('Position: %s', 'custom-floating-buttons'), esc_html($button['position'])); ?></p>
                <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(array('page' => 'custom-floating-buttons', 'action' => 'edit', 'button' => $index), admin_url('options-general.php')), 'edit_button_' . $index)); ?>"
                    class="button button-secondary"><?php esc_html_e('Modifier', 'custom-floating-buttons'); ?></a>
                <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(array('page' => 'custom-floating-buttons', 'action' => 'delete', 'button' => $index), admin_url('options-general.php')), 'delete_button_' . $index)); ?>"
                    class="button button-secondary"
                    onclick="return confirm('<?php echo esc_js(__('Êtes-vous sûr de vouloir supprimer ce bouton ?', 'custom-floating-buttons')); ?>');">
                    <?php esc_html_e('Supprimer', 'custom-floating-buttons'); ?>
                </a>
            </div>
            <?php
        }
    } else {
        echo '<p>' . esc_html__('Aucun bouton flottant trouvé.', 'custom-floating-buttons') . '</p>';
    }
}

/**
 * Deletes a floating button based on its index.
 *
 * @param int $button_index The index of the button to delete.
 */
function cflb_delete_button($button_index)
{
    // Check user permissions.
    if (!current_user_can('manage_options') || !is_admin()) {
        wp_die(__('Vous n\'avez pas la permission de supprimer un bouton flottant.', 'custom-floating-buttons'));
    }

    // Verify nonce for security.
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'delete_button_' . $button_index)) {
        wp_die(__('Vérification de sécurité échouée.', 'custom-floating-buttons'));
    }

    // Remove the button from options.
    $buttons = get_option(CFLB_OPTION_NAME, array());
    if (isset($buttons[$button_index])) {
        unset($buttons[$button_index]);
        update_option(CFLB_OPTION_NAME, $buttons);

        // Redirect to the main settings page with a success message
        wp_redirect(add_query_arg(
            array(
                'page' => 'custom-floating-buttons',
                'message' => 'deleted'
            ),
            admin_url('options-general.php')
        ));
        exit;
    } else {
        // Redirect to the main settings page with an error message
        wp_redirect(add_query_arg(
            array(
                'page' => 'custom-floating-buttons',
                'error' => 'not_found'
            ),
            admin_url('options-general.php')
        ));
        exit;
    }
}

/**
 * Handles admin actions for editing and deleting buttons.
 */
add_action('admin_init', function () {
    if (isset($_GET['action'])) {
        if ($_GET['action'] === 'delete' && isset($_GET['page']) && $_GET['page'] === 'custom-floating-buttons') {
            $button_index = isset($_GET['button']) ? intval($_GET['button']) : 0;
            cflb_delete_button($button_index);
        }
        if ($_GET['action'] === 'edit' && !isset($_GET['_wpnonce'])) {
            wp_die(__('Vérification de sécurité échouée.', 'custom-floating-buttons'));
        }
    }
});

/**
 * Displays the floating buttons on the frontend.
 */
function cflb_display_buttons_frontend()
{
    $buttons = get_option(CFLB_OPTION_NAME, array());
    if (!empty($buttons)) {
        foreach ($buttons as $button) {
            printf(
                '<a class="cflb-button cflb-position-%s" style="background-color: %s; color: %s; padding: 10px 20px; text-decoration: none; border-radius: 5px;" href="%s"><span class="dashicons %s"></span>%s</a>',
                esc_attr($button['position']),
                esc_attr($button['bg_color']),
                esc_attr($button['text_color']),
                esc_url($button['link']),
                esc_attr($button['icon']),
                esc_html($button['text'])
            );
        }
    }
}
add_action('wp_footer', 'cflb_display_buttons_frontend');

/**
 * Updates an existing floating button with new data.
 *
 * @param int   $button_index The index of the button to update.
 * @param array $button_data  The new data for the button.
 * @return bool True on success, false on failure.
 */
function cflb_update_button($button_index, $button_data)
{
    // Validate button text.
    if (empty(trim($button_data['text']))) {
        add_settings_error('cflb_messages', 'cflb_message', __('Vérifiez le texte du bouton.', 'custom-floating-buttons'), 'error');
        return false;
    }

    // Validate button position.
    $valid_positions = ['top-left', 'top-right', 'bottom-left', 'bottom-right', 'center'];
    if (!in_array($button_data['position'], $valid_positions, true)) {
        add_settings_error('cflb_messages', 'cflb_message', __('Position invalide.', 'custom-floating-buttons'), 'error');
        return false;
    }

    // Validate button icon.
    $valid_icons = ['dashicons-email', 'dashicons-phone', 'dashicons-location', 'dashicons-admin-site', 'dashicons-share'];
    if (!in_array($button_data['icon'], $valid_icons, true)) {
        add_settings_error('cflb_messages', 'cflb_message', __('Icône invalide.', 'custom-floating-buttons'), 'error');
        return false;
    }

    // Retrieve existing buttons.
    $buttons = get_option(CFLB_OPTION_NAME, array());

    // Ensure the button index exists.
    if (!isset($buttons[$button_index])) {
        add_settings_error('cflb_messages', 'cflb_message', __('Bouton non trouvé.', 'custom-floating-buttons'), 'error');
        return false;
    }

    // Update the button data.
    $buttons[$button_index] = $button_data;

    // Save the updated buttons.
    if (!update_option(CFLB_OPTION_NAME, $buttons)) {
        add_settings_error('cflb_messages', 'cflb_message', __('Erreur lors de la mise à jour du bouton.', 'custom-floating-buttons'), 'error');
        return false;
    }

    // Add success message.
    add_settings_error('cflb_messages', 'cflb_message', __('Bouton mis à jour avec succès.', 'custom-floating-buttons'), 'updated');
    return true;
}