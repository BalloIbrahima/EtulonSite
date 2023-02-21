<?php

namespace FluentForm\App\Modules\Component;

use FluentForm\App\Helpers\Helper;
use FluentForm\App\Modules\Acl\Acl;
use FluentForm\App\Services\FormBuilder\EditorShortcodeParser;
use FluentForm\App\Services\FormBuilder\Notifications\EmailNotificationActions;
use FluentForm\Framework\Foundation\Application;
use FluentForm\Framework\Helpers\ArrayHelper;

class Component
{
    /**
     * FluentForm\Framework\Foundation\Application
     *
     * @var $app
     */
    protected $app = null;

    /**
     * Determine whether to register Elementor PopUp handler.
     *
     * @var bool
     */
    protected $elementorPopUpHandler = false;

    /**
     * Biuld the instance of this class
     *
     * @param \FluentForm\Framework\Foundation\Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function registerScripts()
    {
        $app = $this->app;

        // We will just register the scripts here. We will not load any scripts from here

        $fluentFormPublicCss = $app->publicUrl('css/fluent-forms-public.css');
        $fluentFormPublicDefaultCss = $app->publicUrl('css/fluentform-public-default.css');

        if (is_rtl()) {
            $fluentFormPublicCss = $app->publicUrl('css/fluent-forms-public-rtl.css');
            $fluentFormPublicDefaultCss = $app->publicUrl('css/fluentform-public-default-rtl.css');
        }

        wp_register_style(
            'fluent-form-styles',
            $fluentFormPublicCss,
            [],
            FLUENTFORM_VERSION
        );

        wp_register_style(
            'fluentform-public-default',
            $fluentFormPublicDefaultCss,
            [],
            FLUENTFORM_VERSION
        );

        wp_register_script(
            'fluent-form-submission',
            $app->publicUrl('js/form-submission.js'),
            ['jquery'],
            FLUENTFORM_VERSION,
            true
        );

        wp_register_script(
            'fluentform-advanced',
            $app->publicUrl('js/fluentform-advanced.js'),
            ['jquery'],
            FLUENTFORM_VERSION,
            true
        );

        // Date Pickckr Style
        //fix for essential addon event picker conflict
        if (!wp_script_is('flatpickr', 'registered')) {
            wp_register_style(
                'flatpickr',
                $app->publicUrl('libs/flatpickr/flatpickr.min.css')
            );
        }
        // Date Pickckr Script
        wp_register_script(
            'flatpickr',
            $app->publicUrl('libs/flatpickr/flatpickr.min.js'),
            ['jquery'],
            false,
            true
        );

        wp_register_script(
            'choices',
            $app->publicUrl('libs/choices/choices.min.js'),
            [],
            '9.0.1',
            true
        );

        wp_register_style(
            'ff_choices',
            $app->publicUrl('css/choices.css'),
            [],
            FLUENTFORM_VERSION
        );

        wp_register_script(
            'form-save-progress',
            $app->publicUrl('js/form-save-progress.js'),
            ['jquery'],
            FLUENTFORM_VERSION,
            true
        );

        do_action('fluentform_scripts_registered');

        $this->maybeLoadFluentFormStyles();
    }

    protected function maybeLoadFluentFormStyles()
    {
        global $post;

        $postId = isset($post->ID) ? $post->ID : false;
        if (!$postId) {
            return;
        }
        $fluentFormIds = get_post_meta($postId, '_has_fluentform', true);
        $hasFluentformMeta = is_a($post, 'WP_Post') && $fluentFormIds;

        if ($hasFluentformMeta || apply_filters('fluentform_load_styles', false, $post)) {
            wp_enqueue_style('fluent-form-styles');
            wp_enqueue_style('fluentform-public-default');
            do_action('fluentform_pre_load_scripts', $post);
            wp_enqueue_script('fluent-form-submission');
        }
    }

    /**
     * Get all the available components
     *
     * @return void
     *
     * @throws \Exception
     * @throws \FluentForm\Framework\Exception\UnResolveableEntityException
     */
    public function index()
    {
        $formId = intval($this->app->request->get('formId'));

        $components = $this->app->make('components');

        $this->app->doAction('fluent_editor_init', $components);

        $editorComponents = $components->sort()->toArray();
        $editorComponents = apply_filters('fluent_editor_components', $editorComponents, $formId);

        $countries = $this->app->load($this->app->appPath('Services/FormBuilder/CountryNames.php'));

        wp_send_json_success([
            'countries'           => $countries,
            'components'          => $editorComponents,
            'disabled_components' => $this->getDisabledComponents(),
        ]);
    }

