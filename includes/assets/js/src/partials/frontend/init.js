import submit from './submit';

export default function init( form ) {

	form.addEventListener( 'submit', event => {
		submit( form );

		event.preventDefault();
	} );

}
