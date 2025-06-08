<?php
//If the user is logged in, redirect to the home page
if (is_user_logged_in()) {
    wp_redirect(home_url());
    exit;
}

// Handle form submission
if ($_POST) {
    $userdata = [
        'user_login' => sanitize_text_field($_POST['username']),
        'user_email' => sanitize_email($_POST['email']),
        'user_pass' => sanitize_text_field($_POST['password']),
    ];

    $user_id = wp_insert_user($userdata);

    if (!is_wp_error($user_id)) {
        // Redirect to the home page after successful registration
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);
        wp_redirect(home_url());
        exit;
    } else {

        $error = $user_id->get_error_message();
    }
}
?>

<?php get_header(); ?>

<main>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <h1 class="h1 text-center mt-5 mb-3">Register</h1>

                <div class="card shadow">
                    <div class="card-body">
                        <?php if(!empty($error)): ?>
                            <div class="alert alert-danger">
                                <?php echo wp_strip_all_tags($error); ?>
                            </div>
                        <?php endif; ?>
                        <form method="post">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" name="username" id="username" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" name="email" id="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" name="password" id="password" class="form-control" autocomplete="current-password" required>
                            </div>
                            <div class="mb-3">
                                <button type="submit" name="login" class="btn btn-primary w-100">Register</button>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer text-center">
                        <p class="mb-0">
                            <a href="<?php echo esc_url(home_url('/login')); ?>">Login</a> |
                            <a href="<?php echo esc_url(wp_lostpassword_url()); ?>">Reset Password</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<?php get_footer(); ?>