    /**
     * Get disabled components
     *
     * @return array
     */
    private function getDisabledComponents()
    {
        $isReCaptchaDisabled = !get_option('_fluentform_reCaptcha_keys_status', false);
        $isHCaptchaDisabled = !get_option('_fluentform_hCaptcha_keys_status', false);
        $isTurnstileDisabled = !get_option('_fluentform_turnstile_keys_status', false);

        $disabled = [
            'recaptcha' => [
                'disabled'    => $isReCaptchaDisabled,
                'title'       => __('reCaptcha', 'fluentform'),
                'description' => __('Please enter a valid API key on FluentForms->Settings->reCaptcha', 'fluentform'),
                'hidePro'     => true,
            ],
            'hcaptcha' => [
                'disabled'    => $isHCaptchaDisabled,
                'title'       => __('hCaptcha', 'fluentform'),
                'description' => __('Please enter a valid API key on FluentForms->Settings->hCaptcha', 'fluentform'),
                'hidePro'     => true,
            ],
            'turnstile' => [
                'disabled'    => $isTurnstileDisabled,
                'title'       => __('Turnstile', 'fluentform'),
                'description' => __('Please enter a valid API key on FluentForms->Settings->Turnstile', 'fluentform'),
                'hidePro'     => true,
            ],
            'input_image' => [
                'disabled'    => true,
                'title'       => __('Image Upload', 'fluentform'),
                'description' => __('Image Upload is not available with the free version. Please upgrade to pro to get all the advanced features.', 'fluentform'),
                'image'       => '',
                'video'       => 'https://www.youtube.com/embed/Yb3FSoZl9Zg',
            ],
            'input_file' => [
                'disabled'    => true,
                'title'       => __('File Upload', 'fluentform'),
                'description' => __('File Upload is not available with the free version. Please upgrade to pro to get all the advanced features.', 'fluentform'),
                'image'       => '',
                'video'       => 'https://www.youtube.com/embed/bXbTbNPM_4k',
            ],
            'shortcode' => [
                'disabled'    => true,
                'title'       => __('Shortcode', 'fluentform'),
                'description' => __('Shortcode is not available with the free version. Please upgrade to pro to get all the advanced features.', 'fluentform'),
                'image'       => '',
                'video'       => 'https://www.youtube.com/embed/op3mEQxX1MM',
            ],
            'action_hook' => [
                'disabled'    => true,
                'title'       => __('Action Hook', 'fluentform'),
                'description' => __('Action Hook is not available with the free version. Please upgrade to pro to get all the advanced features.', 'fluentform'),
                'image'       => $this->app->publicUrl('img/pro-fields/Action Hook.png'),
                'video'       => '',
            ],
            'form_step' => [
                'disabled'    => true,
                'title'       => __('Form Step', 'fluentform'),
                'description' => __('Form Step is not available with the free version. Please upgrade to pro to get all the advanced features.', 'fluentform'),
                'image'       => '',
                'video'       => 'https://www.youtube.com/embed/VQTWnM6BbRU',
            ],
        ];

        if (!defined('FLUENTFORMPRO')) {
            $disabled['ratings'] = [
                'disabled'    => true,
                'title'       => __('Ratings', 'fluentform'),
                'description' => __('Ratings is not available with the free version. Please upgrade to pro to get all the advanced features.', 'fluentform'),
                'image'       => '',
                'video'       => 'https://www.youtube.com/embed/YGdkNspMaEs',
            ];
            $disabled['tabular_grid'] = [
                'disabled'    => true,
                'title'       => __('Checkable Grid', 'fluentform'),
                'description' => __('Checkable Grid is not available with the free version. Please upgrade to pro to get all the advanced features.', 'fluentform'),
                'image'       => '',
                'video'       => 'https://www.youtube.com/embed/ayI3TzXXANA',
            ];
            $disabled['chained_select'] = [
                'disabled'    => true,
                'title'       => __('Chained Select Field', 'fluentform'),
                'description' => __('Chained Select Field is not available with the free version. Please upgrade to pro to get all the advanced features.', 'fluentform'),
                'image'       => $this->app->publicUrl('img/pro-fields/Chained Select Field.png'),
                'video'       => '',
            ];
            $disabled['phone'] = [
                'disabled'    => true,
                'title'       => 'Phone Field',
                'description' => __('Phone Field is not available with the free version. Please upgrade to pro to get all the advanced features.', 'fluentform'),
                'image'       => $this->app->publicUrl('img/pro-fields/Phone Field.png'),
                'video'       => '',
            ];
            $disabled['rich_text_input'] = [
                'disabled'    => true,
                'title'       => __('Rich Text Input', 'fluentform'),
                'description' => __('Rich Text Input is not available with the free version. Please upgrade to pro to get all the advanced features.', 'fluentform'),
                'image'       => $this->app->publicUrl('img/pro-fields/Rich Text Input.png'),
                'video'       => '',
            ];
            $disabled['save_progress_button'] = [
                'disabled'    => true,
                'title'       => __('Save & Resume', 'fluentform'),
                'description' => __('Save & Resume is not available with the free version. Please upgrade to pro to get all the advanced features.', 'fluentform'),
                'image'       => $this->app->publicUrl('img/pro-fields/Save Progress Button.png'),
                'video'       => '',
            ];
            $disabled['cpt_selection'] = [
                'disabled'    => true,
                'title'       => __('Post/CPT Selection', 'fluentform'),
                'description' => __('Post/CPT Selection is not available with the free version. Please upgrade to pro to get all the advanced features.', 'fluentform'),
                'image'       => $this->app->publicUrl('img/pro-fields/Post_CPT Selection.png'),
                'video'       => '',
            ];
            $disabled['quiz_score'] = [
                'disabled'    => true,
                'title'       => __('Quiz Score', 'fluentform'),
                'description' => __('Quiz Score is not available with the free version. Please upgrade to pro to get all the advanced features.', 'fluentform'),
                'image'       => '',
                'video'       => 'https://www.youtube.com/embed/bPjDXR0y_Oo',
            ];
            $disabled['net_promoter_score'] = [
                'disabled'    => true,
                'title'       => __('Net Promoter Score', 'fluentform'),
                'description' => __('Net Promoter Score is not available with the free version. Please upgrade to pro to get all the advanced features.', 'fluentform'),
                'image'       => $this->app->publicUrl('img/pro-fields/Net Promoter Score.png'),
                'video'       => '',
            ];
            $disabled['repeater_field'] = [
                'disabled'    => true,
                'title'       => __('Repeat Field', 'fluentform'),
                'description' => __('Repeat Field is not available with the free version. Please upgrade to pro to get all the advanced features.', 'fluentform'),
                'image'       => '',
                'video'       => 'https://www.youtube.com/embed/BXo9Sk-OLnQ',
            ];
            $disabled['rangeslider'] = [
                'disabled'    => true,
                'title'       => __('Range Slider', 'fluentform'),
                'description' => __('Range Slider is not available with the free version. Please upgrade to pro to get all the advanced features.', 'fluentform'),
                'image'       => '',
                'video'       => 'https://www.youtube.com/embed/RaY2VcPWk6I',
            ];
            $disabled['color-picker'] = [
                'disabled'    => true,
                'title'       => __('Color Picker', 'fluentform'),
                'description' => __('Color Picker is not available with the free version. Please upgrade to pro to get all the advanced features.', 'fluentform'),
                'image'       => $this->app->publicUrl('img/pro-fields/Color Picker.png'),
                'video'       => '',
            ];
            $disabled['multi_payment_component'] = [
                'disabled'    => true,
                'is_payment'  => true,
                'title'       => __('Payment Field', 'fluentform'),
                'description' => __('Payment Field is not available with the free version. Please upgrade to pro to get all the advanced features.', 'fluentform'),
                'image'       => $this->app->publicUrl('img/pro-fields/Payment Field.png'),
                'video'       => '',
            ];
            $disabled['custom_payment_component'] = [
                'disabled'    => true,
                'is_payment'  => true,
                'title'       => 'Custom Payment Amount',
                'description' => __('Custom Payment Amount is not available with the free version. Please upgrade to pro to get all the advanced features.', 'fluentform'),
                'image'       => $this->app->publicUrl('img/pro-fields/Custom Payment Amount.png'),
                'video'       => '',
            ];
            $disabled['subscription_payment_component'] = [
                'disabled'    => true,
                'is_payment'  => true,
                'title'       => __('Subscription Field', 'fluentform'),
                'description' => __('Subscription Field is not available with the free version. Please upgrade to pro to get all the advanced features.', 'fluentform'),
                'image'       => $this->app->publicUrl('img/pro-fields/Subscription Field.png'),
                'video'       => '',
            ];
            $disabled['item_quantity_component'] = [
                'disabled'    => true,
                'is_payment'  => true,
                'title'       => __('Item Quantity', 'fluentform'),
                'description' => __('Item Quantity is not available with the free version. Please upgrade to pro to get all the advanced features.', 'fluentform'),
                'image'       => $this->app->publicUrl('img/pro-fields/Item Quantity.png'),
                'video'       => '',
            ];
            $disabled['payment_method'] = [
                'disabled'    => true,
                'is_payment'  => true,
                'title'       => __('Payment Method', 'fluentform'),
                'description' => __('Payment Method is not available with the free version. Please upgrade to pro to get all the advanced features.', 'fluentform'),
                'image'       => $this->app->publicUrl('img/pro-fields/Payment Method.png'),
                'video'       => '',
            ];
            $disabled['payment_summary_component'] = [
                'disabled'    => true,
                'is_payment'  => true,
                'title'       => __('Payment Summary', 'fluentform'),
                'description' => __('Payment Summary is not available with the free version. Please upgrade to pro to get all the advanced features.', 'fluentform'),
                'image'       => $this->app->publicUrl('img/pro-fields/Payment Summary.png'),
                'video'       => '',
            ];
            $disabled['payment_coupon'] = [
                'disabled'    => true,
                'title'       => __('Coupon', 'fluentform'),
                'description' => __('Coupon is not available with the free version. Please upgrade to pro to get all the advanced features.', 'fluentform'),
                'image'       => $this->app->publicUrl('img/pro-fields/Coupon.png'),
                'video'       => '',
            ];
        }
        return $this->app->applyFilters('fluentform_disabled_components', $disabled);
    }

