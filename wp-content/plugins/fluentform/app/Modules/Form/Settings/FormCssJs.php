<?php

namespace FluentForm\App\Modules\Form\Settings;

use FluentForm\App\Helpers\Helper;

class FormCssJs
{
    /**
     * Request object
     *
     * @var \FluentForm\Framework\Request\Request $request
     */
    protected $request;

    public function __construct()
    {
        $this->request = wpFluentForm('request');
    }

    public function addCssJs($formId)
    {
        // @todo: Limit 3 sometimes make things double
        $metas = wpFluent()->table('fluentform_form_meta')
            ->where('form_id', $formId)
            ->whereIn('meta_key', [
                '_custom_form_css',
                '_custom_form_js',
                '_ff_form_styler_css',
            ])
            ->groupBy('meta_key')
            //->limit(3)
            ->get();

        if (!$metas) {
            return;
        }

        foreach ($metas as $meta) {
            if ($meta->value) {
                if ('_custom_form_css' == $meta->meta_key) {
                    $css = $meta->value;
                    $css = str_replace('{form_id}', $formId, $css);
                    $css = str_replace('FF_ID', $formId, $css);
                    $this->addCss($formId, $css, 'fluentform_custom_css_' . $formId);
                } elseif ('_ff_form_styler_css' == $meta->meta_key) {
                    $css = $meta->value;
                    $this->addCss($formId, $css, 'fluentform_styler_css_' . $formId);
                } elseif ('_custom_form_js' == $meta->meta_key) {
                    $this->addJs($formId, $meta->value);
                }
            }
        }
    }

    public function getCss($formId)
    {
        $cssMeta = wpFluent()->table('fluentform_form_meta')
            ->where('form_id', $formId)
            ->where('meta_key', '_custom_form_css')
            ->first();

        if (!$cssMeta || !$cssMeta->value) {
            return '';
        }

        $css = $cssMeta->value;
        $css = str_replace('{form_id}', $formId, $css);
        $css = str_replace('FF_ID', $formId, $css);
        return fluentformSanitizeCSS($css);
    }

    public function getJs($formId)
    {
        $jsMeta = wpFluent()->table('fluentform_form_meta')
            ->where('form_id', $formId)
            ->where('meta_key', '_custom_form_js')
            ->first();

        if (!$jsMeta || !$jsMeta->value) {
            return '';
        }

        return $jsMeta->value;
    }

    public function addCss($formId, $css, $cssId = 'fluentform_custom_css')
    {
        if ($css) {
            if (!did_action('wp_head')) {
                add_action('wp_head', function () use ($css, $formId, $cssId) {
                    ?>

                    <style id="<?php echo esc_attr($cssId); ?>" type="text/css">
                        <?php echo fluentformSanitizeCSS($css); ?>
                    </style>

                    <?php
                }, 10);
            } else {
                ?>

                <style id="<?php echo esc_attr($cssId); ?>" type="text/css">
                    <?php echo fluentformSanitizeCSS($css); ?>
                </style>
                <?php
            }
        }
    }

    public function addJs($formId, $customJS)
    {
        if (trim($customJS)) {
            add_action('wp_footer', function () use ($formId, $customJS) {
                ?>
                <script type="text/javascript">
                    jQuery(document.body).on('fluentform_init_<?php echo esc_attr($formId); ?>',
                        function(event, data) {
                            var $form = jQuery(data[0]);
                            var formId = "<?php echo esc_attr($formId); ?>";
                            var $ = jQuery;
                            try {
                                <?php echo fluentform_kses_js($customJS); ?>
                            } catch (e) {
                                console.warn('Error in custom JS of Fluentform ID: ' + formId);
                                console.error(e);
                            }
                        });
                </script>
                <?php
            }, 100);
        }
    }

    /**
     * Get settings for a particular form by id
     */
    public function getSettingsAjax()
    {
        $formId = absint($this->request->get('form_id'));
        wp_send_json_success([
            'custom_css' => $this->getData($formId, '_custom_form_css'),
            'custom_js'  => $this->getData($formId, '_custom_form_js'),
        ], 200);
    }

    /**
     * Save settings for a particular form by id
     */
    public function saveSettingsAjax()
    {
        if (!fluentformCanUnfilteredHTML()) {
            wp_send_json_error([
                'message' => __('You need unfiltered_html permission to save Custom CSS & JS', 'fluentform'),
            ], 423);
        }

        $formId = absint($this->request->get('form_id'));

        $css = fluentformSanitizeCSS($this->request->get('custom_css'));
        $js = fluentform_kses_js($this->request->get('custom_js'));

        $this->store($formId, '_custom_form_css', $css);
        $this->store($formId, '_custom_form_js', $js);

        wp_send_json_success([
            'message' => __('Custom CSS & JS successfully updated', 'fluentform'),
        ], 200);
    }

    protected function getData($formId, $metaKey)
    {
        $row = wpFluent()->table('fluentform_form_meta')
            ->where('form_id', $formId)
            ->where('meta_key', $metaKey)
            ->first();
        if ($row) {
            return $row->value;
        }
        return '';
    }

    protected function store($formId, $metaKey, $metaValue)
    {
        $row = wpFluent()->table('fluentform_form_meta')
            ->where('form_id', $formId)
            ->where('meta_key', $metaKey)
            ->first();

        if (!$row) {
            return wpFluent()->table('fluentform_form_meta')
                ->insert([
                    'form_id'  => $formId,
                    'meta_key' => $metaKey,
                    'value'    => $metaValue,
                ]);
        }

        return wpFluent()->table('fluentform_form_meta')
            ->where('id', $row->id)
            ->update([
                'value' => $metaValue,
            ]);
    }
}
