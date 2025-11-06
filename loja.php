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
body { font-family:system-ui,sans-serif; margin:0; padding:0; color:#000; background:#fff; }
.lojaContainer { max-width:1200px; margin:0 auto; padding:40px 20px; }

/* ✅ Sidebar novo, responsivo */
.lojaLayout { display:flex; gap:30px; align-items:flex-start; }
.sidebarCategorias {
  flex:0 0 260px;
  background:#fff;
  border:1px solid #e0e0e0;
  border-radius:16px;
  padding:20px;
  box-sizing:border-box;
  transition:all .3s ease;
}
.sidebarCategorias h3 {
  width:100%;
  font-size:18px;
  font-weight:600;
  margin-bottom:15px;
  padding-bottom:8px;
  border-bottom:2px solid #e0e0e0;
  text-transform:uppercase;
  color:#000;
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
.categoryItem:hover { background:#f5f5f5; }
.categoryItem.active { background:#000; color:#fff; }

/* ✅ Mantém a grid ORIGINAL (com 3 colunas) */
.produtosGrid { display:grid; grid-template-columns:repeat(3,1fr); gap:25px; }

@media(max-width:900px){
  .produtosGrid{ grid-template-columns:repeat(2,1fr); }

  /* Sidebar responsivo como no novo */
  .lojaLayout{ flex-direction:column; }
  .sidebarCategorias {
    width:100%;
    display:flex;
    flex-wrap:wrap;
    gap:10px;
    border-radius:12px;
    padding:10px 15px;
  }
  .sidebarCategorias h3 {
    width:100%;
    text-align:center;
    font-size:16px;
    margin-bottom:8px;
    border-bottom:2px solid #e0e0e0;
  }
  .sidebarCategorias .categoryItem {
    margin:0;
    padding:8px 14px;
    font-size:14px;
    display:inline-block;
  }
}

@media(max-width:600px){
  .produtosGrid{ grid-template-columns:1fr; }

  .sidebarCategorias {
    flex-wrap:wrap !important;
    justify-content:flex-start !important;
    align-items:flex-start !important;
    overflow-x:visible !important;
  }
  .sidebarCategorias h3 {
    text-align:left;
    padding-bottom:6px;
    margin-bottom:10px;
  }
}

.productBox {
  border:1px solid #ddd;
  border-radius:10px;
  padding:15px;
  text-align:center;
  background:#fff;
  box-shadow:0 3px 8px rgba(0,0,0,0.05);
  transition:0.3s;
}
.productBox:hover {
  transform:translateY(-4px);
  box-shadow:0 5px 10px rgba(0,0,0,0.1);
}

.productBox img { max-width:100%; border-radius:8px; margin-bottom:10px; }
.productBox h3 { font-size:17px; margin:5px 0; }
.productBox p { margin:0; }

/* ✅ Mantém os BOTÕES ORIGINAIS */
.productButtons { display:flex; justify-content:center; gap:10px; margin-top:10px; }
.btn {
  padding:6px 10px;
  border-radius:0;
  text-decoration:none;
  color:#fff;
  cursor:pointer;
  border:none;
  flex:1;
  text-align:center;
  font-size:14px;
}
.addToCartBtn,
.viewProductBtn {
    background:#000 !important;
    color:#fff !important;
}

.addToCartBtn:hover, .viewProductBtn:hover { filter:brightness(1.1); }

.pagination { text-align:center; margin-top:30px; }
.pagination a,.pagination span{
  padding:8px 12px; margin:0 4px; border:1px solid #ccc; border-radius:6px;
  text-decoration:none; color:#333;
}
.pagination .current { background:#000; color:#fff; border-color:#000; }
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
