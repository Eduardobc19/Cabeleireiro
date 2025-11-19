<?php
/* ===========================================================
   LOJA – SHORTCODE
=========================================================== */
function loja_shortcode() {
    ob_start();

    $template = get_stylesheet_directory() . '/loja/loja.php';

    if (file_exists($template)) {
        include $template;
    } else {
        echo '<p style="color:red;">Erro: loja.php não encontrado na pasta /loja/.</p>';
    }

    return ob_get_clean();
}
add_shortcode('loja', 'loja_shortcode');


/* ===========================================================
   LOJA – ASSETS (JS + CSS)
=========================================================== */
function loja_enqueue_assets() {

    // Apenas carrega scripts quando a página tiver o shortcode [loja]
    if (is_singular() && has_shortcode(get_post()->post_content, 'loja')) {

        /* CSS */
        wp_enqueue_style(
            'loja-css',
            get_stylesheet_directory_uri() . '/loja/loja.css',
            [],
            filemtime(get_stylesheet_directory() . '/loja/loja.css')
        );

        /* JS */
        wp_enqueue_script(
            'loja-js',
            get_stylesheet_directory_uri() . '/loja/loja.js',
            ['jquery'],
            filemtime(get_stylesheet_directory() . '/loja/loja.js'),
            true
        );

        /* Variáveis para AJAX */
        wp_localize_script('loja-js', 'LojaVars', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('loja_nonce')
        ]);
    }
}
add_action('wp_enqueue_scripts', 'loja_enqueue_assets');






// ===========================================================
//  SHORTCODE AGENDAMENTO
// ===========================================================
function agendamento_shortcode() {
    ob_start();
    include get_stylesheet_directory() . '/agendamento/agendamento.php';
    return ob_get_clean();
}
add_shortcode('agendamento', 'agendamento_shortcode');


