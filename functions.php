<?php
// === Shortcode para exibir o template loja.php ===
function loja_shortcode() {
    ob_start();

    $template_path = get_stylesheet_directory() . '/loja.php';

    if (file_exists($template_path)) {
        include $template_path;
    } else {
        echo '<p style="color:red;">Erro: loja.php não encontrado no tema.</p>';
    }

    return ob_get_clean();
}
add_shortcode('loja', 'loja_shortcode');

// === Shortcode Login ===
function shortcode_login_form() {
    ob_start();
    include get_stylesheet_directory() . '/login.php';
    return ob_get_clean();
}
add_shortcode('login', 'shortcode_login_form');

// === Shortcode Register ===
function shortcode_register_form() {
    ob_start();
    include get_stylesheet_directory() . '/register.php';
    return ob_get_clean();
}
add_shortcode('register', 'shortcode_register_form');

// === Shortcode Agendamento ===
function agendamento_shortcode() {
    ob_start();
    include get_stylesheet_directory() . '/agendamento.php';
    return ob_get_clean();
}
add_shortcode('agendamento', 'agendamento_shortcode');

// === FullCalendar Styles ===
function agendamento_enqueue_styles() {
    if (is_singular() && has_shortcode(get_post()->post_content, 'agendamento')) {
        wp_enqueue_style(
            'fullcalendar-css',
            'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css',
            [],
            null
        );
    }
}
add_action('wp_enqueue_scripts', 'agendamento_enqueue_styles');

// === FullCalendar Scripts ===
function agendamento_enqueue_scripts() {
    if (is_singular() && has_shortcode(get_post()->post_content, 'agendamento')) {

        wp_enqueue_script(
            'fullcalendar-js',
            'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.js',
            [],
            null,
            true
        );

        wp_enqueue_script(
            'agendamento-js',
            get_stylesheet_directory_uri() . '/agendamento.js',
            ['fullcalendar-js'],
            null,
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'agendamento_enqueue_scripts');

// === TGMPA ===
require_once get_template_directory() . '/inc/tgm/class-tgm-plugin-activation.php';
add_action('tgmpa_register', 'ruby_ecommerce_register_required_plugins', 0);

function ruby_ecommerce_register_required_plugins() {
    $plugins = [
        ['name'=>'Superb Addons','slug'=>'superb-blocks','required'=>false],
        ['name'=>'WooCommerce','slug'=>'woocommerce','required'=>false]
    ];

    $config = [
        'id'=>'ruby-ecommerce',
        'menu'=>'tgmpa-install-plugins',
        'has_notices'=>true,
        'dismissable'=>true,
        'is_automatic'=>true,
    ];

    tgmpa($plugins,$config);
}

// === Styles do Tema ===
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

// === Categorias de padrões ===
function ruby_ecommerce_register_block_pattern_categories() {

    $categories = [
        'header'=>'Header',
        'call_to_action'=>'Call To Action',
        'content'=>'Content',
        'teams'=>'Teams',
        'banners'=>'Banners',
        'contact'=>'Contact',
        'layouts'=>'Layouts',
        'testimonials'=>'Testimonial'
    ];

    foreach($categories as $slug=>$label) {
        register_block_pattern_category($slug, [
            'label'=>__($label,'ruby-ecommerce'),
            'description'=>__($label.' patterns','ruby-ecommerce')
        ]);
    }
}
add_action('init','ruby_ecommerce_register_block_pattern_categories');

// === Theme Entry Point ===
require_once trailingslashit(get_template_directory()) . 'inc/vendor/autoload.php';
use SuperbThemesThemeInformationContent\ThemeEntryPoint;

add_action("init", function() {
    ThemeEntryPoint::init([
        'type'=>'block',
        'theme_url'=>'https://superbthemes.com/ruby-ecommerce/',
        'demo_url'=>'https://superbthemes.com/demo/ruby-ecommerce/',
        'features'=>[]
    ]);
});

// === Loja: CSS + JS ===
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

// === Shortcode página Login + Register unificada ===
function shortcode_login_register() {
    ob_start();
    include get_stylesheet_directory() . '/login-register.php';
    return ob_get_clean();
}
add_shortcode('login_register', 'shortcode_login_register');

// ===========================================================
// === WooCommerce deve usar o username enviado pelo formulário
// ===========================================================
add_filter('woocommerce_new_customer_data', function($data) {

    if (isset($_POST['username']) && !empty($_POST['username'])) {
        $data['user_login'] = sanitize_user($_POST['username']);
    }

    return $data;

}, 10, 1);

// Impede WooCommerce de gerar automaticamente username
add_filter('woocommerce_registration_generate_username', '__return_false');


// ===========================================================
// === REDIRECIONAMENTO PRINCIPAL SOLICITADO POR TI
// ===========================================================

// 1️⃣ Se o user ACEDER A /conta
//    → logado = my-account
//    → não logado = mostra login/register
add_action('template_redirect', function() {

    if (is_page('conta')) {

        if (is_user_logged_in()) {
            wp_redirect(site_url('/my-account'));
            exit;
        }
    }
});

// 2️⃣ Ao fazer LOGOUT → envia para /conta
add_action('wp_logout', function() {
    wp_redirect(site_url('/conta'));
    exit;
});
// 3️⃣ Se o user tentar abrir /my-account e NÃO estiver logado → redireciona para /conta
add_action('template_redirect', function() {
    if ( is_account_page() && ! is_user_logged_in() ) {
        wp_redirect( site_url('/conta/') );
        exit;
    }
});

