<?php

/**
 * Declare backend actions/filters/shortcodes
 */

/*
 * Regitser All Admin Scripts but don't load it
 */

add_action('admin_init', function () use ($app) {
    (new \FluentForm\App\Modules\Registerer\Menu($app))->reisterScripts();
}, 9);

add_action('admin_enqueue_scripts', function () use ($app) {
    (new \FluentForm\App\Modules\Registerer\Menu($app))->enqueuePageScripts();
}, 10);

// Add Entries Menu
$app->addAction('ff_fluentform_form_application_view_entries', function ($form_id) use ($app) {
    (new \FluentForm\App\Modules\Entries\Entries())->renderEntries($form_id);
});

$app->addAction('fluentform_after_form_navigation', function ($form_id) use ($app) {
    (new \FluentForm\App\Modules\Registerer\Menu($app))->addCopyShortcodeButton($form_id);
    (new \FluentForm\App\Modules\Registerer\Menu($app))->addPreviewButton($form_id);
});

$app->addAction('media_buttons', function () {
    (new \FluentForm\App\Modules\EditorButtonModule())->addButton();
});

$app->addAction('fluentform_addons_page_render_fluentform_add_ons', function () {
    (new \FluentForm\App\Modules\AddOnModule())->showFluentAddOns();
});

// This is temp, we will remove this after 2-3 versions.
add_filter('pre_set_site_transient_update_plugins', function ($updates) {
    if (!empty($updates->response['fluentformpro'])) {
        $updates->response['fluentformpro/fluentformpro.php'] = $updates->response['fluentformpro'];
        unset($updates->response['fluentformpro']);
    }
    return $updates;
}, 999, 1);

$app->addAction('fluentform_global_menu', function () use ($app) {
    $menu = new \FluentForm\App\Modules\Registerer\Menu($app);
    $menu->renderGlobalMenu();
    if ('yes' != get_option('fluentform_scheduled_actions_migrated')) {
        \FluentForm\App\Databases\Migrations\ScheduledActions::migrate();
    }

    $hookName = 'fluentform_do_scheduled_tasks';
    if (!wp_next_scheduled($hookName)) {
        wp_schedule_event(time(), 'ff_every_five_minutes', $hookName);
    }

    $emailReportHookName = 'fluentform_do_email_report_scheduled_tasks';
    if (!wp_next_scheduled($emailReportHookName)) {
        wp_schedule_event(time(), 'daily', $emailReportHookName);
    }
});

$app->addAction('wp_dashboard_setup', function () {
    $roleManager = new \FluentForm\App\Modules\Acl\Acl();

    if (!$roleManager->getCurrentUserCapability()) {
        return;
    }
    wp_add_dashboard_widget('fluentform_stat_widget', __('Fluent Forms Latest Form Submissions', 'fluentform'), function () {
        (new \FluentForm\App\Modules\DashboardWidgetModule())->showStat();
    }, 10, 1);
});

add_action('admin_init', function () {
    $disablePages = [
        'fluent_forms',
        'fluent_forms_transfer',
        'fluent_forms_settings',
        'fluent_forms_add_ons',
        'fluent_forms_docs',
        'fluent_forms_all_entries',
        'msformentries',
    ];

    $page = wpFluentForm('request')->get('page');

    if ($page && in_array($page, $disablePages)) {
        remove_all_actions('admin_notices');
    }
});

add_action('enqueue_block_editor_assets', function () use ($app) {
    wp_enqueue_script(
        'fluentform-gutenberg-block',
        $app->publicUrl('js/fluent_gutenblock.js'),
        ['wp-element', 'wp-polyfill', 'wp-i18n', 'wp-blocks', 'wp-components'],
        FLUENTFORM_VERSION
    );

    $forms = wpFluent()->table('fluentform_forms')
        ->select(['id', 'title'])
        ->orderBy('id', 'DESC')
        ->get();

    array_unshift($forms, (object) [
        'id'    => '',
        'title' => __('-- Select a form --', 'fluentform'),
    ]);

    wp_localize_script('fluentform-gutenberg-block', 'fluentform_block_vars', [
        'logo'  => $app->publicUrl('img/fluent_icon.png'),
        'forms' => $forms,
    ]);

    wp_enqueue_style(
        'fluentform-gutenberg-block',
        $app->publicUrl('css/fluent_gutenblock.css'),
        ['wp-edit-blocks']
    );
});