// ===========================================================
//  FULLCALENDAR – CSS + JS
// ===========================================================
function agendamento_enqueue_styles() {
    if (is_singular() && has_shortcode(get_post()->post_content, 'agendamento')) {
        wp_enqueue_style('fullcalendar-css', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css');
    }
}
add_action('wp_enqueue_scripts', 'agendamento_enqueue_styles');

function agendamento_enqueue_scripts() {
    if (is_singular() && has_shortcode(get_post()->post_content, 'agendamento')) {

        wp_enqueue_script('fullcalendar-js', 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.js', [], null, true);

        // Teu CSS do agendamento
        wp_enqueue_style(
            'agendamento-css',
            get_stylesheet_directory_uri() . '/agendamento/agendamento.css',
            [],
            filemtime(get_stylesheet_directory() . '/agendamento/agendamento.css')
        );
        wp_enqueue_script(
            'agendamento-js',
            get_stylesheet_directory_uri() . '/agendamento/agendamento.js',
            ['fullcalendar-js'],
            filemtime(get_stylesheet_directory() . '/agendamento/agendamento.js'),
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'agendamento_enqueue_scripts');


// ===========================================================
//  TGMPA (plugins recomendados)
// ===========================================================
require_once get_template_directory() . '/inc/tgm/class-tgm-plugin-activation.php';
add_action('tgmpa_register', 'ruby_ecommerce_register_required_plugins');

function ruby_ecommerce_register_required_plugins() {
    $plugins = [
        ['name'=>'Superb Addons', 'slug'=>'superb-blocks', 'required'=>false],
        ['name'=>'WooCommerce', 'slug'=>'woocommerce', 'required'=>false],
    ];

    $config = [
        'id'=>'ruby-ecommerce',
        'menu'=>'tgmpa-install-plugins',
        'has_notices'=>true,
        'dismissable'=>true,
        'is_automatic'=>true,
    ];

    tgmpa($plugins, $config);
}


// ===========================================================
//  STYLES NATIVOS DO TEMA
// ===========================================================
function ruby_ecommerce_pattern_styles() {

    wp_enqueue_style(
        'ruby-ecommerce-patterns',
        get_template_directory_uri() . '/assets/css/patterns.css',
        [],
        filemtime(get_template_directory() . '/assets/css/patterns.css')
    );

    if (is_admin()) {
        global $pagenow;
        if ($pagenow !== 'site-editor.php') {
            wp_enqueue_style(
                'ruby-ecommerce-editor',
                get_template_directory_uri() . '/assets/css/editor.css',
                [],
                filemtime(get_template_directory() . '/assets/css/editor.css')
            );
        }
    }
}
add_action('enqueue_block_assets', 'ruby_ecommerce_pattern_styles');

add_theme_support('wp-block-styles');
add_action('init', function() { remove_theme_support('core-block-patterns'); });


// ===========================================================
//  CATEGORIAS DE PADRÕES
// ===========================================================
function ruby_ecommerce_register_block_pattern_categories() {
    $categories = [
        'header'=>'Header',
        'call_to_action'=>'Call To Action',
        'content'=>'Content',
        'teams'=>'Teams',
        'banners'=>'Banners',
        'contact'=>'Contact',
        'layouts'=>'Layouts',
        'testimonials'=>'Testimonials'
    ];

    foreach ($categories as $slug=>$label) {
        register_block_pattern_category($slug, [
            'label'=>__($label, 'ruby-ecommerce'),
            'description'=>$label . ' patterns'
        ]);
    }
}
add_action('init','ruby_ecommerce_register_block_pattern_categories');

// ===========================================================
//  LOJA — CSS + JS
// ===========================================================
function loja_assets() {

    if (is_singular() && has_shortcode(get_post()->post_content, 'loja')) {

        wp_enqueue_style(
            'loja-css',
            get_stylesheet_directory_uri() . '/loja/loja.css',
            [],
            filemtime(get_stylesheet_directory() . '/loja/loja.css')
        );

        wp_enqueue_script(
            'loja-js',
            get_stylesheet_directory_uri() . '/loja/loja.js',
            ['jquery'],
            filemtime(get_stylesheet_directory() . '/loja/loja.js'),
            true
        );

        wp_localize_script('loja-js', 'LojaVars', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('loja_nonce')
        ]);
    }
}
add_action('wp_enqueue_scripts', 'loja_assets');


// ===========================================================
//  LOGIN + REGISTER UNIFICADO (tua pasta nova)
// ===========================================================
function login_register_assets() {

    if (is_page('conta')) {

        wp_enqueue_style(
            'login-register-css',
            get_stylesheet_directory_uri() . '/login-register/login-register.css',
            [],
            filemtime(get_stylesheet_directory() . '/login-register/login-register.css')
        );

        wp_enqueue_script(
            'login-register-js',
            get_stylesheet_directory_uri() . '/login-register/login-register.js',
            ['jquery'],
            filemtime(get_stylesheet_directory() . '/login-register/login-register.js'),
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'login_register_assets');


// SHORTCODE
function shortcode_login_register() {
    ob_start();
    include get_stylesheet_directory() . '/login-register/login-register.php';
    return ob_get_clean();
}
add_shortcode('login_register', 'shortcode_login_register');


// ===========================================================
//  REGISTO — WooCommerce usa o username personalizado
// ===========================================================
add_filter('woocommerce_new_customer_data', function($data) {

    if (!empty($_POST['username'])) {
        $data['user_login'] = sanitize_user($_POST['username']);
    }

    return $data;
});

add_filter('woocommerce_registration_generate_username', '__return_false');


// ===========================================================
//  REDIRECIONAMENTOS — pedido por ti
// ===========================================================

// 1 — /conta → se logado manda para My Account
add_action('template_redirect', function() {
    if (is_page('conta') && is_user_logged_in()) {
        wp_redirect(site_url('/my-account/'));
        exit;
    }
});

// 2 — LOGOUT → volta para /conta
add_action('wp_logout', function() {
    wp_redirect(site_url('/conta/'));
    exit;
});

// 3 — my-account sem login → vai para /conta
add_action('template_redirect', function() {
    if (is_account_page() && !is_user_logged_in()) {
        wp_redirect(site_url('/conta/'));
        exit;
    }
});

// Redirecionar tudo que tenta ir para wp-login.php → /conta
add_action('init', function() {

    $request_uri = $_SERVER['REQUEST_URI'];

    // Se alguém tentar acessar /wp-login.php ou passar ?action=login
    if (strpos($request_uri, 'wp-login.php') !== false || isset($_GET['action']) && $_GET['action'] === 'login') {
        wp_redirect(site_url('/conta/'));
        exit;
    }
});

// Redirecionar /login → /conta
add_action('template_redirect', function() {
    if (trim($_SERVER['REQUEST_URI'], '/') === 'login') {
        wp_redirect(site_url('/conta/'));
        exit;
    }
});

// Substituir o URL de login gerado pelo WordPress
add_filter('login_url', function($login_url, $redirect){
    return site_url('/conta/');
}, 10, 2);

// Redirecionar /register → /conta
add_action('template_redirect', function() {

    $uri = trim($_SERVER['REQUEST_URI'], '/');

    if ($uri === 'register') {
        wp_redirect(site_url('/conta/'));
        exit;
    }
});

