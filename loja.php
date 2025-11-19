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

    <style>
        :root {
            --cor-primaria: #B89DBF;
            --cor-primaria-hover: #A88DB0;
            --cor-fundo-suave: #F8F5FA;
            --cor-borda-suave: #D8CDE0;
        }

        /* Geral */
        body {
            background:#FFF3FF;
            font-family: system-ui, sans-serif;
            color:#000;
        }

        .lojaContainer {
            max-width:1200px;
            margin:0 auto;
            padding:40px 20px;
        }

        .lojaLayout {
            display:flex;
            gap:30px;
            align-items:flex-start;
        }

        /* ======================
           SIDEBAR ESTILIZADA
           ====================== */
        .sidebarCategorias {
            flex:0 0 260px;
            background:var(--cor-fundo-suave);
            border:1px solid var(--cor-borda-suave);
            border-radius:16px;
            padding:20px;
        }

        .sidebarCategorias h3 {
            font-size:18px;
            font-weight:600;
            margin-bottom:15px;
            padding-bottom:6px;
            border-bottom:2px solid var(--cor-borda-suave);
            color:#000;
            text-transform:uppercase;
        }

        .categoryItem {
            display:block;
            padding:10px 12px;
            color:#444;
            text-decoration:none;
            border-radius:8px;
            margin-bottom:6px;
            transition:.3s;
        }

        .categoryItem:hover {
            background:#eee;
        }

        .categoryItem.active {
            background:var(--cor-primaria);
            color:#fff !important;
        }

        /* ======================
           GRID E PRODUCT CARDS
           ====================== */
        .produtosGrid {
            display:grid;
            grid-template-columns:repeat(3,1fr);
            gap:25px;
        }

        .productBox {
            border:1px solid var(--cor-borda-suave);
            border-radius:12px;
            padding:15px;
            text-align:center;
            background:var(--cor-fundo-suave);
            box-shadow:0 3px 8px rgba(0,0,0,0.05);
            transition:0.3s;
        }

        .productBox:hover {
            transform:translateY(-3px);
            box-shadow:0 5px 12px rgba(0,0,0,0.12);
        }

        .productBox img {
            max-width:100%;
            border-radius:10px;
            margin-bottom:10px;
        }

        .productBox h3 {
            font-size:17px;
            margin:5px 0;
        }

        /* ======================
           BOTÕES PREMIUM
           ====================== */
        .productButtons {
            display:flex;
            justify-content:center;
            gap:10px;
            margin-top:12px;
        }

        .btn,
        .addToCartBtn,
        .viewProductBtn {
            padding:10px 12px;
            border-radius:8px;
            text-decoration:none;
            color:#fff !important;
            cursor:pointer;
            border:none;
            flex:1;
            font-size:14px;
            background:var(--cor-primaria) !important;
            transition:0.25s ease;
            box-shadow:0 2px 5px rgba(0,0,0,0.08);
        }

        .btn:hover,
        .addToCartBtn:hover,
        .viewProductBtn:hover {
            background:var(--cor-primaria-hover) !important;
            transform:translateY(-2px);
            box-shadow:0 4px 7px rgba(0,0,0,0.12);
        }

        /* ======================
           BOTÃO DE BUSCA
           ====================== */
        button[type="submit"] {
            background:var(--cor-primaria) !important;
            color:#fff !important;
            border:none !important;
            padding:8px 14px;
            border-radius:8px;
        }

        button[type="submit"]:hover {
            background:var(--cor-primaria-hover) !important;
        }

        /* ======================
           PAGINAÇÃO
           ====================== */
        .pagination a,
        .pagination span {
            padding:8px 12px;
            margin:0 4px;
            border:1px solid var(--cor-borda-suave);
            border-radius:6px;
            text-decoration:none;
            color:#444;
        }

        .pagination .current {
            background:var(--cor-primaria) !important;
            border-color:var(--cor-primaria) !important;
            color:#fff !important;
        }

        /* ======================
           RESPONSIVIDADE
           ====================== */
        @media(max-width:900px){
            .produtosGrid { grid-template-columns:repeat(2,1fr); }

            .lojaLayout { flex-direction:column; }

            .sidebarCategorias {
                width:100%;
                display:flex;
                flex-wrap:wrap;
                gap:10px;
            }

            .sidebarCategorias h3 {
                width:100%;
                text-align:center;
            }
        }

        @media(max-width:600px){
            .produtosGrid { grid-template-columns:1fr; }
        }
    </style>


</head>
<body <?php body_class(); ?>>

<section class="lojaContainer">
  <div class="lojaLayout">

    <!-- Sidebar -->
    <aside class="sidebarCategorias">
      <h3>Categorias</h3>
      <a href="?categoria=all" class="categoryItem <?= $categoriaSelecionada=='all'?'active':'' ?>">Todos</a>

      <?php foreach ($categorias as $cat): ?>
      <a href="?categoria=<?= esc_attr($cat->slug) ?>" class="categoryItem <?= $categoriaSelecionada==$cat->slug?'active':'' ?>">
        <?= esc_html($cat->name) ?>
      </a>
      <?php endforeach; ?>
    </aside>

    <!-- Área dos produtos -->
    <div>

      <div style="margin-bottom:25px;">
        <form method="get">
          <input type="text" name="busca" value="<?= esc_attr($busca) ?>" placeholder="Pesquisar..."
                 style="padding:8px 12px; border:1px solid #ccc; border-radius:8px;">
          <button type="submit" style="padding:8px 14px; background:#000; color:#fff; border:none; border-radius:8px;">Buscar</button>
        </form>
      </div>

      <div class="linha-separadora" style="margin-bottom:25px; border-top:1px solid #ddd;"></div>

      <div class="produtosGrid">
        <?php if($query->have_posts()): ?>
          <?php while($query->have_posts()): $query->the_post(); global $product; ?>
          <div class="productBox" data-id="<?= $product->get_id() ?>">
            <img src="<?= wp_get_attachment_url($product->get_image_id()) ?>">
            <h3><?= esc_html($product->get_name()); ?></h3>
            <p><?= $product->get_price_html(); ?></p>

            <div class="productButtons">
              <button class="btn addToCartBtn" data-id="<?= $product->get_id() ?>">Adicionar ao Carrinho</button>
              <a href="<?= get_permalink($product->get_id()); ?>" class="btn viewProductBtn">Ver Produto</a>
            </div>
          </div>
          <?php endwhile; wp_reset_postdata(); ?>
        <?php else: ?>
          <p>Nenhum produto encontrado.</p>
        <?php endif; ?>
      </div>

      <div class="linha-separadora" style="margin:25px 0; border-top:1px solid #ddd;"></div>

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

<script>
jQuery(function($){
  $('.addToCartBtn').on('click', function(){
    const productId = $(this).data('id');

    $.post('/?wc-ajax=add_to_cart', { product_id: productId, quantity: 1 }, function(){
      $(document.body).trigger('wc_fragment_refresh');
      setTimeout(()=>{ location.reload(); }, 600);
    });
  });
});
</script>

<?php wp_footer(); ?>
</body>
</html>
