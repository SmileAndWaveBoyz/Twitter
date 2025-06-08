<?php

// Adds CSS, Bootstrap, and JS files to the theme
function add_css_and_js() {
    wp_enqueue_style('bootstrap-css', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css');
    wp_enqueue_script('bootstrap-js', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js', ['jquery'], null, true);
    wp_enqueue_style('style', get_stylesheet_uri());
    wp_enqueue_script('main', get_template_directory_uri() . '/js/main.js', [], null, true);

    // Pass REST API URL and nonce to the script
    wp_localize_script('main', 'dataVar', [
        'apiUrl' => esc_url_raw(rest_url()),
        'nonce' => wp_create_nonce('wp_rest'),
    ]);
}
add_action('wp_enqueue_scripts', 'add_css_and_js');

// This function sets up theme support for post thumbnails and register a navigation menu.
function setup() {
    add_theme_support('post-thumbnails');
    register_nav_menus([
        'navigation-bar' => 'Navigation Bar',
    ]);
}
add_action('after_setup_theme', 'setup');

//Add a logout button to the navigation bar
function add_logout_button($items, $args) {
    if (is_user_logged_in() && $args->theme_location == 'navigation-bar') {
        $items .= '<li class="menu-item logout-button"><a href="' . wp_logout_url(site_url('/login')) . '">Logout</a></li>';
    }
    return $items;
}
add_filter('wp_nav_menu_items', 'add_logout_button', 10, 2);

// Non logged in users should only be able to access the login and register pages.
function restrict_access() {
    if (!is_user_logged_in() && !is_page(['login', 'register'])) {
        wp_redirect(site_url('/login'));
        exit;
    }
}
add_action('template_redirect', 'restrict_access');

// Hide the admin bar for non-administrators
function mytheme_show_admin_bar_for_admins($show) {
    if (!current_user_can('administrator')) {
        return false;
    }
    return $show;
}
add_filter('show_admin_bar', 'mytheme_show_admin_bar_for_admins');

// Create a custom post type for tweets
function create_tweet_post_type() {
    register_post_type('tweet', [
        'labels' => [
            'name' => 'Tweets',
            'singular_name' => 'Tweet',
        ],
        'public' => true,
        'show_ui' => true,
        'supports' => ['title', 'editor'],
        'rewrite' => false,
        'show_in_rest' => true,
    ]);
}
add_action('init', 'create_tweet_post_type');

//Create a custom REST API endpoint for searching users
function mytheme_register_user_search_endpoint() {
    register_rest_route('mytheme/v1', '/users', [
        'methods'   => 'GET',
        'callback' => 'mytheme_search_users',
        'permission_callback' => function() {
            return is_user_logged_in(); //Only alow logged-in users to search
        },
    ]);
}
add_action('rest_api_init', 'mytheme_register_user_search_endpoint');

//Callback function for the user search endpoint
function mytheme_search_users(WP_REST_Request $request) {
    global $wpdb;
    $search_query = sanitize_text_field($request->get_param('search'));
    $current_user_id = get_current_user_id();

    if (empty($search_query)) {
        return new WP_Error('no_search_term', 'No search term provided.', ['status' => 400]);
    }

    $args = [
        'search'         => '*' . esc_attr($search_query) . '*',
        'search_columns' => ['user_login', 'user_nicename', 'display_name'],
        'number'         => 10,
        'fields'         => ['ID', 'display_name'],
    ];

    $user_query = new WP_User_Query($args);
    $users = $user_query->get_results();

    if (empty($users)) {
        return [];
    }

    $results = [];
    foreach ($users as $user) {
        // Check if the current user is already friends with this user
        $is_friend = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}friendships
            WHERE (user_id = %d AND friend_id = %d AND status = 'accepted')
            OR (user_id = %d AND friend_id = %d AND status = 'accepted')",
            $current_user_id, $user->ID, $user->ID, $current_user_id
        ));

        $results[] = [
            'id'   => $user->ID,
            'name' => $user->display_name,
            'is_friend'    => $is_friend > 0, // True if already friends
        ];
    }

    return $results;
}

//Create the friendships database
function create_friendships_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'friendships';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        friend_id BIGINT UNSIGNED NOT NULL,
        status ENUM('pending', 'accepted') DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_friendship (user_id, friend_id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    // log any errors to wp-content/debug.log
    if ($wpdb->last_error) {
        error_log('Database error: ' . $wpdb->last_error);
    }
}
add_action('after_switch_theme', 'create_friendships_table');

