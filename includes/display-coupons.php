<?php
// Verifica se o arquivo está sendo acessado diretamente
if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Função que exibe os cupons do usuário logado e cupons específicos
function cpus_show_user_and_specific_coupons() {
    if (!is_user_logged_in()) {
        return '<p>' . esc_html__('Você precisa estar logado para ver seus cupons.', 'cupons-personalizados-para-usuarios') . '</p>';
    }

    // Obter o usuário logado
    $user = wp_get_current_user();
    $user_email = $user->user_email;

    // Obter os códigos de cupons específicos a partir das configurações
    $specific_coupons_option = get_option('cpus_specific_codes', 'primeiracompra');
    $specific_coupons = array_map('trim', explode(',', $specific_coupons_option));

    // Função auxiliar para obter cupons
    function cpus_get_coupons($args) {
        return get_posts($args);
    }

    // Obter cupons do usuário logado
    $user_coupon_args = array(
        'post_type'      => 'shop_coupon',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_query'     => array(
            array(
                'key'     => 'customer_email',
                'value'   => $user_email,
                'compare' => 'LIKE'
            )
        ),
    );
    $user_coupons = cpus_get_coupons($user_coupon_args);

    // Obter cupons específicos
    $specific_coupon_ids = array_filter(array_map('wc_get_coupon_id_by_code', $specific_coupons));
    if (!empty($specific_coupon_ids)) {
        $specific_coupon_args = array(
            'post_type'      => 'shop_coupon',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'post__in'       => $specific_coupon_ids
        );
        $specific_coupons = cpus_get_coupons($specific_coupon_args);
    } else {
        $specific_coupons = array(); 
    }

    // Combinar cupons do usuário e cupons específicos
    $coupons = array_merge($user_coupons, $specific_coupons);

    // Filtrar cupons usados
    $used_coupon_ids = [];
    $customer_orders = wc_get_orders(array(
        'customer' => $user->ID,
        'status'   => array('wc-completed', 'wc-processing'),
        'limit'    => -1
    ));

    foreach ($customer_orders as $order) {
        $used_coupon_ids = array_merge($used_coupon_ids, $order->get_used_coupons());
    }

    // Filtrar cupons expirados e usados
    $coupons = array_filter($coupons, function($coupon_post) use ($used_coupon_ids) {
        $coupon = new WC_Coupon($coupon_post->post_title);
        
        // Verifica se o cupom já foi utilizado
        if (in_array($coupon->get_code(), $used_coupon_ids)) {
            return false; // Excluir cupons já utilizados
        }
        
        // Verifica se o cupom está expirado
        $expiration_date = $coupon->get_date_expires();
        if ($expiration_date && $expiration_date->getTimestamp() < time()) {
            return false; // Excluir cupons expirados
        }
        
        return true; // Manter cupons válidos
    });

    if (empty($coupons)) {
        return '<p>' . esc_html__('Não há cupons disponíveis no momento.', 'cupons-personalizados-para-usuarios') . '</p>';
    }

    // Exibir cupons disponíveis
    ob_start(); 
    ?>
    <div class="containerticket">
    <?php
    foreach ($coupons as $coupon_post) {
        $coupon = new WC_Coupon($coupon_post->post_title);
        $discount_type = $coupon->get_discount_type();
        $discount_value = $coupon->get_amount();
        $formatted_discount = ($discount_type == 'percent') ? esc_html($discount_value) . '%' : 'R$ ' . esc_html(number_format($discount_value, 2, ',', '.'));

        ?>
        <div class="ticket">
            <div class="code"><?php echo esc_html($coupon->get_code()); ?></div>
            <div class="desccode"><?php echo esc_html($coupon_post->post_excerpt); ?></div>
            <div class="codigo"><strong><?php esc_html_e('Desconto: ', 'cupons-personalizados-para-usuarios'); ?></strong><span><?php echo esc_html($formatted_discount); ?></span></div>
            <strong><?php esc_html_e('Data de Expiração: ', 'cupons-personalizados-para-usuarios'); ?></strong><?php echo ($coupon->get_date_expires() ? esc_html($coupon->get_date_expires()->date('Y-m-d')) : 'N/A'); ?>
        </div>
        <?php
    }
    ?>
    </div>
    <?php

    return ob_get_clean(); 
}


// Registrar o shortcode para exibir os cupons
add_shortcode('cpus_show_my_coupons', 'cpus_show_user_and_specific_coupons');
