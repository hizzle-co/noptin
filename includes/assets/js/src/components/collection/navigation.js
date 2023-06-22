/**
 * External dependencies
 */
import { useMemo } from "@wordpress/element";
import { __ } from "@wordpress/i18n";
import {
	Flex,
	FlexItem,
	FlexBlock,
	Card,
	CardHeader,
	Button,
	__experimentalText as Text,
} from "@wordpress/components";
import { useParams } from 'react-router-dom';
import { useCurrentSchema, useCurrentRecord } from "./hooks";
import { Avatar } from "../styled-components";
import { getPath, getNewPath, navigateTo } from "../navigation";

/**
 * Displays the collection navigation title.
 */
const CollectionTitle = ( { append, avatarURL, isSingle } ) => {
	const { namespace, collection } = useParams();
	const { data } = useCurrentSchema();
	append = append ? ` - ${append}` : '';
	avatarURL = avatarURL || data?.avatar_url;

	// Nav title.
	const navTitle = useMemo( () => {

		if ( isSingle && data.labels?.singular_name ) {
			return `${data.labels.singular_name}${append}`;
		}

		if ( data.labels?.name ) {
			return `${data.labels.name}${append}`;
		}

		return `Noptin${append}`;
	}, [ data, append ] );

	return (
		<Flex justify="start" wrap>
			<FlexItem>
				{avatarURL && <Avatar src={ avatarURL } alt={ navTitle } width={24} height={24} />}
			</FlexItem>
			<FlexItem>
				<Text size={ 16 } weight={ 600 } as="h2" color="#23282d">
					{navTitle}
				</Text>
			</FlexItem>
			<FlexItem>
				{ `/${namespace}/${collection}` !== getPath() ? (
					<Button
						variant="primary"
						onClick={ () => navigateTo( getNewPath( {}, `/${namespace}/${collection}` ) ) }
						style={ { marginLeft: '10px' } }
					>
						{ data.labels?.view_items || __( 'View Records', 'newsletter-optin-box' ) }
					</Button>
				) : (
					<Button
						variant="primary"
						onClick={ () => navigateTo( getNewPath( {}, `/${namespace}/${collection}/add` ) ) }
						style={ { marginLeft: '10px' } }
					>
						{ data.labels?.add_new || __( 'Add New', 'newsletter-optin-box' ) }
					</Button>
				) }
			</FlexItem>
		</Flex>
	);
}

/**
 * Displays the collection's record navigation title.
 */
const RecordTitle = () => {
	// Prepare the state.
	const { data } = useCurrentSchema();
	const record = useCurrentRecord();

	if ( 'SUCCESS' !== record.status ) {
		return <CollectionTitle />;
	}

	const sprintWith = data.id_prop ? record.data[data.id_prop] : record.data.id;
	const avatarURL  = record.data.avatar_url;

	return <CollectionTitle append={sprintWith} avatarURL={avatarURL} isSingle />;
}

/**
 * Displays the collection navigation.
 *
 * @returns {JSX.Element} Table actions.
 */
export const Navigation = () => {

	const { data } = useCurrentSchema();
	const { id } = useParams();

	return (
		<Card>
			<CardHeader>
				<Flex wrap>

					<FlexBlock>
						{ id ? <RecordTitle /> : <CollectionTitle /> }
					</FlexBlock>

					{ data.routes && Object.keys( data.routes ).map( ( route ) => {

						return (
							<FlexItem key={ route }>
								{ data.routes[ route ].href ? (
									<Button href={ data.routes[ route ].href } variant="secondary">
										{ data.routes[ route ].title }
									</Button>
								) : (
									<Button onClick={ () => navigateTo( getNewPath( {}, route ) ) } variant="secondary">
										{ data.routes[ route ].title }
									</Button>
								) }
							</FlexItem>
						);

					} )}

				</Flex>
			</CardHeader>
		</Card>
	);
};
