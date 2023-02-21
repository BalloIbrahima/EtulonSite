<?php

namespace FluentForm\App\Modules\Settings;

use FluentForm\Framework\Request\Request;
use FluentForm\Framework\Helpers\ArrayHelper;
use FluentForm\App\Modules\ReCaptcha\ReCaptcha;
use FluentForm\App\Modules\HCaptcha\HCaptcha;
use FluentForm\App\Modules\Turnstile\Turnstile;
use FluentForm\App\Services\Integrations\MailChimp\MailChimp;

/**
 * Global Settings
 *
 * @package FluentForm\App\Modules\Settings
 */
class Settings
{
    /**
     * Request Object
     *
     * @var \FluentForm\Framework\Request\Request
     */
    protected $request;

    /**
     * Settings constructor.
     *
     * @param \FluentForm\Framework\Request\Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Get a global settings for an specified key.
     */
    public function get()
    {
        $values = [];
        $key = $this->request->get('key');

        if (is_array($key)) {
            foreach ($key as $key_item) {
                $values[$key_item] = get_option($key_item);
            }
        } else {
            $values[$key] = get_option($key);
        }
        $values = apply_filters('fluentform_get_global_settings_values', $values, $key);
        wp_send_json_success($values, 200);
    }

    public function store()
    {
        $key = $this->request->get('key');
        $method = 'store' . ucwords($key);

        $allowedMethods = [
            'storeReCaptcha',
            'storeHCaptcha',
            'storeTurnstile',
            'storeSaveGlobalLayoutSettings',
            'storeMailChimpSettings',
            'storeEmailSummarySettings',
        ];
        do_action('fluentform_saving_global_settings_with_key_method', $this->request);

        if (in_array($method, $allowedMethods)) {
            $this->{$method}();
        }
    }

    public function storeReCaptcha()
    {
        $data = $this->request->get('reCaptcha');

        if ('clear-settings' == $data) {
            delete_option('_fluentform_reCaptcha_details');

            update_option('_fluentform_reCaptcha_keys_status', false, 'no');

            wp_send_json_success([
                'message' => __('Your reCAPTCHA settings are deleted.', 'fluentform'),
                'status'  => false,
            ], 200);
        }

        $token = ArrayHelper::get($data, 'token');
        $secretKey = ArrayHelper::get($data, 'secretKey');

        // If token is not empty meaning user verified their captcha.
        if ($token) {
            // Validate the reCaptcha response.
            $version = ArrayHelper::get($data, 'api_version', 'v2_visible');

            $status = ReCaptcha::validate($token, $secretKey, $version);

            // reCaptcha is valid. So proceed to store.
            if ($status) {
                // Prepare captcha data.
                $captchaData = [
                    'siteKey'     => sanitize_text_field(ArrayHelper::get($data, 'siteKey')),
                    'secretKey'   => sanitize_text_field($secretKey),
                    'api_version' => ArrayHelper::get($data, 'api_version'),
                ];

                // Update the reCaptcha details with siteKey & secretKey.
                update_option('_fluentform_reCaptcha_details', $captchaData, 'no');

                // Update the reCaptcha validation status.
                update_option('_fluentform_reCaptcha_keys_status', $status, 'no');

                // Send success response letting the user know that
                // that the reCaptcha is valid and saved properly.
                wp_send_json_success([
                    'message' => __('Your reCAPTCHA is valid and saved.', 'fluentform'),
                    'status'  => $status,
                ], 200);
            } else { // reCaptcha is not valid.
                $message = __('Sorry, Your reCAPTCHA is not valid. Please try again', 'fluentform');
            }
        } else { // The token is empty, so the user didn't verify their captcha.
            $message = __('Please validate your reCAPTCHA first and then hit save.', 'fluentform');

            // Get the already stored reCaptcha status.
            $status = get_option('_fluentform_reCaptcha_keys_status');

            if ($status) {
                $message = __('Your reCAPTCHA details are already valid. So no need to save again.', 'fluentform');
            }
        }

        wp_send_json_error([
            'message' => $message,
            'status'  => $status,
        ], 400);
    }

    public function storeHCaptcha()
    {
        $data = $this->request->get('hCaptcha');

        if ('clear-settings' == $data) {
            delete_option('_fluentform_hCaptcha_details');

            update_option('_fluentform_hCaptcha_keys_status', false, 'no');

            wp_send_json_success([
                'message' => __('Your hCaptcha settings are deleted.', 'fluentform'),
                'status'  => false,
            ], 200);
        }

        $token = ArrayHelper::get($data, 'token');
        $secretKey = ArrayHelper::get($data, 'secretKey');

        // If token is not empty meaning user verified their captcha.
        if ($token) {
            // Validate the hCaptcha response.
            $status = HCaptcha::validate($token, $secretKey);

            // hCaptcha is valid. So proceed to store.
            if ($status) {
                // Prepare captcha data.
                $captchaData = [
                    'siteKey'   => sanitize_text_field(ArrayHelper::get($data, 'siteKey')),
                    'secretKey' => sanitize_text_field($secretKey),
                ];

                // Update the hCaptcha details with siteKey & secretKey.
                update_option('_fluentform_hCaptcha_details', $captchaData, 'no');

                // Update the hCaptcha validation status.
                update_option('_fluentform_hCaptcha_keys_status', $status, 'no');

                // Send success response letting the user know that
                // that the hCaptcha is valid and saved properly.
                wp_send_json_success([
                    'message' => __('Your hCaptcha is valid and saved.', 'fluentform'),
                    'status'  => $status,
                ], 200);
            } else { // hCaptcha is not valid.
                $message = __('Sorry, Your hCaptcha is not valid, Please try again', 'fluentform');
            }
        } else { // The token is empty, so the user didn't verify their captcha.
            $message = __('Please validate your hCaptcha first and then hit save.', 'fluentform');

            // Get the already stored hCaptcha status.
            $status = get_option('_fluentform_hCaptcha_keys_status');

            if ($status) {
                $message = __('Your hCaptcha details are already valid, So no need to save again.', 'fluentform');
            }
        }

        wp_send_json_error([
            'message' => $message,
            'status'  => $status,
        ], 400);
    }

