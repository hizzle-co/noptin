/**
 * External dependencies
 */
import { forwardRef, useState } from "@wordpress/element";
import { Card, CardBody, Flex, FlexItem } from "@wordpress/components";
import styled from '@emotion/styled';

/**
 * Internal dependencies
 */
import { useRecordOverview } from "../../../store-data/hooks";
import { LoadingPlaceholder } from "../../styled-components";
import StatCard from "../stat-card";

/**
 * Displays a record's overview.
 *
 * @param {Object} props
 * @param {Object} props.component
 */
const OverviewSection = ( { namespace, collection, recordID }, ref ) => {

    // Prepare the overview.
    const overview = useRecordOverview( namespace, collection, recordID );

    // In case we don't have an overview yet, display a spinner.
    if ( overview.isResolving() ) {
        return (
            <Flex gap={4} wrap>
                { [ 1, 2, 3 ].map( ( i ) => (
                    <FlexItem key={i}>
                        <LoadingPlaceholder width="100px" height="100px" ref={ ref }/>
                    </FlexItem>
                ) ) }
            </Flex>
        );
    }

    // Abort if we have an error.
    if ( overview.hasResolutionFailed() || ! Array.isArray( overview.data ) || ! overview.data.length ) {
        return null;
    }

	// Display the overview.
    return JSON.stringify( overview.data );
}

export default forwardRef( OverviewSection );
