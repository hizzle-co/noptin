/**
 * External dependencies
 */
import { forwardRef, useState } from "@wordpress/element";
import { Card, CardBody, Button, Flex, FlexItem, Modal, TextControl } from "@wordpress/components";
import copy from 'copy-to-clipboard';
import { __ } from "@wordpress/i18n";

/**
 * Internal dependencies
 */
import { useRecordOverview } from "../../../store-data/hooks";
import { LoadingPlaceholder, CopiedText, withBottomMargin } from "../../styled-components";
import StatCard from "../stat-card";
import { useRecord } from "../../../store-data/hooks";
import { useRoute } from "../hooks";

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
const DeleteLink = ( { confirm, label } ) => {

	// Prepare the state.
	const { namespace, collection, navigate, args } = useRoute();

	const STORE_NAME = `${namespace}/${collection}`;
	const record = useRecord( namespace, collection, args.id );

	// A function to delete a record.
	const onDeleteRecord = () => {

		// Confirm.
		if ( !window.confirm( confirm || __( 'Are you sure you want to delete this record?', 'newsletter-optin-box' ) ) ) {
			return;
		}

		// Delete the record.
		record.delete();

		// Navigate back to the list.
		navigate( STORE_NAME );
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
const ActionLinks = ( { links } ) => (
	<Flex className={withBottomMargin} justify="flex-start" gap={2} wrap>
		{links.map( ( { label, value, action, hide } ) => {

			if ( hide ) {
				return null;
			}

			// Delete record.
			if ( 'delete' === action ) {
				return <DeleteLink key={label} label={label} confirm={value} />;
			}

			// Copy a value.
			if ( 'copy' === action ) {
				return <CopyLink key={label} label={label} value={value} />;
			}

			return (
				<FlexItem key={label}>
					<Button href={value} variant="secondary">
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
 * @param {Object} props
 * @param {Object} props.component
 */
const OverviewSection = ( { namespace, collection, recordID, upsell=null }, ref ) => {

	// Prepare the overview.
	const overview = useRecordOverview( namespace, collection, recordID );

	// In case we don't have an overview yet, display a spinner.
	if ( overview.isResolving() ) {
		return (
			<>
				{upsell}
				<Flex className={ withBottomMargin } gap={4} wrap>
					{[1, 2, 3].map( ( i ) => (
						<FlexItem key={i}>
							<LoadingPlaceholder width="100px" height="100px" ref={ref} />
						</FlexItem>
					) )}
				</Flex>
			</>
		);
	}

	// Abort if we have an error.
	if ( overview.hasResolutionFailed() || !Array.isArray( overview.data ) || !overview.data.length ) {
		return {upsell};
	}

	// Display the overview.
	return (
		<div ref={ref}>
			{upsell}
			{overview.data.map( ( data, index ) => {
				switch ( data.type ) {
					case 'stat_cards':
						return <StatCards key={index} cards={data.cards} />;
					case 'action_links':
						return <ActionLinks key={index} links={data.links} />;
					case 'card':
						return <NormalCard key={index} {...data} />;
					default:
						return null;
				}
			} )}
		</div>
	)
}

export default forwardRef( OverviewSection );
