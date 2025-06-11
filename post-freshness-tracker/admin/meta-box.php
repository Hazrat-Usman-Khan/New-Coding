<?php
/**
 * Handles the meta box for post freshness settings.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Renders the HTML for the post freshness meta box.
 *
 * @param WP_Post $post The current post object.
 */
function pft_render_freshness_meta_box_html( $post ) {
    // Add a nonce field for security.
    wp_nonce_field( 'pft_save_freshness_meta', 'pft_freshness_nonce' );

    // Get the current freshness interval for the post.
    $current_interval = get_post_meta( $post->ID, '_pft_freshness_interval', true );

    // Set default if no interval is saved.
    $default_interval = 120; // Default to 4 Months
    $selected_dropdown_value = $current_interval ? $current_interval : $default_interval;
    $custom_value = '';

    $options = array(
        '14'  => __( '2 Weeks', 'post-freshness-tracker' ),
        '30'  => __( '1 Month', 'post-freshness-tracker' ),
        '60'  => __( '2 Months', 'post-freshness-tracker' ),
        '90'  => __( '3 Months', 'post-freshness-tracker' ),
        '120' => __( '4 Months', 'post-freshness-tracker' ),
        '180' => __( '6 Months', 'post-freshness-tracker' ),
        '365' => __( '1 Year', 'post-freshness-tracker' ),
        'custom' => __( 'Custom', 'post-freshness-tracker' ),
    );

    // If the current interval isn't in the predefined options, select 'custom' and set custom value.
    if ( $current_interval && ! array_key_exists( (string) $current_interval, $options ) ) {
        $selected_dropdown_value = 'custom';
        $custom_value = $current_interval;
    } elseif ( ! $current_interval ) {
        $selected_dropdown_value = $default_interval; // Default selection
    }


    ?>
    <p>
        <label for="pft_freshness_interval_dropdown"><?php esc_html_e( 'Set Freshness Interval:', 'post-freshness-tracker' ); ?></label>
        <span title="<?php esc_attr_e( 'Select how often this post should be reviewed or updated. The \'Freshness\' status will be based on this interval from the last modified date.', 'post-freshness-tracker' ); ?>">ℹ️</span>
    </p>
    <p>
        <select name="pft_freshness_interval_dropdown" id="pft_freshness_interval_dropdown">
            <?php
            foreach ( $options as $value => $label ) {
                echo '<option value="' . esc_attr( $value ) . '"' . selected( $selected_dropdown_value, $value, false ) . '>' . esc_html( $label ) . '</option>';
            }
            ?>
        </select>
    </p>
    <p>
        <input type="number" name="pft_freshness_custom_interval" id="pft_freshness_custom_interval" value="<?php echo esc_attr( $custom_value ); ?>" min="1" style="display:none;">
        <label for="pft_freshness_custom_interval" style="display:none;"><?php esc_html_e( 'Days', 'post-freshness-tracker' ); ?></label>
    </p>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const dropdown = document.getElementById('pft_freshness_interval_dropdown');
        const customInput = document.getElementById('pft_freshness_custom_interval');
        const customInputLabel = document.querySelector('label[for="pft_freshness_custom_interval"]');

        if (!dropdown || !customInput || !customInputLabel) return;

        function toggleCustomInput() {
            if (dropdown.value === 'custom') {
                customInput.style.display = 'inline-block';
                customInputLabel.style.display = 'inline-block';
            } else {
                customInput.style.display = 'none';
                customInputLabel.style.display = 'none';
            }
        }
        dropdown.addEventListener('change', toggleCustomInput);
        toggleCustomInput(); // Initial check
    });
    </script>
    <?php
}

/**
 * Adds the meta box to the post edit screen.
 */
function pft_add_freshness_meta_box() {
    add_meta_box(
        'pft_freshness_meta',                            // ID
        __( 'Post Freshness', 'post-freshness-tracker' ), // Title
        'pft_render_freshness_meta_box_html',            // Callback
        'post',                                          // Screen (post type)
        'side',                                          // Context
        'default'                                        // Priority
    );
    // To add to pages as well:
    // add_meta_box(
    //     'pft_freshness_meta',
    //     __( 'Post Freshness', 'post-freshness-tracker' ),
    //     'pft_render_freshness_meta_box_html',
    //     'page', // Screen (post type)
    //     'side',
    //     'default'
    // );
}
add_action( 'add_meta_boxes', 'pft_add_freshness_meta_box' );

// Saving the meta box data will be handled in a separate function hooked to 'save_post'.
// This will be added in a subsequent step.

/**
 * Saves the freshness interval meta data for the post.
 *
 * @param int $post_id The ID of the post being saved.
 */
function pft_save_freshness_meta_data( $post_id ) {
    // 1. Verify the nonce.
    if ( ! isset( $_POST['pft_freshness_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pft_freshness_nonce'] ) ) , 'pft_save_freshness_meta' ) ) {
        return;
    }

    // 2. Handle autosave.
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // 3. Check permissions.
    if ( isset( $_POST['post_type'] ) && 'post' === $_POST['post_type'] ) { // Check for post type as well
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
    } else {
        // Assuming 'page' or other post types might be added later.
        // For now, if it's not 'post', we also check 'edit_page'.
        // This part might need adjustment if more specific post types are handled.
        if ( ! current_user_can( 'edit_page', $post_id ) ) {
             return;
        }
    }


    // 4. Retrieve and Sanitize Data.
    $interval_to_save = 120; // Default to 4 Months (120 days)

    if ( isset( $_POST['pft_freshness_interval_dropdown'] ) ) {
        $selected_dropdown_value = sanitize_text_field( wp_unslash( $_POST['pft_freshness_interval_dropdown'] ) );

        if ( $selected_dropdown_value === 'custom' ) {
            $custom_value = isset( $_POST['pft_freshness_custom_interval'] ) ? intval( $_POST['pft_freshness_custom_interval'] ) : 0;
            if ( $custom_value > 0 ) {
                $interval_to_save = $custom_value;
            }
            // If custom_value is not > 0, it remains the default (120 or previously set default).
        } else {
            $predefined_value = intval( $selected_dropdown_value );
            if ( $predefined_value > 0 ) {
                // Check if the predefined value is one of the valid options to be extra sure.
                // For now, intval > 0 is a basic check.
                $valid_predefined_options = array('14', '30', '60', '90', '120', '180', '365');
                if (in_array((string)$predefined_value, $valid_predefined_options, true)) {
                    $interval_to_save = $predefined_value;
                }
            }
        }
    }

    // 5. Save the Meta Data.
    update_post_meta( $post_id, '_pft_freshness_interval', $interval_to_save );
}
add_action( 'save_post', 'pft_save_freshness_meta_data' );

?>
