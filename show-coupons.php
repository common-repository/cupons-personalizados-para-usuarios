<?php
/**
 * Plugin Name: Cupons Personalizados para Usuários
 * Description: Exibe cupons do usuário logado ou cupons com códigos específicos.
 * Version: 1.0.0
 * Author: Ronaldo
 * License: GPL2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Função para verificar se o WooCommerce está ativo
function cpus_check_woocommerce_active() {
    return class_exists('WooCommerce');
}

// Ação de inicialização do plugin, só roda se o WooCommerce estiver ativo
function cpus_init() {
    if (cpus_check_woocommerce_active()) {
        // Adicionar a página de configurações no menu do WooCommerce
        add_action('admin_menu', 'cpus_add_woocommerce_menu_page');
    } else {
        // WooCommerce não está ativo, exibe um aviso no admin
        add_action('admin_notices', 'cpus_woocommerce_inactive_notice');
    }
}
add_action('plugins_loaded', 'cpus_init');

// Função para exibir aviso se o WooCommerce não estiver ativo
function cpus_woocommerce_inactive_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php esc_html_e('O plugin Cupons Personalizados para Usuários requer o WooCommerce para funcionar corretamente. Por favor, instale e ative o WooCommerce.', 'cupons-personalizados-para-usuarios'); ?></p>
    </div>
    <?php
}

// Função para adicionar a página de configurações no menu do WooCommerce
function cpus_add_woocommerce_menu_page() {
    add_submenu_page(
        'woocommerce', // Slug do menu pai
        __('Configurações de Cupons', 'cupons-personalizados-para-usuarios'), // Título da página
        __('Cupons Personalizados', 'cupons-personalizados-para-usuarios'), // Nome do submenu
        'manage_options', // Capacidade necessária
        'cpus-settings', // Slug da página
        'cpus_settings_page' // Função que renderiza a página
    );
}

// Função para renderizar a página de configurações
function cpus_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Configurações de Cupons Personalizados', 'cupons-personalizados-para-usuarios'); ?></h1><br><br>
        <form method="post" action="options.php">
            <?php
            settings_fields('cpus_settings_group');
            do_settings_sections('cpus-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Registrar as configurações
function cpus_register_settings() {
    register_setting(
        'cpus_settings_group',
        'cpus_specific_codes'
    );

    add_settings_section(
        'cpus_section',
        __('Configurações de Cupons', 'cupons-personalizados-para-usuarios'),
        null,
        'cpus-settings'
    );

    add_settings_field(
        'cpus_specific_codes',
        __('Códigos de Cupons Específicos', 'cupons-personalizados-para-usuarios'),
        'cpus_specific_codes_field',
        'cpus-settings',
        'cpus_section'
    );
}
add_action('admin_init', 'cpus_register_settings');

// Função para exibir o campo de entrada de códigos de cupons específicos
function cpus_specific_codes_field() {
    $specific_codes = get_option('cpus_specific_codes', 'primeiracompra');
    echo '<input type="text" name="cpus_specific_codes" value="' . esc_attr($specific_codes) . '" class="regular-text" />';
    echo '<p class="description">' . esc_html__('Insira os códigos de cupons separados por vírgula.', 'cupons-personalizados-para-usuarios') . '</p>';
    echo '<p class="description">' . esc_html__('Esses códigos serão exibidos para todos os usuários.', 'cupons-personalizados-para-usuarios') . '</p>';
}

// Adicionar link de configurações na página de plugins
function cpus_add_settings_link($links) {
    $settings_link = '<a href="admin.php?page=cpus-settings">' . __('Configurações', 'cupons-personalizados-para-usuarios') . '</a>';
    array_push($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'cpus_add_settings_link');

// Função para registrar e enfileirar o CSS do plugin
function cpus_enqueue_styles() {
    wp_register_style('cpus_coupons_style', plugins_url('/css/coupons-style.css', __FILE__), array(), '1.0.0');
    wp_enqueue_style('cpus_coupons_style');
}
add_action('wp_enqueue_scripts', 'cpus_enqueue_styles');

// Inclui a função que exibe os cupons
include_once plugin_dir_path(__FILE__) . 'includes/display-coupons.php';
