<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class REK_Rating_Notice {
    private $past_date;

    public function __construct() {
        $this->past_date = false == get_option('rek_maybe_later_time') ? strtotime( '-5 days' ) : strtotime('-15 days');

        if ( current_user_can('administrator') ) {
            if ( empty(get_option('rek_rating_dismiss_notice')) && empty(get_option('rek_rating_already_rated')) ) {
                add_action( 'admin_init', [$this, 'check_theme_install_time'] );
            }
        }

        if ( is_admin() ) {
            add_action( 'admin_head', [$this, 'enqueue_scripts' ] );
        }

        add_action( 'wp_ajax_rek_rating_dismiss_notice', [$this, 'rek_rating_dismiss_notice'] );
        add_action( 'wp_ajax_rek_rating_already_rated', [$this, 'rek_rating_already_rated'] );
        add_action( 'wp_ajax_rek_rating_maybe_later', [$this, 'rek_rating_maybe_later'] );
    }

    public function check_theme_install_time() {   
        $install_date = get_option('rek_activation_time');

        if ( false !== $install_date && $this->past_date >= $install_date ) {
            add_action( 'admin_notices', [$this, 'rek_render_rating_notice' ]);
        }
    }

    public function rek_rating_maybe_later() {
        update_option('rek_maybe_later_time', true);
        update_option('rek_activation_time', strtotime('now'));
    }
    
    public function rek_rating_dismiss_notice() {
        update_option( 'rek_rating_dismiss_notice', true );
    }

    function rek_rating_already_rated() {    
        update_option( 'rek_rating_already_rated' , true );
    }

    public function rek_render_rating_notice() {
        if ( is_admin() ) {

            echo '<div class="notice rek-rating-notice is-dismissible" style="border-left-color: #0073aa!important; display: flex; align-items: center;">
                        <div class="rek-rating-notice-logo">
                        <img class="rek-logo" src="'.get_theme_file_uri().'/inc/activation/img/logo-128x128.png">
                        </div>
                        <div>
                            <h3>Thank you for using Royal Elementor Kit Theme to build this website!</h3>
                            <p>Could you please do us a BIG favor and give it a 5-star rating on WordPress? Just to help us spread the word and boost our motivation.</p>
                            <p>
                                <a href="https://wordpress.org/support/theme/royal-elementor-kit/reviews/?filter=5" target="_blank" class="rek-you-deserve-it button button-primary">OK, you deserve it!</a>
                                <a class="rek-maybe-later"><span class="dashicons dashicons-clock"></span> Maybe Later</a>
                                <a class="rek-already-rated"><span class="dashicons dashicons-yes"></span> I Already did</a>
                            </p>
                        </div>
                </div>';
        }
    }

    public function enqueue_scripts() { 
        echo "
        <script>
        jQuery( document ).ready( function() {

            jQuery(document).on( 'click', '.rek-rating-notice .notice-dismiss', function(e) {
                e.preventDefault();
                jQuery(document).find('.rek-rating-notice').slideUp();
                jQuery.post({
                    url: ajaxurl,
                    data: {
                        action: 'rek_rating_dismiss_notice',
                    }
                })
            });

            jQuery(document).on( 'click', '.rek-maybe-later', function() {
                jQuery(document).find('.rek-rating-notice').slideUp();
                jQuery.post({
                    url: ajaxurl,
                    data: {
                        action: 'rek_rating_maybe_later',
                    }
                })
            });
        
            jQuery(document).on( 'click', '.rek-already-rated', function() {
                jQuery(document).find('.rek-rating-notice').slideUp();
                jQuery.post({
                    url: ajaxurl,
                    data: {
                        action: 'rek_rating_already_rated',
                    }
                })
            });
        });
        </script>

        <style>
            .rek-rating-notice {
              padding: 0 15px;
            }

            .rek-rating-notice-logo {
                margin-right: 20px;
                width: 100px;
                height: 100px;
            }

            .rek-rating-notice-logo img {
                max-width: 100%;
            }

            .rek-rating-notice h3 {
              margin-bottom: 0;
            }

            .rek-rating-notice p {
              margin-top: 3px;
              margin-bottom: 15px;
            }

            .rek-already-rated,
            .rek-maybe-later {
              text-decoration: none;
              margin-left: 12px;
              font-size: 14px;
              cursor: pointer;
            }

            .rek-already-rated .dashicons,
            .rek-maybe-later .dashicons {
              vertical-align: sub;
            }

            .rek-logo {
                height: 100%;
                width: auto;
            }

        </style>
        ";
    }
}

new REK_Rating_Notice();