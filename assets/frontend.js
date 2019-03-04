(function($) {

    //Select apply forms
    $('.wp-block-noptin-email-optin form')
        //Watch for form submit events
        .on('submit', function(e) {

            //Prevent the form from submitting
            e.preventDefault();

            //Fade out the form
            var that = $(this);
            $(this).fadeTo(600, 0.2);

            //Hide feedback divs
            $(that).find('.noptin_feedback_success').hide();
            $(that).find('.noptin_feedback_error').hide();

            //Retrieve the email
            var _email = $(this).find('.noptin_form_input_email').val();

            //Send an ajax request to the server
            $.post(noptin.ajaxurl, {
                        email: _email,
                        action: 'noptin_new_user',
                        noptin_subscribe: noptin.noptin_subscribe
                    },
                    function(data, status, xhr) {
                        data = JSON.parse(data);
                        $(that).fadeTo(600, 1);
                        if (data.result == '1') {
                            $(that).find('.noptin_feedback_success').text(data.msg).show();

                            var url = $(that).find('.noptin_form_redirect').val();
                            if (url) {
                                window.location = url;
                            }
                        } else {
                            $(that).find('.noptin_feedback_error').text(data.msg).show();
                        }
                    })
                .fail(function() {
                    $(that).fadeTo(600, 1);
                    $(that).find('.noptin_feedback_error').text('Could not establish a connection to the server.').show();
                });
        })
})(jQuery);