    public function storeTurnstile()
    {
        $data = $this->request->get('turnstile');

        if ('clear-settings' == $data) {
            delete_option('_fluentform_turnstile_details');

            update_option('_fluentform_turnstile_keys_status', false, 'no');

            wp_send_json_success([
                'message' => __('Your Turnstile settings are deleted.', 'fluentform'),
                'status'  => false,
            ], 200);
        }

        $token = ArrayHelper::get($data, 'token');
        $secretKey = sanitize_text_field(ArrayHelper::get($data, 'secretKey'));

        // Prepare captcha data.
        $captchaData = [
            'siteKey'   => ArrayHelper::get($data, 'siteKey'),
            'secretKey' => $secretKey,
            'invisible' => ArrayHelper::get($data, 'invisible', 'no'),
            'theme'     => ArrayHelper::get($data, 'theme', 'auto')
        ];

        // If token is not empty meaning user verified their captcha.
        if ($token) {
            // Validate the turnstile response.
            $status = Turnstile::validate($token, $secretKey);

            // turnstile is valid. So proceed to store.
            if ($status) {
                // Update the turnstile details with siteKey & secretKey.
                update_option('_fluentform_turnstile_details', $captchaData, 'no');

                // Update the turnstile validation status.
                update_option('_fluentform_turnstile_keys_status', $status, 'no');

                // Send success response letting the user know that
                // that the turnstile is valid and saved properly.
                wp_send_json_success([
                    'message' => __('Your Turnstile Keys are valid.', 'fluentform'),
                    'status'  => $status,
                ], 200);
            } else {
                // turnstile is not valid.
                $message = __('Sorry, Your Turnstile Keys are not valid. Please try again!', 'fluentform');
            }
        } else {
            // The token is empty, so the user didn't verify their captcha.
            $message = __('Please validate your Turnstile first and then hit save.', 'fluentform');

            // Get the already stored reCaptcha status.
            $status = get_option('_fluentform_turnstile_keys_status');

            if ($status) {
                update_option('_fluentform_turnstile_details', $captchaData, 'no');
                $message = __('Your Turnstile settings is saved.', 'fluentform');

                wp_send_json_success([
                    'message' => $message,
                    'status'  => $status,
                ], 200);
            }
        }

        wp_send_json_error([
            'message' => $message,
            'status'  => $status,
        ], 400);
    }

    public function storeSaveGlobalLayoutSettings()
    {
        $settings = $this->request->get('value');
        $settings = json_decode($settings, true);
        $sanitizedSettings = fluentFormSanitizer($settings);

        if (ArrayHelper::get($settings, 'misc.email_footer_text')) {
            $sanitizedSettings['misc']['email_footer_text'] = wp_unslash($settings['misc']['email_footer_text']);
        }

        update_option('_fluentform_global_form_settings', $sanitizedSettings, 'no');

        wp_send_json_success([
            'message' => __('Global layout settings has been saved'),
        ], 200);
    }

    public function storeMailChimpSettings()
    {
        $mailChimp = $this->request->get('mailchimp');

        if (!$mailChimp['apiKey']) {
            $mailChimpSettings = [
                'apiKey' => '',
                'status' => false,
            ];
            // Update the reCaptcha details with siteKey & secretKey.

            update_option('_fluentform_mailchimp_details', $mailChimpSettings, 'no');

            wp_send_json_success([
                'message' => __('Your settings has been updated', 'fluentform'),
                'status'  => false,
            ], 200);
        }

        // Verify API key now
        try {
            $MailChimp = new MailChimp($mailChimp['apiKey']);
            $result = $MailChimp->get('lists');
            if (!$MailChimp->success()) {
                throw new \Exception($MailChimp->getLastError());
            }
        } catch (\Exception $exception) {
            wp_send_json_error([
                'message' => $exception->getMessage(),
            ], 400);
        }

        // Mailchimp key is verified now, Proceed now

        $mailChimpSettings = [
            'apiKey' => sanitize_text_field($mailChimp['apiKey']),
            'status' => true,
        ];

        // Update the reCaptcha details with siteKey & secretKey.
        update_option('_fluentform_mailchimp_details', $mailChimpSettings, 'no');

        wp_send_json_success([
            'message' => __('Your mailchimp api key has been verfied and successfully set', 'fluentform'),
            'status'  => true,
        ], 200);
    }

    public function storeEmailSummarySettings()
    {
        $defaults = [
            'status'            => 'yes',
            'send_to_type'      => 'admin_email',
            'custom_recipients' => '',
            'sending_day'       => 'Mon',
        ];
        $settings = $this->request->get('value');
        $settings = json_decode($settings, true);

        $settings = wp_parse_args($settings, $defaults);

        update_option('_fluentform_email_report_summary', $settings);

        $emailReportHookName = 'fluentform_do_email_report_scheduled_tasks';
        if (!wp_next_scheduled($emailReportHookName)) {
            wp_schedule_event(time(), 'daily', $emailReportHookName);
        }

        wp_send_json_success([
            'message' => __('Email Summary Settings has been updated'),
        ], 200);
    }
}
