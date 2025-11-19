<?php
/* Template Name: Registro */
session_start();
if ( ! defined( 'ABSPATH' ) ) exit;

$success = false;
$errors = [];

if (isset($_POST['register_user'])) {
    global $wpdb;

    $username   = sanitize_user($_POST['username']);
    $email      = sanitize_email($_POST['email']);
    $password   = $_POST['password'];
    $password2  = $_POST['password2'];
    $first_name = sanitize_text_field($_POST['first_name']);
    $last_name  = sanitize_text_field($_POST['last_name']);

    // Validações básicas
    if (empty($username) || empty($email) || empty($password) || empty($password2) || empty($first_name) || empty($last_name)) {
        $errors[] = "Todos os campos são obrigatórios.";
    }

    if (username_exists($username)) {
        $errors[] = "Este nome de usuário já existe.";
    }

    if (email_exists($email)) {
        $errors[] = "Este email já está cadastrado.";
    }

    if ($password !== $password2) {
        $errors[] = "As senhas não coincidem.";
    }

    if (!preg_match('/^(?=.*[!@#$%^&*(),.?":{}|<>]).{8,}$/', $password)) {
        $errors[] = "A senha deve ter pelo menos 8 caracteres e conter pelo menos 1 símbolo.";
    }

    if (empty($errors)) {
        // Cria o utilizador WordPress
        $user_id = wp_create_user($username, $password, $email);

        if (!is_wp_error($user_id)) {
            update_user_meta($user_id, 'first_name', $first_name);
            update_user_meta($user_id, 'last_name', $last_name);

            // Atualiza também a tabela custom DBTinausers
            $wpdb->update(
                'DBTinausers',
                [
                    'first_name' => $first_name,
                    'last_name'  => $last_name
                ],
                [ 'ID' => $user_id ]
            );

            $success = true;
            echo '<script>setTimeout(()=>{window.location.href="/my-account";},2000);</script>';
        } else {
            $errors[] = $user_id->get_error_message();
        }
    }
}
?>

<style>
body { 
    background:#f9f3ff;
    font-family:system-ui, sans-serif; 
    margin:0; 
    padding:0; 
    color:#000; 
}

.entry-title { display:none !important; }

.container {
    max-width:400px;
    margin:80px auto 80px auto;
    background:#fff;
    padding:40px 30px;
    border-radius:20px;
    box-shadow:0 10px 40px rgba(0,0,0,0.12);
    transition: all 0.3s ease;
    box-sizing:border-box;
}
.container:hover { box-shadow:0 12px 50px rgba(0,0,0,0.15); }

h1 { 
    text-align:center; 
    margin-bottom:30px; 
    font-weight:700; 
    color:#333; 
}

form input, form button {
    width:100%;
    padding:12px 15px;
    margin-bottom:10px;
    border:1px solid #ddd;
    border-radius:12px;
    transition:0.3s;
    font-size:1rem;
    box-sizing:border-box;
}

form input:focus {
    border-color:#000;
    outline:none;
    box-shadow:0 0 8px rgba(0,0,0,0.08);
}

form button {
    background:#000;
    color:#fff;
    border:none;
    border-radius:12px;
    font-weight:600;
    cursor:pointer;
}
form button:hover { background:#444; }

p a { 
    color:#000; 
    text-decoration:underline; 
}

.alert {
    padding:15px 20px;
    border-radius:12px;
    margin-bottom:20px;
    text-align:center;
    font-weight:500;
}
.alert-success { background:#D4EDDA; color:#155724; }
.alert-error { background:#F8D7DA; color:#721C24; }

.password-hint { font-size:0.85rem; color:#555; margin-bottom:15px; }
.password-match { font-size:0.85rem; margin-bottom:15px; }
.password-match.valid { color:green; }
.password-match.invalid { color:red; }
</style>

<div class="container">

    <?php
    if($success){
        echo '<div class="alert alert-success">Registro realizado com sucesso! Redirecionando...</div>';
    }

    if(!empty($errors)){
        foreach($errors as $err){
            echo '<div class="alert alert-error">'.$err.'</div>';
        }
    }
    ?>

    <form method="post">
        <input type="text" name="first_name" placeholder="Nome" required>
        <input type="text" name="last_name" placeholder="Apelido" required>
        <input type="text" name="username" placeholder="Nome de usuário" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Senha" required>

        <div class="password-hint">A senha deve ter pelo menos 8 caracteres e 1 símbolo.</div>

        <input type="password" name="password2" placeholder="Confirme a senha" required id="password2">

        <div class="password-match" id="passwordMatchMessage"></div>

        <button type="submit" name="register_user">Registrar</button>
    </form>

    <p style="text-align:center;">Já tem uma conta? <a href="/my-account">Fazer login</a></p>
</div>

<script>
const password = document.querySelector('input[name="password"]');
const password2 = document.getElementById('password2');
const passwordMatchMessage = document.getElementById('passwordMatchMessage');

password2.addEventListener('input', function() {
    if(password2.value === password.value && password2.value.length > 0){
        password2.style.borderColor = 'green';
        passwordMatchMessage.textContent = 'As senhas coincidem';
        passwordMatchMessage.className = 'password-match valid';
    } else {
        password2.style.borderColor = 'red';
        passwordMatchMessage.textContent = 'As senhas não coincidem';
        passwordMatchMessage.className = 'password-match invalid';
    }
});
</script>

<?php wp_footer(); ?>
</body>
</html>
