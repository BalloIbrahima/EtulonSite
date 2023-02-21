<?php

/**
 * Welcome Notice class.
 */
class REK_Welcome_Notice {

	/**
	** Constructor.
	*/
	public function __construct() {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// Render Notice
		add_action( 'admin_notices', [$this, 'render_notice'] );

		// Enque AJAX Script
		add_action( 'admin_enqueue_scripts', [$this, 'admin_enqueue_scripts'], 5 );

		// Dismiss
		add_action( 'admin_enqueue_scripts', [$this, 'notice_enqueue_scripts'], 5 );
		add_action( 'wp_ajax_rek_dismissed_handler', [$this, 'dismissed_handler'] );

		// Reset
		add_action( 'switch_theme', [$this, 'reset_notices'] );
		add_action( 'after_switch_theme', [$this, 'reset_notices'] );

		// Install Plugins
		add_action( 'wp_ajax_rek_install_activate_elementor', [$this, 'install_activate_elementor'] );
		add_action( 'wp_ajax_nopriv_rek_install_activate_elementor', [$this, 'install_activate_elementor'] );
		add_action( 'wp_ajax_rek_install_activate_royal_addons', [$this, 'install_activate_royal_addons'] );
		add_action( 'wp_ajax_nopriv_rek_install_activate_royal_addons', [$this, 'install_activate_royal_addons'] );

		add_action( 'wp_ajax_rek_cancel_elementor_redirect', [$this, 'rek_cancel_elementor_redirect'] );
	}

	public function rek_cancel_elementor_redirect() {
		exit;
	}

	/**
	** Get plugin status.
	*/
	public function get_plugin_status( $plugin_path ) {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return;
		}

