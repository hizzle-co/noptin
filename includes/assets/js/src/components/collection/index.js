/**
 * External dependencies
 */
import { useEffect } from "@wordpress/element";
import {
	Notice,
	Flex,
	FlexBlock,
	FlexItem,
	__experimentalNavigatorProvider as NavigatorProvider,
	__experimentalNavigatorScreen as NavigatorScreen,
} from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { Provider, useAtomValue, useSetAtom } from "jotai";

/**
 * Internal dependencies.
 */
import Screen from "./screen";
import Navigation from "./navigation";
import * as store from "./store";

console.log( store );

/**
 * Renders the Collection.
 * @param {Object} props
 * @param {Object} props.components
 * @returns 
 */
const RenderCollection = ( { components } ) => {

	return (
		<>
			<FlexItem>
				<Navigation components={ components } />
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
		</>
	);
}

/**
 * Collection overview table.
 *
 * @param {Object} props
 * @param {string} props.namespace
 * @param {string} props.collection
 * @param {Object} props.components
 * @returns
 */
export default function Collection( { namespace, collection, components } ) {

	// Prepare the store.
	const setCollection = useSetAtom(store.collection);
	const setNamespace = useSetAtom(store.namespace);
	const setUrl = useSetAtom(store.url);
	const route = useAtomValue( store.route );

	// Set the collection and namespace once the component mounts.
	useEffect( () => {
		setCollection( collection );
		setNamespace( namespace );
	}, [] );

	// Watch for route changes.
	useEffect( () => {
		const updateURL = () => setUrl( window.location.href );
		window.addEventListener('locationchange', updateURL);
		return () => window.removeEventListener('locationchange', updateURL);
	}, []);

	// Render the collection.
	return (
		<Provider>
			<NavigatorProvider
				initialPath={ route.path ? route.path : '/' }
				as={Flex}
				direction="column"
				gap={ 4 }
				className="noptin-collection__wrapper"
				style={{ minHeight: '100vh' }}
			>
				<RenderCollection components={components} />
			</NavigatorProvider>
		</Provider>
	);
}
