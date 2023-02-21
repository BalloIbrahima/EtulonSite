<?php

use FluentForm\App\Helpers\Helper;
use FluentForm\App\Modules\Component\Component;

/**
 * Declare common actions/filters/shortcodes
 */

/**
 * App instance
 *
 * @var $app \FluentForm\Framework\Foundation\Application
 */

add_action('save_post', function ($post_id) use ($app) {
    $post_content = $app->request->get('post_content');
    if ($post_content) {
        $post_content = wp_kses_post(wp_unslash($post_content));
    } else {
        $post = get_post($post_id);
        $post_content = $post->post_content;
    }

    $shortcodeIds = Helper::getShortCodeIds(
        $post_content,
        'fluentform',
        'id'
    );

    $shortcodeModalIds = Helper::getShortCodeIds(
        $post_content,
        'fluentform_modal',
        'form_id'
    );

    if ($shortcodeModalIds) {
        $shortcodeIds = array_merge($shortcodeIds, $shortcodeModalIds);
    }

    if ($shortcodeIds) {
        update_post_meta($post_id, '_has_fluentform', $shortcodeIds);
    } elseif (get_post_meta($post_id, '_has_fluentform', true)) {
        update_post_meta($post_id, '_has_fluentform', []);
    }
});

$component = new Component($app);
$component->addRendererActions();
$component->addFluentFormShortCode();
$component->addFluentFormDefaultValueParser();

$component->addFluentformSubmissionInsertedFilter();
$component->addIsRenderableFilter();
$component->registerInputSanitizers();

add_action('wp', function () use ($app) {
    // @todo: We will remove the fluentform_pages check from April 2021
    $fluentFormPages = $app->request->get('fluent_forms_pages') || $app->request->get('fluentform_pages');

    if ($fluentFormPages) {
        add_action('wp_enqueue_scripts', function () use ($app) {
            wp_enqueue_script('jquery');
            wp_enqueue_script(
                'fluent_forms_global',
                $app->publicUrl('js/fluent_forms_global.js'),
                ['jquery'],
                FLUENTFORM_VERSION,
                true
            );
            wp_localize_script('fluent_forms_global', 'fluent_forms_global_var', [
                'fluent_forms_admin_nonce' => wp_create_nonce('fluent_forms_admin_nonce'),
                'ajaxurl'                  => admin_url('admin-ajax.php'),
            ]);
            wp_enqueue_style('fluent-form-styles');
            $form = wpFluent()->table('fluentform_forms')->find(intval($app->request->get('preview_id')));
            if (apply_filters('fluentform_load_default_public', true, $form)) {
                wp_enqueue_style('fluentform-public-default');
            }
            wp_enqueue_script('fluent-form-submission');
            wp_enqueue_style('fluent-form-preview', $app->publicUrl('css/preview.css'));
        });

        (new \FluentForm\App\Modules\ProcessExteriorModule())->handleExteriorPages();
    }
}, 1);

$elements = [
    'select',
    'input_checkbox',
    'input_radio',
    'address',
    'select_country',
    'gdpr_agreement',
    'terms_and_condition',
    'multi_payment_component',
];

foreach ($elements as $element) {
    $event = 'fluentform_response_render_' . $element;
    $app->addFilter($event, function ($response, $field, $form_id, $isLabel = false) {
        $element = $field['element'];

        if ('address' == $element && !empty($response->country)) {
            $countryList = getFluentFormCountryList();
            if (isset($countryList[$response->country])) {
                $response->country = $countryList[$response->country];
            }
        }

        if ('select_country' == $element) {
            $countryList = getFluentFormCountryList();
            if (isset($countryList[$response])) {
                $response = $countryList[$response];
            }
        }

        if (in_array($field['element'], array('gdpr_agreement', 'terms_and_condition'))) {
            if (!empty($response) && $response == 'on') {
                $response = __('Accepted', 'fluentform');
            } else {
                $response = __('Declined', 'fluentform');
            }
        }

        if ($response && $isLabel && in_array($element, ['select', 'input_radio']) && !is_array($response)) {
            if (!isset($field['options'])) {
                $field['options'] = [];
                foreach (\FluentForm\Framework\Helpers\ArrayHelper::get($field, 'raw.settings.advanced_options', []) as $option) {
                    $field['options'][$option['value']] = $option['label'];
                }
            }
            if (isset($field['options'][$response])) {
                return $field['options'][$response];
            }
        }

        if (in_array($element, ['select', 'input_checkbox', 'multi_payment_component']) && is_array($response)) {
            return \FluentForm\App\Modules\Form\FormDataParser::formatCheckBoxValues($response, $field, $isLabel);
        }

        return \FluentForm\App\Modules\Form\FormDataParser::formatValue($response);
    }, 10, 4);
}