    /**
     * Get available shortcodes for editor
     *
     * @return void
     *
     * @throws \Exception
     */
    public function getEditorShortcodes()
    {
        $editor_shortcodes = fluentFormEditorShortCodes();
        wp_send_json_success(['shortcodes' => $editor_shortcodes], 200);
    }

    /**
     * Get all available shortcodes for editor
     *
     * @return void
     *
     * @throws \Exception
     */
    public function getAllEditorShortcodes()
    {
        wp_send_json(fluentFormGetAllEditorShortCodes(
            $this->app->request->get('formId')
        ), 200);
    }

    /**
     * Register the form renderer shortcode
     *
     * @return void
     */
    public function addFluentFormShortCode()
    {
        add_action('wp_enqueue_scripts', [$this, 'registerScripts'], 9);

        $this->app->addShortCode('fluentform', function ($atts, $content) {
            $shortcodeDefaults = apply_filters('fluentform_shortcode_defaults', [
                'id'                 => null,
                'title'              => null,
                'css_classes'        => '',
                'permission'         => '',
                'type'               => 'classic',
                'permission_message' => __('Sorry, You do not have permission to view this form', 'fluentform'),
            ], $atts);

            $atts = shortcode_atts($shortcodeDefaults, $atts);

            return $this->renderForm($atts);
        });

        $this->app->addShortCode('fluentform_info', function ($atts) {
            $shortcodeDefaults = apply_filters('fluentform_info_shortcode_defaults', [
                'id'                 => null, // This is the form id
                'info'               => 'submission_count', // submission_count | created_at | updated_at | payment_total
                'status'             => 'all', // get submission cound of a particular entry status favourites | unread | read
                'with_trashed'       => 'no', // yes | no
                'substract_from'     => 0, // [fluentform_info id="2" info="submission_count" substract_from="20"]
                'hide_on_zero'       => 'no',
                'payment_status'     => 'all', // it can be all / specific payment status
                'currency_formatted' => 'yes',
                'date_format'        => '',
            ], $atts);

            $atts = shortcode_atts($shortcodeDefaults, $atts);
            $formId = $atts['id'];
            $form = wpFluent()->table('fluentform_forms')->find($formId);

            if (!$form) {
                return '';
            }

            if ('submission_count' == $atts['info']) {
                $countQuery = wpFluent()->table('fluentform_submissions')
                    ->where('form_id', $formId);

                if ('trashed' != $atts['status'] && 'no' == $atts['with_trashed']) {
                    $countQuery = $countQuery->where('status', '!=', 'trashed');
                }

                if ('all' == $atts['status']) {
                    // ...
                } elseif ('favourites' == $atts['status']) {
                    $countQuery = $countQuery->where('is_favourite', '=', 1);
                } else {
                    $countQuery = $countQuery->where('status', '=', sanitize_key($atts['status']));
                }
                if ($atts['payment_status'] && defined('FLUENTFORMPRO') && 'all' != $atts['payment_status']) {
                    $countQuery = $countQuery->where('payment_status', '=', sanitize_key($atts['payment_status']));
                }

                $total = $countQuery->count();

                if ($atts['substract_from']) {
                    $total = intval($atts['substract_from']) - $total;
                }

                if ('yes' == $atts['hide_on_zero'] && !$total || $total < 0) {
                    return '';
                }

                return $total;
            } elseif ('created_at' == $atts['info']) {
                if ($atts['date_format']) {
                    $dateFormat = $atts['date_format'];
                } else {
                    $dateFormat = get_option('date_format') . ' ' . get_option('time_format');
                }
                return date($dateFormat, strtotime($form->created_at));
            } elseif ('updated_at' == $atts['info']) {
                if ($atts['date_format']) {
                    $dateFormat = $atts['date_format'];
                } else {
                    $dateFormat = get_option('date_format') . ' ' . get_option('time_format');
                }
                return date($dateFormat, strtotime($form->updated_at));
            } elseif ('payment_total' == $atts['info']) {
                if (!defined('FLUENTFORMPRO')) {
                    return '';
                }

                global $wpdb;
                $countQuery = wpFluent()
                    ->table('fluentform_submissions')
                    ->select(wpFluent()->raw('SUM(payment_total) as payment_total'))
                    ->where('form_id', $formId);

                if ('trashed' != $atts['status'] && 'no' == $atts['with_trashed']) {
                    $countQuery = $countQuery->where('status', '!=', 'trashed');
                }

                if ('all' == $atts['status']) {
                    // ...
                } elseif ('favourites' == $atts['status']) {
                    $countQuery = $countQuery->where('is_favourite', '=', 1);
                } else {
                    $countQuery = $countQuery->where('status', '=', sanitize_key($atts['status']));
                }

                if ('all' == $atts['payment_status']) {
                    // ...
                } elseif ($atts['payment_status']) {
                    $countQuery = $countQuery->where('payment_status', '=', sanitize_key($atts['payment_status']));
                }

                $row = $countQuery->first();

                $total = 0;
                if ($row) {
                    $total = $row->payment_total;
                }

                if ($atts['substract_from']) {
                    $total = intval($atts['substract_from'] * 100) - $total;
                }

                if ('yes' == $atts['hide_on_zero'] && !$total) {
                    return '';
                }

                if ('yes' == $atts['currency_formatted']) {
                    $currency = \FluentFormPro\Payments\PaymentHelper::getFormCurrency($formId);
                    return \FluentFormPro\Payments\PaymentHelper::formatMoney($total, $currency);
                }

                if (!$total) {
                    return 0;
                }

                return $total / 100;
            }

            return '';
        });

        $this->app->addShortCode('ff_get', function ($atts) {
            $atts = shortcode_atts([
                'param' => '',
            ], $atts);
            
            $value = $this->app->request->get($atts['param']);

            if ($atts['param'] && $value) {
                if (is_array($value)) {
                    return implode(', ', $value);
                }
                return esc_html($value);
            }
            return '';
        });
    }

