<?php
/**
 * Plugin Name:       Post Freshness Tracker
 * Plugin URI:        https://example.com/plugins/post-freshness-tracker/
 * Description:       Tracks the freshness of posts and pages, reminding users to update old content.
 * Version:           1.0.0
 * Author:            Your Name or Company
 * Author URI:        https://example.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       post-freshness-tracker
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Define plugin constants
define( 'PFT_VERSION', '1.0.0' );
define( 'PFT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PFT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include other files
require_once PFT_PLUGIN_DIR . 'includes/functions.php';
require_once PFT_PLUGIN_DIR . 'admin/meta-box.php';
require_once PFT_PLUGIN_DIR . 'admin/list-columns.php';
require_once PFT_PLUGIN_DIR . 'includes/freshness-checker.php';

?>
