import $ from './myquery';

export default function display( popup, force ) {

    // Set variables.
    const popup_type = popup.dataset.type;

    if ( ! window.noptinPopups[popup_type] ) {
        window.noptinPopups[popup_type] = {
            showing: false,
            closed: false,
        }
    }

    // Abort if a popup is already showing.
    if ( ! force && ( window.noptinPopups[popup_type].showing || window.noptinSubscribed ) ) {
        return;
    }

    // Do not display a popup that has been closed.
    if ( ! force ) {

        if ( ! popup.dataset.key ) {
            return;
        }

        if ( sessionStorage.getItem( "noptinFormDisplayed" + popup.dataset.key ) ) {
            return;
        }

    }

    // Log that we're already displayed the popup in this session.
	sessionStorage.setItem( "noptinFormDisplayed" + popup.dataset.key, '1' );

    // Indicate that we're displaying a popup.
    window.noptinPopups[popup_type].showing = true;

    // Closes the popup.
    const closePopup = () => {
        window.noptinPopups[popup_type].showing = false;
        $( popup ).removeClass( 'noptin-show' );
        $( 'body' ).removeClass( 'noptin-showing-' + popup_type );

        if ( 'popup' == popup_type ) {
            $( 'body' ).removeClass('noptin-hide-overflow');
        }

    }

    // Display the popup.
    $( popup ).addClass( 'noptin-show' );
    $( 'body' ).addClass( 'noptin-showing-' + popup_type );

    if ( 'popup' == popup_type ) {
        $( 'body' ).addClass('noptin-hide-overflow');
    }

    // Close the popup.
    $( popup ).find( '.noptin-close-popup' ).on( 'click', closePopup );

    if ( 'popup' == popup_type ) {
        $( '.noptin-popup-backdrop' ).on( 'click', closePopup );
    }

};
