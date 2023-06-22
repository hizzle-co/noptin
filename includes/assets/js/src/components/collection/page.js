/**
 * External dependencies.
 */
import {
	useParams,
	Outlet,
} from 'react-router-dom';
import { CardBody, Spinner, Notice, Flex, FlexBlock, FlexItem, Fill } from '@wordpress/components';
import { __ } from "@wordpress/i18n";

/**
 * Internal dependencies.
 */
import ErrorBoundary from "./error-boundary";
import initStore from "../../store-data";
import Wrap  from './wrap';
import { useSchema } from '../../store-data/hooks';
import { useCurrentSchema } from './hooks';
import { Navigation } from "./navigation";
import UpsellCard from "../upsell-card";

/**
 * Displays an entire page.
 *
 * @param {Object} props
 */
export const Page = () => {

    // Get the collection and namespace from the URL.
    const { namespace, collection } = useParams();

	// Init the store if it's not already initialized.
	initStore( namespace, collection );

	// Get the schema.
    const schema = useSchema( namespace, collection );

    // Show the loading indicator if we're loading the schema.
	if (schema.isResolving || ! schema.hasResolved) {

		return (
			<Wrap title={__('Loading', 'newsletter-optin-box')}>
				<CardBody>
					<Spinner />
				</CardBody>
			</Wrap>
		);
	}

	// Show error if any.
	if ( 'ERROR' === schema.status) {

		return (
			<Wrap title={__('Error', 'newsletter-optin-box')}>
				<CardBody>
					<Notice status="error" isDismissible={false}>
						{schema.error?.message || __('An unknown error occurred.', 'newsletter-optin-box')}
					</Notice>
				</CardBody>
			</Wrap>
		);
	}

	// Render the rest of the page.
	return (
		<Flex gap={2} direction="column">
			<FlexItem>
				<Navigation />
			</FlexItem>

			<FlexBlock>
				<ErrorBoundary>
					<Outlet />
				</ErrorBoundary>
				<ErrorBoundary>
					<CollectionFills />
				</ErrorBoundary>
			</FlexBlock>
		</Flex>
	);
};

/**
 * Renders a collection's custom fills.
 */
const CollectionFills = () => {

	const {data} = useCurrentSchema();

	if ( data?.fills ) {
		return data.fills.map( (fill) => (
			<Fill key={fill.name} name={fill.name}>
				{ fill.content && <span dangerouslySetInnerHTML={{ __html: fill.content} } /> }
				{ fill.upsell && <UpsellCard upsell={fill.upsell} /> }
			</Fill>
		) );
	}

};