$app->addFilter('fluentform_response_render_textarea', function ($value, $field, $formId, $isHtml) {
    $value = $value ? nl2br($value) : $value;

    if (!$isHtml || !$value) {
        return $value;
    }
    return '<span style="white-space: pre-line">' . $value . '</span>';
}, 10, 4);

$app->addFilter('fluentform_response_render_input_file', function ($response, $field, $form_id, $isHtml = false) {
    return \FluentForm\App\Modules\Form\FormDataParser::formatFileValues($response, $isHtml, $form_id);
}, 10, 4);

$app->addFilter('fluentform_response_render_input_image', function ($response, $field, $form_id, $isHtml = false) {
    return \FluentForm\App\Modules\Form\FormDataParser::formatImageValues($response, $isHtml, $form_id);
}, 10, 4);

$app->addFilter('fluentform_response_render_input_repeat', function ($response, $field, $form_id) {
    return \FluentForm\App\Modules\Form\FormDataParser::formatRepeatFieldValue($response, $field, $form_id);
}, 10, 3);

$app->addFilter('fluentform_response_render_tabular_grid', function ($response, $field, $form_id, $isHtml = false) {
    return \FluentForm\App\Modules\Form\FormDataParser::formatTabularGridFieldValue($response, $field, $form_id, $isHtml);
}, 10, 4);

$app->addFilter('fluentform_response_render_input_name', function ($response) {
    return \FluentForm\App\Modules\Form\FormDataParser::formatName($response);
}, 10, 1);

$app->addFilter('fluentform_response_render_input_password', function ($value, $field, $formId) {
    if (Helper::shouldHidePassword($formId)) {
        $value = str_repeat('*', 6) . ' ' . __('(truncated)', 'fluentform');
    }

    return $value;
}, 10, 3);

$app->addFilter('fluentform_filter_insert_data', function ($data) {
    $settings = get_option('_fluentform_global_form_settings', false);
    if (is_array($settings) && isset($settings['misc'])) {
        if (isset($settings['misc']['isIpLogingDisabled'])) {
            if ($settings['misc']['isIpLogingDisabled']) {
                unset($data['ip']);
            }
        }
    }
    return $data;
});

// Register api response log hooks
$app->addAction(
    'fluentform_after_submission_api_response_success',
    'fluentform_after_submission_api_response_success',
    10,
    6
);

$app->addAction(
    'fluentform_after_submission_api_response_failed',
    'fluentform_after_submission_api_response_failed',
    10,
    6
);

function fluentform_after_submission_api_response_success($form, $entryId, $data, $feed, $res, $msg = '')
{
    try {
        $isDev = 'production' != wpFluentForm()->getEnv();
        if (!apply_filters('fluentform_api_success_log', $isDev, $form, $feed)) {
            return;
        }

        wpFluent()->table('fluentform_submission_meta')->insert([
            'response_id' => $entryId,
            'form_id'     => $form->id,
            'meta_key'    => 'api_log',
            'value'       => $msg,
            'name'        => $feed->formattedValue['name'],
            'status'      => 'success',
            'created_at'  => current_time('mysql'),
            'updated_at'  => current_time('mysql'),
        ]);
    } catch (Exception $e) {
        error_log($e->getMessage());
    }
}

