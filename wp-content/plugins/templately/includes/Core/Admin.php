<?php
namespace Templately\Core;

use PriyoMukul\WPNotice\Notices;
use Templately\API\Login;
use Templately\Utils\Base;
use Templately\Utils\Helper;

use Templately\Core\Platform\Elementor;
use Templately\Core\Platform\Gutenberg;
use Templately\Utils\Options;

use function site_url;
use function home_url;
use function get_rest_url;
use function wp_create_nonce;
use function add_menu_page;

class Admin extends Base {

	/**
	 * Initially invoked function.
	 * Menu, Assets and maybe redirect on plugin activation is initialized.
	 */
    public function __construct() {
        add_action( 'admin_enqueue_scripts', [$this, 'scripts'] );
        add_action( 'admin_init', [$this, 'notices'] );
        add_action( 'admin_init', [$this, 'maybe_redirect_templately'] );
        add_action( 'admin_menu', [$this, 'admin_menu'] );
    }

	/**
	 * Enqueuing Assets
	 *
	 * @param  string $hook
	 * @return void
	 */
    public function scripts( $hook ){
		if ( ! in_array( $hook, array ( 'toplevel_page_templately', 'elementor', 'gutenberg' ), true ) ) {
			return;
		}

        $script_dependencies = [];
        $_localize_handle    = 'templately';
        $_current_screen     = 'templately';

        if( $hook === 'elementor' || $hook === 'gutenberg' ) {
			$_current_screen     = $hook;
            $_localize_handle = 'templately-' . $hook;
            $script_dependencies = [ $_localize_handle ];
        }

        if( $hook === 'toplevel_page_templately' ) {
			templately()->assets->enqueue( 'templately-admin', 'css/admin.css', [ 'templately' ] );
        }

        // Google Font Enqueueing
        templately()->assets->enqueue(
            'templately-dmsans',
            set_url_scheme( '//fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,400;0,500;0,700;1,400;1,500;1,700&display=swap' )
        );

        templately()->assets->enqueue( 'templately', 'js/templately.js', $script_dependencies, true );
        templately()->assets->enqueue( 'templately', 'css/templately.css', [ 'templately-dmsans' ] );

		/**
		 * @var Elementor|Gutenberg $platform
		 */
		$platform = $this->platform( $_current_screen );

        templately()->assets->localize( $_localize_handle, 'templately', [
            'url'       => home_url(),
            'site_url'  => site_url(),
            'nonce'     => wp_create_nonce( 'templately_nonce' ),
            'rest_args' => [
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'endpoint' => get_rest_url( null, 'templately/v1/' )
            ],
			'log' => defined('TEMPLATELY_DEBUG_LOG') && TEMPLATELY_DEBUG_LOG,
			'dev_mode' => defined('TEMPLATELY_DEV') && TEMPLATELY_DEV,
			"icons" => [
				'profile' => templately()->assets->icon('icons/profile.svg'),
				'warning' => templately()->assets->icon('icons/warning.png')
			],
            'promo_image'      => templately()->assets->icon('single-page-promo.png'),
            'default_image'      => templately()->assets->icon('clouds/cloud-item.svg'),
            'not_found'          => templately()->assets->icon('no-item-found.png'),
            'no_items'           => templately()->assets->icon('no-items.png'),
            'loadingImage'       => templately()->assets->icon('logos/loading-logo.gif'),
            'current_url'        => admin_url('admin.php?page=templately'),
            'is_signed'          => Login::is_signed(),
            'is_globally_signed' => Login::is_globally_signed(),
            'signed_as_global'   => Login::signed_as_global(),
            'current_screen'     => $_current_screen,
            'has_elementor_pro'  => rest_sanitize_boolean(is_plugin_active('elementor-pro/elementor-pro.php')),
            'theme'              => $_current_screen == 'templately' ? 'light' : $platform->ui_theme(),
        ] );
    }