//Create a custom REST API endpoint for sending friend requests
function register_add_friend_request_endpoint() {
    register_rest_route('mytheme/v1', '/add-friend-request', [
        'methods'   => 'POST',
        'callback'  =>  'add_friend_request',
        'permission_callback' => function () {
            return is_user_logged_in(); //Only allow logged-in users
        },
    ]);
}
add_action('rest_api_init', 'register_add_friend_request_endpoint');

function add_friend_request($request){
    global $wpdb;

    $current_user_id = get_current_user_id();
    $friend_id = intval($request->get_param('friend_id'));

    if ($current_user_id === $friend_id) {
        return new WP_Error('Invalid', 'You cannot send a friend request to yourself.', ['status' => 400]);
    }

    $table_name = $wpdb->prefix . 'friendships';

    //Check if a request already exists
    $existing_request = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table_name WHERE (user_id = %d AND friend_id = %d) OR (user_id = %d AND friend_id = %d)",
        $current_user_id, $friend_id, $friend_id, $current_user_id
    ));

    if ($existing_request) {
        return new WP_Error('duplicate_request', 'A friend request already exists.', ['status' => 400]);
    }

    // Insert the friend request
    $result = $wpdb->insert(
        $table_name,
        [
            'user_id'   => $current_user_id,
            'friend_id' => $friend_id,
            'status'    => 'pending',
        ],
        ['%d', '%d', '%s']
    );

    if ($result === false) {
        return new WP_Error('db_error', 'Failed to send friend request.', ['status' => 500]);
    }

    return rest_ensure_response(['success' => true, 'message' => 'Friend request sent.']);
}

//Create custom REST API endpoints for accepting, rejecting and unfriending
function register_friend_routes() {
    //Accept friend request route
    register_rest_route('mytheme/v1', '/accept-friend-request', [
        'methods'   => 'POST',
        'callback'  =>  'accept_friend_request',
        'permission_callback' => function () { return is_user_logged_in(); }, //Only alow logged in users
    ]);

    //Reject friend request route
    register_rest_route('mytheme/v1', '/reject-friend-request', [
        'methods'   => 'DELETE',
        'callback'  =>  'reject_friend_request',
        'permission_callback' => function () { return is_user_logged_in(); }, //Only alow logged in users
    ]);

    // Register the unfriend route
    register_rest_route('mytheme/v1', '/unfriend', [
        'methods'   => 'DELETE',
        'callback'  => 'unfriend_user',
        'permission_callback' => function () { return is_user_logged_in(); },
    ]);
}
add_action('rest_api_init', 'register_friend_routes');

//Accept friend request function
function accept_friend_request($request) {
    global $wpdb;

    $friend_request_id = sanitize_text_field($request->get_param('friendRequestId'));
    $current_user_id = get_current_user_id();

    //Update the friendship status to accepted
    $table_name = $wpdb->prefix . 'friendships';
    $result = $wpdb->update(
        $table_name,
        ['status' => 'accepted'],
        ['id'     => $friend_request_id, 'friend_id' => $current_user_id],
        ['%s'],
        ['%d', '%d']
    );

    if ($result === false) {
        return new WP_Error('db_error', 'Failed to accept friend request.', ['status' => 500]);
    }

    return rest_ensure_response(['success' => true, 'message' => 'Friend request accepted.']);
}

//Reject friend request function
function reject_friend_request($request) {
    global $wpdb;

    $friend_request_id = sanitize_text_field($request->get_param('friendRequestId'));
    $current_user_id = get_current_user_id();

    //Delete the friend request from the database.
    $table_name = $wpdb->prefix . 'friendships';
    $result = $wpdb->delete(
        $table_name,
        ['id' => $friend_request_id, 'friend_id' => $current_user_id],
        ['%d', '%d']
    );

    if ($result === false) {
        return new WP_Error('db_error', 'Failed to reject friend request.', ['status' => 500]);
    }

    return rest_ensure_response(['success' => true, 'message' => 'Friend request rejected']);
}

//Unfriend someone you hate now
function unfriend_user($request) {
    global $wpdb;
    
    $current_user_id = get_current_user_id();
    $friend_id = intval($request->get_param('friend_id'));
    $table_name = $wpdb->prefix . 'friendships';

    // Delete friendship in either direction, only if accepted
    $result = $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM $table_name WHERE ((user_id = %d AND friend_id = %d) OR (user_id = %d AND friend_id = %d)) AND status = 'accepted'",
            $current_user_id, $friend_id, $friend_id, $current_user_id
        )
    );

    if ($result === false) {
        return new WP_Error('db_error', 'Failed to unfriend.', ['status' => 500]);
    }

    return rest_ensure_response(['success' => true, 'message' => 'Unfriended successfully.']);
}