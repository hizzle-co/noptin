/**
 * Wordpress dependancies.
 */
import { CardFooter, CardHeader, CardBody, Icon, Button, Tip } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Local dependancies.
 */
import Setting from '../setting';

/**
 * Displays a wizard step.
 *
 * @param {Object} props
 * @param {String} props.title
 * @param {String} props.description
 * @param {Array} props.settings
 * @param {Function} props.handler
 * @param {Object} props.saved
 * @return {JSX.Element}
 */
export default function Step( { title, description, settings, handler, saved, onClickNext, onClickBack, isLastStep, isFirstStep } ) {

	// Prepares default settings.
	const defaults = settings.reduce( ( acc, setting ) => {
		acc[ setting.settingKey ] = saved[ setting.settingKey ] ? saved[ setting.settingKey ] : '';
		return acc;
	}, {} );

	const [ toSave, setToSave ] = useState( defaults );

	return (
		<>

			<CardHeader>
				<h3>{title}</h3>
			</CardHeader>

			{ description && (
				<CardBody>
					<Tip>{description}</Tip>
				</CardBody>
			) }

			<CardBody>
				{ settings.map( ( setting, index ) => (
					<Setting
						key={ index }
						settingKey={ setting.settingKey }
						saved={ toSave }
						setAttributes={ ( obj ) => setToSave( { ...toSave, ...obj } ) }
						setting={ setting }
					/>
				) ) }
			</CardBody>

			<CardFooter>

				{ ! isFirstStep && (
					<Button variant="secondary" onClick={ onClickBack }>
						<Icon icon="arrow-left-alt2" />
						{ __( 'Back', 'newsletter-optin-box' ) }
					</Button>
				) }

				{ ! isLastStep && (
					<Button variant="primary" onClick={ () => handler( toSave, onClickNext ) }>
						{ __( 'Next', 'newsletter-optin-box' ) }
						<Icon icon="arrow-right-alt2" />
					</Button>
				) }
			</CardFooter>
		</>
	);
}
