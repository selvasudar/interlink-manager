<?php
/*
Plugin Name: Interlink Manager 
Description: A WordPress plugin to manage interlinking based on a CSV file with source URL, destination URL, and keyword.
Version: 1.0
Author: Selvakumar Duraipandian
Author URI: https://www.linkedin.com/in/selvakumarduraipandian/
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: interlink-manager
Domain Path: /languages
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu
add_action('admin_menu', 'interlink_manager_menu');
function interlink_manager_menu() {
    add_menu_page(
        'Interlink Manager',
        'Interlink Manager',
        'manage_options',
        'interlink-manager',
        'interlink_manager_page',
        'dashicons-admin-links'
    );
}

// Enqueue scripts and styles
add_action('admin_enqueue_scripts', 'interlink_manager_enqueue_scripts');
function interlink_manager_enqueue_scripts($hook) {
    if ($hook !== 'toplevel_page_interlink-manager') {
        return;
    }
    
    // Make sure we're using unique version numbers to prevent caching
    $version = time();
    
    wp_enqueue_style('interlink-manager-style', plugin_dir_url(__FILE__) . 'css/style.css', array(), $version);
    wp_enqueue_script('interlink-manager-script', plugin_dir_url(__FILE__) . 'js/script.js', array('jquery'), $version, true);
    wp_localize_script('interlink-manager-script', 'interlinkAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('interlink_nonce')
    ));
}

// Main admin page
function interlink_manager_page() {
    ?>
    <div class="wrap">
        <h1>Interlink Manager</h1>
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="interlink_csv" accept=".csv" required>
            <input type="submit" name="upload_csv" value="Upload CSV" class="button button-primary">
        </form>
        <?php
        // Handle CSV upload
        if (isset($_POST['upload_csv']) && !empty($_FILES['interlink_csv']['tmp_name'])) {
            $file = $_FILES['interlink_csv']['tmp_name'];
            $rows = array_map('str_getcsv', file($file));
            $header = array_shift($rows);
            $expected_headers = array('source_url', 'destination_url', 'keyword');
            if ($header === $expected_headers) {
                global $wpdb;
                $table_name = $wpdb->prefix . 'interlink_data';
                $wpdb->query("TRUNCATE TABLE $table_name");
                foreach ($rows as $row) {
                    $wpdb->insert($table_name, array(
                        'source_url' => esc_url_raw($row[0]),
                        'destination_url' => esc_url_raw($row[1]),
                        'keyword' => sanitize_text_field($row[2]),
                        'status' => 'pending'
                    ));
                }
                echo '<div class="notice notice-success"><p>CSV uploaded successfully!</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>Invalid CSV format. Expected headers: source_url, destination_url, keyword</p></div>';
            }
        }

        // Display table
        global $wpdb;
        $table_name = $wpdb->prefix . 'interlink_data';
        
        // Check if table exists before querying
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        
        if ($table_exists) {
            $results = $wpdb->get_results("SELECT * FROM $table_name");
            if ($results) {
                ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Source URL</th>
                            <th>Destination URL</th>
                            <th>Keyword</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $row) : ?>
                            <tr data-id="<?php echo esc_attr($row->id); ?>">
                                <td><?php echo esc_url($row->source_url); ?></td>
                                <td><?php echo esc_url($row->destination_url); ?></td>
                                <td><?php echo esc_html($row->keyword); ?></td>
                                <td><?php echo esc_html($row->status); ?></td>
                                <td>
                                    <?php if ($row->status === 'pending') : ?>
                                        <button class="button approve-link" data-id="<?php echo esc_attr($row->id); ?>">Approve</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Improved popup structure -->
                <div id="keyword-popup" style="display:none;">
                    <h2>Select Keyword Positions</h2>
                    <div id="keyword-options"></div>
                    <div class="button-container">
                        <button id="confirm-links" class="button button-primary">Confirm</button>
                        <button id="cancel-popup" class="button">Cancel</button>
                    </div>
                </div>
                <?php
            } else {
                echo '<div class="notice notice-info"><p>No interlinking data found. Please upload a CSV file.</p></div>';
            }
        } else {
            echo '<div class="notice notice-error"><p>Database table not found. Please deactivate and reactivate the plugin.</p></div>';
        }
        ?>
    </div>
    <?php
}

// Create database table on plugin activation
register_activation_hook(__FILE__, 'interlink_manager_activate');
function interlink_manager_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'interlink_data';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        source_url varchar(255) NOT NULL,
        destination_url varchar(255) NOT NULL,
        keyword varchar(100) NOT NULL,
        status varchar(20) DEFAULT 'pending',
        PRIMARY KEY (id)
    ) $charset_collate;";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

// AJAX handler for approving links
add_action('wp_ajax_approve_interlink', 'approve_interlink_callback');
function approve_interlink_callback() {
    // Verify nonce
    check_ajax_referer('interlink_nonce', 'nonce');
    
    // Get and validate the ID
    $id = intval($_POST['id']);
    if ($id <= 0) {
        wp_send_json_error('Invalid ID');
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'interlink_data';
    $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
    
    if (!$row) {
        wp_send_json_error('Record not found');
    }

    // Find post by source URL
    $post_id = url_to_postid($row->source_url);
    if (!$post_id) {
        wp_send_json_error('Post not found for source URL: ' . $row->source_url);
    }

    $post = get_post($post_id);
    $content = $post->post_content;
    $keyword = $row->keyword;
    $destination_url = $row->destination_url;

    // Find all occurrences of the keyword
    $pattern = '/\b' . preg_quote($keyword, '/') . '\b/i';
    preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE);
    $positions = array();
    
    foreach ($matches[0] as $match) {
        // Get some context around the match
        $start = max(0, $match[1] - 20);
        $length = strlen($keyword) + 40;
        $positions[] = array(
            'text' => $match[0],
            'offset' => $match[1],
            'context' => substr($content, $start, $length)
        );
    }

    if (empty($positions)) {
        wp_send_json_error('Keyword "' . $keyword . '" not found in content');
    }

    wp_send_json_success(array(
        'positions' => $positions,
        'post_id' => $post_id,
        'destination_url' => $destination_url,
        'keyword' => $keyword,
        'row_id' => $id
    ));
}

// AJAX handler for confirming links
add_action('wp_ajax_confirm_interlink', 'confirm_interlink_callback');
function confirm_interlink_callback() {
    // Verify nonce
    check_ajax_referer('interlink_nonce', 'nonce');
    
    // Get and validate params
    $post_id = intval($_POST['post_id']);
    $row_id = intval($_POST['row_id']);
    $positions = json_decode(stripslashes($_POST['positions']), true);
    $destination_url = esc_url_raw($_POST['destination_url']);
    $keyword = sanitize_text_field($_POST['keyword']);

    if ($post_id <= 0 || $row_id <= 0 || empty($positions) || empty($destination_url) || empty($keyword)) {
        wp_send_json_error('Invalid parameters provided');
    }

    $post = get_post($post_id);
    if (!$post) {
        wp_send_json_error('Post not found');
    }

    $content = $post->post_content;
    $new_content = $content;
    $replacements = 0;

    // Sort positions in reverse order to avoid offset issues
    usort($positions, function($a, $b) {
        return $b['offset'] - $a['offset'];
    });

    foreach ($positions as $pos) {
        $offset = intval($pos['offset']);
        $length = strlen($keyword);
        $link = '<a href="' . esc_url($destination_url) . '">' . $keyword . '</a>';
        $new_content = substr_replace($new_content, $link, $offset, $length);
        $replacements++;
    }

    if ($replacements > 0) {
        // Update the post
        $update_result = wp_update_post(array(
            'ID' => $post_id,
            'post_content' => $new_content
        ), true);
        
        if (is_wp_error($update_result)) {
            wp_send_json_error('Error updating post: ' . $update_result->get_error_message());
        }
        
        // Update the status in the database
        global $wpdb;
        $table_name = $wpdb->prefix . 'interlink_data';
        $update_status = $wpdb->update(
            $table_name, 
            array('status' => 'approved'), 
            array('id' => $row_id)
        );
        
        if ($update_status === false) {
            wp_send_json_error('Error updating record status');
        }
        
        wp_send_json_success('Links added successfully. ' . $replacements . ' replacements made.');
    } else {
        wp_send_json_error('No links added');
    }
}
?>