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
import { Avatar } from "../styled-components";
import { normalizeAvatarColors } from "./records-table/display-cell";
import { getPath, getNewPath, navigateTo } from "../navigation";
import { useRecord } from "../../store-data/hooks";

/**
 * Displays the collection navigation title.
 */
const CollectionTitle = ( { namespace, collection, append, avatarURL, isSingle, schema } ) => {
	avatarURL = normalizeAvatarColors( avatarURL || schema?.avatar_url, append );
	append = append ? ` - ${append}` : '';

	// Nav title.
	const navTitle = useMemo( () => {

		if ( isSingle && schema?.labels?.singular_name ) {
			return `${schema.labels.singular_name}${append}`;
		}

		if ( schema?.labels?.name ) {
			return `${schema.labels.name}${append}`;
		}

		return `Noptin${append}`;
	}, [schema, append] );

	return (
		<Flex justify="start" wrap>
			<FlexItem>
				{avatarURL && <Avatar src={avatarURL} alt={navTitle} width={24} height={24} />}
			</FlexItem>
			<FlexItem>
				<Text size={16} weight={600} as="h2" color="#23282d">
					{navTitle}
				</Text>
			</FlexItem>
			<FlexItem>
				{`/${namespace}/${collection}` !== getPath() ? (
					<Button
						variant="primary"
						onClick={() => navigateTo( getNewPath( {}, `/${namespace}/${collection}` ) )}
						style={{ marginLeft: '10px' }}
					>
						{schema?.labels?.view_items || __( 'View Records', 'newsletter-optin-box' )}
					</Button>
				) : (
					<Button
						variant="primary"
						onClick={() => navigateTo( getNewPath( {}, `/${namespace}/${collection}/add` ) )}
						style={{ marginLeft: '10px' }}
					>
						{schema?.labels?.add_new || __( 'Add New', 'newsletter-optin-box' )}
					</Button>
				)}
			</FlexItem>
		</Flex>
	);
}

/**
 * Displays the collection's record navigation title.
 */
const RecordTitle = ( { namespace, collection, id, schema } ) => {
	// Prepare the state.
	const record = useRecord( namespace, collection, id );

	if ( 'SUCCESS' !== record.status ) {
		return <CollectionTitle namespace={namespace} collection={collection} schema={schema} isSingle />;
	}

	const sprintWith = schema.id_prop ? record.data[schema.id_prop] : '';
	const avatarURL = record.data.avatar_url;

	return <CollectionTitle namespace={namespace} collection={collection} append={sprintWith} avatarURL={avatarURL} schema={schema} isSingle />;
}

/**
 * Displays the collection navigation.
 *
 * @returns {JSX.Element} Table actions.
 */
export const Navigation = ( { schema, namespace, collection, id } ) => (
	<Card>
		<CardHeader>
			<Flex wrap>

				<FlexBlock>
					{id ?
						( <RecordTitle namespace={namespace} collection={collection} id={id} schema={schema} /> ) :
						( <CollectionTitle namespace={namespace} collection={collection} schema={schema} /> )
					}
				</FlexBlock>

				{schema.routes && Object.keys( schema.routes ).map( ( route ) => {

					return (
						<FlexItem key={route}>
							{schema.routes[route].href ? (
								<Button href={schema.routes[route].href} variant="secondary">
									{schema.routes[route].title}
								</Button>
							) : (
								<Button onClick={() => navigateTo( getNewPath( {}, route ) )} variant="secondary">
									{schema.routes[route].title}
								</Button>
							)}
						</FlexItem>
					);

				} )}

			</Flex>
		</CardHeader>
	</Card>
);
