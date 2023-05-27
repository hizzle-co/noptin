/**
 * External dependencies
 */
import { useState } from "@wordpress/element";
import {
	Notice,
	Flex,
	FlexBlock,
	FlexItem,
	__experimentalNavigatorProvider as NavigatorProvider,
	__experimentalNavigatorScreen as NavigatorScreen,
} from "@wordpress/components";
import { __ } from "@wordpress/i18n";

/**
 * Internal dependencies.
 */
import Screen from "./screen";
import Navigation from "./navigation";

/**
 * Collection overview table.
 * @param {Object} props
 * @param {string} props.namespace
 * @param {string} props.collection
 * @param {Object} props.components
 * @returns 
 */
export default function Collection( { namespace, collection, components } ) {

	// Set the current collection.
	const [ currentCollection, setCurrentCollection ] = useState( collection );

	// Set the current namespace.
	const [ currentNamespace, setCurrentNamespace ] = useState( namespace );

	// Fetch current URL params.
	const urlParams = new URLSearchParams( window.location.search );

	// Current component state.
	const [ currentCompoent, setCurrentComponent ] = useState( urlParams.get( 'section' ) || '/' );

	// Add all url param to the current component props.
	const [ componentProps, setComponentProps ] = useState( {
		[currentCompoent]: { ...urlParams.entries() }
	} );

	// If no namespace or collection, abort.
	if ( ! currentNamespace || ! currentCollection ) {
		return (
			<Notice status="error">
				{ __( 'No namespace or collection provided. Please check your browser console for any errors.', 'newsletter-optin-box' ) }
			</Notice>
		);
	}

	// Render the current component.
	const renderCompoent = components[ currentCompoent ] ? components[ currentCompoent ] : components[ '/' ];

	return (
		<NavigatorProvider
			initialPath={ components[ currentCompoent ] ? currentCompoent : '/' }
			as={Flex}
			direction="column"
			gap={ 4 }
			className="noptin-collection__wrapper"
			style={{ minHeight: '100vh' }}
		>

			<FlexItem>
				<Navigation
					components={ components }
					selected={ renderCompoent }
					setSelected={ setCurrentComponent }
					setCollection={ setCurrentCollection }
					setNamespace={ setCurrentNamespace }
				/>
			</FlexItem>

			<FlexBlock>
				{ Object.keys( components ).map( ( component ) => {

					return (
						<NavigatorScreen key={ component } path={ component }>
							<Screen path={ component } {...components[ component ]} />
						</NavigatorScreen>
					);
				} ) }
			</FlexBlock>
		</NavigatorProvider>
	);
}
