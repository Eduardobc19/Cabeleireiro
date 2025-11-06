<?php
// === Shortcode para exibir o template loja.php ===
function loja_shortcode() {
    ob_start(); // Inicia buffer de saída

    $template_path = get_stylesheet_directory() . '/loja.php';

    if (file_exists($template_path)) {
        include $template_path;
    } else {
        echo '<p style="color:red;">Erro: loja.php não encontrado no tema.</p>';
    }

    return ob_get_clean(); // Retorna o conteúdo do buffer
}
add_shortcode('loja', 'loja_shortcode');

function shortcode_login_form() {
    ob_start();
    include get_stylesheet_directory() . '/login.php'; // crie esse arquivo
    return ob_get_clean();
}
add_shortcode('login', 'shortcode_login_form');

// Shortcode para página de registro
function shortcode_register_form() {
    ob_start();
    include get_stylesheet_directory() . '/register.php'; // crie esse arquivo
    return ob_get_clean();
}
add_shortcode('register', 'shortcode_register_form');
// Shortcode para incluir o agendamento
function agendamento_shortcode() {
    ob_start();
    include get_stylesheet_directory() . '/agendamento.php';
    return ob_get_clean();
}
add_shortcode('agendamento', 'agendamento_shortcode');

// Enfileira apenas o CSS do FullCalendar quando shortcode estiver presente
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

// Enfileira o JS do FullCalendar + agendamento apenas quando shortcode estiver presente
function agendamento_enqueue_scripts() {
    if (is_singular() && has_shortcode(get_post()->post_content, 'agendamento')) {
        // FullCalendar
        wp_enqueue_script(
            'fullcalendar-js',
            'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.js',
            [],
            null,
            true
        );

        // JS do agendamento (externo)
        wp_enqueue_script(
            'agendamento-js',
            get_stylesheet_directory_uri() . '/agendamento.js',
            ['fullcalendar-js'], // garante que o FullCalendar carregue antes
            null,
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'agendamento_enqueue_scripts');

// --- TGMPA Plugins ---
require_once get_template_directory() . '/inc/tgm/class-tgm-plugin-activation.php';
add_action('tgmpa_register', 'ruby_ecommerce_register_required_plugins', 0);
function ruby_ecommerce_register_required_plugins() {
    $plugins = [
        ['name'=>'Superb Addons','slug'=>'superb-blocks','required'=>false],
        ['name'=>'WooCommerce','slug'=>'woocommerce','required'=>false]
    ];

    $config = [
        'id'=>'ruby-ecommerce',
        'default_path'=>'',
        'menu'=>'tgmpa-install-plugins',
        'has_notices'=>true,
        'dismissable'=>true,
        'dismiss_msg'=>'',
        'is_automatic'=>true,
        'message'=>'',
    ];

    tgmpa($plugins,$config);
}

// Enfileira estilos do tema
function ruby_ecommerce_pattern_styles() {
    wp_enqueue_style(
        'ruby-ecommerce-patterns',
        get_template_directory_uri() . '/assets/css/patterns.css',
        [],
        filemtime(get_template_directory() . '/assets/css/patterns.css')
    );
    if (is_admin()) {
        global $pagenow;
        if ('site-editor.php' === $pagenow) return;
        wp_enqueue_style(
            'ruby-ecommerce-editor',
            get_template_directory_uri() . '/assets/css/editor.css',
            [],
            filemtime(get_template_directory() . '/assets/css/editor.css')
        );
    }
}
add_action('enqueue_block_assets', 'ruby_ecommerce_pattern_styles');

add_theme_support('wp-block-styles');

// Remove padrões do WordPress
add_action('init', function() { remove_theme_support('core-block-patterns'); });

// Categorias de padrões
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

// Inicialização do Theme Entry Point
require_once trailingslashit(get_template_directory()) . 'inc/vendor/autoload.php';
use SuperbThemesThemeInformationContent\ThemeEntryPoint;
add_action("init", function() {
    ThemeEntryPoint::init([
        'type'=>'block',
        'theme_url'=>'https://superbthemes.com/ruby-ecommerce/',
        'demo_url'=>'https://superbthemes.com/demo/ruby-ecommerce/',
        'features'=>[
            ['title'=>"Theme Designer",'icon'=>"lego-duotone.webp",'description'=>"Choose from over 300 designs for footers, headers, landing pages & all other theme parts."],
            ['title'=>"Editor Enhancements",'icon'=>"1-1.png",'description'=>"Enhanced editor experience, grid systems, improved block control and much more."],
            ['title'=>"Custom CSS",'icon'=>"2-1.png",'description'=>"Add custom CSS with syntax highlight, custom display settings, and minified output."],
            ['title'=>"Animations",'icon'=>"wave-triangle-duotone.webp",'description'=>"Animate any element on your website with one click. Choose from over 50+ animations."],
            ['title'=>"WooCommerce Integration",'icon'=>"shopping-cart-duotone.webp",'description'=>"Choose from over 100 unique WooCommerce designs for your e-commerce store."],
            ['title'=>"Responsive Controls",'icon'=>"arrows-out-line-horizontal-duotone.webp",'description'=>"Make any theme mobile-friendly with SuperbThemes responsive controls."]
        ]
    ]);
});
