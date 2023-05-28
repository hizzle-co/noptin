const badgeCache = {};
const allBadges = [ 'info', 'success', 'warning', 'error', 'new', 'notification', 'pro' ];

/**
 * Generates a badge color based on the given string.
 *
 * @param {string} str The string to generate a color for.
 * @return {Object} The generated background
 */
export default function getEnumBadge( str ) {

    // Try to guess the color from the string.
    if ( ['subscribed', 'active', 'yes', 'true', '1'].includes( str ) ) {
        return 'success';
    }

    if ( ['unsubscribed', 'inactive', 'no', 'false', '0'].includes( str ) ) {
        return 'error';
    }

    if ( ['pending', 'waiting', 'maybe', '2'].includes( str ) ) {
        return 'warning';
    }

    // Try to retrieve the color from the cache.
    if ( badgeCache[ str ] ) {
        return badgeCache[ str ];
    }

    // Generate a random color.
    badgeCache[ str ] = allBadges[ Math.floor( Math.random() * allBadges.length ) ];

    return badgeCache[ str ];
}
