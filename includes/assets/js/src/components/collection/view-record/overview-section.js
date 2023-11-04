/**
 * External dependencies
 */
import { useState } from "@wordpress/element";
import { Card, CardBody, CardHeader, Button, Flex, FlexItem } from "@wordpress/components";
import copy from 'copy-to-clipboard';
import { __ } from "@wordpress/i18n";

/**
 * Internal dependencies
 */
import { useRecord, useRecordOverview } from "../../../store-data/hooks";
import { LoadingPlaceholder, CopiedText, withBottomMargin } from "../../styled-components";
import List from "../../list";
import StatCard from "../stat-card";
import { useNavigateCollection } from "../hooks";

/**
 * Displays a card list.
 *
 * @param {Object} props
 * @param {Array} props.items
 * @param {String} props.title
 */
const CardList = ( { items, title } ) => (
	<Card className={withBottomMargin}>
		<CardHeader>
			{title}
		</CardHeader>
		<CardBody>
			<List items={items} />
		</CardBody>
	</Card>
);

/**
 * Displays stat cards.
 *
 * @param {Object} props
 * @param {Array} props.cards
 */
const StatCards = ( { cards } ) => (
	<Flex className={withBottomMargin} gap={2} wrap>
		{cards.map( ( { title, value, status } ) => (
			<FlexItem key={title}>
				<StatCard
					status={status || 'info'}
					label={title} value={<span dangerouslySetInnerHTML={{ __html: value }} />}
				/>
			</FlexItem>
		) )}
	</Flex>
);

/**
 * Displays a normal card.
 *
 * @param {Object} props
 * @param {String} props.heading The card heading.
 * @param {String} props.buttonText The card button text.
 * @param {Object} props.buttonLink The card button link.
 */
const NormalCard = ( { content, buttonText, buttonLink } ) => (
	<Card className={withBottomMargin} variant="secondary">
		<CardBody>
			<div className={withBottomMargin} dangerouslySetInnerHTML={{ __html: content }} />

			{ ( buttonText && buttonLink ) && (
				<Button
					variant="secondary"
					href={buttonLink}
					text={buttonText}
				/>
			) }
		</CardBody>
	</Card>
);

/**
 * Displays a delete link.
 */
const DeleteLink = ( { confirm, label, namespace, collection, id, basePath } ) => {

	// Prepare the state.
	const record = useRecord( namespace, collection, id );
	const navigateTo = useNavigateCollection();

	// A function to delete a record.
	const onDeleteRecord = () => {

		// Confirm.
		if ( !window.confirm( confirm || __( 'Are you sure you want to delete this record?', 'newsletter-optin-box' ) ) ) {
			return;
		}

		// Delete the record.
		record.delete();

		// Navigate back to the list.
		navigateTo( basePath )
	}

	return (
		<FlexItem>
			<Button isDestructive onClick={onDeleteRecord} variant="secondary">
				{label}
			</Button>
		</FlexItem>
	);
}

/**
 * Displays a copy link.
 */
const CopyLink = ( { value, label } ) => {

	// Prepare the state.
	const [copied, setCopied] = useState( false );

	// Click handler.
	const clickHandler = () => {
		copy( value, {
			format: 'text/plain',
			onCopy: () => {
				setCopied( true );

				setTimeout( () => {
					setCopied( false );
				}, 1000 );
			}
		} );
	}

	return (
		<FlexItem>
			<Button onClick={clickHandler} variant="secondary">
				{copied ?
					( <CopiedText> {__( 'Copied to clipboard!', 'newsletter-optin-box' )} </CopiedText> )
					: label
				}
			</Button>
		</FlexItem>
	);

}

/**
 * Displays action links.
 *
 * @param {Object} props
 * @param {Array} props.links
 */
const ActionLinks = ( { links, ...props } ) => (
	<Flex className={withBottomMargin} justify="flex-start" gap={2} wrap>
		{links.map( ( { label, value, action, hide } ) => {

			if ( hide ) {
				return null;
			}

			// Delete record.
			if ( 'delete' === action ) {
				return <DeleteLink key={label} label={label} confirm={value} {...props}  />;
			}

			// Copy a value.
			if ( 'copy' === action ) {
				return <CopyLink key={label} label={label} value={value} {...props} />;
			}

			return (
				<FlexItem key={label}>
					<Button href={value} variant="secondary" target="_blank">
						{label}
					</Button>
				</FlexItem>
			);
		} )}
	</Flex>
);

/**
 * Displays a record's overview.
 *
 */
export const OverviewSection = ( { namespace, collection, id, basePath } ) => {

	// Prepare the overview.
	const overview = useRecordOverview( namespace, collection, id );

	// In case we don't have an overview yet, display a spinner.
	if ( overview.isResolving ) {
		return (
			<>
				<Flex className={ withBottomMargin } gap={4} wrap>
					{[1, 2, 3].map( ( i ) => (
						<FlexItem key={i}>
							<LoadingPlaceholder width="100px" height="100px" />
						</FlexItem>
					) )}
				</Flex>
			</>
		);
	}

	// Abort if we have an error.
	if ( 'ERROR' === overview.status || !Array.isArray( overview.data ) || ! overview.data.length ) {
		return null;
	}

	// Display the overview.
	return (
		<>
			{overview.data.map( ( data, index ) => {
				switch ( data.type ) {
					case 'stat_cards':
						return <StatCards key={index} cards={data.cards} />;
					case 'action_links':
						return <ActionLinks key={index} links={data.links} namespace={namespace} collection={collection} id={id} basePath={basePath} />;
					case 'card':
						return <NormalCard key={index} {...data} />;
					case 'card_list':
						return <CardList key={index} {...data} />;

					default:
						return null;
				}
			} )}
		</>
	)
}
