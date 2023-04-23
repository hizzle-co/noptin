(function ($) {

	// Wait for the dom to load...
	$( document ).ready( function() {

		// When .noptin-toggle-automation-rule checkbox changes, save via ajax.
        $( '.noptin-toggle-automation-rule' ).on( 'change', function() {
            const isChecked = $( this ).is( ':checked' );
            const ruleID = $( this ).closest('tr').data( 'id' );

            $.post( noptinViewRules.ajaxurl, {
                action: 'noptin_toggle_automation_rule',
                rule_id: ruleID,
                _ajax_nonce: noptinViewRules.nonce,
                enabled: isChecked ? 1 : 0,
            } ).catch( function( error ) {
                console.log( error );
            });

        });

        // When .noptin-automation-rule-action__delete is clicked, delete via ajax.
        $( '.noptin-automation-rule-action__delete' ).on( 'click', function( e ) {

            e.preventDefault();

            // Confirm the user wants to delete the rule.
            if ( ! confirm( noptinViewRules.confirmDelete ) ) {
                return;
            }

            const row    = $( this ).closest('tr');
            const ruleID = row.data( 'id' );

            $.post( noptinViewRules.ajaxurl, {
                action: 'noptin_delete_automation_rule',
                rule_id: ruleID,
                _ajax_nonce: noptinViewRules.nonce,
            } )

            // Fade out the row.
            row.fadeOut( 1000, function() {
                row.remove();
            })
        });
	});

})(jQuery);
