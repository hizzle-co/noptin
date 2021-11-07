import get_triggers from './triggers';

export default function init( popup ) {

	try {

		// Ensure we have a trigger.
		if ( popup.dataset.trigger && popup.dataset.type ) {

			const triggers = get_triggers( popup );
			const trigger = popup.dataset.trigger;

			if ( trigger && triggers[ trigger ] ) {
				triggers[ trigger ]()
			}
		}

	} catch( e ) {
		console.log( e )
	}

}
