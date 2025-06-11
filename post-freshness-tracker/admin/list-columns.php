<?php
/**
 * Handles the custom "Freshness" column in the posts list table.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Adds the "Freshness" column to the posts list table.
 *
 * @param array $columns An array of existing columns.
 * @return array An array of columns with the "Freshness" column added.
 */
function pft_add_freshness_column_to_post_list( $columns ) {
    // Add "Freshness" column before the "Date" column if it exists
    $new_columns = array();
    $date_column_key = 'date';

    foreach ( $columns as $key => $title ) {
        if ( $key === $date_column_key ) {
            $new_columns['pft_freshness'] = __( 'Freshness', 'post-freshness-tracker' );
        }
        $new_columns[ $key ] = $title;
    }

    // If 'date' column was not found, add 'pft_freshness' at the end
    if ( ! isset( $new_columns['pft_freshness'] ) ) {
         $new_columns['pft_freshness'] = __( 'Freshness', 'post-freshness-tracker' );
    }

    return $new_columns;
}
add_filter( 'manage_post_posts_columns', 'pft_add_freshness_column_to_post_list' );

/**
 * Displays the data for the custom "Freshness" column.
 *
 * @param string $column_name The name of the current column.
 * @param int    $post_id     The ID of the current post.
 */
function pft_display_freshness_column_data( $column_name, $post_id ) {
    if ( $column_name === 'pft_freshness' ) {
        // Ensure the freshness checker function is available
        if ( ! function_exists( 'pft_get_freshness_status' ) ) {
            // This might happen if includes/freshness-checker.php wasn't loaded properly,
            // though post-freshness-tracker.php should handle it.
            echo esc_html__( 'Checker N/A', 'post-freshness-tracker' );
            return;
        }

        $freshness_data = pft_get_freshness_status( $post_id );

        $status_text = esc_html( $freshness_data['status'] );
        $badge_color = esc_attr( $freshness_data['badge_color'] );
        $details = isset($freshness_data['details']) ? esc_attr($freshness_data['details']) : '';

        printf(
            '<span title="%s" style="display:inline-block; padding: 3px 8px; color:white; background-color:%s; border-radius:4px; font-size:0.9em;">%s</span>',
            $details,
            $badge_color,
            $status_text
        );
    }
}
add_action( 'manage_post_posts_custom_column', 'pft_display_freshness_column_data', 10, 2 );

?>
