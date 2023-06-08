import { Button, __experimentalText as Text, TabPanel } from "@wordpress/components";
import styled from '@emotion/styled';
import { css } from '@emotion/react';

const BORDER_COLOR = '#e2e4e7';

/**
 * Displays a bordered component.
 */
export const Bordered = styled.div`
	border: 1px solid ${BORDER_COLOR};
`

/**
 * Displays a full height component.
 */
export const FullHeight = styled.div`
	min-height: 100vh;
`

/**
 * Displays a block button.
 *
 * @param {Object} props
 * @returns {styled} The block button.
 */
export const BlockButton = styled( Button, { shouldForwardProp: prop => ! ['maxWidth', '__withNoMargin'].includes( prop ) })`
	width: 100%;
	justify-content: center;
	font-size: 14px;
	min-height: 50px;
	margin: ${props => (props.__withNoMargin ? '0' : '1.6rem 0')};
	max-width: ${props => (props.maxWidth ? props.maxWidth : '100%')};
`

/**
 * Displays an error Notice.
 */
export const ErrorNotice = styled.div`
	border-left: 4px solid #cc1818;
	margin: 5px 15px 2px 0;
	padding: 16px 12px;
	background-color: #f8cbcb;
`

/**
 * Displays a heading text.
 */
export const HeadingText = styled( Text )`
	margin-bottom: 1.6rem;
	font-weight: 600;
	font-size: 20px;
`

/**
 * Displays a heading text.
 */
export const CardHeadingText = styled( Text )`
	font-weight: 600;
	font-size: 16px;
	color: currentColor;
`

/**
 * Wraps a progressbar.
 */
const ProgressBarWrapper = styled.div`
	width: 100%;
	height: 20px;
	background: #eee;
	margin: 1.6rem 0;
	border-radius: 0.25rem;
	max-width: 600px;
	overflow: hidden;
`

/**
 * Renders the progressbar child.
 */
const progressbarWidthStyle = ({ total, processed }) => {
	const width = total === processed ? '100%' : ( processed ? `${ ( processed / total ) * 100 }%` : '1%' );

	return css`width: ${width};`;
}

/**
 * Renders the progressbar child.
 */
const ProgressBarInner = styled.div`
	${progressbarWidthStyle};
	height: 100%;
	transition: width 3s ease-in-out;
	animation: position 3s linear infinite;
	position: relative;
	border-radius: 0.25rem;

	@keyframes position {
		0% {
			left: 0;
			right: 100%;
			background: #72aee6;
		}
		100% {
			right: 0;
			left: 100%;
			background: #007cba;
		}
	}
`

/**
 * Displays a progress bar.
 *
 * @param {Object} props
 * @param {Number} props.total - The total number of records.
 * @param {Number} props.processed - The number of records processed.
 * @returns {JSX.Element} The progress bar.
 */
export const ProgressBar = ( { total, processed } ) => {

	return (
		<ProgressBarWrapper>
			<ProgressBarInner total={ total } processed={ processed } />
		</ProgressBarWrapper>
	);
};

/**
 * Renders an avatar
 */
export const Avatar = styled.img`
	height: 32px;
	width: 32px;
	border-radius: 50%;
	box-shadow: 2px 2px 2px rgb(0 0 0 / 5%);
	background-color: currentColor;
`

/**
 * Renders a styled tab panel.
 */
export const StyledTabPanel = styled( TabPanel )`
	& > .components-tab-panel__tabs {
		border: 1px solid #9E9E9E;
		background-color: #fff;
		margin-bottom: 1rem;
	}
`

/**
 * Calculates a loading placeholder size.
 */
const placeholderSizeStyle = ({ height, width, maxWidth }) => {
	height = height ? height : '16px';
	width = width ? width : '80%';
	maxWidth = maxWidth ? maxWidth : '120px';

	return css`
		height: ${height};
		width: ${width};
		max-width: ${maxWidth};
	`;
}

/**
 * Displays a loading placeholder with a fading animation.
 *
 * @param {Object} props
 * @param {String} props.height - The height of the placeholder.
 * @param {String} props.width - The width of the placeholder.
 * @param {String} props.maxWidth - The max width of the placeholder.
 */
export const LoadingPlaceholder = styled.div`
	${placeholderSizeStyle};
	display: inline-block;
	min-height: 1em;
	vertical-align: middle;
	cursor: wait;
	background-color: currentColor;
	opacity: .5;

	animation: placeholder-glow 2s ease-in-out infinite;

	@keyframes placeholder-glow {
		50% {
			opacity: .2;
		}
	}
`

/**
 * Renders a scrollable table.
 */
export const ScrollableTable = styled.div`
	overflow-x: auto;

	& > table {
		width: 100%;
		border-collapse: collapse;
	}
`

/**
 * Renders a table row.
 */
export const TableRow = styled.tr`
	background-color: transparent;

	&:hover,
	&:focus-within,
	&:hover td,
	&:hover th {
		background-color: #f8f9fa;
	}

	&:last-child td,
	&:last-child th {
		border-bottom: 0;
	}
`

/**
 * Calculates a table cell size.
 */
const tableCellStyle = ({ align='left', minWidth='160px', isSorted }) => {
	minWidth      = minWidth ? minWidth : 'auto';
	align         = align ? align : 'left';
	const justify = align === 'center' ? 'center' : 'space-between';

	let extraStyle = '';

	if ( isSorted ) {
		extraStyle = `
			background-color: #f8f9fa;
			&:not(:first-child) {
				border-left: 1px solid ${BORDER_COLOR};
			}
			&:not(:last-child) {
				border-right: 1px solid ${BORDER_COLOR};
			}
		`;
	}

	return css`
		text-align: ${align};
		min-width: ${minWidth};
		${extraStyle}

		& > .components-button {
			justify-content: ${justify};
		}

		& > a:only-child {
			display: block;
		}
	`;
}

/**
 * Displays a table header.
 */
export const TableHeader = styled.th`
	padding: 16px 24px;
	background-color: #f8f9fa;
	font-weight: bold;
	white-space: nowrap;
	${tableCellStyle};
	border-bottom: 1px solid ${BORDER_COLOR};

	& > .components-button {
		height: auto;
		width: 100%;
		text-align: left;
		vertical-align: middle;
		line-height: 1;
		font-weight: bold;
		background: transparent !important;
		box-shadow: none !important;
		padding: 0;
	}
`

/**
 * Displays a table cell.
 */
export const TableCell = styled.td`
	padding: 16px 24px;
	border-bottom: 1px solid ${BORDER_COLOR};
	font-weight: normal;
	${tableCellStyle};
`

/**
 * Displays a table cell when there is no data.
 */
export const TableCellNoData = styled.td`
	text-align: left;
	color: #757575;
	font-weight: bold;
	padding: 16px 24px;

	@media (min-width: 782px) {
		padding: 2rem;
		font-size: 1.125rem;
	}
`