function fluentform_after_submission_api_response_failed($form, $entryId, $data, $feed, $res, $msg = '')
{
    try {
        $isDev = 'production' != wpFluentForm()->getEnv();
        if (!apply_filters('fluentform_api_failed_log', $isDev, $form, $feed)) {
            return;
        }

        wpFluent()->table('fluentform_submission_meta')->insert([
            'response_id' => $entryId,
            'form_id'     => $form->id,
            'meta_key'    => 'api_log',
            'value'       => json_encode($res),
            'name'        => $feed->formattedValue['name'],
            'status'      => 'failed',
            'created_at'  => current_time('mysql'),
            'updated_at'  => current_time('mysql'),
        ]);
    } catch (Exception $e) {
        error_log($e->getMessage());
    }
}

$app->bindInstance(
    'fluentFormAsyncRequest',
    new \FluentForm\App\Services\WPAsync\FluentFormAsyncRequest($app),
    'FluentFormAsyncRequest',
    'FluentForm\App\Services\WPAsync\FluentFormAsyncRequest'
);

$app->addFilter('fluentform-disabled_analytics', function ($status) {
    $settings = get_option('_fluentform_global_form_settings');
    if (isset($settings['misc']['isAnalyticsDisabled']) && $settings['misc']['isAnalyticsDisabled']) {
        return true;
    }
    return $status;
});

$app->addAction('fluentform_before_form_render', function ($form) {
    do_action('fluentform_load_form_assets', $form->id);
});

$app->addAction('fluentform_load_form_assets', function ($formId) {
    // check if alreaded loaded
    if (!in_array($formId, \FluentForm\App\Helpers\Helper::$loadedForms)) {
        (new \FluentForm\App\Modules\Form\Settings\FormCssJs())->addCssJs($formId);
        \FluentForm\App\Helpers\Helper::$loadedForms[] = $formId;
        $selectedStyle = \FluentForm\App\Helpers\Helper::getFormMeta($formId, '_ff_selected_style');

        if ($selectedStyle) {
            do_action('fluentform_init_custom_stylesheet', $selectedStyle, $formId);
        }
    }
});

$app->addAction('fluentform_submission_inserted', function ($insertId, $formData, $form) use ($app) {
    $notificationManager = new \FluentForm\App\Services\Integrations\GlobalNotificationManager($app);
    $notificationManager->globalNotify($insertId, $formData, $form);
}, 10, 3);

$app->addAction('init', function () use ($app) {
    new \FluentForm\App\Services\Integrations\MailChimp\MailChimpIntegration($app);
});

$app->addAction('fluentform_form_element_start', function ($form) use ($app) {
    $honeyPot = new \FluentForm\App\Modules\Form\HoneyPot($app);
    $honeyPot->renderHoneyPot($form);
});

$app->addAction('fluentform_before_insert_submission', function ($insertData, $requestData, $form) use ($app) {
    $honeyPot = new \FluentForm\App\Modules\Form\HoneyPot($app);
    $honeyPot->verify($insertData, $requestData, $form->id);
}, 9, 3);

add_action('ff_log_data', function ($data) use ($app) {
    $dataLogger = new \FluentForm\App\Modules\Logger\DataLogger($app);
    $dataLogger->log($data);
});

// permision based filters
add_filter('fluentform_permission_callback', function ($status, $permission) {
    return (new \FluentForm\App\Modules\Acl\RoleManager())->currentUserFormFormCapability();
}, 10, 2);

// widgets
add_action('widgets_init', function () {
    register_widget('FluentForm\App\Modules\Widgets\SidebarWidgets');
});

add_action('wp', function () {
    global $post;

    if (!is_a($post, 'WP_Post')) {
        return;
    }

    $fluentFormIds = get_post_meta($post->ID, '_has_fluentform', true);

    if ($fluentFormIds && is_array($fluentFormIds)) {
        foreach ($fluentFormIds as $formId) {
            do_action('fluentform_load_form_assets', $formId);
        }
    }
});

add_filter('fluentform_validate_input_item_input_email', ['\FluentForm\App\Helpers\Helper', 'isUniqueValidation'], 10, 5);
add_filter('fluentform_validate_input_item_input_text', ['\FluentForm\App\Helpers\Helper', 'isUniqueValidation'], 10, 5);

add_filter('cron_schedules', function ($schedules) {
    $schedules['ff_every_five_minutes'] = [
        'interval' => 300,
        'display'  => esc_html__('Every 5 minutes (FluentForm)', 'fluentform'),
    ];
    return $schedules;
}, 10, 1);