    public function renderForm($atts)
    {
        $form_id = $atts['id'];
        $query = wpFluent()->table('fluentform_forms');
        
        if (! $this->app->request->get('preview_id')) {
            $query->where('status', 'published');
        }
        
        if ($form_id) {
            $form = $query->find($form_id);
        } elseif ($formTitle = $atts['title']) {
            $form = $query->where('title', $formTitle)->first();
        } else {
            return '';
        }

        if (!$form) {
            return '';
        }

        if (!empty($atts['permission'])) {
            if (!current_user_can($atts['permission'])) {
                return "<div id='ff_form_{$form->id}' class='ff_form_not_render'>{$atts['permission_message']}</div>";
            }
        }

        if (is_feed()) {
            global $post;
            $feedText = sprintf(__('The form can be filled in the actual <a href="%s">website url</a>.', 'fluentform'), get_permalink($post));
            $feedText = apply_filters('fluentform_shortcode_feed_text', $feedText, $form);
            return $feedText;
        }

        $formSettings = wpFluent()
            ->table('fluentform_form_meta')
            ->where('form_id', $form_id)
            ->where('meta_key', 'formSettings')
            ->first();

        if (!$formSettings) {
            return '';
        }

        $form->fields = json_decode($form->form_fields, true);

        if (!$form->fields['fields']) {
            return '';
        }

        $form->settings = json_decode($formSettings->value, true);
        $form = $this->app->applyFilters('fluentform_rendering_form', $form);

        $isRenderable = [
            'status'  => true,
            'message' => '',
        ];

        $isRenderable = $this->app->applyFilters('fluentform_is_form_renderable', $isRenderable, $form);

        if (is_array($isRenderable) && !$isRenderable['status']) {
            return "<div id='ff_form_{$form->id}' class='ff_form_not_render'>{$isRenderable['message']}</div>";
        }

        $instanceCssClass = Helper::getFormInstaceClass($form->id);

        $form->instance_css_class = $instanceCssClass;
        $form->instance_index = Helper::$formInstance;

        if ('conversational' == $atts['type']) {
            $this->addInlineVars();
            return (new \FluentForm\App\Services\FluentConversational\Classes\Form())->renderShortcode($form);
        }

        $formBuilder = $this->app->make('formBuilder');

        $cssClasses = trim($instanceCssClass . ' ff-form-loading');

        $output = $formBuilder->build($form, $cssClasses, $instanceCssClass, $atts);

        $output = $this->replaceEditorSmartCodes($output, $form);

        if (!wp_script_is('fluent-form-submission', 'registered')) {
            $this->registerScripts();
        }

        wp_enqueue_style('fluent-form-styles');
        if (apply_filters('fluentform_load_default_public', true, $form)) {
            wp_enqueue_style('fluentform-public-default');
        }
        /*
         * We will load fluentform-advanced if the form has certain fields or feature
         */
        $this->maybeHasAdvandedFields($form, $formBuilder);
        wp_enqueue_script('fluent-form-submission');

        $stepText = __('Step %activeStep% of %totalStep% - %stepTitle%', 'fluentform');
        $stepText = apply_filters('fluentform_step_string', $stepText);
        $vars = apply_filters('fluentform_global_form_vars', [
            'ajaxUrl'               => admin_url('admin-ajax.php'),
            'forms'                 => [],
            'step_text'             => $stepText,
            'is_rtl'                => is_rtl(),
            'date_i18n'             => $this->getDatei18n(),
            'pro_version'           => (defined('FLUENTFORMPRO_VERSION')) ? FLUENTFORMPRO_VERSION : false,
            'fluentform_version'    => FLUENTFORM_VERSION,
            'force_init'            => false,
            'stepAnimationDuration' => 350,
            'upload_completed_txt'  => __('100% Completed', 'fluentform'),
            'upload_start_txt'      => __('0% Completed', 'fluentform'),
            'uploading_txt'         => __('Uploading', 'fluentform'),
            'choice_js_vars'        => [
                'noResultsText'  => __('No results found', 'fluentform'),
                'loadingText'    => __('Loading...', 'fluentform'),
                'noChoicesText'  => __('No choices to choose from', 'fluentform'),
                'itemSelectText' => __('Press to select', 'fluentform'),
                'maxItemText'    => __('Only %%maxItemCount%% options can be added', 'fluentform'),
            ],
            'input_mask_vars' => [
                'clearIfNotMatch' => false,
            ],
        ]);

        wp_localize_script('fluent-form-submission', 'fluentFormVars', $vars);

        $formSettings = $form->settings;

        $formSettings = ArrayHelper::only($formSettings, ['layout', 'id']);

        $formSettings['restrictions']['denyEmptySubmission'] = [
            'enabled' => false,
        ];

        $form_vars = [
            'id'               => $form->id,
            'settings'         => $formSettings,
            'form_instance'    => $instanceCssClass,
            'form_id_selector' => 'fluentform_' . $form->id,
            'rules'            => $formBuilder->validationRules,
        ];

        if ($conditionals = $formBuilder->conditions) {
            $form_vars['conditionals'] = $conditionals;
        }

        $form_vars = apply_filters('fluentform/form_vars_for_JS', $form_vars, $form);

        if ($form->has_payment) {
            do_action('fluentform_rendering_payment_form', $form);
        }

        $otherScripts = '';
        ob_start();
        ?>
        <script type="text/javascript">
            window.fluent_form_<?php echo esc_attr($instanceCssClass); ?> = <?php echo wp_json_encode($form_vars); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $form_vars is escaped before being passed in.?>;
            <?php if (wp_doing_ajax()): ?>
            function initFFInstance_<?php echo esc_attr($form_vars['id']); ?>() {
                if (!window.fluentFormApp) {
                    console.log('No fluentFormApp found');
                    return;
                }
                var ajax_formInstance = window.fluentFormApp(jQuery('form.<?php echo esc_attr($form_vars['form_instance']); ?>'));
                if (ajax_formInstance) {
                    ajax_formInstance.initFormHandlers();
                }
            }

            initFFInstance_<?php echo esc_attr($form_vars['id']); ?>();
            <?php endif; ?>
        </script>
        <?php
        $this->addInlineVars();
        $otherScripts .= ob_get_clean();

        if (!apply_filters('fluentform-disabled_analytics', false)) {
            if (!Acl::hasAnyFormPermission($form->id)) {
                (new \FluentForm\App\Modules\Form\Analytics($this->app))->record($form->id);
            }
        }

        return $output . $otherScripts;
    }

