import { LoadingPlaceholder } from "../styled-components";
import { TableSummaryList } from "../styled-components";

/**
 * A component to display summarized table data - the list of data passed in on a single line.
 */
const TableSummary = ( { data } ) => {
	return (
		<TableSummaryList role="complementary">
			{ data.map( ( { label, value }, i ) => (
				<li key={ i }>
					<strong>{ value }</strong>
					<span>{ label }</span>
				</li>
			) ) }
		</TableSummaryList>
	);
};

export default TableSummary;

/**
 * A component to display a placeholder box for `TableSummary`.
 *
 * @return {Object} -
 */
export const TableSummaryPlaceholder = () => {
	return (
		<TableSummaryList role="complementary">
			<li>
				<LoadingPlaceholder />
			</li>
		</TableSummaryList>
	);
};
