import { __ } from '@wordpress/i18n';
import { Tip, Modal } from '@wordpress/components';

/**
 * Displays a single merge tag.
 *
 * @param {Object} props
 * @param {Object} props.mergeTag The merge tag object.
 * @param {Function} props.onMergeTagClick The callback to call when a merge tag is clicked.
 * @returns {JSX.Element}
 */
export const MergeTag = ({mergeTag, onMergeTagClick}) => {

    // Returns the merge tag value to use.
    const theValue = () => {

        if ( mergeTag.example ) {
            return mergeTag.example;
        }

        let defaultValue = "default value";

        if ( mergeTag.replacement ) {
            defaultValue = mergeTag.replacement;
        }

        if ( mergeTag.default ) {
            defaultValue = mergeTag.default;
        }

        if ( ! defaultValue ) {
            return `${mergeTag.smart_tag}`;
        }

        return `${mergeTag.smart_tag} default="${defaultValue}"`;
    }

    const showDescription = mergeTag.description && mergeTag.description !== mergeTag.label;

    // Selects the merge tag.
    const select = (e) => {

        // Select.
        e.target.select();

        // If we have a click handler, call it.
        if ( onMergeTagClick ) {
            onMergeTagClick(`[[${theValue()}]]`);
        }
    };

    return (
        <tr className="noptin-merge-tag">
            <td>
                <label>
                    <span className="noptin-merge-tag-label">{mergeTag.label}</span>
                    <input
                        type="text"
                        className="widefat"
                        value={`[[${theValue()}]]`}
                        onFocus={select}
                        readOnly
                    />
                </label>
                {showDescription && <p className="description noptin-mb0">{mergeTag.description}</p>}
            </td>
        </tr>
    );
};

/**
 * Displays a list of available merge tags.
 *
 * @param {Object} props
 * @param {Array} props.availableSmartTags The available smart tags.
 * @param {Function} props.onMergeTagClick The function to call when a merge tag is clicked.
 * @returns {JSX.Element}
 */
export const MergeTags = ({availableSmartTags, onMergeTagClick}) => {

    return (
        <div className="noptin-merge-tags-wrapper">
            <table className="widefat striped">
                <tbody>
                    {availableSmartTags.map((mergeTag) => (
                        <MergeTag key={mergeTag.smart_tag} mergeTag={mergeTag} onMergeTagClick={onMergeTagClick} />
                    ))}
                </tbody>
            </table>
        </div>
    );
};

/**
 * The merge tags modal.
 *
 * @param {Object} props
 * @param {boolean} props.isOpen Whether the modal is open.
 * @param {Function} props.closeModal The function to close the modal.
 * @param {Array} props.availableSmartTags The available smart tags. 
 * @param {Function} props.onMergeTagClick The function to call when a merge tag is clicked.
 * @returns 
 */
export const MergeTagsModal = ({isOpen, closeModal, availableSmartTags, onMergeTagClick}) => {

    return (
        <>
            { isOpen && (
                <Modal title={__( 'Smart Tags', 'newsletter-optin-box' )} onRequestClose={ closeModal }>
                    <div className="noptin-component__field-lg noptin-component__field-noptin_description">
                        <Tip>
                            {__( 'You can use the following smart tags to generate dynamic values.', 'newsletter-optin-box' )}
                        </Tip>
                    </div>
                    <MergeTags availableSmartTags={availableSmartTags} onMergeTagClick={onMergeTagClick} />
                </Modal>
            ) }
        </>
    );
};

/**
 * The merge tags thickbox modal.
 *
 * @param {Object} props
 * @param {Array} props.availableSmartTags The available smart tags. 
 * @returns 
 */
export const MergeTagsThickboxModal = ({availableSmartTags}) => {

    return (
        <div id="noptin-automation-rule-smart-tags" style={{display: 'none'}}>
            <h2>{__( 'Smart Tags', 'newsletter-optin-box' )}</h2>
            <p>{__( 'You can use the following smart tags to generate dynamic values.', 'newsletter-optin-box' )}</p>
			<MergeTags availableSmartTags={availableSmartTags} />
		</div>
    );
};
