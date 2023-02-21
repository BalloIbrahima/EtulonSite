<?php

namespace FluentForm\App\Services\Integrations;

use FluentForm\Framework\Helpers\ArrayHelper;
use FluentForm\Framework\Foundation\Application;

class GlobalIntegrationManager
{
    /**
     * Request object
     *
     * @var \FluentForm\Framework\Request\Request $request
     */
    protected $request;

    public function __construct(Application $app)
    {
        $this->request = wpFluentForm('request');
    }

    public function getGlobalSettingsAjax()
    {
        $settingsKey = sanitize_text_field($this->request->get('settings_key'));
        $settings = apply_filters('fluentform_global_integration_settings_' . $settingsKey, []);
        $fieldSettings = apply_filters('fluentform_global_integration_fields_' . $settingsKey, []);

        if (! $fieldSettings) {
            wp_send_json_error([
                'settings'     => $settings,
                'settings_key' => $settingsKey,
                'message'      => __('Sorry! No integration failed found with: ', 'fluentform') . $settingsKey,
            ], 423);
        }

        if (empty($fieldSettings['save_button_text'])) {
            $fieldSettings['save_button_text'] = __('Save Settings', 'fluentform');
        }

        if (empty($fieldSettings['valid_message'])) {
            $fieldSettings['valid_message'] = __('Your API Key is valid', 'fluentform');
        }

        if (empty($fieldSettings['invalid_message'])) {
            $fieldSettings['invalid_message'] = __('Your API Key is not valid', 'fluentform');
        }

        wp_send_json_success([
            'integration' => $settings,
            'settings'    => $fieldSettings,
        ], 200);
    }

    public function saveGlobalSettingsAjax()
    {
        $settingsKey = sanitize_text_field($this->request->get('settings_key'));
        $integration = wp_unslash($this->request->get('integration'));
        do_action('fluentform_save_global_integration_settings_' . $settingsKey, $integration);

        // Someone should catch that above action and send response
        wp_send_json_error([
            'message' => __('Sorry, no Integration found. Please make sure that latest version of Fluent Forms pro installed', 'fluentform'),
        ], 423);
    }

    public function getAllFormIntegrations()
    {
        $formId = $this->request->get('form_id');

        $formattedFeeds = $this->getNotificationFeeds($formId);

        $availableIntegrations = apply_filters('fluentform_get_available_form_integrations', [], $formId);

        wp_send_json_success([
            'feeds'                  => $formattedFeeds,
            'available_integrations' => $availableIntegrations,
            'all_module_config_url'  => admin_url('admin.php?page=fluent_forms_add_ons'),
        ], 200);
    }

    public function getNotificationFeeds($formId)
    {
        $notificationKeys = apply_filters('fluentform_global_notification_types', [], $formId);

        if ($notificationKeys) {
            $feeds = wpFluent()->table('fluentform_form_meta')
                ->where('form_id', $formId)
                ->whereIn('meta_key', $notificationKeys)
                ->orderBy('id', 'DESC')
                ->get();
        } else {
            $feeds = [];
        }

        $formattedFeeds = [];

        foreach ($feeds as $feed) {
            $data = json_decode($feed->value, true);
            $enabled = $data['enabled'];
            if ($enabled && 'true' == $enabled) {
                $enabled = true;
            } elseif ('false' == $enabled) {
                $enabled = false;
            }
            $feedData = [
                'id'       => $feed->id,
                'name'     => ArrayHelper::get($data, 'name'),
                'enabled'  => $enabled,
                'provider' => $feed->meta_key,
                'feed'     => $data,
            ];
            $feedData = apply_filters('fluentform_global_notification_feed_' . $feed->meta_key, $feedData, $formId);
            $formattedFeeds[] = $feedData;
        }
        return $formattedFeeds;
    }

    public function updateNotificationStatus()
    {
        $formId = intval($this->request->get('form_id'));
        $notificationId = intval($this->request->get('notification_id'));
        $status = sanitize_key($this->request->get('status'));

        $feed = wpFluent()->table('fluentform_form_meta')
            ->where('form_id', $formId)
            ->where('id', $notificationId)
            ->first();

        $notification = json_decode($feed->value, true);

        if ('false' == $status || ! $status) {
            $notification['enabled'] = false;
        } else {
            $notification['enabled'] = true;
        }

        wpFluent()->table('fluentform_form_meta')
            ->where('form_id', $formId)
            ->where('id', $notificationId)
            ->update([
                'value' => json_encode($notification, JSON_NUMERIC_CHECK),
            ]);

        $feed = wpFluent()->table('fluentform_form_meta')
            ->where('form_id', $formId)
            ->where('id', $notificationId)
            ->first();

        wp_send_json_success([
            'message' => __('Integration successfully updated', 'fluentform'),
        ], 200);
    }

