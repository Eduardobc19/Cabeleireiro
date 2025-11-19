<?php
/* Template Name: Login Register */
defined('ABSPATH') || exit;

if (is_user_logged_in()) {
    wc_get_template('myaccount/my-account.php');
    return;
}

wc_clear_notices();

// =========================
// PROCESSO LOGIN
// =========================
if (isset($_POST['do_login'])) {

    $creds = [
        'user_login'    => sanitize_text_field($_POST['username']),
        'user_password' => $_POST['password'],
        'remember'      => true,
    ];

    $user = wp_signon($creds);

    if (is_wp_error($user)) {
        wc_add_notice('Credenciais inválidas.', 'error');
    } else {
        wp_redirect(home_url('/my-account'));
        exit;
    }
}

// =========================
// PROCESSO REGISTO
// =========================
if (isset($_POST['do_register'])) {

    $first_name = sanitize_text_field($_POST['first_name']);
    $last_name  = sanitize_text_field($_POST['last_name']);
    $username   = sanitize_user($_POST['username']);
    $email      = sanitize_email($_POST['email']);
    $password   = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($first_name) || empty($last_name) || empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        wc_add_notice("Todos os campos são obrigatórios.", "error");

    } elseif ($password !== $confirm_password) {
        wc_add_notice("As passwords não coincidem.", "error");

    } elseif (username_exists($username)) {
        wc_add_notice("O nome de utilizador já existe.", "error");

    } elseif (email_exists($email)) {
        wc_add_notice("O email já está registado.", "error");

    } else {

        $user_id = wp_create_user($username, $password, $email);

        if (!is_wp_error($user_id)) {

            update_user_meta($user_id, 'first_name', $first_name);
            update_user_meta($user_id, 'last_name', $last_name);

            update_user_meta($user_id, 'billing_first_name', $first_name);
            update_user_meta($user_id, 'billing_last_name', $last_name);

            global $wpdb;
            $wpdb->update(
                'DBTinausers',
                [
                    'first_name' => $first_name,
                    'last_name'  => $last_name
                ],
                [ 'ID' => $user_id ]
            );

            $user = new WP_User($user_id);
            $user->set_role('customer');

            wc_add_notice("Conta criada com sucesso! Já pode iniciar sessão.", "success");

        } else {
            wc_add_notice($user_id->get_error_message(), "error");
        }
    }
}

wp_head();
?>

<link rel="stylesheet" href="<?php echo get_stylesheet_directory_uri(); ?>/login-register/login-register.css">

<div class="page-wrapper">

    <div class="container" id="main-container">

        <div class="notice-area">
            <?php wc_print_notices(); ?>
        </div>

        <div class="forms">


        <!-- LOGIN -->
            <form method="post" id="login-form" class="form-panel active-panel">

                <h2>Login</h2>

                <input type="text" name="username" placeholder="Email ou Utilizador" required>
                <input type="password" name="password" placeholder="Password" required>

                <input type="hidden" name="do_login" value="1">

                <button type="submit">Entrar</button>
            </form>

            <!-- REGISTO -->
            <form method="post" id="register-form" class="form-panel hidden-panel mobile-hidden">

                <h2>Registar</h2>

                <input type="text" name="first_name" placeholder="Nome" required>
                <input type="text" name="last_name" placeholder="Apelido" required>
                <input type="text" name="username" placeholder="Utilizador" required>
                <input type="email" name="email" placeholder="Email" required>

                <input type="password" name="password" placeholder="Password" required>
                <input type="password" name="confirm_password" placeholder="Confirmar Password" required>

                <input type="hidden" name="do_register" value="1">

                <button type="submit">Criar Conta</button>
            </form>

        </div>

        <div class="switch">
            <h3 id="switch-text">Ainda não tens conta?</h3>
            <button id="switch-btn">Registar</button>
        </div>

    </div>

</div>

<script src="<?php echo get_stylesheet_directory_uri(); ?>/login-register/login-register.js"></script>

<?php wp_footer(); ?>
