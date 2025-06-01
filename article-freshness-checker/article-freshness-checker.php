<?php
/**
 * Plugin Name:       Article Freshness Checker
 * Plugin URI:
 * Description:       Highlights stale articles and indicates when they have been updated.
 * Version:           1.0.0
 * Author:            Plugin User
 * Author URI:
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       article-freshness-checker
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// --- Internationalization ---

/**
 * Load plugin textdomain for internationalization.
 */
function afc_load_textdomain() {
    load_plugin_textdomain(
        'article-freshness-checker',
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages/'
    );
}
add_action( 'plugins_loaded', 'afc_load_textdomain' );

// --- Admin Settings ---

/**
 * Adds the settings page to the WordPress admin menu.
 */
function afc_add_settings_page() {
    add_options_page(
        __( 'Article Freshness Checker Settings', 'article-freshness-checker' ), // Page title
        __( 'Article Freshness', 'article-freshness-checker' ),                  // Menu title
        'manage_options',                     // Capability
        'afc-settings',                       // Menu slug
        'afc_render_settings_page'            // Callback function
    );
}
add_action( 'admin_menu', 'afc_add_settings_page' );

/**
 * Registers the plugin settings and fields.
 */
function afc_register_settings() {
    register_setting(
        'afc-options-group',          // Option group
        'afc_staleness_threshold',    // Option name
        array( 'sanitize_callback' => 'absint' ) // Sanitize callback
    );

    add_settings_section(
        'afc_general_section',        // ID
        __( 'General Settings', 'article-freshness-checker' ),           // Title
        null,                         // Callback (optional)
        'afc-settings'                // Page
    );

    add_settings_field(
        'afc_staleness_threshold',    // ID
        __( 'Staleness Threshold (days)', 'article-freshness-checker' ), // Title
        'afc_staleness_threshold_field_html', // Callback
        'afc-settings',               // Page
        'afc_general_section'         // Section
    );
}
add_action( 'admin_init', 'afc_register_settings' );

/**
 * Renders the HTML for the settings page.
 */
function afc_render_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Article Freshness Checker Settings', 'article-freshness-checker' ); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'afc-options-group' );
            do_settings_sections( 'afc-settings' );
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

/**
 * Renders the HTML for the staleness threshold field.
 */
function afc_staleness_threshold_field_html() {
    $value = get_option( 'afc_staleness_threshold', 30 );
    echo "<input type='number' name='afc_staleness_threshold' value='" . esc_attr( $value ) . "' />";
}

// --- Core Logic ---

/**
 * Checks if an article is stale based on its last modified date.
 *
 * @param int $post_id The ID of the post to check.
 * @return bool True if the article is stale, false otherwise.
 */
function afc_is_article_stale( $post_id ) {
    if ( ! is_numeric( $post_id ) || $post_id <= 0 ) {
        return false;
    }

    $post = get_post( $post_id );
    if ( ! $post ) {
        return false;
    }

    $last_modified_timestamp = get_post_modified_time( 'U', false, $post_id );
    $current_timestamp = time();
    // Use the value from settings, default to 30 if not set.
    $staleness_days = get_option( 'afc_staleness_threshold', 30 );
    $threshold_seconds = absint( $staleness_days ) * DAY_IN_SECONDS;
    $staleness_threshold_timestamp = $current_timestamp - $threshold_seconds;

    if ( $last_modified_timestamp < $staleness_threshold_timestamp ) {
        return true;
    }

    return false;
}

/**
 * Adds a freshness label to the article title if it's stale.
 *
 * @param string $title The original post title.
 * @param int|null $id The post ID.
 * @return string The modified title with freshness label or the original title.
 */
function afc_add_freshness_label_to_title( $title, $id = null ) {
    // Ensure it's modifying a title within the main query, in the loop, on a single post view, and not in an admin area.
    if ( ! is_main_query() || ! in_the_loop() || ! is_singular() || is_admin() ) {
        return $title;
    }

    // Get the post ID.
    $post_id = $id ? $id : get_the_ID();

    // If no valid post ID can be obtained, return the original title.
    if ( ! $post_id ) {
        return $title;
    }

    // Check if the article is stale.
    if ( afc_is_article_stale( $post_id ) ) {
        // Append the staleness indicator to the title.
        $title .= sprintf( ' <span style="color: red;">(%s)</span>', esc_html__( 'Needs Update', 'article-freshness-checker' ) );
    }

    return $title;
}
add_filter( 'the_title', 'afc_add_freshness_label_to_title', 10, 2 );

/*
 * == Developer Notes & Manual Testing ==
 *
 * To manually test the `afc_is_article_stale()` function:
 *
 * 1. Ensure you have posts with various modification dates.
 * 2. You can temporarily use the following snippet by uncommenting it and placing it
 *    where it can be executed (e.g., temporarily in your theme's functions.php or
 *    using a code snippets plugin).
 *    Remember to remove it after testing.
 *
 * <?php
 * // Make sure the plugin is active and functions are loaded.
 * if (function_exists('afc_is_article_stale')) {
 *     // Fetch a few post IDs for testing (replace with actual post IDs from your site)
 *     $test_post_ids = [1, 2, 3]; // Example post IDs
 *     $staleness_threshold = get_option('afc_staleness_threshold', 30);
 *
 *     echo "<h3>Article Freshness Tests (Threshold: " . esc_html($staleness_threshold) . " days)</h3>";
 *     echo "<pre>";
 *
 *     foreach ($test_post_ids as $post_id) {
 *         $post = get_post($post_id);
 *         if ($post) {
 *             $modified_date = get_post_modified_time('Y-m-d H:i:s', false, $post_id);
 *             $is_stale = afc_is_article_stale($post_id);
 *             echo "Post ID: " . esc_html($post_id) . " ('" . esc_html(get_the_title($post_id)) . "')";
 *             echo " - Modified: " . esc_html($modified_date);
 *             echo " - Is Stale? " . ($is_stale ? "Yes" : "No") . "\n";
 *         } else {
 *             echo "Post ID: " . esc_html($post_id) . " - Not found.\n";
 *         }
 *     }
 *     echo "</pre>";
 * } else {
 *     echo "Article Freshness Checker plugin function 'afc_is_article_stale' not found. Is the plugin active?\n";
 * }
 * ?>
 *
 * 3. Common scenarios to check:
 *    - Post modified more than `afc_staleness_threshold` days ago (should be stale).
 *    - Post modified less than `afc_staleness_threshold` days ago (should not be stale).
 *    - Post modified exactly `afc_staleness_threshold` days ago (should typically be considered stale, depending on < vs <= comparison, current logic is <).
 *    - Invalid post ID (should not be stale, and not error out).
 */