    public function getIntegrationSettings()
    {
        $integrationName = $this->request->get('integration_name');
        $integrationId = intval($this->request->get('integration_id'));
        $formId = intval($this->request->get('form_id'));

        $settings = [
            'conditionals' => [
                'conditions' => [],
                'status'     => false,
                'type'       => 'all',
            ],
            'enabled'      => true,
            'list_id'      => '',
            'list_name'    => '',
            'name'         => '',
            'merge_fields' => [],
        ];

        $mergeFields = false;
        if ($integrationId) {
            $feed = wpFluent()->table('fluentform_form_meta')
                ->where('form_id', $formId)
                ->where('id', $integrationId)
                ->first();

            if ($feed->value) {
                $settings = json_decode($feed->value, true);

                $settings = apply_filters('fluentform_get_integration_values_' . $integrationName, $settings, $feed, $formId);
                if (! empty($settings['list_id'])) {
                    $mergeFields = apply_filters('fluentform_get_integration_merge_fields_' . $integrationName, false, $settings['list_id'], $formId);
                }
            }
        } else {
            $settings = apply_filters('fluentform_get_integration_defaults_' . $integrationName, $settings, $formId);
        }

        if ('true' == $settings['enabled']) {
            $settings['enabled'] = true;
        } elseif ('false' == $settings['enabled'] || $settings['enabled']) {
            $settings['enabled'] = false;
        }

        $settingsFields = apply_filters('fluentform_get_integration_settings_fields_' . $integrationName, $settings, $formId, $settings);

        wp_send_json_success([
            'settings'        => $settings,
            'settings_fields' => $settingsFields,
            'merge_fields'    => $mergeFields,
        ], 200);
    }

    public function saveIntegrationSettings()
    {
        $integrationName = $this->request->get('integration_name');
        $integrationId = intval($this->request->get('integration_id'));
        $formId = intval($this->request->get('form_id'));

        if ('stringify' == $this->request->get('data_type')) {
            $integration = \json_decode($this->request->get('integration'), true);
        } else {
            $integration = wp_unslash($this->request->get('integration'));
        }

        if ($integration['enabled'] && 'true' == $integration['enabled']) {
            $integration['status'] = true;
        }

        if (! $integration['name']) {
            wp_send_json_error([
                'message' => 'Validation Failed',
                'errors'  => [
                    'name' => ['Feed name is required'],
                ],
            ], 423);
        }

        $integration = apply_filters('fluentform_save_integration_value_' . $integrationName, $integration, $integrationId, $formId);

        $data = [
            'form_id'  => $formId,
            'meta_key' => $integrationName . '_feeds',
            'value'    => \json_encode($integration),
        ];

        $data = apply_filters('fluentform_save_integration_settings_' . $integrationName, $data, $integrationId);

        $created = false;
        if ($integrationId) {
            wpFluent()->table('fluentform_form_meta')
                ->where('form_id', $formId)
                ->where('id', $integrationId)
                ->update($data);
        } else {
            $created = true;
            $integrationId = wpFluent()->table('fluentform_form_meta')
                ->insert($data);
        }

        wp_send_json_success([
            'message'          => __('Integration successfully saved', 'fluentform'),
            'integration_id'   => $integrationId,
            'integration_name' => $integrationName,
            'created'          => $created,
        ], 200);
    }

    public function deleteIntegrationFeed()
    {
        $formId = intval($this->request->get('form_id'));
        $integrationId = intval($this->request->get('integration_id'));

        wpFluent()->table('fluentform_form_meta')
            ->where('form_id', $formId)
            ->where('id', $integrationId)
            ->delete();

        wp_send_json_success([
            'message' => __('Selected integration feed successfully deleted', 'fluentform'),
        ]);
    }

    public function getIntegrationList()
    {
        $integrationName = $this->request->get('integration_name');
        $formId = intval($this->request->get('form_id'));
        $listId = $this->request->get('list_id');
        $merge_fields = apply_filters('fluentform_get_integration_merge_fields_' . $integrationName, false, $listId, $formId);

        wp_send_json_success([
            'merge_fields' => $merge_fields,
        ], 200);
    }
}
