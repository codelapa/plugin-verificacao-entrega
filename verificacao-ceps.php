<?php
/**
 * Plugin Name: Verificação CEPs
 * Description: Plugin para verificar CEPs e personalizar mensagens.
 * Version: 1.0
 * Author: Codelapa
 */



// Incluir a biblioteca jQuery Mask Plugin
function incluir_jquery_mask() {
    // Verificar se o script já está enfileirado
    if (!wp_script_is('jquery-mask', 'enqueued')) {
        wp_enqueue_script('jquery-mask', 'https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js', array('jquery'), '1.14.16', true);
    }
}
add_action('wp_enqueue_scripts', 'incluir_jquery_mask');





// Função para verificar o frete pelo CEP
function verificar_frete_cep() {
    if (isset($_POST['cep'])) {
        $cep = sanitize_text_field($_POST['cep']);
        
        // Simular um carrinho temporário
        WC()->customer->set_billing_postcode($cep);
        WC()->customer->set_shipping_postcode($cep);
        WC()->customer->set_shipping_city('');
        WC()->customer->set_shipping_state('');
        WC()->customer->set_shipping_country('BR');
        WC()->customer->save();

        // Obter pacotes e métodos de envio
        WC()->cart->calculate_shipping();
        $packages = WC()->cart->get_shipping_packages();
        $shipping_methods = WC()->shipping->calculate_shipping_for_package($packages[0]);

        $frete_disponivel = get_option('frete_disponivel', 'Frete disponível para este CEP.');
        $frete_indisponivel = get_option('frete_indisponivel', 'Poxa… Ainda não atendemos esse endereço.');

        if (!empty($shipping_methods['rates'])) {
            $fretes = array();
            foreach ($shipping_methods['rates'] as $rate) {
                $fretes[] = array(
                    'label' => $rate->label,
                    'cost' => wc_price($rate->cost)
                );
            }
            $response = array('available' => true, 'fretes' => $fretes, 'message' => $frete_disponivel);
        } else {
            $response = array('available' => false, 'message' => $frete_indisponivel);
        }

        wp_send_json($response);
    }

    wp_die();
}
add_action('wp_ajax_verificar_frete_cep', 'verificar_frete_cep');
add_action('wp_ajax_nopriv_verificar_frete_cep', 'verificar_frete_cep');

function formulario_verificacao_frete() {
    $botao_texto = get_option('botao_texto', 'Verificar entrega');
      $titulo_verificacao_frete = get_option('titulo_verificacao_frete', 'consulte aqui se entregamos em seu bairro');
    $botao_cor = get_option('botao_cor', '#657f66');
    $input_cor = get_option('input_cor', '#657f6629');
    $placeholder_cep = get_option('placeholder_cep', 'Digite seu CEP');
    
    $texto_input_cor = get_option('texto_input_cor', '#657f66');
    $texto_botao_cor = get_option('texto_botao_cor', 'white');
    
    
    ob_start();
    ?>
    
    
    <style>
        
        
        @media (max-width: 767px) {
            #cep, .btn-verificar-entrega {
                width: 100% !important;
                height: 50px !important;
                margin: 0px !important;
            }
        }

        #form-verificacao-frete {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 10px;
        }
        #cep {
            width: 48%;
        }
        .btn-verificar-entrega {
            width: 48%;
            background-color: <?php echo esc_attr($botao_cor); ?>;
            color: <?php echo esc_attr($texto_botao_cor); ?>;

        }

        h3.title-veri-frete {
    text-align: center;
     color: <?php echo esc_attr($texto_input_cor); ?>;
}

        input#cep {
            border: none;
            box-shadow: none;
            height: 50px;
            border-radius: 8px;
            background-color: <?php echo esc_attr($input_cor); ?>;
            color: <?php echo esc_attr($texto_input_cor); ?>;
        }

        input#cep::placeholder {
   color: <?php echo esc_attr($texto_input_cor); ?>;
}



        #resultado-frete {
            width: 100%;
            text-align: center;
            margin-top: 20px;
        }
        
    </style>
    
  
  
    <form id="form-verificacao-frete" method="post">
        <h3 class="title-veri-frete"><?php echo esc_html($titulo_verificacao_frete); ?></h3>
        <input type="text" name="cep" id="cep" placeholder="<?php echo esc_attr($placeholder_cep); ?>" required>
        <button type="submit" class="btn-verificar-entrega"><?php echo esc_html($botao_texto); ?></button>
        <div id="resultado-frete"></div>
    </form>
    
    <?php
    return ob_get_clean();
}

add_shortcode('verificacao_frete', 'formulario_verificacao_frete');

// Atualizar pacotes de envio
function atualizar_pacotes_envio() {
    WC()->cart->calculate_shipping();
}
add_action('woocommerce_after_calculate_totals', 'atualizar_pacotes_envio');

// Adicionar menu ao painel do WordPress
function verificacao_ceps_menu() {
    add_menu_page(
        'Verificação CEPs',
        'Verificação CEPs',
        'manage_options',
        'verificacao-ceps',
        'verificacao_ceps_page',
        'dashicons-admin-tools',
        20
    );
}
add_action('admin_menu', 'verificacao_ceps_menu');

// Página de configurações do plugin
function verificacao_ceps_page() {
    ?>
    <div class="wrap">
        <h1>Configurações de Verificação de CEPs</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('verificacao_ceps_options');
            do_settings_sections('verificacao_ceps');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}




