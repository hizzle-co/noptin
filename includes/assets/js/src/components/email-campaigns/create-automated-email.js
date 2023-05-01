/**
 * WordPress dependencies
 */
import {
	Flex,
	FlexBlock,
	FlexItem,
	Card,
	CardBody,
	CardFooter,
	SlotFillProvider,
	SVG,
	Path,
	Icon,
	Button,
	Notice,
	Spinner,
} from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

/**
 * Local dependancies.
 */
import Section from '../section';

/**
 * Displays a campaign type.
 *
 * @param {Object} props
 * @param {String} props.name The campaign type.
 * @param {String} props.title The campaign type title.
 * @param {String} props.description The campaign type description.
 * @param {String|Object} props.image The campaign type image.
 * @param {Boolean} props.is_available Whether the campaign type is available.
 * @param {String} props.create_url The campaign type creation URL.
 * @param {String} props.upgrade_url The campaign type upgrade URL.
 * @return {JSX.Element}
 */
function EmailType( {name, title, description, image, is_available, create_url, upgrade_url} ) {

	const Image = () => {

		// URLs.
		if ( typeof image === 'string' && image.startsWith( 'http' ) ) {
			return <img src={image} alt={title} />;
		}

		// Dashicons.
		if ( typeof image === 'string' ) {
			return <Icon size={64} icon={image} style={{ color: '#424242' }} />;
		}

		// SVG or Dashicons with fill color.
		if ( typeof image === 'object' ) {
			const fill = image.fill || '#008000';
			const path = image.path || '';
			const viewBox = image.viewBox || '0 0 64 64';

			if ( image.path ) {
				return (
					<SVG viewBox={viewBox} xmlns="http://www.w3.org/2000/svg">
						<Path fill={fill} d={path} />
					</SVG>
				);
			}

			return <Icon size={64} style={{ color: fill }} icon={image.icon} />;
		}

		return <Icon size={64} icon="email" style={{ color: '#424242' }} />;;
	}

	const buttonVariant = is_available ? 'primary' : 'secondary';
	const buttonIcon    = is_available ? 'plus' : 'lock';
	const buttonLabel   = is_available ? __( 'Set-up', 'newsletter-optin-box' ) : __( 'Upgrade', 'newsletter-optin-box' );
	const buttonUrl     = is_available ? create_url : upgrade_url;

	return (
		<FlexItem
			as={Card}
			className={`noptin-component-card noptin-automated-email-type noptin-automated-email-type__${name}`}
			variant="secondary"
		>

			<Flex direction="column" justify="space-between">

				<FlexBlock>
					<CardBody>
						<Flex wrap>

							<FlexItem className="noptin-component-card-image">
								<Image />
							</FlexItem>

							<FlexBlock className="noptin-component-card-content">
								<h3>{title}</h3>
								<p>{description}</p>

								{! is_available ? (
									<p style={{ color: '#a00' }}>
										<em>{__( 'Not available in your plan', 'newsletter-optin-box' )}</em>
									</p>
								) : null}
							</FlexBlock>

						</Flex>
					</CardBody>
				</FlexBlock>

				<FlexItem>
					<CardFooter className="noptin-email-type-action" justify="flex-end">
						<Button variant={buttonVariant} href={buttonUrl}>
							<Icon icon={buttonIcon} />&nbsp;
							<span className="noptin-email-type-action__label">{buttonLabel}</span>
						</Button>
					</CardFooter>
				</FlexItem>
			</Flex>
		</FlexItem>
	);
}

/**
 * Displays several campaign types.
 *
 * @param {Object} props
 * @param {Array} props.types The campaign types.
 * @param {String} props.title The campaign types title.
 * @return {JSX.Element}
 */
function EmailTypes( {types, title} ) {

	const [ showingAll, setShowingAll ] = useState( false );
	const shouldLimit = types.length > 3;

	if ( types.length === 0 ) {
		return null;
	}

	const visibleTypes = showingAll ? types : types.slice( 0, 3 );

	return (
		<Section className="noptin-automated-email-types" title={title}>

			<CardBody>
				<Flex className="noptin-component-card-list" justify="left" align="stretch" wrap>
					{visibleTypes.map( ( type, index ) => (
						<EmailType key={index} {...type} />
					))}
				</Flex>
			</CardBody>

			{shouldLimit ? (
				<CardFooter>
					<div className="noptin-automated-email-types__show-all">
						<Button isLink onClick={() => setShowingAll( ! showingAll )}>
							{showingAll ? __( 'Show less', 'newsletter-optin-box' ) : __( 'Show all', 'newsletter-optin-box' )}
						</Button>
					</div>
				</CardFooter>
			) : null}
			

		</Section>
	);
}

/**
 * Groups the campaign types by category.
 *
 * @param {Array} types The campaign types.
 * @returns {Object}
 */
function groupTypesByCategory( types ) {
	const categories = {};

	types.forEach( type => {
		if ( ! categories[ type.category ] ) {
			categories[ type.category ] = [];
		}

		categories[ type.category ].push( type );
	});

	return categories;
}

/**
 * Displays the app.
 *
 * @param {Object} props
 * @returns {JSX.Element}
 */
export default function CreateAutomatedEmail() {

	// Prepare the app.
	const [ loading, setLoading ] = useState( true );
	const [ types, setTypes ]     = useState( [] );
	const [ error, setError ]     = useState( null );

	// Fetch the campaign types.
	useEffect( () => {
		apiFetch( { path: '/noptin/v1/automated-email-campaign-types' } )
			.then( types => {
				setTypes( groupTypesByCategory( types ) );
			})
			.catch( error => {
				setError( error );
			})
			.finally( () => {
				setLoading( false );
			});
	}, [] );

	// Loading indicator.
	if ( loading ) {
		return <Spinner />;
	}

	// Spinner.
	if ( error ) {
		return <Notice status="error" isDismissible={false}>{error.message}</Notice>;
	}

	const categories = Object.keys( types );

	// Display the app.
	return (
		<div className="noptin-es6-app">
			<SlotFillProvider>
				{categories.map( ( category, index ) => <EmailTypes key={index} title={category} types={types[ category ]} />)}
			</SlotFillProvider>
		</div>
	);
}