    /**
     * Process the output HTML to generate the default values.
     *
     * @param string    $output
     * @param \stdClass $form
     *
     * @return string
     */
    public function replaceEditorSmartCodes($output, $form)
    {
        // Get the patterns for default values from the output HTML string.
        // The example of a pattern would be for user ID: {user.ID}
        preg_match_all('/{(.*?)}/', $output, $matches);
        $patterns = array_unique($matches[0]);

        $attrDefaultValues = [];

        foreach ($patterns as $pattern) {
            // The default value for each pattern will be resolved here.
            $attrDefaultValues[$pattern] = apply_filters('fluentform_parse_default_value', $pattern, $form);
        }

        // Raising an event so that others can hook into it and modify the default values later.
        $attrDefaultValues = (array) apply_filters('fluentform_parse_default_values', $attrDefaultValues);

        if (isset($attrDefaultValues['{payment_total}'])) {
            $attrDefaultValues['{payment_total}'] = '<span class="ff_order_total"></span>';
        }

        // Finally, replace the patterns with the replacements and return the output HTML.
        return str_replace(array_keys($attrDefaultValues), array_values($attrDefaultValues), $output);
    }

    /**
     * Register renderer actions for compiling each element
     *
     * @return void
     */
    public function addRendererActions()
    {
        $actionMappings = [
            'Select@compile'        => ['fluentform_render_item_select'],
            'Rating@compile'        => ['fluentform_render_item_ratings'],
            'Address@compile'       => ['fluentform_render_item_address'],
            'Name@compile'          => ['fluentform_render_item_input_name'],
            'TextArea@compile'      => ['fluentform_render_item_textarea'],
            'DateTime@compile'      => ['fluentform_render_item_input_date'],
            'Recaptcha@compile'     => ['fluentform_render_item_recaptcha'],
            'Hcaptcha@compile'      => ['fluentform_render_item_hcaptcha'],
            'Turnstile@compile'     => ['fluentform_render_item_turnstile'],
            'Container@compile'     => ['fluentform_render_item_container'],
            'CustomHtml@compile'    => ['fluentform_render_item_custom_html'],
            'SectionBreak@compile'  => ['fluentform_render_item_section_break'],
            'SubmitButton@compile'  => ['fluentform_render_item_submit_button'],
            'SelectCountry@compile' => ['fluentform_render_item_select_country'],

            'TermsAndConditions@compile' => [
                'fluentform_render_item_terms_and_condition',
                'fluentform_render_item_gdpr_agreement',
            ],

            'TabularGrid@compile' => [
                'fluentform_render_item_tabular_grid',
            ],

            'Checkable@compile' => [
                'fluentform_render_item_input_radio',
                'fluentform_render_item_input_checkbox',
            ],

            'Text@compile' => [
                'fluentform_render_item_input_url',
                'fluentform_render_item_input_text',
                'fluentform_render_item_input_email',
                'fluentform_render_item_input_number',
                'fluentform_render_item_input_hidden',
                'fluentform_render_item_input_password',
            ],
        ];

        $path = 'FluentForm\App\Services\FormBuilder\Components\\';
        foreach ($actionMappings as $handler => $actions) {
            foreach ($actions as $action) {
                $this->app->addAction($action, function () use ($path, $handler) {
                    list($class, $method) = $this->app->parseHandler($path . $handler);
                    call_user_func_array([$class, $method], func_get_args());
                }, 10, 2);
            }
        }
    }

