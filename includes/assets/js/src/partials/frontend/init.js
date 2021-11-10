import submit from './submit';
import $ from './myquery';

export default function init( form ) {
	$( form ).on( 'submit', event => {
		event.preventDefault();

		try {
			submit( form );
		} catch(e) {
			console.log(e)
			form.submit();
		}

	});
}
