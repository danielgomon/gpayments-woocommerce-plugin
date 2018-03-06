<?php
class WC_GPayments_Connection extends WC_Payment_Gateway_CC {

	function __construct() {

		// Global ID
		$this->id = "wc-4gpayments";

		// Show title
		$this->method_title = __( "4GPayments", 'wc-4gpayments' );
		
		// "Place order" button text
		$this->order_button_text  = __( 'Pago seguro', 'woocommerce' );

		// Show description
		$this->method_description = __( "Plugin para conectar 4Geeks Payments con WooCommerce. Si no tienes cuenta aún, créala en https://4geeks.io/payments", 'wc-4gpayments' );

		// Vertical tab title
		$this->title = __( "4GPayments", 'wc-4gpayments' );

		$this->icon = null;

		$this->has_fields = true;

		// Support default form with credit card
		$this->supports = array( 'default_credit_card_form' );

		// Setting defines
		$this->init_form_fields();

		// Load time variable setting
		$this->init_settings();

		// Turn these settings into variables we can use
		foreach ( $this->settings as $setting_key => $value ) {
			$this->$setting_key = $value;
		}

		// Save settings
		if ( is_admin() ) {
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		}
	}

	// Administration fields for specific Gateway
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'		=> __( 'Activo/Inactivo', 'wc-4gpayments' ),
				'label'		=> __( 'Activar esta pasarela de pago', 'wc-4gpayments' ),
				'type'		=> 'checkbox',
				'default'	=> 'no',
			),
			'title' => array(
				'title'		=> __( 'Título', '4gpayments' ),
				'type'		=> 'text',
				'desc_tip'	=> __( 'Nombre de la pasarela de pago.', 'wc-4gpayments' ),
				'default'	=> __( 'Pagar con tarjeta', '4gpayments' ),
				'custom_attributes' => array(
					'required' => 'required'
				),
			),
			'description' => array(
				'title'		=> __( 'Descripción', '4gpayments' ),
				'type'		=> 'text',
				'default'	=> __( 'Pague con su tarjeta de débito o crédito.', 'wc-4gpayments' ),
				'css'		=> 'max-width:450px;',
				'custom_attributes' => array(
					'required' => 'required'
				),
			),
			'entity_description' => array(
				'title'		=> __( 'Detalle bancario (máx. 22 caracteres)', 'wc-4gpayments' ),
				'type'		=> 'text',
				'default' 	=> 'Pago a traves de 4GP',
				'desc_tip'	=> __( 'Detalle que aparece en el estado de cuenta del cliente final.', '4gpayments' ),
				'custom_attributes' => array(
					'required' => 'required',
					'maxlength' => '22'
				),
			),
			'charge_description' => array(
				'title'		=> __( '4GP Descripción del cargo', 'wc-4gpayments' ),
				'type'		=> 'text',
				'default' 	=> 'Compra en linea',
				'desc_tip'	=> __( 'Es la descripción por defecto de un cargo (compra) en la tarjeta del cliente.', '4gpayments' ),
				'custom_attributes' => array(
					'required' => 'required'
				),
			),
			'client_id' => array(
				'title'		=> __( '4GP Client ID', 'wc-4gpayments' ),
				'type'		=> 'text',
				'desc_tip'	=> __( 'API Client ID provisto por 4Geeks Payments.', 'wc-4gpayments' ),
				'custom_attributes' => array(
					'required' => 'required'
				),
			),
			'client_secret' => array(
				'title'		=> __( '4GP Client Secret', 'wc-4gpayments' ),
				'type'		=> 'password',
				'desc_tip'	=> __( 'API Client Secret provisto por 4Geeks Payments.', 'wc-4gpayments' ),
				'custom_attributes' => array(
					'required' => 'required'
				),
			)
		);
	}
	
		// Custom credit card form
	public function form() {
        wp_enqueue_script( 'wc-credit-card-form' );

        $fields = array();

        $cvc_field = '<p class="form-row form-row-last">
            <label for="' . esc_attr( $this->id ) . '-card-cvc">' . esc_html__( 'Código de seguridad', 'gpayments-woocommerce' ) . ' <span class="required">*</span></label>
            <input id="' . esc_attr( $this->id ) . '-card-cvc" class="input-text wc-credit-card-form-card-cvc" inputmode="numeric" autocomplete="cc-csc" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" maxlength="4" placeholder="' . esc_attr__( 'CVC', 'gpayments-woocommerce' ) . '" ' . $this->field_name( 'card-cvc' ) . ' /><i class="fa fa-key" aria-hidden="true"></i></p>';

        $default_fields = array(
            'card-number-field' => '<p class="form-row form-row-wide">
                <label for="' . esc_attr( $this->id ) . '-card-number">' . esc_html__( 'Número de tarjeta', 'gpayments-woocommerce' ) . ' <span class="required">*</span></label>
                <input id="' . esc_attr( $this->id ) . '-card-number" class="input-text wc-credit-card-form-card-number" inputmode="numeric" autocomplete="cc-number" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;" ' . $this->field_name( 'card-number' ) . ' />
            <i class="fa fa-credit-card" aria-hidden="true"></i></p>',
            'card-expiry-field' => '<p class="form-row form-row-first">
                <label for="' . esc_attr( $this->id ) . '-card-expiry">' . esc_html__( 'Vencimiento (MM / AA)', 'gpayments-woocommerce' ) . ' <span class="required">*</span></label>
                <input id="' . esc_attr( $this->id ) . '-card-expiry" class="input-text wc-credit-card-form-card-expiry input-sm" inputmode="numeric" autocomplete="cc-exp" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" placeholder="' . esc_attr__( 'MM / AA', 'gpayments-woocommerce' ) . '" ' . $this->field_name( 'card-expiry' ) . ' /><i class="fa fa-calendar-o" aria-hidden="true"></i></span></p>',
        );

        if ( ! $this->supports( 'credit_card_form_cvc_on_saved_method' ) ) {
            $default_fields['card-cvc-field'] = $cvc_field;
        }

        $fields = wp_parse_args( $fields, apply_filters( 'woocommerce_credit_card_form_fields', $default_fields, $this->id ) );
        ?>

        <fieldset id="wc-<?php echo esc_attr( $this->id ); ?>-cc-form" class='wc-credit-card-form wc-payment-form'>
            <?php do_action( 'woocommerce_credit_card_form_start', $this->id ); ?>
            <?php
                foreach ( $fields as $field ) {
                echo $field;
                }
            ?>
            <?php do_action( 'woocommerce_credit_card_form_end', $this->id ); ?>
            <div class="clear"></div>
        </fieldset>
        <?php

        if ( $this->supports( 'credit_card_form_cvc_on_saved_method' ) ) {
            echo '<fieldset>' . $cvc_field . '</fieldset>';
        }
    }

	// Response handled for payment gateway
	public function process_payment( $order_id ) {
		global $woocommerce;

		$customer_order = new WC_Order( $order_id );

		//API Auth URL
		$api_auth_url = 'https://api.payments.4geeks.io/authentication/token/';

		//API base URL
		$api_url = 'https://api.payments.4geeks.io/v1/charges/simple/create/';

		$data_to_send = array("grant_type" => "client_credentials",
							  "client_id" => $this->client_id,
							  "client_secret" => $this->client_secret );
							  
		// Make all fields required					  
		if(empty($_POST['wc-4gpayments-card-number']) || empty($_POST['wc-4gpayments-card-cvc']) || empty($_POST['wc-4gpayments-card-expiry'])){
			throw new Exception( __( 'El número de tarjeta, la fecha de vencimiento y el código de seguridad son obligatorios.', 'wc-4gpayments' ) );
		}
		
		$response_token = wp_remote_post( $api_auth_url, array(
			'method'   => 'POST',
			'timeout'  => 90,
			'blocking' => true,
			'headers'  => array('content-type' => 'application/json'),
			'body' 	   => json_encode($data_to_send, true)
		) );

		$api_token = json_decode( wp_remote_retrieve_body($response_token), true)['access_token'];

		if($this->entity_description == ''){
			$this->entity_description = 'Pago a traves de 4GP';
			}
		
		$payload = array(
			"amount"             	=> $customer_order->get_total(),
			"description"           => $this->charge_description,
			"entity_description"    => strtoupper($this->entity_description),
			"currency"           	=> get_woocommerce_currency(),
			"credit_card_number"    => str_replace( array(' ', '-' ), '', $_POST['wc-4gpayments-card-number'] ),
			"credit_card_security_code_number" => str_replace( array(' ', '-' ), '', $_POST['wc-4gpayments-card-cvc'] ),
			"exp_month" 			=> substr($_POST['wc-4gpayments-card-expiry'], 0, 2),
			"exp_year" 				=> "20" . substr($_POST['wc-4gpayments-card-expiry'], -2),
		);

		// Send this payload to 4GP for processing
		$response = wp_remote_post( $api_url, array(
			'method'    => 'POST',
			'body'      => json_encode($payload, true),
			'timeout'   => 90,
			'blocking'  => true,
			'headers'   => array('authorization' => 'bearer ' . $api_token, 'content-type' => 'application/json'),
		 ) );

		$JsonResponse = json_decode($response['body']);
		$response_Detail = $JsonResponse->error->es;

		if ( is_wp_error( $response ) )
			throw new Exception( __( 'Hubo un problema para comunicarse con el procesador de pagos...', 'wc-4gpayments' ) );

		if ( empty( $response['body'] ) )
			throw new Exception( __( 'La respuesta no obtuvo nada.', 'wc-4gpayments' ) );

		// get body response while get not error
		$responde_code = wp_remote_retrieve_response_code( $response );
		// 1 or 4 means the transaction was a success
		if ( $responde_code == 201 ) {
			// Payment successful
			$customer_order->add_order_note( __( 'Pago completo.', 'wc-4gpayments' ) );

			// paid order marked
			$customer_order->payment_complete();

			// this is important part for empty cart
			$woocommerce->cart->empty_cart();

			// Redirect to thank you page
			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $customer_order ),
			);
		} else {
			//transiction fail
			wc_add_notice( $response_Detail, 'error' );
			$customer_order->add_order_note( 'Error: '. $response_Detail );
		}
	}

	// Validate fields
	public function validate_fields() {
		return true;
	}

	public function do_ssl_check() {
		if( $this->enabled == "yes" ) {
			if( get_option( 'woocommerce_force_ssl_checkout' ) == "no" ) {
				echo "<div class=\"error\"><p>". sprintf( __( "<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href=\"%s\">forcing the checkout pages to be secured.</a>" ), $this->method_title, admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) ."</p></div>";
			}
		}
	}

}
?>
