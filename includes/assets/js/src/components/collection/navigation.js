/**
 * External dependencies
 */
import { plus, cloudUpload, trash } from "@wordpress/icons";
import { useMemo } from "@wordpress/element";
import { __ } from "@wordpress/i18n";
import {
	Flex,
	FlexItem,
	FlexBlock,
	Card,
	CardHeader,
	Button,
	Icon,
	__experimentalText as Text,
} from "@wordpress/components";

import AppIcon from "./app-icon";
import { useRoute } from "./hooks";
import { useRecord, useSchema } from "../../store-data/hooks";

/**
 * Displays the collection navigation title.
 */
const CollectionTitle = ( { append, avatarURL, isSingle } ) => {
	const { namespace, collection } = useRoute();
	const { data } = useSchema( namespace, collection );
	append = append ? ` - ${append}` : '';

	// Nav title.
	const navTitle = useMemo( () => {

		if ( isSingle && data.labels?.view_item ) {
			return `${data.labels.view_item}${append}`;
		}

		if ( data.labels?.name ) {
			return `${data.labels.name}${append}`;
		}

		return `Noptin${append}`;
	}, [ data, append ] );

	// APP Icon.
	const appIcon = avatarURL ? <img src={ avatarURL } alt={ navTitle } width={24} height={24} style={{ borderRadius: '50%' }} /> : <AppIcon />;

	return (
		<Flex justify="start" wrap>
			<FlexItem>
				{appIcon}
			</FlexItem>
			<FlexItem>
				<Text size={ 16 } weight={ 600 } as="h2" color="#23282d">
					{navTitle}
				</Text>
			</FlexItem>
		</Flex>
	);
}

/**
 * Displays the collection's record navigation title.
 */
const RecordTitle = () => {
	// Prepare the state.
	const { namespace, collection, args: { id } } = useRoute();

	const schema = useSchema( namespace, collection );
	const record = useRecord( namespace, collection, id );

	if ( record.isResolving() || record.hasResolutionFailed() ) {
		return <CollectionTitle />;
	}

	const sprintWith = schema.data.id_prop ? record.record[schema.data.id_prop] : record.record.id;
	const avatarURL  = record.record.avatar_url;

	return <CollectionTitle append={sprintWith} avatarURL={avatarURL} isSingle />;
}

/**
 * Displays the collection navigation.
 *
 * @returns {JSX.Element} Table actions.
 */
export default function Navigation() {

	const { namespace, collection, path, navigate } = useRoute();
	const { data }        = useSchema( namespace, collection );
	const isEditingRecord = path === `/${namespace}/${collection}/update`;

	// Filter out routes that don't have a display.
	const TheRoutes = Object.keys( data.routes ).map( ( route ) => {

		// Don't display routes that don't have a display.
		if ( data.routes[ route ].hide || path === route ) {
			return null;
		}

		// Prepare the icon.
		let icon    = data.routes[ route ].icon;
		let variant = 'secondary';

		if ( `/${namespace}/${collection}` === route ) {
			variant = 'primary';
		}

		// Add icon if it doesn't exist.
		if ( ! icon ) {

			switch (data.routes[ route ].component) {
				case 'create-record':
					icon = plus;
					variant = 'primary';
					break;
				case 'import':
					icon = cloudUpload;
					break;
				case 'delete':
					icon = trash;
					break;
			}
		}

		return (
			<FlexItem key={ route }>
				<Button onClick={ () => navigate( route ) } variant={ variant }>
					{ icon && <Icon icon={ icon } /> }
					{ data.routes[ route ].title }
				</Button>
			</FlexItem>
		);

	} );

	return (
		<Card>
			<CardHeader>
				<Flex wrap>

					<FlexBlock>
						{ isEditingRecord ? <RecordTitle /> : <CollectionTitle /> }
					</FlexBlock>

					{ TheRoutes }

				</Flex>
			</CardHeader>
		</Card>
	);
};
