/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { useDebouncedCallback } from "use-debounce";
import { SearchControl, FlexBlock } from '@wordpress/components';

/**
 * Displays a search form.
 */
const SearchForm = ( { value, onChange, searchPlaceholder } ) => {

	const [ searchTerm, setSearchTerm ] = useState( value ? value : '' );

	// Fired when the search text changes.
	const onSearchTextChange = useDebouncedCallback((value) => {
		onChange(value);
	}, 500);

	return (
		<FlexBlock style={{minWidth: '200px'}}>
			<SearchControl
				value={ searchTerm }
				onChange={ (value) => {
					setSearchTerm(value);
					onSearchTextChange(value);
				}}
				placeholder={ searchPlaceholder }
				__nextHasNoMarginBottom
			/>
		</FlexBlock>
	);
};

export default SearchForm;
