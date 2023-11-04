/**
 * External dependencies.
 */
import {
	useParams,
	Outlet,
} from 'react-router-dom';
import { CardBody, Spinner, Notice, Flex, FlexBlock, FlexItem, Fill, Modal } from '@wordpress/components';
import { __ } from "@wordpress/i18n";
import { useCallback } from '@wordpress/element';

/**
 * Internal dependencies.
 */
import ErrorBoundary, { withErrorBoundaryWrapper } from "./error-boundary";
import Wrap from './wrap';
import { useSchema } from '../../store-data/hooks';
import { Navigation } from "./navigation";
import UpsellCard from "../upsell-card";
import { useNavigateCollection } from "./hooks";

export const withSchema = ( WrappedComponent ) => {
	return withErrorBoundaryWrapper(
		function WithSchemaProps( props ) {

			// Get the schema.
			const schema = useSchema( props.namespace, props.collection );

			// Show the loading indicator if we're loading the schema.
			if ( schema.isResolving || !schema.hasResolved ) {

				return (
					<Wrap title={__( 'Loading', 'newsletter-optin-box' )}>
						<CardBody>
							<Spinner />
						</CardBody>
					</Wrap>
				);
			}

			// Show error if any.
			if ( 'ERROR' === schema.status ) {

				return (
					<Wrap title={__( 'Error', 'newsletter-optin-box' )}>
						<CardBody>
							<Notice status="error" isDismissible={false}>
								{schema.error?.message || __( 'An unknown error occurred.', 'newsletter-optin-box' )}
							</Notice>
						</CardBody>
					</Wrap>
				);
			}

			return <WrappedComponent {...props} schema={schema.data} />;
		}
	);
};

/**
 * Displays page content.
 *
 * @param {Object} props
 */
const PageContent = withSchema( ( { namespace, collection, isParent, id, schema } ) => (
	<Flex gap={2} direction="column">
		{isParent && (
			<FlexItem>
				<Navigation namespace={namespace} collection={collection} id={id} schema={schema} />
			</FlexItem>
		)}

		<FlexBlock>
			<ErrorBoundary>
				<Outlet />
			</ErrorBoundary>
		</FlexBlock>

		{schema.fills && schema.fills.map( ( fill ) => (
			<Fill key={fill.name} name={`${fill.name}${isParent ? '' : '--inner'}`}>
				<ErrorBoundary>
					{fill.content && <span dangerouslySetInnerHTML={{ __html: fill.content }} />}
					{fill.upsell && <UpsellCard upsell={fill.upsell} />}
				</ErrorBoundary>
			</Fill>
		) )}
	</Flex>
)
);

/**
 * Displays an entire page.
 *
 * @param {Object} props
 */
export const Page = () => {

	const { namespace, collection, id } = useParams();

	// Render the rest of the page.
	return <PageContent namespace={namespace} collection={collection} id={id} isParent={true} />;
};

/**
 * Displays an entire page.
 *
 * @param {Object} props
 */
export const InnerPage = () => {

	const { id, tab, innerTab, innerNamespace, innerCollection, innerId } = useParams();
	const navigateTo = useNavigateCollection();
	const goBack = useCallback( () => navigateTo( `${id}/${tab}` ), [navigateTo, tab, id] );
	const schema = useSchema( innerNamespace, innerCollection );

	const labels = schema.data?.labels || {};
	const titles = {
		add_new_item: __( 'Add New Item', 'newsletter-optin-box' ),
		view_item: __( 'View Item', 'newsletter-optin-box' ),
	}

	const tabs = {
		add: 'add_new_item',
		edit: 'view_item',
	}

	const title = tabs[innerTab] ? ( labels[tabs[innerTab]] || titles[tabs[innerTab]] ) : ( labels.name || __( 'Items', 'newsletter-optin-box' ) );

	return (
		<Modal title={title} onRequestClose={goBack}>
			<PageContent namespace={innerNamespace} collection={innerCollection} id={innerId} isParent={false} />
		</Modal>
	);
};