	/**
	 * Admin notices for Review and others.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function notices(){
		global $current_screen;

		$notices = new Notices([
            'id'          => 'templately',
            'store'       => 'options',
            'storage_key' => 'notices',
            'version'     => '1.0.0',
            'lifetime'    => 3,
            'styles'      => TEMPLATELY_ASSETS . 'css/notices.css',
        ]);

		$global_user = Options::get_instance()->get('user', false, get_current_user_id());

		if( ! empty( $global_user['my_cloud']['usages'] ) ) {
			$cloud_items = intval($global_user['my_cloud']['usages']);
			if( $cloud_items >= 15 ) {
				$message = sprintf(
					__( 'Hi, %s 👋 You have saved %s in MyCloud so far ☁️ If you are enjoying <strong>Templately</strong>, would you mind taking a few seconds to give us a <strong>5-star</strong> rating on WordPress?', 'templately' ),
					$global_user['name'],
					$cloud_items . ' ' . _n( 'item', 'items', $cloud_items, 'templately' )
				);

				$_review_notice = [
					'thumbnail' => templately()->assets->icon('logos/logo.svg'),
					'html' => '<p>'. $message .'</p>',
					'links' => [
						'later' => array(
							'link' => 'https://wordpress.org/support/plugin/templately/reviews/#new-post',
							'target' => '_blank',
							'label' => __( 'Sure, you deserve it!', 'templately' ),
							'icon_class' => 'dashicons dashicons-external',
						),
						'allready' => array(
							'label' => __( 'I already did', 'templately' ),
							'icon_class' => 'dashicons dashicons-smiley',
							'attributes' => [
								'data-dismiss' => true
							],
						),
						'maybe_later' => array(
							'label' => __( 'Maybe Later', 'templately' ),
							'icon_class' => 'dashicons dashicons-calendar-alt',
							'attributes' => [
								'data-later' => true,
								'class' => 'dismiss-btn'
							],
						),
						'support' => array(
							'link' => 'https://wpdeveloper.com/support',
							'attributes' => [
								'target' => '_blank',
							],
							'label' => __( 'I need help', 'templately' ),
							'icon_class' => 'dashicons dashicons-sos',
						),
						'never_show_again' => array(
							'label' => __( 'Never show again', 'templately' ),
							'icon_class' => 'dashicons dashicons-dismiss',
							'attributes' => [
								'data-dismiss' => true
							],
						)
					]
				];

				$notices->add(
					'review',
					$_review_notice,
					[
						'start'       => $notices->strtotime('+15 day'),
						'recurrence'  => 30,
						'dismissible' => true,
						'refresh'     => TEMPLATELY_VERSION,
						'screens'     => [
							'dashboard', 'plugins', 'themes', 'edit-page',
							'edit-post', 'users', 'tools', 'options-general',
							'nav-menus'
						]
					]
				);
			}
		}

		$notices->init();
	}

	/**
	 * Adding Menu In Sidebar ( WordPress Left-side Dashboard Menu )
	 *
	 * @return void
	 */
    public function admin_menu() {
        // TODO: Role Management
        add_menu_page(
            'Templately',
            'Templately',
            'delete_posts',
            'templately',
            array($this, 'display'),
            templately()->assets->icon('logos/logo-icon.svg'),
            '58.7'
        );
    }

    public function display() {
        Helper::views('settings');
    }

    /**
     * Redirect on Active
     */
    public function maybe_redirect_templately(){
        if ( ! get_transient( 'templately_activation_redirect' ) ) {
			return;
		}
		if ( wp_doing_ajax() ) {
			return;
		}

		delete_transient( 'templately_activation_redirect' );
		if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
			return;
        }
        // Safe Redirect to Templately Page
        wp_safe_redirect( admin_url( 'admin.php?page=templately' ) );
        exit;
    }

    /**
     * If Elementor doesn't exists.
     *
     * @return void
     */
    public static function has_no_elementor() {
        $plugin_url = \wp_nonce_url(\self_admin_url('update.php?action=install-plugin&amp;plugin=elementor'), 'install-plugin_elementor');
        $button_text = 'Install Elementor';
        if (isset(Helper::get_plugins()['elementor/elementor.php'])) {
            $plugin_url = \wp_nonce_url('plugins.php?action=activate&amp;plugin=elementor/elementor.php', 'activate-plugin_elementor/elementor.php');
            $button_text = 'Activate Elementor';
        }
        $output = '<div class="notice notice-error">';
        $output .= sprintf(
            "<p><strong>%s</strong> %s <strong>%s</strong> %s &nbsp;&nbsp;<a  class='button-primary' href='%s'>%s</a></p>",
            __('Templately', 'templately'),
            __('requires', 'templately'),
            __('Elementor', 'templately'),
            __('plugin to be installed and activated. Please install Elementor to continue.', 'templately'),
            esc_url( $plugin_url ),
            __($button_text, 'templately')
        );
        $output .= '</div>';
        echo $output;
    }

    public function header(){
        Helper::views('header');
    }
}