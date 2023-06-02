/**
 * External dependencies
 */
import {
	Card,
	CardHeader,
	__experimentalText as Text,
    Flex,
    FlexItem,
} from '@wordpress/components';
import { forwardRef } from "@wordpress/element";

/**
 * Wraps content.
 */
const Wrap = ( { actions, className, title, menu, children }, ref ) => {

	return (
		<Card className={ className } ref={ ref }>

			<CardHeader>
                <Flex justify="start" wrap>

                    <FlexItem>
                        <Text size={ 16 } weight={ 600 } as="h2" color="#23282d">
                            { title }
                        </Text>
                    </FlexItem>

                    {actions && <FlexItem className="noptin-screen__actions"> { actions } </FlexItem> }
                </Flex>

				{ menu && menu }
			</CardHeader>

			{ children }

		</Card>
	);
};

export default forwardRef( Wrap );