// Registrar configurações do plugin
function verificacao_ceps_settings() {
    register_setting('verificacao_ceps_options', 'frete_disponivel');
    register_setting('verificacao_ceps_options', 'frete_indisponivel');
    register_setting('verificacao_ceps_options', 'botao_texto');
    
    register_setting('verificacao_ceps_options', 'botao_cor');
    register_setting('verificacao_ceps_options', 'input_cor');
    
    register_setting('verificacao_ceps_options', 'placeholder_cep');
    
    
    register_setting('verificacao_ceps_options', 'texto_input_cor');
    register_setting('verificacao_ceps_options', 'texto_botao_cor');
    
    
    register_setting('verificacao_ceps_options', 'titulo_verificacao_frete');

add_settings_field(
    'titulo_verificacao_frete',
    'Título do Formulário',
    'titulo_verificacao_frete_callback',
    'verificacao_ceps',
    'verificacao_ceps_section'
);


add_settings_field(
    'texto_input_cor',
    'Cor do Texto do Campo de Entrada',
    'texto_input_cor_callback',
    'verificacao_ceps',
    'verificacao_ceps_section'
);








add_settings_field(
    'input_cor',
    'Cor do Campo de Entrada',
    'input_cor_callback',
    'verificacao_ceps',
    'verificacao_ceps_section'
);
add_settings_field(
    'placeholder_cep',
    'Placeholder do CEP',
    'placeholder_cep_callback',
    'verificacao_ceps',
    'verificacao_ceps_section'
);



    add_settings_section(
        'verificacao_ceps_section',
        'Mensagens Personalizadas',
        'verificacao_ceps_section_callback',
        'verificacao_ceps'
    );

    add_settings_field(
        'frete_disponivel',
        'Mensagem de Frete Disponível',
        'frete_disponivel_callback',
        'verificacao_ceps',
        'verificacao_ceps_section'
    );

    add_settings_field(
        'frete_indisponivel',
        'Mensagem de Frete Indisponível',
        'frete_indisponivel_callback',
        'verificacao_ceps',
        'verificacao_ceps_section'
    );
    
    
add_settings_field(
    'texto_botao_cor',
    'Cor do Texto do Botão',
    'texto_botao_cor_callback',
    'verificacao_ceps',
    'verificacao_ceps_section'
);

add_settings_field(
    'botao_cor',
    'Cor do Botão',
    'botao_cor_callback',
    'verificacao_ceps',
    'verificacao_ceps_section'
);

    add_settings_field(
        'botao_texto',
        'Texto do Botão',
        'botao_texto_callback',
        'verificacao_ceps',
        'verificacao_ceps_section'
    );
}
add_action('admin_init', 'verificacao_ceps_settings');



function texto_input_cor_callback() {
    $texto_input_cor = get_option('texto_input_cor', '#657f66');
    echo '<input type="text" name="texto_input_cor" value="' . esc_attr($texto_input_cor) . '" class="color-field">';
}

function texto_botao_cor_callback() {
    $texto_botao_cor = get_option('texto_botao_cor', 'white');
    echo '<input type="text" name="texto_botao_cor" value="' . esc_attr($texto_botao_cor) . '" class="color-field">';
}

function titulo_verificacao_frete_callback() {
    $titulo_verificacao_frete = get_option('titulo_verificacao_frete', 'consulte aqui se entregamos em seu bairro');
    echo '<input type="text" name="titulo_verificacao_frete" value="' . esc_attr($titulo_verificacao_frete) . '" class="regular-text">';
}



function incluir_wp_color_picker() {
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');
    wp_enqueue_script('verificacao_ceps_custom_script', plugins_url('/custom-script.js', __FILE__), array('wp-color-picker'), false, true);
}
add_action('admin_enqueue_scripts', 'incluir_wp_color_picker');

function placeholder_cep_callback() {
    $placeholder_cep = get_option('placeholder_cep', 'Digite seu CEP');
    echo '<input type="text" name="placeholder_cep" value="' . esc_attr($placeholder_cep) . '" class="regular-text">';
}



function botao_cor_callback() {
    $botao_cor = get_option('botao_cor', '#657f66');
    echo '<input type="text" name="botao_cor" value="' . esc_attr($botao_cor) . '" class="color-field">';
}

function input_cor_callback() {
    $input_cor = get_option('input_cor', '#657f6629');
    echo '<input type="text" name="input_cor" value="' . esc_attr($input_cor) . '" class="color-field">';
}


function verificacao_ceps_section_callback() {
    echo 'Use o shortcode: <b>[verificacao_frete]</b> para inserir em suas páginas.';
}

function frete_disponivel_callback() {
    $frete_disponivel = get_option('frete_disponivel', 'Frete disponível para este CEP.');
    echo '<input type="text" name="frete_disponivel" value="' . esc_attr($frete_disponivel) . '" class="regular-text">';
}

function frete_indisponivel_callback() {
    $frete_indisponivel = get_option('frete_indisponivel', 'Poxa… Ainda não atendemos esse endereço.');
    echo '<input type="text" name="frete_indisponivel" value="' . esc_attr($frete_indisponivel) . '" class="regular-text">';
}

function botao_texto_callback() {
    $botao_texto = get_option('botao_texto', 'Verificar entrega');
    echo '<input type="text" name="botao_texto" value="' . esc_attr($botao_texto) . '" class="regular-text">';
}
?>
