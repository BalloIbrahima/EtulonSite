<?php do_action('fluentform_global_menu'); ?>
<div class="ff_form_wrap">
    <div class="ff_form_wrap_area">
        <h2><?php _e('Help & Documentation', 'fluentform'); ?></h2>
        <div class="ff_documentaion_wrapper">
            <?php do_action('fluentform_before_documentation_wrapper'); ?>

            <div class="ff_doc_top_blocks">
                <div class="ff_block block_1_3">
                    <div class="ff_block_box text-center">
                        <img src="<?php echo esc_url($icon_path_url); ?>img/support.png"/>
                        <h3><?php _e('Need Expert Support?', 'fluentform') ?></h3>
                        <p><?php _e('Our EXPERTS would like to assist you for your query and any customization.', 'fluentform') ?></p>
                        <p><a target="_blank" class="button button-primary"
                              href="https://wpmanageninja.com/support-tickets/"><?php _e('Contact
                                Support', 'fluentform') ?></a></p>
                    </div>
                </div>
                <div class="ff_block block_1_3">
                    <div class="ff_block_box text-center">
                        <img src="<?php echo esc_url($icon_path_url); ?>img/fb_group.png"/>
                        <h3><?php _e('Join our facebook community', 'fluentform') ?></h3>
                        <p><?php _e('We have a strong community where we discuss ideas and help each other.', 'fluentform') ?></p>
                        <p><a target="_blank" class="button button-primary" href="https://www.facebook.com/groups/fluentforms/">
                            <?php _e('Join Facebook Group', 'fluentform') ?>
                        </a></p>
                    </div>
                </div>
                <div class="ff_block block_1_3">
                    <div class="ff_block_box text-center">
                        <img src="<?php echo esc_url($icon_path_url); ?>img/bug.png"/>
                        <h3><?php _e('Found a Bug?', 'fluentform') ?></h3>
                        <p><?php _e('Please report us and we promise we will fix that as soon as humanly possible', 'fluentform') ?></p>
                        <p><a target="_blank" class="button button-primary" href="https://github.com/fluentform/fluentform/issues">
                            <?php _e('Report Bug on Github', 'fluentform') ?>
                        </a></p>
                    </div>
                </div>
            </div>
            <div class="ff_doc_top_blocks">
                <div class="ff_block block_2_3">
                    <div class="ff_block_box text-center">
                        <h2><?php _e('Fluent Forms Video Tutorials', 'fluentform') ?></h2>
                        <div class="videoWrapper">
                            <iframe width="1280" height="720" src="https://www.youtube.com/embed/M_r-4Ernjj0?list=PLXpD0vT4thWEpMEGqaEQaxTQ34ac2xzfb" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                        </div>
                    </div>
                </div>

                <div class="ff_block block_1_3">
                    <div class="ff_block_box text-center">
                        <img src="<?php echo esc_url($icon_path_url); ?>img/love.png"/>
                        <h3><?php _e('Love this Plugin?', 'fluentform') ?></h3>
                        <p><?php _e('Please write a review in wp.org plugin repository. We appreciate it!', 'fluentform') ?></p>
                        <p><a target="_blank" class="button button-primary" href="https://wordpress.org/support/plugin/fluentform/reviews/#new-post">
                            <?php _e('Write Review', 'fluentform') ?>
                        </a></p>
                    </div>
                    <div style="margin-top: 20px;" class="ff_block_box">
                        <h3><?php _e('User Guides', 'fluentform') ?></h3>
                        <p><?php _e('Please check the following', 'fluentform') ?><b<?php _e(' Tutorials and Documentations ', 'fluentform') ?></b> <?php _e('for getting started with Fluent Forms', 'fluentform') ?> </p>
                        <ul>
                            <?php foreach ($user_guides as $user_guide): ?>
                                <li><a target="_blank"
                                       href="<?php echo esc_url($user_guide['link']); ?>"><?php echo esc_html($user_guide['title']); ?></a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <?php do_action('fluentform_after_documentation_wrapper') ?>

        </div>
    </div>
</div>
