<?php
//If the user is logged in, redirect to the home page
if (is_user_logged_in()) {
    wp_redirect(home_url());
    exit;
}

// Handle form submission
if ($_POST) {
    $creds = [
        'user_login' => sanitize_text_field($_POST['log']),
        'user_password' => sanitize_text_field($_POST['password']),
        'remember' => isset($_POST['rememberme'])
    ];

    $user = wp_signon($creds, false);
    if (is_wp_error($user)) {
        $error = $user->get_error_message();
    } else {
        // Redirect to the home page after successful login
        wp_redirect(home_url());
        exit;
    }
}

?>

<?php get_header(); ?>

<main>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <h1 class="h1 text-center mt-5 mb-3">Login</h1>

                <div class="card shadow">
                    <div class="card-body">
                        <?php if(!empty($error)): ?>
                            <div class="alert alert-danger">
                                <?php echo wp_strip_all_tags($error); ?>
                            </div>
                        <?php endif; ?>
                        <form method="post">
                            <div class="mb-3">
                                <label for="user_login" class="form-label">Username or Email Address</label>
                                <input type="text" name="log" id="user_login" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" name="password" id="password" class="form-control" autocomplete="current-password" required>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" name="rememberme" id="rememberme" class="form-check-input">
                                <label for="rememberme" class="form-check-label">Remember Me</label>
                            </div>
                            <div class="mb-3">
                                <button type="submit" name="login" class="btn btn-primary w-100">Login</button>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer text-center">
                        <p class="mb-0">
                            <a href="<?php echo esc_url(home_url('/register')); ?>">Register</a> |
                            <a href="<?php echo esc_url(home_url('/password-reset')); ?>">Reset Password</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<?php get_footer(); ?>