/**
 * Wordpress dependancies.
 */
import { SelectControl, Button, Flex, FlexItem, ButtonGroup, } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import classNames from 'classnames';
import { noop } from 'lodash';
import { Icon, chevronLeft, chevronRight } from '@wordpress/icons';

const PER_PAGE_OPTIONS = [ 25, 50, 75, 100 ];

/**
 * Displays the page arrows.
 *
 * @param {Object} props The component props.
 * @param {number} props.page The current page.
 * @param {number} props.pageCount The total number of pages.
 * @param {boolean} props.showPageArrowsLabel Whether to show the page arrows label.
 * @param {Function} props.onPageChange Callback function when the page changes.
 * @return {WPElement} The page arrows element.
 */
function PageArrows( { page, showPageArrowsLabel, pageCount, onPageChange }  ) {

	return null;
	if ( pageCount <= 1 ) {
		return null;
	}

	// Prepare the page arrows.
	const hasPrevious  = page > 1;
	const previousPage = page - 1;
	const hasNext      = page < pageCount;
	const nextPage     = page + 1;

	return (
		<FlexItem className="noptin-pagination__page-arrows">
			<Flex wrap gap={2} align="center" justify="center">

				{ showPageArrowsLabel && (
					<FlexItem role="status" aria-live="polite">
						{ sprintf(
							__( 'Page %d of %d', 'newsletter-optin-box' ),
							page,
							pageCount
						) }
					</FlexItem>
				) }

				<FlexItem className="noptin-pagination__page-arrows-buttons">
					<ButtonGroup>
						<Button
							disabled={ ! hasPrevious }
							onClick={ onPageChange( previousPage ) }
							label={ __( 'Previous Page', 'newsletter-optin-box' ) }
						>
							<Icon icon={ chevronLeft } />
						</Button>
						<Button
							disabled={ ! hasNext }
							onClick={ onPageChange( nextPage ) }
							label={ __( 'Next Page', 'newsletter-optin-box' ) }
						>
							<Icon icon={ chevronRight } />
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
 * @param {number} props.total The total number of results.
 * @param {Function} props.onChange A function to execute when the per page option is changed.
 */
function PerPagePicker( { perPage, total, onChange } ) {

	// Prepare the options list.
	const options = PER_PAGE_OPTIONS;

	// Ensure that the current perPage value is in the options list.
	if ( ! options.includes( perPage ) ) {
		options.push( perPage );
		options.sort( ( a, b ) => a - b );
	}

	// Prepare the options list for the SelectControl.
	const pickerOptions = options.map( ( option ) => {
		return { value: option, label: option };
	} );

	// Allow the user to select "All" if there are less than 100 results.
	if ( total <= 100 ) {
		pickerOptions.push( { value: total, label: __( 'All', 'newsletter-optin-box' ) } );
	}

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
 * @param {number} props.page The current page of the collection.
 * @param {Function} props.onPageChange A function to execute when the page is changed.
 * @param {number} props.perPage The amount of results that are being displayed per page.
 * @param {Function} props.onPerPageChange A function to execute when the per page option is changed.
 * @param {number} props.total The total number of results.
 * @param {string} [props.className] Additional classNames.
 * @param {boolean} [props.showPerPagePicker=true] Whether the per page picker should be rendered.
 * @param {boolean} [props.showPageArrowsLabel=true] Whether the page arrows label should be rendered.
 */
export default function Pagination( { page, onPageChange = noop, perPage = 10, onPerPageChange = noop, total, className, showPerPagePicker = true, showPageArrowsLabel = true } ) {

	// Abort if there are no results.
	if ( ! total ) {
		return null;
	}

	// Calculate the page count.
	const pageCount = Math.ceil( total / perPage );
	const classes = classNames( 'noptin-pagination', className );

	// Handle the per page change.
	function perPageChange( perPage ) {

		// Handle the per page change.
		const newPerPage = parseInt( perPage, 10 );
		onPerPageChange( newPerPage ? newPerPage : 1 );

		// Ensure that the page is not out of bounds.
		const newMaxPage = newPerPage ? Math.ceil( total / newPerPage ) : total;
		if ( page > newMaxPage ) {
			onPageChange( newMaxPage );
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

		onPageChange( newPage );
	}

	// If there is only one page, don't render the pagination unless there are more results than the first perPage option.
	if ( pageCount <= 1 ) {
		return (
			( total > PER_PAGE_OPTIONS[ 0 ] && (
				<Flex className={ classes } wrap gap={4} align="center" justify="center">
					<PerPagePicker perPage={ perPage } total={ total } onChange={ perPageChange } />
				</Flex>
			) ) || null
		);
	}

		return (
			<Flex className={ classes } wrap gap={2} align="center" justify="center">
				<PageArrows page={ page } pageCount={ pageCount } onPageChange={ pageChange } showPageArrowsLabel={ showPageArrowsLabel } />
				{ showPerPagePicker && <PerPagePicker perPage={ perPage } total={ total } onChange={ perPageChange } /> }
			</Flex>
		);
}
