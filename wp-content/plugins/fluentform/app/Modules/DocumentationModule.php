<?php

namespace FluentForm\App\Modules;

use FluentForm\App;
use FluentForm\View;

class DocumentationModule
{
    public function render()
    {
        View::render('admin.docs.index', [
            'icon_path_url' => App::publicUrl(),
            'user_guides'   => $this->getUserGuides(),
        ]);
    }

    private function getUserGuides()
    {
        $guides = [
            [
                'title' => __('Adding a new form', 'fluentform'),
                'link'  => 'https://wpmanageninja.com/docs/fluent-form/getting-started/create-fluent-form/',
            ],
            [
                'title' => __('Setting up form submission confirmation', 'fluentform'),
                'link'  => 'https://wpmanageninja.com/docs/fluent-form/getting-started/submission-confirmation-message/',
            ],
            [
                'title' => __('Form layout settings', 'fluentform'),
                'link'  => 'https://wpmanageninja.com/docs/fluent-form/getting-started/form-layout-settings/',
            ],
            [
                'title' => __('Conditional logics', 'fluentform'),
                'link'  => 'https://wpmanageninja.com/docs/fluent-form/advanced-features-functionalities-in-wp-fluent-form/conditional-logic-fluent-form/',
            ],
            [
                'title' => __('Managing form submissions', 'fluentform'),
                'link'  => 'https://wpmanageninja.com/docs/fluent-form/view-submitted-form-data-in-wp-fluent-forms/view-manage-submitted-form-entries/',
            ],
            [
                'title' => __('Setting up email notifications', 'fluentform'),
                'link'  => 'https://wpmanageninja.com/docs/fluent-form/getting-started/email-notification/',
            ],
            [
                'title' => __('Mailchimp integration', 'fluentform'),
                'link'  => 'https://wpmanageninja.com/docs/fluent-form/integrations-availabel-in-wp-fluent-form/mailchimp-integration/',
            ],
            [
                'title' => __('Slack integration', 'fluentform'),
                'link'  => 'https://wpmanageninja.com/docs/fluent-form/integrations-availabel-in-wp-fluent-form/slack-integration-fluentform/',
            ],
            [
                'title' => __('Form restrictions and scheduling', 'fluentform'),
                'link'  => 'https://wpmanageninja.com/docs/fluent-form/advanced-features-functionalities-in-wp-fluent-form/form-restrictions/',
            ],
            [
                'title' => __('Setup conditional confirmation messages', 'fluentform'),
                'link'  => 'https://wpmanageninja.com/docs/fluent-form/advanced-features-functionalities-in-wp-fluent-form/conditional-confirmation-wp-fluent-form/',
            ],
            [
                'title' => __('Predefined form fields', 'fluentform'),
                'link'  => 'https://wpmanageninja.com/docs/fluent-form/field-types/',
            ],
        ];
        return apply_filters('fluentform_user_guide_links', $guides);
    }
}