		if ( ! file_exists( WP_PLUGIN_DIR . '/' . $plugin_path ) ) {
			return 'not_installed';
		} else {
			$plugin_updates = get_site_transient( 'update_plugins' );
			$plugin_needs_update = is_object($plugin_updates) ? array_key_exists($plugin_path, $plugin_updates->response) : false;

			if ( in_array( $plugin_path, (array) get_option( 'active_plugins', array() ), true ) || is_plugin_active_for_network( $plugin_path ) ) {
				return $plugin_needs_update ? 'active_update' : 'active';
			} else {
				return $plugin_needs_update ? 'inactive_update' : 'inactive';
			}	
		}
	}

	/**
	** Install a plugin.
	*/
	public function install_plugin( $plugin_slug ) {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return;
		}

		if ( ! function_exists( 'plugins_api' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		}
		if ( ! class_exists( 'WP_Upgrader' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}

		if ( false === filter_var( $plugin_slug, FILTER_VALIDATE_URL ) ) {
			$api = plugins_api(
				'plugin_information',
				[
					'slug'   => $plugin_slug,
					'fields' => [
						'short_description' => false,
						'sections'          => false,
						'requires'          => false,
						'rating'            => false,
						'ratings'           => false,
						'downloaded'        => false,
						'last_updated'      => false,
						'added'             => false,
						'tags'              => false,
						'compatibility'     => false,
						'homepage'          => false,
						'donate_link'       => false,
					],
				]
			);

			$download_link = $api->download_link;
		} else {
			$download_link = $plugin_slug;
		}

		// Use AJAX upgrader skin instead of plugin installer skin.
		// ref: function wp_ajax_install_plugin().
		$upgrader = new Plugin_Upgrader( new WP_Ajax_Upgrader_Skin() );

		$install = $upgrader->install( $download_link );

		if ( false === $install ) {
			return false;
		} else {
			return true;
		}
	}

	/**
	** Update a plugin.
	*/
	public function update_plugin( $plugin_path ) {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return;
		}

		if ( ! function_exists( 'plugins_api' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		}
		if ( ! class_exists( 'WP_Upgrader' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}

		// Use AJAX upgrader skin instead of plugin installer skin.
		// ref: function wp_ajax_install_plugin().
		$upgrader = new Plugin_Upgrader( new WP_Ajax_Upgrader_Skin() );

		$upgrade = $upgrader->upgrade( $plugin_path );

		if ( false === $upgrade ) {
			return false;
		} else {
			return true;
		}
	}

	/**
	** Update all plugins.
	*/
	public function update_all_plugins() {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return;
		}

		if ( ! function_exists( 'plugins_api' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		}
		if ( ! class_exists( 'WP_Upgrader' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}

		// Use AJAX upgrader skin instead of plugin installer skin.
		// ref: function wp_ajax_install_plugin().
		$upgrader = new Plugin_Upgrader( new WP_Ajax_Upgrader_Skin() );

		$upgrade = $upgrader->bulk_upgrade([
			'elementor/elementor.php',
			'royal-elementor-addons/wpr-addons.php'
		]);

		if ( false === $upgrade ) {
			return false;
		} else {
			return true;
		}
	}

	/**
	** Activate a plugin.
	*/
	public function activate_plugin( $plugin_path ) {

		if ( ! current_user_can( 'install_plugins' ) ) {
			return false;
		}

		$activate = activate_plugin( $plugin_path, '', false, false ); // TODO: last argument changed to false instead of true

		if ( is_wp_error( $activate ) ) {
			return false;
		} else {
			return true;
		}
	}

	/**
	** Install Elementor.
	*/
	public function install_activate_elementor() {
		check_ajax_referer( 'nonce', 'nonce' );

		if ( ! current_user_can( 'install_plugins' ) ) {
			wp_send_json_error( esc_html__( 'Insufficient permissions to install the plugin.', 'royal-elementor-kit' ) );
			wp_die();
		}

		$elementor_status = $this->get_plugin_status( 'elementor/elementor.php' );
		$actions_data = [];

		if ( 'not_installed' === $elementor_status ) {
			$this->install_plugin( 'elementor' );
			$this->activate_plugin( 'elementor/elementor.php' );
		} else {
			if ( 'inactive' === $elementor_status ) {
				$this->activate_plugin( 'elementor/elementor.php' );
			} elseif ( 'inactive_update' === $elementor_status || 'active_update' === $elementor_status ) {
				$addons_status = $this->get_plugin_status( 'royal-elementor-addons/wpr-addons.php' );
				
				if ( 'inactive_update' === $addons_status || 'active_update' === $addons_status ) {
					$this->update_all_plugins();
					$this->activate_plugin( 'elementor/elementor.php' );
					$this->activate_plugin( 'royal-elementor-addons/wpr-addons.php' );
					$actions_data['plugins_updated'] = true;
				} else {
					$this->update_plugin( 'elementor/elementor.php' );
					$this->activate_plugin( 'elementor/elementor.php' );
				}
			}
		}

		if ( 'active' === $this->get_plugin_status( 'elementor/elementor.php' ) ) {
			wp_send_json_success( $actions_data );
		}

		wp_send_json_error( esc_html__( 'Failed to initialize or activate importer plugin.', 'royal-elementor-kit' ) );

		wp_die();
	}

	/**
	** Install Royal Elementor Addons.
	*/
	public function install_activate_royal_addons() {
		check_ajax_referer( 'nonce', 'nonce' );

		if ( ! current_user_can( 'install_plugins' ) ) {
			wp_send_json_error( esc_html__( 'Insufficient permissions to install the plugin.', 'royal-elementor-kit' ) );
			wp_die();
		}

		$plugin_status = $this->get_plugin_status( 'royal-elementor-addons/wpr-addons.php' );

		if ( 'not_installed' === $plugin_status ) {
			$this->install_plugin( 'royal-elementor-addons' );
			$this->activate_plugin( 'royal-elementor-addons/wpr-addons.php' );

		} else {
			if ( 'inactive' === $plugin_status ) {
				$this->activate_plugin( 'royal-elementor-addons/wpr-addons.php' );
			} elseif ( 'inactive_update' === $plugin_status || 'active_update' === $plugin_status ) {
				$this->update_plugin( 'royal-elementor-addons/wpr-addons.php' );
				$this->activate_plugin( 'royal-elementor-addons/wpr-addons.php' );
			}
		}

		if ( 'active' === $this->get_plugin_status( 'royal-elementor-addons/wpr-addons.php' ) ) {
			wp_send_json_success();
		}

		wp_send_json_error( esc_html__( 'Failed to initialize or activate importer plugin.', 'royal-elementor-kit' ) );

		wp_die();
	}

	/**
	** Render Notice
	*/
	public function render_notice() {
		global $pagenow;

		$screen = get_current_screen();

		if ( 'wpr-addons' !== $screen->parent_base ) {
			$transient_name = sprintf( '%s_activation_notice', get_template() );

			if ( ! get_transient( $transient_name ) ) {
				?>
				<div class="rek-notice notice notice-success is-dismissible" data-notice="<?php echo esc_attr( $transient_name ); ?>">
					<button type="button" class="notice-dismiss"></button>

					<?php $this->render_notice_content(); ?>
				</div>
				<?php
			}
		}
	}

	/**
	** Render Notice Content
	*/
	public function render_notice_content() {
		$action = 'install-activate';
		$freemius_passed = 'false';
		$redirect_url = 'admin.php?page=wpr-addons';
		$elementor_status = $this->get_plugin_status( 'elementor/elementor.php' );
		$royal_addons_status = $this->get_plugin_status( 'royal-elementor-addons/wpr-addons.php' );
		
		if ( 'active' === $elementor_status && 'active' === $royal_addons_status ) {
			$action = 'default';
		}

		if ( function_exists('wpr_fs') ) {
			if ( wpr_fs()->is_registered() && wpr_fs()->is_tracking_allowed() || wpr_fs()->is_pending_activation() || wpr_fs()->is_anonymous() ) {
				$redirect_url = 'admin.php?page=wpr-templates-kit';
				$freemius_passed = 'true';
			}
		}

		$screen = get_current_screen();
		$flex_attr = '';
		$display_attr = 'display: inline-block !important';

		if ( 'appearance_page_about-rek' === $screen->id ) {
			$flex_attr = 'display:-webkit-box;display:-ms-flexbox;display:flex;-webkit-box-orient:vertical;-webkit-box-direction:normal;-ms-flex-direction:column;flex-direction:column;-webkit-box-pack:center;-ms-flex-pack:center;justify-content:center';
			$display_attr = 'display: none !important';
		}

		?>

		<div class="welcome-message" style="<?php echo esc_attr($flex_attr); ?>">
			<h1 style="<?php echo esc_attr($display_attr); ?>"><?php esc_html_e('Welcome to Royal Elementor Kit', 'royal-elementor-kit'); ?></h1>
			<p>
				<?php echo sprintf( esc_html__( 'Royal Elementor Kit comes with %s with various designs to pick from.', 'royal-elementor-kit' ), '<strong><a href="https://demosites.royal-elementor-addons.com/elementor-templates/?ref=royal-elementor-theme-wellcome-banner" target="_blank">Elementor based 40+ sites library </a></strong>' ); ?>
				<?php echo sprintf( esc_html__('%s  and many other Elementor Extensions & Effects.', 'royal-elementor-kit'), '<strong><a href="https://royal-elementor-addons.com/royal-elementor-kit/?ref=royal-elementor-theme-wellcome-banner#widgets" target="_blank">70+ Premium Elementor Widgets like Post and Product Grid, Slider, Menu,</a><a href="https://royal-elementor-addons.com/royal-elementor-kit/?ref=royal-elementor-theme-wellcome-banner#headerfooterbuilder"  target="_blank"> Theme & Woocommerce Shop Builder</a><a href="https://royal-elementor-addons.com/royal-elementor-kit/?ref=royal-elementor-theme-wellcome-banner#popupbuilder" target="_blank">, Popup Builder </a></strong>') ?>
				<?php esc_html_e( 'Easily Customize every fundamental part of your WordPress site with Royal Elementor Theme & Woocommerce Shop Builder.', 'royal-elementor-kit' ); ?>
				<?php echo sprintf( esc_html__( 'Including your %s etc.', 'royal-elementor-kit' ), '<strong>Header, Footer, Archives, Product Archives, Posts, Products, Default Pages, My Account, Cart, Checkout, Category, 404 Pages,</strong>' ); ?>
			</p>
			
			<div class="action-buttons">
				<a href="<?php echo esc_url(admin_url($redirect_url)); ?>" class="button button-primary" data-action="<?php echo esc_attr($action); ?>" data-freemius="<?php echo esc_attr($freemius_passed); ?>">
					<?php echo sprintf( esc_html__( 'Get Started with Templates Kit %s', 'royal-elementor-kit' ), '<span class="dashicons dashicons-arrow-right-alt"></span>' ); ?>
				</a>
			
			</div>
		</div>

		<div class="image-wrap">
			<img src="<?php echo esc_url(get_template_directory_uri()) . '/inc/activation/img/welcome-banner.png'; ?>" alt="">
		</div>

		<?php
	}

	/**
	** Reset Notice.
	*/
	public function reset_notices() {
		delete_transient( sprintf( '%s_activation_notice', get_template() ) );
	}

	/**
	** Dismissed handler
	*/
	public function dismissed_handler() {
		wp_verify_nonce( null );

		if ( isset( $_POST['notice'] ) ) {
			set_transient( sanitize_text_field( wp_unslash( $_POST['notice'] ) ), true, 0 );
		}
	}

	/**
	** Notice Enqunue Scripts
	*/
	public function notice_enqueue_scripts( $page ) {
		
		wp_enqueue_script( 'jquery' );

		ob_start();
		?>
		<script>
			jQuery(function($) {
				$( document ).on( 'click', '.rek-notice .notice-dismiss', function () {
					jQuery.post( 'ajax_url', {
						action: 'rek_dismissed_handler',
						notice: $( this ).closest( '.rek-notice' ).data( 'notice' ),
					});
					$( '.rek-notice' ).hide();
				} );
			});
		</script>
		<?php
		$script = str_replace( 'ajax_url', admin_url( 'admin-ajax.php' ), ob_get_clean() );

		wp_add_inline_script( 'jquery', str_replace( ['<script>', '</script>'], '', $script ) );
	}

	/**
	** Register scripts and styles for welcome notice.
	*/
	public function admin_enqueue_scripts( $page ) {
		// Enqueue Scripts
		wp_enqueue_script( 'welcome-notic-js', get_template_directory_uri() . '/inc/activation/js/welcome-notice.js', ['jquery'], false, true );

		wp_localize_script( 'welcome-notic-js', 'rek_localize', [
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'elementor_nonce' => wp_create_nonce( 'nonce' ),
			'royal_addons_nonce' => wp_create_nonce( 'nonce' ),
			'failed_message' => esc_html__( 'Something went wrong, contact support.', 'royal-elementor-kit' ),
		] );

		// Enqueue Styles.
		wp_enqueue_style( 'welcome-notic-css', get_template_directory_uri() . '/inc/activation/css/welcome-notice.css' );
	}

}

new REK_Welcome_Notice();