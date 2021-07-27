(function ($) {

	// Watch deactivation clicks.
	$( document ).ready( function() {
        var noptin_deactivation_url;

        $('#deactivate-newsletter-optin-box, #deactivate-noptin').on( 'click', function( e ) {
            e.preventDefault();

            noptin_deactivation_url = e.target.href;

            $('.noptin-deactivation-skip-survey').attr( 'href', noptin_deactivation_url );

            tb_show(
                noptin_deactivation_survey.quick_feedback,
                '#TB_inline?height=auto&inlineId=tmpl-noptin-deactivation-survey'
            );

            $( '#TB_window' ).addClass( 'noptin-deactivation' )
        })

        $( 'body' ).on(
            'change',
            '#noptin-deactivation-survey-list [name="deactivation_reason"]',
            function() {
                var placeholder = $('#noptin-deactivation-survey-list [name="deactivation_reason"]:checked').data( 'placeholder' )

                if ( placeholder.length ) {
                    $('.noptin-deactivation-reason2')
                        .css( 'visibility', 'visible' )
                        .find( 'input' )
                        .attr( 'placeholder', placeholder )
                } else {
                    $('.noptin-deactivation-reason2').css( 'visibility', 'hidden' )
                }

            }
        )

        $( '.noptin-deactivation-survey-form' ).on(
            'submit',
            function( e ) {
                e.preventDefault()
                $( '.noptin-deactivation-survey-form' ).css( 'opacity', '0.3' )

                jQuery
                    .post(
                        'https://noptin.com/wp-json/nopcom/1/stats/deactivate',
                        $( '.noptin-deactivation-survey-form' ).serialize()
                    )
					.always(function () {
						window.location.href = noptin_deactivation_url;
                        $( '.noptin-deactivation-survey-form' ).css( 'opacity', '1' )
					})
            }
        )
	});

})(jQuery);
