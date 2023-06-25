const badgeCache = {};
const allBadges = [ 'info', 'success', 'warning', 'error', 'new', 'notification', 'pro' ];

/**
 * Generates a badge color based on the given string.
 *
 * @param {string} str The string to generate a color for.
 * @return {Object} The generated background and text colors.
 */
export default function getEnumBadge( str ) {

    // Try to guess the color from the string.
    if ( ['subscribed', 'active', 'yes', 'true', '1'].includes( str ) ) {
        return {
            backgroundColor: '#d8eacc',
            color: '#241c15',
        }
    }

    if ( ['unsubscribed', 'inactive', 'no', 'false', '0'].includes( str ) ) {
        return {
            backgroundColor: '#fbcfbd',
            color: '#241c15',
        }
    }

    if ( ['pending', 'waiting', 'maybe', '2'].includes( str ) ) {
        return {
            backgroundColor: '#fbeeca',
            color: '#241c15',
        }
    }

    // Try to retrieve the color from the cache.
    if ( badgeCache[ str ] ) {
        return badgeCache[ str ];
    }

    // Generate a random background color and a contrasting text color.
    const backgroundColor = '#' + Math.floor( Math.random() * 16777215 ).toString( 16 );
    const color           = backgroundColor.replace( '#', '' ).match( /.{2}/g ).map( ( x ) => parseInt( x, 16 ) ).reduce( ( a, b, i ) => i < 3 ? a + b : a ) > 382 ? '#000' : '#fff';

    badgeCache[ str ] = {
        backgroundColor,
        color,
    }

    return badgeCache[ str ];
}
