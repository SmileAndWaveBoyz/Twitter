<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php wp_title(); ?></title>
    <?php wp_head(); ?>
</head>
<body>

<?php
    $friend_requests_count = 0;
    $friend_requests_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}friendships WHERE friend_id = %d AND status = 'pending'",
        get_current_user_id()
    ));
?>

<?php if(is_user_logged_in()): ?>
    <header>
        <nav class="navigationBar">
            <?php
                wp_nav_menu([
                    'theme_location' => 'navigation-bar',
                    'container' => 'ul',
                    'menu_class' => 'navigationBar__container',
                    'fallback_cb' => false,
                ]);
            ?>

            <ul class="navigationBar__container">
                <li class="navigationBar__listItem search">
                    <input type="text" class="navigationBar__search" placeholder="Search...">
                    <svg class="navigationBar__search-svg" viewBox="0 0 512 512"><path d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z"/></svg>
                    <div class="navigationBar__searchResults">
                        <!-- The js will display them here  -->
                    </div>
                </li>
                <li class="navigationBar__listItem friendRequests">
                    <svg class="navigationBar__friendRequests-svg" viewBox="0 0 640 512"><path d="M96 128a128 128 0 1 1 256 0A128 128 0 1 1 96 128zM0 482.3C0 383.8 79.8 304 178.3 304l91.4 0C368.2 304 448 383.8 448 482.3c0 16.4-13.3 29.7-29.7 29.7L29.7 512C13.3 512 0 498.7 0 482.3zM609.3 512l-137.8 0c5.4-9.4 8.6-20.3 8.6-32l0-8c0-60.7-27.1-115.2-69.8-151.8c2.4-.1 4.7-.2 7.1-.2l61.4 0C567.8 320 640 392.2 640 481.3c0 17-13.8 30.7-30.7 30.7zM432 256c-31 0-59-12.6-79.3-32.9C372.4 196.5 384 163.6 384 128c0-26.8-6.6-52.1-18.3-74.3C384.3 40.1 407.2 32 432 32c61.9 0 112 50.1 112 112s-50.1 112-112 112z"/></svg>
                    <?php if($friend_requests_count > 0) : ?>
                        <p class="numberOfFriendRequests"><?php echo $friend_requests_count; ?></p>
                    <?php endif; ?>
                </li>
            </ul>

            <div class="navigationBar__friendRequests">
                <div class="navigationBar__friendRequestsHeader">
                    <p class="navigationBar__arrow">◄</p>
                    <h2 class="navigationBar__friendRequestsTitle">Friend Requests</h2>
                </div>

                <ul class="navigationBar__friendRequestsList">
                    <?php
                        $friend_requests = $wpdb->get_results($wpdb->prepare(
                            "SELECT * FROM {$wpdb->prefix}friendships WHERE friend_id = %d AND status = 'pending'",
                            get_current_user_id()
                        ));
                        foreach ($friend_requests as $request) {
                            $user = get_userdata($request->user_id);
                            ?>
                                <li class="navigationBar__friendRequest">
                                    <div class="navigationBar__friendRequestInfo">
                                        <img class="navigationBar__friendRequestAvatar" src="<?php echo get_avatar_url($user->ID); ?>" alt="">
                                        <p class="navigationBar__friendRequestName"><?php echo $user->display_name; ?></p>
                                    </div>
                                    <div class="navigationBar__friendRequestActions">
                                        <button class="navigationBar__friendRequestAccept tweet__button" data-friend-request-id="<?php echo $request->id; ?>">Accept</button>
                                        <button class="navigationBar__friendRequestDecline tweet__button" data-friend-request-id="<?php echo $request->id; ?>">Decline</button>
                                    </div>
                                </li>
                            <?php
                        }
                    ?>
                </ul>
            </div>
        </nav>
    </header>
<?php endif ?>

