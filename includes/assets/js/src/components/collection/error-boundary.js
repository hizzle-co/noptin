import { ErrorBoundary } from "react-error-boundary";
import { __ } from "@wordpress/i18n";
import { Notice, CardBody } from "@wordpress/components";

function Fallback({ error }) {

    return (
        <Notice status="error" isDismissible={false}>
            <strong>{__('Error:', 'newsletter-optin-box')}</strong>&nbsp;
            {error.message}
        </Notice>
    );
}

/**
 * Attaches an error boundary to the children.
 *
 * @returns 
 */
export default function ErrorBoundaryWrapper({ children }) {

    return (
        <ErrorBoundary FallbackComponent={Fallback}>
            {children}
        </ErrorBoundary>
    );
}
