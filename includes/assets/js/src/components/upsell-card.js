import { Upsell } from "./styled-components";
import { Button, Tip } from "@wordpress/components";

/**
 * Displays an upsell card.
 *
 * @param {Object} props
 * @returns 
 */
const UpsellCard = ({upsell}) => {

    if ( ! upsell ) {
        return null;
    }

    const { content, buttonURL, buttonText } = upsell;

    return (
        <Upsell>
            <Tip>
                { content }
                <Button href={buttonURL} target="_blank" variant="link">
                    {buttonText}
                </Button>
            </Tip>
        </Upsell>
    );
};

export default UpsellCard;
