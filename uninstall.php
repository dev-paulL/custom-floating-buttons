<?php
// Exit if accessed directly
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Option name used by the plugin
$option_name = 'cflb_buttons';

// Delete the option from the database
delete_option($option_name);
