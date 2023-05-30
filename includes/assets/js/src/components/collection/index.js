/**
 * External dependencies
 */
import {
	Flex,
	FlexBlock,
	FlexItem,
	__experimentalNavigatorProvider as NavigatorProvider,
	__experimentalNavigatorScreen as NavigatorScreen,
} from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { useAtomValue, useAtom } from "jotai";
import { useHydrateAtoms } from 'jotai/utils';

/**
 * Internal dependencies.
 */
import ErrorBoundary from "./error-boundary";
import Screen from "./screen";
import Navigation from "./navigation";
import * as store from "./store";
import initStore from "../../store-data";
import { useSchema } from "../../store-data/hooks";

// Initialize the store.
initStore( 'noptin', 'subscribers' );

/**
 * Renders the Collection.
 * @returns 
 */
const RenderCollection = () => {

	const [components] = useAtom( store.components );

	return (
		<>
			<FlexItem>
				<Navigation />
			</FlexItem>

			<FlexBlock>
				{ Object.keys( components ).map( ( component ) => {
					return (
						<NavigatorScreen key={ component } path={ component }>
							<ErrorBoundary>
								<Screen path={ component } />
							</ErrorBoundary>
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

	console.log( useSchema( namespace, collection ) );

	// Prepare the store.
	const route = useAtomValue( store.route );

	// Set the collection and namespace once the component mounts.
	useHydrateAtoms([
		[store.collection, collection],
		[store.namespace, namespace],
		[store.components, components],
	]);

	// Render the collection.
	return (
		<NavigatorProvider
			initialPath={ route.path ? route.path : '/' }
			as={Flex}
			direction="column"
			gap={ 4 }
			className="noptin-collection__wrapper"
			style={{ minHeight: '100vh' }}
		>
			<ErrorBoundary>
				<RenderCollection />
			</ErrorBoundary>
		</NavigatorProvider>
	);
}
