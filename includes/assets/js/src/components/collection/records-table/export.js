/**
 * External dependencies
 */
import { useState, useMemo } from "@wordpress/element";
import { Notice, ToggleControl, Button, Modal, Spinner, Flex, FlexItem, __experimentalText as Text, } from "@wordpress/components";
import { Icon, download } from "@wordpress/icons";
import { __ } from "@wordpress/i18n";
import { compact } from 'lodash';
import Papa from 'papaparse';

/**
 * Local dependencies.
 */
import { useSchema, useRecords } from "../../../store-data/hooks";
import { useRoute } from "../hooks";

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
	const { namespace, collection, args } = useRoute();

	// Fetch the records.
	const newArgs = {
		...args,
		_fields: fields.join(','),
		per_page: -1,
		context: 'edit',
	}

	const records = useRecords(namespace, collection, newArgs);

	// Loop through columns and and try to replace the column name with the label where possible.
	const columns = useMemo( () => {

		if ( ! records.data.length ) {
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

		if (!records.data.length) {
			return [];
		}

		return records.data.map((record) => {
			return Object.values(record);
		});
	}, [records.data]);

	const backButton = (
		<Button variant="primary" onClick={back}>
			{__('Go Back', 'newsletter-optin-box')}
		</Button>
	);

	// Short spinner if loading.
	if (records.isResolving()) {
		return (
			<Text size={ 16 } weight={ 600 } as="h2" color="#23282d">
				<Spinner />
				{__('Fetching records...', 'newsletter-optin-box')}
			</Text>
		)
	}

	// Show error if any.
	if (records.hasResolutionFailed()) {

		const error = records.getResolutionError();
		return (
			<Notice status="error" isDismissible={false} style={{ margin: 0 }}>
				<p>
					{error.message || __('An unknown error occurred.', 'newsletter-optin-box')}
				</p>

				{backButton}
			</Notice>
		)
	}

	// If no records, nothing to export.
	if (!records.data.length) {
		return (
			<Notice status="info" isDismissible={false} style={{ margin: 0 }}>
				<p>
					{__('Found no records to export.', 'newsletter-optin-box')}
				</p>

				{backButton}
			</Notice>
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
		<Notice status="success" isDismissible={false}>
			{__("We've prepared your download. Please click the button below to download the CSV file.", 'newsletter-optin-box')}
			&nbsp; &nbsp;
			<Button
				variant="primary"
				href={`data:text/csv;charset=utf-8,${encodeURIComponent(csv)}`}
				download={ filename }
				text={ __('Download CSV', 'newsletter-optin-box') }
			/>
		</Notice>
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
		<Flex direction={'column'} gap={3}>

			<FlexItem>
				<Text size={ 16 } weight={ 600 } as="h2" color="#23282d">
					{__('Select the fields to include in your download', 'newsletter-optin-box')}
				</Text>
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
				<Button variant="primary" onClick={next}>
					<Icon icon={download} />
					{__('Download', 'newsletter-optin-box')}
				</Button>
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
	const { namespace, collection } = useRoute();
	const schema = useSchema(namespace, collection);
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

	return (
		<>

			<Button onClick={() => setOpen(true)} variant="primary">
				<Icon icon={download} />
				{ __( 'Download', 'newsletter-optin-box' ) }
			</Button>

			{isOpen && (
				<Modal title="&nbsp;" onRequestClose={() => setOpen(false)}>
					<div className="hizzle-records-export-modal__body">
						<TheModal />
					</div>
				</Modal>
			)}

		</>
	);

}