    /**
     * Register dynamic value shortcode parser (filter default value)
     *
     * @return void
     */
    public function addFluentFormDefaultValueParser()
    {
        $this->app->addFilter('fluentform_parse_default_value', function ($value, $form) {
            return EditorShortcodeParser::filter($value, $form);
        }, 10, 2);
    }

    /**
     * Register filter to check whether the form is renderable
     *
     * @return mixed
     */
    public function addIsRenderableFilter()
    {
        $this->app->addFilter('fluentform_is_form_renderable', function ($isRenderable, $form) {
            $checkables = ['limitNumberOfEntries', 'scheduleForm', 'requireLogin'];

            foreach ($form->settings['restrictions'] as $key => $restrictions) {
                if (in_array($key, $checkables)) {
                    $isRenderable['status'] = $this->{$key}($restrictions, $form, $isRenderable);
                    if (!$isRenderable['status']) {
                        $isRenderable['status'] = false;
                        return $isRenderable;
                    }
                }
            }

            return $isRenderable;
        }, 10, 2);
    }

    /**
     * Check if limit is set on form submits and it's valid yet
     *
     * @param array $restrictions
     *
     * @return bool
     */
    private function limitNumberOfEntries($restrictions, $form, &$isRenderable)
    {
        if (!$restrictions['enabled'] || !isset($restrictions['period']) || !isset($restrictions['numberOfEntries'])) {
            return true;
        }

        $col = 'created_at';
        $period = $restrictions['period'];
        $maxAllowedEntries = $restrictions['numberOfEntries'];

        if (!$maxAllowedEntries) {
            return true;
        }

        $query = wpFluent()->table('fluentform_submissions')
            ->where('form_id', $form->id)
            ->where('status', '!=', 'trashed');

        if ('day' == $period) {
            $year = "YEAR(`{$col}`) = YEAR(NOW())";
            $month = "MONTH(`{$col}`) = MONTH(NOW())";
            $day = "DAY(`{$col}`) = DAY(NOW())";
            $query->where(wpFluent()->raw("{$year} AND {$month} AND {$day}"));
        } elseif ('week' == $period) {
            $query->where(
                wpFluent()->raw("YEARWEEK(`{$col}`, 1) = YEARWEEK(CURDATE(), 1)")
            );
        } elseif ('month' == $period) {
            $year = "YEAR(`{$col}`) = YEAR(NOW())";
            $month = "MONTH(`{$col}`) = MONTH(NOW())";
            $query->where(wpFluent()->raw("{$year} AND {$month}"));
        } elseif ('year' == $period) {
            $query->where(wpFluent()->raw("YEAR(`{$col}`) = YEAR(NOW())"));
        } elseif ('per_user_ip' == $period) {
            $ip = $this->app->request->getIp();
            $query->where('ip', $ip);
        } elseif ('per_user_id' == $period) {
            $userId = get_current_user_id();
            if (!$userId) {
                return true;
            }
            $query->where('user_id', $userId);
        }

        $count = $query->count();

        if ($count >= $maxAllowedEntries) {
            $isRenderable['message'] = $restrictions['limitReachedMsg'];
            return false;
        }

        return true;
    }

