<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @package ARGPD
 * @subpackage Pages
 * @since 0.0.0
 *
 * @author César Maeso <info@superadmin.es>
 *
 * @copyright (c) 2018, César Maeso (https://superadmin.es)
 */

/**
 * Pages class.
 *
 * @since  0.0.0
 */
class ARGPD_Pages {

	/**
	 * Parent plugin class.
	 *
	 * @var    string
	 * @since  0.0.0
	 */
	protected $plugin = null;

	/**
	 * Parent plugin class.
	 *
	 * @var    class
	 * @since  0.0.0
	 */
	protected $compiler = null;

	/**
	 * Constructor.
	 *
	 * @since  0.0.0
	 *
	 * @param  string $plugin Main plugin object.
	 */
	public function __construct( $plugin ) {

		// set parent plugin.
		$this->plugin = $plugin;

		// Init mustache.
		if ( ! class_exists( 'Mustache_Autoloader' ) ) {
			require_once dirname( __FILE__ ) . '/../lib/vendor/Mustache/Autoloader.php';
			Mustache_autoloader::register();
		}

		// Init template engine.
		$this->compiler = new Mustache_Engine(
			array(
				'loader' => new Mustache_Loader_FilesystemLoader( dirname( __FILE__ ) . '/../views' ),
			)
		);

		// initiate our hooks.
		$this->hooks();
	}


	/**
	 * Initiate our hooks.
	 *
	 * @since  0.0.0
	 */
	public function hooks() {

		// create shortcodes for main views.
		add_shortcode(
			'argpd_aviso-legal',
			function() {
				return $this->aviso_legal(true);
			}
		);

		add_shortcode(
			'argpd_politica-cookies',
			function() {
				return $this->politica_cookies(true);
			}
		);

		add_shortcode(
			'argpd_politica-privacidad',
			function() {
				return $this->politica_privacidad(true);
			}
		);

		add_shortcode(
			'argpd_preferencias-cookies',
			function() {
				return $this->custom_cookies_page_render(true);
			}
		);

		add_shortcode(
			'argpd_consentimiento',
			function() {
				return $this->consentimiento_view();
			}
		);

		add_shortcode(
			'argpd_deber_de_informar',
			function( $atts = [] ) {
				$finalidad = isset( $atts['finalidad'] ) ? $atts['finalidad'] : null;
				$destinatarios = isset( $atts['destinatarios'] ) ? $atts['destinatarios'] : null;
				return $this->deber_de_informar_view( $finalidad, $destinatarios );
			}
		);

		// filter legal pages.
		add_filter(
			'the_content',
			function( $content ) {

				$page_id  = get_the_ID();
				if ( 0 == $page_id ) {
					return $content;
				}

				$settings = $this->plugin->argpd_settings->get_settings();
				switch ( $page_id ) {
					case $settings['avisolegalID']:
						$content = $this->aviso_legal() . $content;
						break;
					case $settings['privacidadID']:
						$content = $this->politica_privacidad() . $content;
						break;
					case $settings['cookiesID']:
						$content = $this->politica_cookies() . $content;
						break;
					case $settings['custom-cookies-page-id']:
						$content = $this->custom_cookies_page_render() . $content;
						break;
					default:
						break;
				}
				return $content;
			}
		);
	}


	/**
	 * Create Legal Pages
	 *
	 * @since  0.0.0
	 */
	public function create_all() {
		$this->create_legal_page();
		$this->create_privacy_page();
		$this->create_cookies_page();
		$this->create_custom_cookies_page();
	}

	/**
	 * Create legal page
	 *
	 * @since  1.0.1
	 */
	public function create_legal_page() {
		$id = $this->create_page( 'Aviso Legal' );
		( 0 != $id ) && $this->plugin->argpd_settings->update_setting( 'avisolegalID', $id );
		return $id;
	}

	/**
	 * Create privacy page
	 *
	 * @since  1.0.1
	 */
	public function create_privacy_page() {
		$id = $this->create_page( 'Política de Privacidad' );
		( 0 != $id ) && $this->plugin->argpd_settings->update_setting( 'privacidadID', $id );
		return $id;
	}

	/**
	 * Create cookies page
	 *
	 * @since  1.0.1
	 */
	public function create_cookies_page() {
		$id = $this->create_page( 'Política de Cookies' );
		( 0 != $id ) && $this->plugin->argpd_settings->update_setting( 'cookiesID', $id );
		return $id;
	}

	/**
	 * Crear la página para personalizar cookies.
	 *
	 * @since  1.3
	 */
	public function create_custom_cookies_page() {
		$id = $this->create_page( 'Personalizar Cookies' );
		( 0 != $id ) && $this->plugin->argpd_settings->update_setting( 'custom-cookies-page-id', $id );
		return $id;
	}

	/**
	 * Create Page by Name
	 *
	 * @param string $name page title.
	 *
	 * @return int the page_id if created else 0
	 */
	public function create_page( $name ) {

		if ( ! get_page_by_title( $name ) ) {
			$page = array(
				'post_content' => '',
				'post_title'   => $name,
				'post_status'  => 'publish',
				'post_parent'  => 0,
				'post_type'    => 'page',
			);

			return wp_insert_post( $page );
		}
		return 0;
	}


	/**
	 * Echo "Aviso Legal" page
	 *
	 * @since  0.0.0
	 * @return string
	 */
	public function aviso_legal($check_disabled=false) {
		$settings = $this->plugin->argpd_settings->get_settings();
		$settings['site-url'] = get_site_url();
		if ( $check_disabled || ! $settings['avisolegal-disabled'] ) {
			return $this->compiler->render( 'aviso-legal', $settings );
		}
	}

	/**
	 * Echo Disclaimer page
	 *
	 * @since  0.0.0
	 * @return string
	 */
	public function disclaimer() {
		return $this->compiler->render( 'disclaimer', null );
	}

	/**
	 * Echo "Politica de cookies" page
	 *
	 * @since  0.0.0
	 * @return string
	 */
	public function politica_cookies($check_disabled=false) {

		$settings = $this->plugin->argpd_settings->get_settings();
		
		if ( $check_disabled || ! $settings['cookies-disabled'] ) {
			//$settings['site-url'] = get_site_url();	
			$settings['cookies-html'] = nl2br ( esc_textarea( $settings[ 'lista-cookies'] ) );
			return $this->compiler->render( 'politica-cookies', $settings );
		}
	}

	/**
	 * Echo "Politica de privacidad" page
	 *
	 * @since  0.0.0
	 * @return string
	 */
	public function politica_privacidad($check_disabled=false){
		$settings = $this->plugin->argpd_settings->get_settings();
		// controles utilizados en el template.
		$settings['procedimientosderecogida-control'] = ( 
			$settings['option-forms']  				||
			$settings['option-comments']  			||
			$settings['thirdparty-mailchimp']  		||
			$settings['thirdparty-sendinblue']  	||
			$settings['thirdparty-mailpoet']  		||
			$settings['thirdparty-activecampaign'] 	||
			$settings['thirdparty-mailerlite'] );

		if ( $check_disabled || ! $settings['privacidad-disabled'] ) {
			return $this->compiler->render( 'politica-privacidad', $settings );
		}
	}

	/**
	 * Crea el contenido de la página de personalización de cookies.
	 *
	 * @since  1.3
	 * @return string
	 */
	public function custom_cookies_page_render($check_disabled=false) {
		$settings = $this->plugin->argpd_settings->get_settings();


		if ( isset( $_COOKIE['hasConsents'] ) ) {
			$has_consents = wp_kses_data( $_COOKIE['hasConsents'] );
			$consents = explode( " ", $has_consents );
			if ( in_array("ANLTCS", $consents) ){
				$settings['consent-analytics'] = true;
			}		

			if ( in_array("SCLS", $consents) ){
				$settings['consent-social'] = true;
			}		
		}

		if ( $check_disabled || ! $settings['custom-cookies-page-disabled'] ) {
			return $this->compiler->render( 'custom-cookies-page', $settings );
		}
	}


	/**
	 * Echo "consentimiento" View
	 *
	 * @since  0.0.0
	 * @return string
	 */
	public function consentimiento_view() {
		$settings = $this->plugin->argpd_settings->get_settings();
		return $this->compiler->render( 'consentimiento', $settings );
	}


	/**
	 * Echo "Deber de informar" View
	 *
	 * @since  0.0.0
	 * @param  string $finalidad valor personalizado para el campo finalidad.
	 * @param  string $destinatarios valor personalizado para el campo destinatarios.
	 * @return string
	 */
	public function deber_de_informar_view( $finalidad = null, $destinatarios = null ) {
		$settings = $this->plugin->argpd_settings->get_settings();

		if ( isset( $finalidad ) ) {
			$settings['deber-finalidad'] = trim( sanitize_text_field( $finalidad ) );
		}

		if ( isset( $destinatarios ) ) {
			$settings['deber-destinatarios'] = trim( sanitize_text_field( $destinatarios ) );
		}

		return $this->compiler->render( 'deber-de-informar', $settings );
	}


	/**
	 * Render Help Page
	 *
	 * @since  0.0.0
	 * @return string
	 */
	public function ayuda_view() {
		$settings = array(
			'url' => $this->plugin->url,
		);
		return $this->compiler->render( 'ayuda', $settings );
	}

	/**
	 * Echo cookies banner
	 *
	 * @since  0.0.0
	 * @return string
	 */
	public function cookiesbanner_view() {
		$settings = $this->plugin->argpd_settings->get_settings();
		return $this->compiler->render( 'cookies-banner', $settings );
	}



	/**
	 * Echo footer links
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function footer_links_view() {
		$settings = $this->plugin->argpd_settings->get_settings();
		return $this->compiler->render( 'pie-de-pagina-legal', $settings );
	}

}
