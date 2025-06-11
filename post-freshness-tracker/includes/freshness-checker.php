<?php
/**
 * Handles the calculation of post freshness status.
 */

// Exit if accessed directly.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Calculates the freshness status of a post.
 *
 * @param int $post_id The ID of the post.
 * @return array An array containing 'status' (text), 'badge_color' (CSS color),
 *               'details' (string for tooltip).
 */
function pft_get_freshness_status( $post_id ) {
    $interval_days = get_post_meta( $post_id, '_pft_freshness_interval', true );

    if ( empty( $interval_days ) || ! is_numeric( $interval_days ) || intval( $interval_days ) <= 0 ) {
        return [
            'status'      => __( 'Not Set', 'post-freshness-tracker' ),
            'badge_color' => '#808080', // Grey
            'details'     => __( 'No freshness interval defined for this post.', 'post-freshness-tracker' ),
        ];
    }

    $interval_days = intval( $interval_days );
    $last_modified_gmt = get_post_field( 'post_modified_gmt', $post_id );

    if ( ! $last_modified_gmt ) {
        // Should not happen for actual posts, but as a fallback
        return [
            'status'      => __( 'Error', 'post-freshness-tracker' ),
            'badge_color' => '#FFA500', // Orange for error
            'details'     => __( 'Could not retrieve the last modified date for this post.', 'post-freshness-tracker' ),
        ];
    }

    try {
        $site_timezone_string = wp_timezone_string();
        $last_modified_date = new DateTime( $last_modified_gmt, new DateTimeZone('GMT') );
        // Convert to site's timezone for consistent display and calculation base if needed
        $last_modified_date->setTimezone(new DateTimeZone($site_timezone_string));

        $next_due_date = clone $last_modified_date;
        $next_due_date->add(new DateInterval("P{$interval_days}D"));

        $current_date = new DateTime('now', new DateTimeZone($site_timezone_string));

        // For clarity in comparison, especially around "end of day" scenarios,
        // it can be useful to normalize times. For instance, consider a post due "today".
        // If current time is 10:00 and due time is 23:59, it's still "Updated".
        // If current time is past midnight of the due day, it becomes "Needs Update".
        // The current direct comparison works if we consider "due" as the exact timestamp
        // calculated by adding interval days.
        // For "due by end of day", setTime(23,59,59) on next_due_date is a good strategy.
        // For this implementation, we'll keep the direct comparison.

        $last_modified_formatted = $last_modified_date->format(get_option('date_format', 'Y-m-d H:i:s'));
        $next_due_date_formatted = $next_due_date->format(get_option('date_format', 'Y-m-d H:i:s'));

        $tooltip_details = sprintf(
            // translators: 1: Last modified date, 2: Interval in days, 3: Next review due date.
            __( 'Last Modified: %1$s. Interval: %2$d days. Review Due: %3$s.', 'post-freshness-tracker' ),
            $last_modified_date->format(get_option('date_format', 'Y-m-d')), // Simpler date for tooltip
            $interval_days,
            $next_due_date->format(get_option('date_format', 'Y-m-d')) // Simpler date for tooltip
        );

        if ( $current_date <= $next_due_date ) {
            return [
                'status'        => __( 'Updated', 'post-freshness-tracker' ),
                'badge_color'   => '#28a745', // Green
                'details'       => $tooltip_details,
            ];
        } else {
            return [
                'status'        => __( 'Needs Update', 'post-freshness-tracker' ),
                'badge_color'   => '#dc3545', // Red
                'details'       => $tooltip_details,
            ];
        }
    } catch (Exception $e) {
        return [
            'status'      => __( 'Date Error', 'post-freshness-tracker' ),
            'badge_color' => '#FFA500', // Orange for error
            'details'     => __( 'Error during date calculation: ', 'post-freshness-tracker' ) . $e->getMessage(),
        ];
    }
}
?>
