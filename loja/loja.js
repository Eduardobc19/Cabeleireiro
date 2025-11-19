jQuery(function($){

    // Botão de adicionar ao carrinho
    $('.addToCartBtn').on('click', function(){
        const productId = $(this).data('id');

        $.post('/?wc-ajax=add_to_cart', {
            product_id: productId,
            quantity: 1
        }, function(){

            // Atualiza mini carrinho
            $(document.body).trigger('wc_fragment_refresh');

            // Reload leve
            setTimeout(() => {
                location.reload();
            }, 600);
        });
    });

});
