import { Card, CardHeader, Flex, FlexBlock, FlexItem, Icon, Button } from '@wordpress/components';
import { useState } from '@wordpress/element';

/**
 * Displays a section.
 *
 * @param {Object} props
 * @param {String} props.title
 * @param {JSX.Element} props.children
 * @param {String} props.className
 * @param {Boolean} props.isSecodary
 * @return {JSX.Element}
 */
export default function Section( { title, isSecodary, className, children } ) {

    const [ isOpen, setIsOpen ] = useState( true );
    className = className || '';

    return (
        <Card variant={ isSecodary ? 'secondary' : 'primary' } className={`noptin-component__section ${className}`}>

            <CardHeader>
                <Flex>
                    <FlexBlock>
                        <h3>{title}</h3>
                    </FlexBlock>
                    <FlexItem>
                        <Button
                            isTertiary
                            onClick={() => setIsOpen( ! isOpen )}
                        >
                            <Icon icon={isOpen ? 'arrow-up-alt2' : 'arrow-down-alt2'}/>
                        </Button>
                    </FlexItem>
                </Flex>
            </CardHeader>

            {isOpen && children}

        </Card>
    );
}
