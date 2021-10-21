import $ from './myquery';

export default function display( form, force ) {
    const form_type = form.dataset.type;
    let closed = false;

    if ( ! window.noptinPopups[form_type] ) {
        window.noptinPopups[form_type] = {
            showing: false,
            closed: false,
        }
    }

    if ( 'top_bar' == form_type ) {
        force = true;
    }

    // Closes the popup.
    const closePopup = () => {
        closed = true;

        $( form ).removeClass( 'noptin-showing-popup' );
    }

    // Displays the popup.
    const displayPopup = () => {

        // Maybe log the form view.
        if ( form.dataset.source ) {

        }
        window.noptinPopupShowing = true;
        form.dataset.trigger
        $( form ).removeClass( 'noptin-showing-popup' );
    }

    // Close the popup on clicking inside.
    $( form ).find( '.noptin-close-popup' ).on( 'click', closePopup );

    
    // TODO: If hiding from existing subscribers, check cookie.
    return {

        // Avoid displaying a popup when the user subscribes via one popup.
        subscribed: false,

        // Hides a displayed popup
        hidePopup() {
            popups.close()
        },

        // Log form view.
        logFormView(form_id) {
            $.post(noptin.ajaxurl, {
                action: "noptin_log_form_impression",
                _wpnonce: noptin.nonce,
                form_id: form_id,
            })
        },

        // Display a popup.
        displayPopup(popup, force) {

            if ($(popup).closest('.noptin-optin-main-wrapper').hasClass('noptin-slide_in-main-wrapper')) {
                return this.displaySlideIn(popup, force)
            }

            // Do not display several popups at once.
            if ( ! force && ( popups.is_showing || this.subscribed ) ) {
                return;
            }

            // Log form view
            this.logFormView($(popup).find('input[name=noptin_form_id]').val())

            // Replace the content if a popup is already showing.
            if ( popups.is_showing ) {
                popups.replaceContent( $( popup ).closest('.noptin-popup-main-wrapper') )
            } else {
                popups.open( $( popup ).closest('.noptin-popup-main-wrapper') )
            }

            // Some forms are only set to be displayed once per session.
            var id = $(popup).find('input[name=noptin_form_id]').val()
            if (typeof $(popup).data('once-per-session') !== 'undefined') {
                localStorage.setItem("noptinFormDisplayed" + id, new Date().getTime());
            } else {
                sessionStorage.setItem("noptinFormDisplayed" + id, '1');
            }

        },

        // Displays a slide in and attaches "close" event handlers.
        displaySlideIn( slide_in, force ) {

            if (!force && this.subscribed) {
                return;
            }

            //Log form view
            this.logFormView($(slide_in).find('input[name=noptin_form_id]').val())

            //Display the form
            $(slide_in).addClass('noptin-showing')
        }
    }

};
