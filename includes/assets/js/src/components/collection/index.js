/**
 * External dependencies
 */
import {
	Flex,
	FlexBlock,
	FlexItem,
	Spinner,
	Notice,
	CardBody,
	__experimentalNavigatorProvider as NavigatorProvider,
	__experimentalNavigatorScreen as NavigatorScreen,
	__experimentalUseNavigator as useNavigator,
} from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { useState, useEffect } from "@wordpress/element";
import { getQueryArg } from "@wordpress/url";

/**
 * Internal dependencies.
 */
import ErrorBoundary from "./error-boundary";
import Screen from "./screen";
import Navigation from "./navigation";
import initStore from "../../store-data";
import { useRoute, URLContext } from "./hooks";
import { useSchema } from "../../store-data/hooks";
import Wrap from "./wrap";

// Initialize the store.
initStore('noptin', 'subscribers');

/**
 * Renders the Collection.
 * @returns 
 */
const RenderCollection = () => {

	const { namespace, collection } = useRoute();
	const schema = useSchema(namespace, collection);

	// Show the loading indicator if we're loading the schema.
	if (schema.isResolving()) {

		return (
			<Wrap title={__('Loading', 'newsletter-optin-box')}>
				<CardBody>
					<Spinner />
				</CardBody>
			</Wrap>
		);
	}

	// Show error if any.
	if (schema.hasResolutionFailed()) {
		const error = records.getResolutionError();

		return (
			<Wrap title={__('Error', 'newsletter-optin-box')}>
				<CardBody>
					<Notice status="error" isDismissible={false}>
						{error.message || __('An unknown error occurred.', 'newsletter-optin-box')}
					</Notice>
				</CardBody>
			</Wrap>
		);
	}

	return (
		<>
			<FlexItem>
				<Navigation />
			</FlexItem>

			<FlexBlock>
				{Object.keys(schema.data.routes).map((route) => {
					return (
						<NavigatorScreen key={route} path={route}>
							<ErrorBoundary>
								<Screen path={route} />
							</ErrorBoundary>
						</NavigatorScreen>
					);
				})}
			</FlexBlock>
		</>
	);
}

/**
 * Collection overview table.
 *
 * @returns
 */
const Collection = () => {
	const { goTo } = useNavigator();
	const [url, setURL] = useState(window.location.href);

	// Watch for hash changes.
	useEffect(() => {

		const handleURLChange = () => {
			setURL(window.location.href);

			const newPath = getQueryArg(window.location.href, 'hizzle_path');

			if (newPath) {
				goTo(newPath);
			}
		};

		window.addEventListener('popstate', handleURLChange);
		return () => {
			window.removeEventListener('popstate', handleURLChange);
		};
	}, []);

	// Render the collection.
	return (
		<URLContext.Provider value={[url, setURL]}>
			{url}
			<ErrorBoundary>
				<RenderCollection />
			</ErrorBoundary>
		</URLContext.Provider>
	);
}

/**
 * Wraps the navigation provider around the collection.
 *
 * @param {Object} props
 * @param {string} props.defaultRoute The default route.
 * @returns 
 */
const WithNavigationProvider = ({ defaultRoute }) => (
	<NavigatorProvider
		initialPath={defaultRoute}
		as={Flex}
		direction="column"
		gap={4}
		className="noptin-collection__wrapper"
		style={{ minHeight: '100vh' }}
	>
		<Collection />
	</NavigatorProvider>
);

export default WithNavigationProvider;
