/**
 * Generates a random string.
 *
 *
 * @return {String}
 */
export default function randomString() {
	var rand = Math.random()
	return 'key' + rand.toString(36).replace(/[^a-z]+/g, '')
}