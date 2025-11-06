<?php
/* Template Name: Login */
session_start();
if ( ! defined( 'ABSPATH' ) ) exit;

if(isset($_POST['login_user'])){
    $username = sanitize_user($_POST['username']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);

    $creds = array(
        'user_login'    => $username,
        'user_password' => $password,
        'remember'      => $remember
    );

    $user = wp_signon($creds, false);

    if(is_wp_error($user)){
        $error_message = $user->get_error_message();
    } else {
        // Guardar dados do usuário na sessão
        $_SESSION['user_email'] = $user->user_email;
        $_SESSION['user_nome']  = $user->display_name;

        // Redirecionar para a página de agendamento
        wp_redirect('/agendamento'); 
        exit;
    }
}
?>

<style>
body { 
    background:#fff; /* fundo branco */
    font-family:system-ui, sans-serif; 
    margin:0; 
    padding:0; 
    color:#000; 
}
.site-branding, .entry-title, header .site-title, header .site-description,
.site-footer, .site-info, footer, footer * { display:none !important; }

.header-login {
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:30px 50px;
    max-width:1200px;
    margin:0 auto;
}
.header-login a {
    font-weight:700;
    font-size:2rem;
    text-decoration:none;
    color:#000;
}
.header-login nav a {
    margin-left:20px;
    font-size:1rem;
    font-weight:500;
    text-decoration:none;
    color:#000;
    transition:0.3s;
}
.header-login nav a:hover { text-decoration:underline; }

.container {
    max-width:400px;
    margin:150px auto 100px auto;
    background:#fff;
    padding:40px 30px;
    border-radius:20px;
    box-shadow:0 10px 40px rgba(0,0,0,0.12);
    transition: all 0.3s ease;
}
.container:hover { box-shadow:0 12px 50px rgba(0,0,0,0.15); }
h1 { text-align:center; margin-bottom:30px; font-weight:700; color:#333; letter-spacing:1px; }
form input {
    width:100%;
    padding:12px 15px;
    margin-bottom:20px;
    border:1px solid #ddd;
    border-radius:12px;
    transition:0.3s;
    font-size:1rem;
}
form input:focus {
    border-color:#000;
    outline:none;
    box-shadow:0 0 8px rgba(0,0,0,0.08);
}
form button {
    width:100%;
    padding:12px;
    background:#000;
    color:#fff;
    border:none;
    border-radius:12px;
    font-weight:600;
    font-size:1rem;
    cursor:pointer;
    transition:0.3s;
}
form button:hover { background:#444; }
.checkbox-container { display:flex; align-items:center; margin-bottom:20px; font-size:0.95rem; color:#555; }
.checkbox-container input { margin-right:10px; width:16px; height:16px; cursor:pointer; }
p.message { color:green; text-align:center; margin-bottom:15px; }
p.error { color:red; text-align:center; margin-bottom:15px; }
p a { color:#000; text-decoration:underline; }
</style>



<div class="container">


    <?php if(isset($error_message)){
        echo '<p class="error">'.$error_message.'</p>';
    } ?>

    <form method="post">
        <input type="text" name="username" placeholder="Nome de usuário" required>
        <input type="password" name="password" placeholder="Senha" required>
        <div class="checkbox-container">
            <input type="checkbox" name="remember" id="remember">
            <label for="remember">Lembrar-me</label>
        </div>
        <button type="submit" name="login_user">Login</button>
    </form>
    <p>Não tem uma conta? <a href="/register">Registrar</a></p>
</div>