    /**
     * Check if form has scheduled date & weekdays and open for submission
     *
     * @param array $restrictions
     *
     * @return bool
     */
    private function scheduleForm($restrictions, $form, &$isRenderable)
    {
        if (!$restrictions['enabled']) {
            return true;
        }

        $time = time();
        $start = strtotime($restrictions['start']);
        $end = strtotime($restrictions['end']);

        if ($time < $start) {
            $isRenderable['message'] = $restrictions['pendingMsg'];
            return false;
        }

        if ($time >= $end) {
            $isRenderable['message'] = $restrictions['expiredMsg'];

            return false;
        }

        $weekDayToday = date('l');   //day of the week
        $selectedWeekDays = ArrayHelper::get($restrictions, 'selectedDays');
        //skip if it was not set initially and $selectedWeekDays is null
        if ($selectedWeekDays && is_array($selectedWeekDays) && !in_array($weekDayToday, $selectedWeekDays)) {
            $isRenderable['message'] = $restrictions['expiredMsg'];
            return false;
        }

        return true;
    }

    /**
     * * Check if form requires loged in user and user is logged in
     *
     * @param array $restrictions
     *
     * @return bool
     */
    private function requireLogin($restrictions, $form, &$isRenderable)
    {
        if (!$restrictions['enabled']) {
            return true;
        }

        if (!($isLoggedIn = is_user_logged_in())) {
            $isRenderable['message'] = $restrictions['requireLoginMsg'];
        }

        return $isLoggedIn;
    }

    /**
     * Register fluentform_submission_inserted actions
     *
     * @return void
     */
    public function addFluentformSubmissionInsertedFilter()
    {
        (new EmailNotificationActions($this->app))->register();
    }

