const badgeCache = {};
import uniqolor from './uniqolor';

/**
 * Generates a random color and appropriate text color.
 */
const randomColor = () => {

	// Generate a random int.
	const randomInt = (min, max) => {
		return Math.floor(Math.random() * (max - min + 1)) + min;
	};

	// Generate a random color.
	const hue = randomInt(20, 360);
	const saturation = randomInt(60, 100);
	const lightness = randomInt(30, 45);

	// Generate a matching text color.
	const textColor = lightness > 70 ? '#111111' : '#ffffff';

	// Return the colors.
	return {
		backgroundColor: `hsl(${hue},${saturation}%,${lightness}%)`,
		color: textColor,
	}
};

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
			backgroundColor: '#78c67a',
			color: '#111111',
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

	// Generate unique color for the string.
	const color = uniqolor( str, {
		saturation: [60, 100],
   		lightness: [30, 45],
	} );

	return {
		backgroundColor: color.color,
		color: color.isLight ? '#111111' : '#ffffff',
	}
}
