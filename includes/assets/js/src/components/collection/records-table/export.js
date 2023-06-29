/**
 * External dependencies
 */
import { useState, useMemo } from "@wordpress/element";
import { ToggleControl, Button, Icon, Modal, Spinner, Flex, FlexItem, __experimentalText as Text, } from "@wordpress/components";
import { __, sprintf } from "@wordpress/i18n";
import { compact } from 'lodash';
import Papa from 'papaparse';

/**
 * Local dependencies.
 */
import { usePartialRecords } from "../../../store-data/hooks";
import { useCurrentSchema } from "../hooks";
import { useSelected } from "../../table/selected-context";
import { BlockButton, NoMarginNotice } from "../../styled-components";
import ErrorBoundary from "../error-boundary";
import { useQuery } from "../../navigation";
import { useParams } from "react-router-dom";
import { useCurrentQueryRecordCount } from "../hooks";

/**
 * Fetches records from the API and converts them to CSV.
 *
 * @param {Object} args
 * @param {Array} args.fields The fields to export.
 * @param {Function} args.back The callback to call when clicking on the back button.
 * @param {Array} args.schema The schema of the collection.
 */
const DownloadProgress = ({ fields, back, schema }) => {

	// Prepare state.
	const args = useQuery();
	const { namespace, collection } = useParams();
	const [selected] = useSelected();

	// Fetch the records.
	const exportArgs = selected.length === 0 ? { ...args, number: -1 } : { include: selected.join( ',' ) };

	// Add the fields to the args.
	exportArgs.__fields = fields.join( ',' );

	// Set context to edit.
	exportArgs.context = 'edit';

	const records = usePartialRecords(namespace, collection, exportArgs);

	// Loop through columns and and try to replace the column name with the label where possible.
	const columns = useMemo( () => {

		if ( ! records.data?.length ) {
			return [];
		}

		// Pluck name and label from the schema.
		const knownFields = schema.reduce((acc, field) => {
			acc[field.name] = field.label;
			return acc;
		}, {});

		return Object.keys(records.data[0]).map((column) => {
			return knownFields[column] || column;
		});
	}, [schema, records.data]);

	// Convert data from array of objects to array of arrays.
	const recordArray = useMemo(() => {

		if (!records.data?.length) {
			return [];
		}

		return records.data.map((record) => {
			return Object.values(record);
		});
	}, [records.data]);

	const backButton = (
		<Button variant="link" onClick={back}>
			{__('Go Back', 'newsletter-optin-box')}
		</Button>
	);

	// Short spinner if loading.
	if (records.isResolving) {
		return (
			<Text size={ 16 } as="p">
				<Spinner style={{marginLeft: 0}} />
				{__('Preparing records...', 'newsletter-optin-box')}
			</Text>
		);
	}

	// Show error if any.
	if ( 'ERROR' === records.status ) {

		return (
			<NoMarginNotice status="error" isDismissible={false}>
				{records.error.message || __('An unknown error occurred.', 'newsletter-optin-box')}&nbsp; &nbsp;
				{backButton}
			</NoMarginNotice>
		)
	}

	// If no records, nothing to export.
	if (!records.data.length) {
		return (
			<NoMarginNotice status="info" isDismissible={false}>
				{__('Found no records to export.', 'newsletter-optin-box')}&nbsp; &nbsp;
				{backButton}
			</NoMarginNotice>
		)
	}

	// Convert to CSV.
	const csv = Papa.unparse(
		{
			fields: columns,
			data: recordArray,
		},
		{ escapeFormulae: true }
	);

	const filename = `${namespace}-${collection}-${Date.now()}.csv`;

	// Force download.
	return (
		<NoMarginNotice status="success" isDismissible={false}>
			{__("Done! Click the button below to download records.", 'newsletter-optin-box')}
			&nbsp; &nbsp;
			<Button
				variant="primary"
				href={`data:text/csv;charset=utf-8,${encodeURIComponent(csv)}`}
				download={ filename }
				text={ __('Download CSV', 'newsletter-optin-box') }
			/>
		</NoMarginNotice>
	);
}

/**
 * Allows the user to select which fields to export.
 *
 * @param {Object} args
 * @param {Array} args.fields The fields to export.
 * @param {Function} args.setFields The function to update the fields.
 * @param {Object} args.schema The schema of the collection.
 * @param {Array} args.schema.schema The schema of the collection.
 * @param {Array} args.schema.ignore The fields to ignore.
 * @param {Function} args.next The function to go to the next step.
 */
const DownloadFields = ({ fields, setFields, schema: { schema, ignore }, next } ) => {

	return (
		<Flex direction={'column'} gap={4}>

			<FlexItem>
				<p className="description">
					{__('Select the fields to include in your exported file', 'newsletter-optin-box')}
				</p>
			</FlexItem>

			{schema.map((field) => {

				// Abort if dynamic field.
				if (ignore.includes(field.name)) {
					return;
				}

				return (
					<FlexItem key={field.name}>
						<ToggleControl
							label={field.label === field.description ? field.label : `${field.label} (${field.description})`}
							checked={fields.includes(field.name)}
							onChange={() => {
								if (fields.includes(field.name)) {
									setFields(fields.filter((name) => name !== field.name));
								} else {
									setFields([...fields, field.name]);
								}
							}}
							__nextHasNoMarginBottom
						/>
					</FlexItem>
				)
			})}

			<FlexItem>
				<BlockButton variant="primary" onClick={next}>
					<Icon icon="download" />
					{__('Download', 'newsletter-optin-box')}
				</BlockButton>
			</FlexItem>
		</Flex>
	);
}

/**
 * The modal content.
 *
 */
const TheModal = () => {

	// Prepare state.
	const schema = useCurrentSchema();
	const [fields, setFields] = useState(compact(schema.data.schema.map((field) => (
		(schema.data.hidden.includes(field.name) || schema.data.ignore.includes(field.name)) ? null : field.name
	))));
	const [step, setStep] = useState('fields');

	// If we are showing fields...
	if ('fields' === step) {
		return (
			<DownloadFields
				fields={fields}
				setFields={setFields}
				schema={schema.data}
				next={() => setStep('progress')}
			/>
		);
	}

	// If we are showing the download progress...
	if ('progress' === step) {
		return (
			<DownloadProgress
				fields={fields}
				schema={schema.data.schema}
				back={() => setStep('fields')}
			/>
		);
	}
}

/**
 * Displays a modal allowing users to export all records matching the current collection.
 *
 */
export default function ExportButton() {

	const [isOpen, setOpen] = useState(false);
	const [selected]  = useSelected();
	const downloadAll = selected.length === 0;
	const count       = useCurrentQueryRecordCount();
	const title       = downloadAll ? __( 'Download', 'newsletter-optin-box' ) : __( 'Download Selected', 'newsletter-optin-box' );
	const modalTitle  = downloadAll ? sprintf(
		/* translators: %s: number of records */
		__( 'Download all %s records', 'newsletter-optin-box' ),
		count
	) : sprintf(
		/* translators: %s: number of records */
		__( 'Download %s selected records', 'newsletter-optin-box' ),
		selected.length
	);

	return (
		<>

			<Button
				onClick={() => setOpen(true)}
				icon="download"
				text={ title }
			/>

			{isOpen && (
				<Modal title={ modalTitle } onRequestClose={() => setOpen(false)}>
					<div className="hizzle-records-export-modal__body">
						<ErrorBoundary>
							<TheModal />
						</ErrorBoundary>
					</div>
				</Modal>
			)}

		</>
	);

}