add_action('fluentform_do_scheduled_tasks', 'fluentFormHandleScheduledTasks');
add_action('fluentform_do_email_report_scheduled_tasks', 'fluentFormHandleScheduledEmailReport');

add_action('ff_integration_action_result', function ($feed, $status, $note = '') {
    if (!isset($feed['scheduled_action_id']) || !$status) {
        return;
    }
    if (!$note) {
        $note = $status;
    }

    if (strlen($note) > 255) {
        if (function_exists('mb_substr')) {
            $note = mb_substr($note, 0, 251) . '...';
        } else {
            $note = substr($note, 0, 251) . '...';
        }
    }

    $actionId = intval($feed['scheduled_action_id']);
    wpFluent()->table('ff_scheduled_actions')
        ->where('id', $actionId)
        ->update([
            'status' => $status,
            'note'   => $note,
        ]);
}, 10, 3);

add_action('fluentform_global_notify_completed', function ($insertId, $form) use ($app) {
    if (strpos($form->form_fields, '"element":"input_password"') && apply_filters('fluentform_truncate_password_values', true, $form->id)) {
        // we have password
        (new \FluentForm\App\Services\Integrations\GlobalNotificationManager($app))->cleanUpPassword($insertId, $form);
    }
}, 10, 2);

/*
 * Elementor Block Init
 */

if (defined('ELEMENTOR_VERSION')) {
    new \FluentForm\App\Modules\Widgets\ElementorWidget($app);
}
/*
 * Oxygen Widget Init
 */

add_action('init', function () {
    if (class_exists('OxyEl')) {
        if (file_exists(FLUENTFORM_DIR_PATH . 'app/Modules/Widgets/OxygenWidget.php')) {
            new FluentForm\App\Modules\Widgets\OxygenWidget();
        }
    }
});

(new FluentForm\App\Services\Integrations\Slack\SlackNotificationActions($app))->register();

/*
 * Smartcode parser shortcodes
 */

add_filter('ff_will_return_html', function ($result, $integration, $key) {
    $dictionary = [
        'notifications' => ['message'],
    ];

    if (!isset($dictionary[$integration])) {
        return $result;
    }

    if (in_array($key, $dictionary[$integration])) {
        return true;
    }

    return $result;
}, 10, 3);

$app->addFilter('fluentform_response_render_input_number', function ($response, $field, $form_id, $isHtml = false) {
    if (!$response || !$isHtml) {
        return $response;
    }
    $fieldSettings = \FluentForm\Framework\Helpers\ArrayHelper::get($field, 'raw.settings');
    $formatter = \FluentForm\Framework\Helpers\ArrayHelper::get($fieldSettings, 'numeric_formatter');
    if (!$formatter) {
        return $response;
    }
    return \FluentForm\App\Helpers\Helper::getNumericFormatted($response, $formatter);
}, 10, 4);

new \FluentForm\App\Services\FormBuilder\Components\CustomSubmitButton();

if (function_exists('register_block_type')) {
    register_block_type('fluentfom/guten-block', [
        'render_callback' => function ($atts) {
            if (empty($atts['formId'])) {
                return '';
            }

            $className = \FluentForm\Framework\Helpers\ArrayHelper::get($atts, 'className');

            if ($className) {
                $classes = explode(' ', $className);
                $className = '';
                if (!empty($classes)) {
                    foreach ($classes as $class) {
                        $className .= sanitize_html_class($class) . ' ';
                    }
                }
            }
            $type = \FluentForm\App\Helpers\Helper::isConversionForm($atts['formId']) ? 'conversational' : '';
            return do_shortcode('[fluentform css_classes="' . $className . ' ff_guten_block" id="' . $atts['formId'] . '"  type="' . $type . '"]');
        },
        'attributes' => [
            'formId' => [
                'type' => 'string',
            ],
            'className' => [
                'type' => 'string',
            ],
        ],
    ]);
}

// require the CLI
if (defined('WP_CLI') && WP_CLI) {
    \WP_CLI::add_command('fluentform', '\FluentForm\App\Modules\CLI\Commands');
}
