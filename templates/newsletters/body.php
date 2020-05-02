<div style="margin-top: 20px;"></div>
<?php
    $body = is_object( $campaign ) ? wp_unslash( $campaign->post_content ) : get_noptin_default_newsletter_body();

    wp_editor(
        $body,
        'noptinemailbody',
        array(
            'media_buttons'    => true,
            'drag_drop_upload' => true,
            'textarea_rows'    => 15,
            'textarea_name'    => 'email_body',
            'tabindex'         => 4,
            'tinymce'          => array(
                'theme_advanced_buttons1' => 'bold,italic,underline,|,bullist,numlist,blockquote,|,link,unlink,|,spellchecker,fullscreen,|,formatselect,styleselect',
            ),
        )
    );