add_action('wp_print_scripts', function () {
    if (is_admin()) {
        if (\FluentForm\App\Helpers\Helper::isFluentAdminPage()) {
            $option = get_option('_fluentform_global_form_settings');
            $isSkip = 'no' == \FluentForm\Framework\Helpers\ArrayHelper::get($option, 'misc.noConflictStatus');
            $isSkip = apply_filters('fluentform_skip_no_conflict', $isSkip);

            if ($isSkip) {
                return;
            }

            global $wp_scripts;
            if (!$wp_scripts) {
                return;
            }

            $pluginUrl = plugins_url();
            foreach ($wp_scripts->queue as $script) {
                if (!isset($wp_scripts->registered[$script])) {
                    continue;
                }

                $src = $wp_scripts->registered[$script]->src;
                if (false !== strpos($src, $pluginUrl) && false !== !strpos($src, 'fluentform')) {
                    wp_dequeue_script($wp_scripts->registered[$script]->handle);
                }
            }
        }
    }
}, 1);

add_action('fluentform_loading_editor_assets', function ($form) {
    add_filter('fluentform_editor_init_element_input_name', function ($field) {
        if (empty($field['settings']['label_placement'])) {
            $field['settings']['label_placement'] = '';
        }
        return $field;
    });

    $upgradableCheckInputs = [
        'input_radio',
        'select',
        'select_country',
        'input_checkbox',
    ];

    foreach ($upgradableCheckInputs as $upgradeElement) {
        add_filter('fluentform_editor_init_element_' . $upgradeElement, function ($element) use ($upgradeElement) {
            if (!\FluentForm\Framework\Helpers\ArrayHelper::get($element, 'settings.advanced_options')) {
                $formattedOptions = [];
                $oldOptions = \FluentForm\Framework\Helpers\ArrayHelper::get($element, 'options', []);
                foreach ($oldOptions as $value => $label) {
                    $formattedOptions[] = [
                        'label'      => $label,
                        'value'      => $value,
                        'calc_value' => '',
                        'image'      => '',
                    ];
                }
                $element['settings']['advanced_options'] = $formattedOptions;
                $element['settings']['enable_image_input'] = false;
                $element['settings']['calc_value_status'] = false;
                unset($element['options']);

                if ('input_radio' == $upgradeElement || 'input_checkbox' == $upgradeElement) {
                    $element['editor_options']['template'] = 'inputCheckable';
                }
            }

            if (!isset($element['settings']['layout_class']) && in_array($upgradeElement, ['input_radio', 'input_checkbox'])) {
                $element['settings']['layout_class'] = '';
            }

            if (!isset($element['settings']['dynamic_default_value'])) {
                $element['settings']['dynamic_default_value'] = '';
            }

            if ('select_country' != $upgradeElement && !isset($element['settings']['randomize_options'])) {
                $element['settings']['randomize_options'] = 'no';
            }

            if ('select' == $upgradeElement && \FluentForm\Framework\Helpers\ArrayHelper::get($element, 'attributes.multiple')) {
                if (empty($element['settings']['max_selection'])) {
                    $element['settings']['max_selection'] = '';
                }
                if (isset($element['settings']['enable_select_2'])) {
                    \FluentForm\Framework\Helpers\ArrayHelper::forget($element, 'settings.enable_select_2');
                }
            }

            if (
                (
                    (
                        'select' == $upgradeElement &&
                        ! \FluentForm\Framework\Helpers\ArrayHelper::get($element, 'attributes.multiple')
                    ) ||
                    'select_country' == $upgradeElement
                ) &&
                ! isset($element['settings']['enable_select_2'])
            ) {
                $element['settings']['enable_select_2'] = 'no';
            }

            if ('select_country' != $upgradeElement && !isset($element['settings']['values_visible'])) {
                $element['settings']['values_visible'] = false;
            }

            return $element;
        });
    }

    $upgradableFileInputs = [
        'input_file',
        'input_image',
    ];
    foreach ($upgradableFileInputs as $upgradeElement) {
        add_filter('fluentform_editor_init_element_' . $upgradeElement, function ($element) {
            if (!isset($element['settings']['upload_file_location'])) {
                $element['settings']['upload_file_location'] = 'default';
            };
            if (!isset($element['settings']['file_location_type'])) {
                $element['settings']['file_location_type'] = 'follow_global_settings';
            };
            return $element;
        });
    }

    add_filter('fluentform_editor_init_element_gdpr_agreement', function ($element) {
        if (!isset($element['settings']['required_field_message'])) {
            $element['settings']['required_field_message'] = '';
        }
        return $element;
    });

    add_filter('fluentform_editor_init_element_input_text', function ($element) {
        if (!isset($element['attributes']['maxlength'])) {
            $element['attributes']['maxlength'] = '';
        }
        return $element;
    });

    add_filter('fluentform_editor_init_element_textarea', function ($element) {
        if (!isset($element['attributes']['maxlength'])) {
            $element['attributes']['maxlength'] = '';
        }
        return $element;
    });

    add_filter('fluentform_editor_init_element_input_date', function ($item) {
        if (!isset($item['settings']['date_config'])) {
            $item['settings']['date_config'] = '';
        }
        return $item;
    });

    add_filter('fluentform_editor_init_element_container', function ($item) {
        if (!isset($item['settings']['conditional_logics'])) {
            $item['settings']['conditional_logics'] = [];
        }

        if (!isset($item['settings']['container_width'])) {
            $item['settings']['container_width'] = '';
        }

        $shouldSetWidth = !empty($item['columns']) && (!isset($item['columns'][0]['width']) || !$item['columns'][0]['width']);

        if ($shouldSetWidth) {
            $perColumn = round(100 / count($item['columns']), 2);

            foreach ($item['columns'] as &$column) {
                $column['width'] = $perColumn;
            }
        }

        return $item;
    });

    add_filter('fluentform_editor_init_element_input_number', function ($item) {
        if (!isset($item['settings']['number_step'])) {
            $item['settings']['number_step'] = '';
        }
        if (!isset($item['settings']['numeric_formatter'])) {
            $item['settings']['numeric_formatter'] = '';
        }
        if (!isset($item['settings']['prefix_label'])) {
            $item['settings']['prefix_label'] = '';
        }
        if (!isset($item['settings']['suffix_label'])) {
            $item['settings']['suffix_label'] = '';
        }
        return $item;
    });

    add_filter('fluentform_editor_init_element_input_email', function ($item) {
        if (!isset($item['settings']['is_unique'])) {
            $item['settings']['is_unique'] = 'no';
        }
        if (!isset($item['settings']['unique_validation_message'])) {
            $item['settings']['unique_validation_message'] = __('Email address need to be unique.', 'fluentform');
        }
        if (!isset($item['settings']['prefix_label'])) {
            $item['settings']['prefix_label'] = '';
        }
        if (!isset($item['settings']['suffix_label'])) {
            $item['settings']['suffix_label'] = '';
        }
        return $item;
    });

    add_filter('fluentform_editor_init_element_input_text', function ($item) {
        if (isset($item['attributes']['data-mask'])) {
            if (!isset($item['settings']['data-mask-reverse'])) {
                $item['settings']['data-mask-reverse'] = 'no';
            }
            if (!isset($item['settings']['data-clear-if-not-match'])) {
                $item['settings']['data-clear-if-not-match'] = 'no';
            }
        } else {
            if (!isset($item['settings']['is_unique'])) {
                $item['settings']['is_unique'] = 'no';
            }
            if (!isset($item['settings']['unique_validation_message'])) {
                $item['settings']['unique_validation_message'] = __('This field value need to be unique.', 'fluentform');
            }
        }

        if (!isset($item['settings']['prefix_label'])) {
            $item['settings']['prefix_label'] = '';
        }
        if (!isset($item['settings']['suffix_label'])) {
            $item['settings']['suffix_label'] = '';
        }

        return $item;
    });

    add_filter('fluentform_editor_init_element_recaptcha', function ($item, $form) {
        $item['attributes']['name'] = 'g-recaptcha-response';
        return $item;
    }, 10, 2);

    add_filter('fluentform_editor_init_element_hcaptcha', function ($item, $form) {
        $item['attributes']['name'] = 'h-captcha-response';
        return $item;
    }, 10, 2);

    add_filter('fluentform_editor_init_element_turnstile', function ($item, $form) {
        $item['attributes']['name'] = 'cf-turnstile-response';
        return $item;
    }, 10, 2);
}, 10);

