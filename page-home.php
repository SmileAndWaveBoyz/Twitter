<?php
// Server code to add new tweet if javascript is disabled.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['tweet__input']) && is_user_logged_in()) {
    $tweet_content = sanitize_textarea_field($_POST['tweet__input']);

    $new_tweet = wp_insert_post([
        'post_title'    => wp_trim_words($tweet_content, 10, '...'),
        'post_content'  => $tweet_content,
        'post_status'   => 'publish',
        'post_type'     => 'tweet',
        'post_author'   => get_current_user_id(),
    ]);

    if (is_wp_error($new_tweet)) {
        echo '<p class="text-danger">There was an error posting your tweet.</p>';
    } 
}
?>

<?php get_header(); ?>
<main>
    <?php if(is_user_logged_in()): ?>
        <form class="tweet__form" method="post">
            <textarea class="tweet__input" name="tweet__input" placeholder="What's on your mind ?"></textarea>
            <input type="hidden" name="tweet__author" class="tweet__author" value="<?php echo esc_attr(wp_get_current_user()->user_login) ?>">
            <button class="tweet__postButton" name="tweet__postButton" type="submit">Post</button>
        </form>
    <?php endif; ?>

    <?php
    // Fetch the current user's friends from the database and display thier tweets.
    global $wpdb;
    $table_name = $wpdb->prefix . 'friendships';
    $current_user_id = get_current_user_id();

    $friends = $wpdb->get_col($wpdb->prepare(
        "SELECT friend_id FROM $table_name WHERE user_id = %d AND status = 'accepted'
        UNION
        SELECT user_id FROM $table_name WHERE friend_id = %d AND status = 'accepted'",
        $current_user_id, $current_user_id
    ));

    $friends[] = $current_user_id; //Include self in the feed

    $query = new WP_Query([
        'post_type'     =>  'tweet',
        'posts_per_page'=>  10,
        'orderby'       => 'date',
        'order'         => 'DESC',
        'author__in'     =>  $friends, //Only show tweets from friends
    ]);

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();// This makes template tags like the_title(), the_content(), get_the_author_meta(), etc.,
            $is_author = get_current_user_id() === get_the_author_meta('ID');

            ?>
                <div class="tweet">
                    <div class="tweet__titleContainer">
                        <p>
                            <strong><?php the_author(); ?></strong>
                            <?php echo get_the_date(); ?>
                        </p>
                        <?php if(current_user_can('administrator') || current_user_can('editor') || $is_author ): ?>
                            <div class="tweet__buttons">
                                <button class="tweet__button delete" data-tweet-id="<?php echo get_the_ID(); ?>">Delete</button>
                                <button class="tweet__button edit" data-tweet-id="<?php echo get_the_ID(); ?>">Edit</button>
                            </div>
                        <?php endif; ?>
                    </div>
                    <p class="tweet__content"><?php echo wp_strip_all_tags(get_the_content()); ?></p>
                </div>
            <?php
        }
    }
    ?>

</main>
<?php get_footer(); ?>