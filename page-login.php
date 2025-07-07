<?php 
    //If the user is already logged in, redirect to the home page
    if (is_user_logged_in()) {
        wp_redirect(site_url());
        exit;
    }

    //Handle form submission
    if ($_POST) {
        $credentials = [
            'user_login' => sanitize_text_field($_POST['user_login']),
            'user_password' => sanitize_text_field($_POST['password']),
            'remember'  => true
        ];

        $user = wp_signon($credentials, false);
        if (is_wp_error($user)) {
            $error = $user->get_error_message();
        } else{
            wp_redirect(site_url());
        }
    }

    get_header(); 
?>

<main>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <h1 class="h1 text-center mt-5 mb-3">Login</h1>
                <div class="card shadow">
                    <div class="card-body">
                        <?php if(!empty($error)): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo wp_strip_all_tags($error); ?>
                            </div>
                        <?php endif;?>
                        <form method="post">
                            <div class="mb-3">
                                <label for="user_login" class="form-label">Username or Email Address</label>
                                <input type="text" class="form-control" id="user_login" name="user_login" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Login</button>
                        </form>
                        <p class="mt-3 text-center">
                            Don't have an account? <a href="<?php echo esc_url(site_url('/register')) ?>">Register here</a>.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<?php get_footer(); ?>