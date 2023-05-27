/**
 * External dependencies
 */
import {
	Card,
	CardHeader,
	__experimentalText as Text,
    Flex,
    FlexItem,
    FlexBlock,
} from '@wordpress/components';

/**
 * Wraps content.
 */
const Wrap = ( { actions, className, title, menu, children } ) => {

	return (
		<Card className={ className }>

			<CardHeader>
                <Flex wrap>

                    <FlexBlock>
                        <Text size={ 16 } weight={ 600 } as="h2" color="#23282d">
                            { title }
                        </Text>
                    </FlexBlock>

                    {actions && <FlexItem className="noptin-screen__actions"> { actions } </FlexItem> }
                </Flex>

				{ menu && menu }
			</CardHeader>

			{ children }

		</Card>
	);
};

export default Wrap;