add_filter('fluentform_addons_extra_menu', function ($menus) {
    $menus['fluentform_pdf'] = __('Fluent Forms PDF', 'fluentform');
    return $menus;
}, 99, 1);

add_action('fluentform_addons_page_render_fluentform_pdf', function () {
    $url = '';
    if (!defined('FLUENTFORM_PDF_VERSION')) {
        $url = wp_nonce_url(
            self_admin_url('update.php?action=install-plugin&plugin=fluentforms-pdf'),
            'install-plugin_fluentforms-pdf'
        );
    }

    \FluentForm\View::render('admin.addons.pdf_promo', [
        'install_url'  => $url,
        'is_installed' => defined('FLUENTFORM_PDF_VERSION'),
    ]);
});

//Add file upload location in global settings
add_filter('fluentform_get_global_settings_values', function ($values, $key) {
    if (is_array($key)) {
        if (in_array('_fluentform_global_form_settings', $key)) {
            $values['file_upload_optoins'] = FluentForm\App\Helpers\Helper::fileUploadLocations();
        }

        if (in_array('_fluentform_turnstile_details', $key)) {
            $values = FluentForm\App\Modules\Turnstile\Turnstile::ensureSettings($values);
        }
    }
    return $values;
}, 10, 2);

add_action('ff_installed_by', function ($by) {
    if (is_string($by) && !get_option('_ff_ins_by')) {
        update_option('_ff_ins_by', sanitize_text_field($by), 'no');
    }
});

//Enables recaptcha validation when autoload recaptcha enabled for all forms
$autoIncludeRecaptcha = [
    [
        'type'        => 'hcaptcha',
        'is_disabled' => !get_option('_fluentform_hCaptcha_keys_status', false),
    ],
    [
        'type'        => 'recaptcha',
        'is_disabled' => !get_option('_fluentform_reCaptcha_keys_status', false),
    ],
    [
        'type'        => 'turnstile',
        'is_disabled' => !get_option('_fluentform_turnstile_keys_status', false),
    ],
];

foreach ($autoIncludeRecaptcha as $input) {
    if ($input['is_disabled']) {
        continue;
    }
    add_filter('ff_has_auto_' . $input['type'], function () use ($input) {
        $option = get_option('_fluentform_global_form_settings');
        $autoload = \FluentForm\Framework\Helpers\ArrayHelper::get($option, 'misc.autoload_captcha');
        $type = \FluentForm\Framework\Helpers\ArrayHelper::get($option, 'misc.captcha_type');

        if ($autoload && $type == $input['type']) {
            return true;
        }
        return false;
    });
}

//
add_action('admin_init', function ()  {
    (new \FluentForm\App\Modules\Registerer\AdminBar())->register();
}, 9);
