import get_triggers from './triggers';

export default function init( form ) {

	try {

		// Ensure we have a trigger.
		if ( form.dataset.trigger && form.dataset.type ) {

			const triggers = get_triggers( form );
			const trigger = form.dataset.trigger;

			if ( trigger && triggers[ trigger ] ) {
				triggers[ trigger ]()
			}
		}

	} catch( e ) {
		console.log( e )
	}

}
