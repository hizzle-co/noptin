/**
 * Wordpress dependancies.
 */
import {
	SelectControl,
	__experimentalInputControl as InputControl,
	__experimentalInputControlPrefixWrapper as InputControlPrefixWrapper,
	Button,
	Flex,
	FlexItem,
	ButtonGroup,
	Icon,
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import classNames from 'classnames';

const PER_PAGE_OPTIONS = [ 25, 50, 75, 100 ];

/**
 * Displays the page arrows.
 *
 * @param {Object} props The component props.
 * @param {number} props.page The current page.
 * @param {number} props.pageCount The total number of pages.
 * @param {Function} props.onPageChange Callback function when the page changes.
 * @return {WPElement} The page arrows element.
 */
function PageArrows( { page, pageCount, onPageChange }  ) {

	if ( pageCount <= 1 ) {
		return null;
	}

	// Prepare the page arrows.
	const hasPrevious  = page > 1;
	const previousPage = page - 1;
	const hasNext      = page < pageCount;
	const nextPage     = page + 1;

	const prefix       = (
		<InputControlPrefixWrapper>
			{ __( 'Page', 'newsletter-optin-box' ) }
		</InputControlPrefixWrapper>
	);

	return (
		<FlexItem className="noptin-pagination__page-arrows">
			<Flex wrap gap={2} align="center" justify="center">

				<FlexItem role="status" aria-live="polite">
					<InputControl
						type="number"
						value={ page }
						onChange={ onPageChange }
						min={ 1 }
						max={ pageCount }
						prefix={ prefix }
						label={ __( 'Page', 'newsletter-optin-box' ) }
						isPressEnterToChange
						hideLabelFromVision
						__next36pxDefaultSize
					/>
				</FlexItem>

				<FlexItem>
					{ sprintf( __( 'of %d', 'newsletter-optin-box' ), pageCount ) }
				</FlexItem>

				<FlexItem className="noptin-pagination__page-arrows-buttons">
					<ButtonGroup>
						<Button
							disabled={ ! hasPrevious }
							onClick={ () => onPageChange( previousPage ) }
							label={ __( 'Previous Page', 'newsletter-optin-box' ) }
						>
							<Icon icon="arrow-left-alt2" />
						</Button>
						<Button
							disabled={ ! hasNext }
							onClick={ () => onPageChange( nextPage ) }
							label={ __( 'Next Page', 'newsletter-optin-box' ) }
						>
							<Icon icon="arrow-right-alt2" />
						</Button>
					</ButtonGroup>
				</FlexItem>
			</Flex>
		</FlexItem>
	);
}

/**
 * Displays the per page picker component.
 *
 * @param {Object} props The component props.
 * @param {number} props.perPage The amount of results that are being displayed per page.
 * @param {Function} props.onChange A function to execute when the per page option is changed.
 */
function PerPagePicker( { perPage, onChange } ) {

	// Prepare the options list for the SelectControl.
	const pickerOptions = PER_PAGE_OPTIONS.map( ( option ) => {
		return { value: option, label: option };
	} );

	return (
		<FlexItem className="noptin-pagination__per-page-picker">
			<SelectControl
				label={ __( 'Rows per page', 'newsletter-optin-box' ) }
				labelPosition="side"
				value={ perPage }
				onChange={ onChange }
				options={ pickerOptions }
				__nextHasNoMarginBottom
				__next36pxDefaultSize
			/>
		</FlexItem>
	);
}

/**
 * Displays the pagination component.
 *
 * @param {Object} props The component props.
 * @param {Object} props.query The current query object.
 * @param {Function} props.onQueryChange Callback function when the query changes.
 * @param {number} props.total The total number of results.
 * @param {string} [props.className] Additional classNames.
 * @param {boolean} [props.showPerPagePicker=true] Whether the per page picker should be rendered.
 */
export default function Pagination( { query, onQueryChange, total, className, showPerPagePicker = true } ) {

	// Abort if there are no results.
	if ( ! total ) {
		return null;
	}

	// Calculate the page count.
	const page = query.paged ? parseInt( query.paged, 10 ) : 1;
	const perPage = query.per_page ? parseInt( query.per_page, 10 ) : 25;
	const pageCount = Math.ceil( total / perPage );
	const classes = classNames( 'noptin-pagination', className );

	// Handle the per page change.
	function perPageChange( perPage ) {

		// Handle the per page change.
		const newPerPage = parseInt( perPage, 10 );
		onQueryChange( { per_page: newPerPage ? newPerPage : 1 } );

		// Ensure that the page is not out of bounds.
		const newMaxPage = newPerPage ? Math.ceil( total / newPerPage ) : total;
		if ( page > newMaxPage ) {
			onQueryChange( { paged: newMaxPage } );
		}
	}

	// Handle the page change.
	function pageChange( page ) {

		// Ensure that the page is not out of bounds.
		let newPage = parseInt( page, 10 );

		if ( ! newPage || newPage < 1 ) {
			newPage = 1;
		} else if ( newPage > pageCount ) {
			newPage = pageCount;
		}

		onQueryChange( { paged: newPage } );
	}

	// If there is only one page, don't render the pagination unless there are more results than the first perPage option.
	if ( pageCount <= 1 ) {
		return (
			( total > PER_PAGE_OPTIONS[ 0 ] && (
				<Flex className={ classes } wrap gap={4} align="center" justify="center">
					<PerPagePicker perPage={ perPage } onChange={ perPageChange } />
				</Flex>
			) ) || null
		);
	}

		return (
			<Flex className={ classes } wrap gap={2} align="center" justify="center">
				<PageArrows page={ page } pageCount={ pageCount } onPageChange={ pageChange } />
				{ showPerPagePicker && <PerPagePicker perPage={ perPage } onChange={ perPageChange } /> }
			</Flex>
		);
}