    /**
     * Add inline scripts [Add localized script using same var]
     *
     * @return void
     */
    private function addInlineVars()
    {
        if (!defined('ELEMENTOR_PRO_VERSION') || $this->elementorPopUpHandler) {
            return '';
        }

        // Previously this handler was registered for every form
        // in a single page. So multiple event handlers were
        // registered. This flag ensures the handler is
        // registered just once, since one is enough!
        $this->elementorPopUpHandler = true;

        $actionName = 'wp_footer';
        if (is_admin()) {
            $actionName = 'admin_footer';
        }

        add_action($actionName, function () {
            ?>
            <script type="text/javascript">
                <?php if (defined('ELEMENTOR_PRO_VERSION')): ?>

                window.addEventListener('elementor/popup/show', function (e) {
                    var ffForms = jQuery('#elementor-popup-modal-' + e.detail.id).find('form.frm-fluent-form');

                    /**
                     * Support conversation form in elementor popup
                     * No regular form found, check for conversational form
                     */
                    if (!ffForms.length) {
                        const elements = document.getElementsByClassName('ffc_conv_form');
                        if (elements.length) {
                            let jsEvent = new CustomEvent('ff-elm-conv-form-event', {
                                detail: elements
                            });
                            document.dispatchEvent(jsEvent);
                        }
                    }
                    if (ffForms.length) {
                        jQuery.each(ffForms, function(index, ffForm) {
                            jQuery(ffForm).trigger('reInitExtras');
                            jQuery(document).trigger('ff_reinit', [ffForm]);
                        });
                    }
                });
                <?php endif; ?>
            </script>
            <?php
        }, 999);
        return '';
    }

    public static function getDatei18n()
    {
        $i18n = [
            'previousMonth' => __('Previous Month', 'fluentform'),
            'nextMonth'     => __('Next Month', 'fluentform'),
            'months'        => [
                'shorthand' => [
                    __('Jan', 'fluentform'),
                    __('Feb', 'fluentform'),
                    __('Mar', 'fluentform'),
                    __('Apr', 'fluentform'),
                    __('May', 'fluentform'),
                    __('Jun', 'fluentform'),
                    __('Jul', 'fluentform'),
                    __('Aug', 'fluentform'),
                    __('Sep', 'fluentform'),
                    __('Oct', 'fluentform'),
                    __('Nov', 'fluentform'),
                    __('Dec', 'fluentform'),
                ],
                'longhand' => [
                    __('January', 'fluentform'),
                    __('February', 'fluentform'),
                    __('March', 'fluentform'),
                    __('April', 'fluentform'),
                    __('May', 'fluentform'),
                    __('June', 'fluentform'),
                    __('July', 'fluentform'),
                    __('August', 'fluentform'),
                    __('September', 'fluentform'),
                    __('October', 'fluentform'),
                    __('November', 'fluentform'),
                    __('December', 'fluentform'),
                ],
            ],
            'weekdays' => [
                'longhand' => [
                    __('Sunday', 'fluentform'),
                    __('Monday', 'fluentform'),
                    __('Tuesday', 'fluentform'),
                    __('Wednesday', 'fluentform'),
                    __('Thursday', 'fluentform'),
                    __('Friday', 'fluentform'),
                    __('Saturday', 'fluentform'),
                ],
                'shorthand' => [
                    __('Sun', 'fluentform'),
                    __('Mon', 'fluentform'),
                    __('Tue', 'fluentform'),
                    __('Wed', 'fluentform'),
                    __('Thu', 'fluentform'),
                    __('Fri', 'fluentform'),
                    __('Sat', 'fluentform'),
                ],
            ],
            'daysInMonth' => [
                31,
                28,
                31,
                30,
                31,
                30,
                31,
                31,
                30,
                31,
                30,
                31,
            ],
            'rangeSeparator'   => __(' to ', 'fluentform'),
            'weekAbbreviation' => __('Wk', 'fluentform'),
            'scrollTitle'      => __('Scroll to increment', 'fluentform'),
            'toggleTitle'      => __('Click to toggle', 'fluentform'),
            'amPM'             => [
                __('AM', 'fluentform'),
                __('PM', 'fluentform'),
            ],
            'yearAriaLabel' => __('Year', 'fluentform'),
        ];

        return apply_filters('fluentform/date_i18n', $i18n);
    }

    protected function maybeHasAdvandedFields($form, $formBuilder)
    {
        $advancedFields = [
            'step_start',
            'repeater_field',
            'ratings',
            'form_step',
            'input_file',
            'input_image',
            'net_promoter_score',
            'featured_image',
        ];

        if ($formBuilder->conditions || array_intersect($formBuilder->fieldLists, $advancedFields)) {
            wp_enqueue_script('fluentform-advanced');
        }
    }

    public function registerInputSanitizers()
    {
        add_filter('fluentform_input_data_input_number', [$this, 'getNumericInputValue'], 10, 2);
        add_filter('fluentform_input_data_custom_payment_component', [$this, 'getNumericInputValue'], 10, 2);
    }

    public function getNumericInputValue($value, $field)
    {
        $formatter = ArrayHelper::get($field, 'raw.settings.numeric_formatter');
        if (!$formatter) {
            return $value;
        }
        return Helper::getNumericValue($value, $formatter);
    }
}
