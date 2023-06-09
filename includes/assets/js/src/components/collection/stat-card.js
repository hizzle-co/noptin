/**
 * External dependencies
 */
import { Flex, FlexItem, __experimentalText as Text } from '@wordpress/components';
import styled from '@emotion/styled';
import { css } from '@emotion/react';

/**
 * Dynamically sets the status styles.
 */
const statusStyle = ({ status }) => {

    // Success.
    if ( 'success' === status ) {
        return css`
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;

            & h2,
            & h3 {
                color: #155724;
            }
        `;
    }

    // Info.
    if ( 'info' === status ) {
        return css`
            background-color: #d1ecf1;
            border-color: #bee5eb;
            color: #0c5460;

            & h2,
            & h3 {
                color: #0c5460;
            }
        `;
    }

    // Warning.
    if ( 'warning' === status ) {
        return css`
            background-color: #fff3cd;
            border-color: #ffeeba;
            color: #856404;

            & h2,
            & h3 {
                color: #856404;
            }
        `;
    }

    // Error.
    if ( 'error' === status ) {
        return css`
            background-color: #f8cbcb;
            border-color: #f5c6cb;
            color: #cc1818;

            & h2,
            & h3 {
                color: #cc1818;
            }
        `;
    }

    // Dark.
    if ( 'dark' === status ) {
        return css`
            background-color: #212529;
            border-color: #212529;
            color: #f8f9fa;

            & h2,
            & h3 {
                color: #f8f9fa;
            }
        `;
    }

    // Default.
    return css`
        background-color: #f8f9fa;
        border-color: rgb(0 0 0 / 13%);
        color: #212529;

        & h2,
        & h3 {
            color: #212529;
        }
    `;
}

/**
 * Renders the container styles.
 */
const Container = styled.div`
    ${statusStyle};
    border-width: 1px;
    border-style: none;
    padding: 0.25rem;
    text-align: center;
    box-shadow: 2px 2px 2px rgb(0 0 0 / 5%);
    min-width: 100px;
    border-radius: 4px;
`

/**
 * Displays a stat card
 *
 * @param {Object} props
 * @param {Number} props.value - The value to display.
 * @param {String} props.label - The label to display.
 * @param {String} props.status - success, info, warning, error, light
 * @return {JSX.Element} The stat card.
 */
export default function StatCard( { value, label, status } ) {

    return (
        <Container status={ status }>
            <Flex direction="column" justify="center" style={{ minHeight: '100px' }}>

                <FlexItem>
                    <Text size={ 48 } weight={ 600 } as="h2">
                        { value }
                    </Text>
                </FlexItem>

                <FlexItem>
                    <Text size={ 13 } weight={ 400 } as="h3">
                        { label }
                    </Text>
                </FlexItem>
            </Flex>
        </Container>
    );
}
