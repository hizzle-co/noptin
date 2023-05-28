/**
 * A component to display summarized table data - the list of data passed in on a single line.
 */
const TableSummary = ( { data } ) => {
	return (
		<ul className="noptin-table__summary" role="complementary">
			{ data.map( ( { label, value }, i ) => (
				<li className="noptin-table__summary-item" key={ i }>
					<span className="noptin-table__summary-value">
						{ value }
					</span>
					<span className="noptin-table__summary-label">
						{ label }
					</span>
				</li>
			) ) }
		</ul>
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
		<ul className="noptin-table__summary is-loading" role="complementary">
			<li className="noptin-table__summary-item">
				<span className="is-placeholder" />
			</li>
		</ul>
	);
};
