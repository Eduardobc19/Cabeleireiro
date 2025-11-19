<?php
/* Template Name: Loja WooCommerce */
if (!defined('ABSPATH')) exit;

// Categoria selecionada
$categoriaSelecionada = isset($_GET['categoria']) ? sanitize_text_field($_GET['categoria']) : 'all';

// Ordenação
$ordenar = isset($_GET['ordenar']) ? sanitize_text_field($_GET['ordenar']) : 'nome_asc';
switch ($ordenar) {
    case 'nome_desc': $orderby = 'title'; $order = 'DESC'; break;
    case 'preco_asc': $orderby = 'meta_value_num'; $order = 'ASC'; $meta_key = '_price'; break;
    case 'preco_desc': $orderby = 'meta_value_num'; $order = 'DESC'; $meta_key = '_price'; break;
    default: $orderby = 'title'; $order = 'ASC';
}

// Busca
$busca = isset($_GET['busca']) ? sanitize_text_field($_GET['busca']) : '';

// Paginação
$paged = max(1, get_query_var('paged', 1));

// Query WooCommerce
$args = [
    'post_type' => 'product',
    'posts_per_page' => 15,
    'paged' => $paged,
    'orderby' => $orderby,
    'order' => $order,
    's' => $busca,
];
if (isset($meta_key)) $args['meta_key'] = $meta_key;

if ($categoriaSelecionada !== 'all') {
    $args['tax_query'] = [[
        'taxonomy' => 'product_cat',
        'field'    => 'slug',
        'terms'    => $categoriaSelecionada,
    ]];
}

$query = new WP_Query($args);
$categorias = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => true]);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>

<section class="lojaContainer">
    <div class="lojaLayout">

        <!-- Sidebar -->
        <aside class="sidebarCategorias">
            <h3>Categorias</h3>

            <a href="?categoria=all"
               class="categoryItem <?= $categoriaSelecionada=='all'?'active':'' ?>">Todos</a>

            <?php foreach ($categorias as $cat): ?>
                <a href="?categoria=<?= esc_attr($cat->slug) ?>"
                   class="categoryItem <?= $categoriaSelecionada==$cat->slug?'active':'' ?>">
                    <?= esc_html($cat->name) ?>
                </a>
            <?php endforeach; ?>
        </aside>

        <!-- Área dos produtos -->
        <div>

            <div style="margin-bottom:25px;">
                <form method="get">
                    <input type="text" name="busca" value="<?= esc_attr($busca) ?>" placeholder="Pesquisar...">
                    <button type="submit">Buscar</button>
                </form>
            </div>

            <div class="produtosGrid">
                <?php if($query->have_posts()): ?>
                    <?php while($query->have_posts()): $query->the_post(); global $product; ?>
                        <div class="productBox">
                            <img src="<?= wp_get_attachment_url($product->get_image_id()) ?>">
                            <h3><?= esc_html($product->get_name()); ?></h3>
                            <p><?= $product->get_price_html(); ?></p>

                            <div class="productButtons">
                                <button class="btn addToCartBtn" data-id="<?= $product->get_id(); ?>">Adicionar ao Carrinho</button>
                                <a class="btn viewProductBtn" href="<?= get_permalink($product->get_id()); ?>">Ver Produto</a>
                            </div>

                        </div>
                    <?php endwhile; wp_reset_postdata(); ?>
                <?php else: ?>
                    <p>Nenhum produto encontrado.</p>
                <?php endif; ?>
            </div>

            <div class="pagination">
                <?= paginate_links([
                    'total'=>$query->max_num_pages,
                    'current'=>$paged,
                    'prev_text'=>'« Anterior',
                    'next_text'=>'Próxima »'
                ]); ?>
            </div>

        </div>
    </div>
</section>


<?php wp_footer(); ?>
</body>
</html>
