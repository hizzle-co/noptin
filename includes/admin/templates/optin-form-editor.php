<style id="popupCustomCSS"></style>
<div class="noptin-popup-designer-loader"><div class="noptin-spinner"></div></div>
<div class="noptin-popup-designer">
    <div id="noptin-popups-app" v-if="!isPreviewShowing">
        <div class="noptin-popup-editor-header" tabindex="-1">
            <div class="noptin-popup-editor-title">{{headerTitle}} &mdash; {{optinName}} <button @click="saveAsTemplate()" type="button" class="button button-link noptin-popup-editor-header-button">{{saveAsTemplateText}}</button></div>
            <div class="noptin-popup-editor-toolbar">
                <button v-if="optinType == 'popup'" @click="previewPopup()" type="button" class="button button-secondary noptin-popup-editor-header-button">{{previewText}}</button>
                <button @click="save()" type="button" class="button button-primary noptin-popup-editor-header-button">{{saveText}}</button>
            </div>
        </div>
        <div class="noptin-divider"></div>
        <div class="noptin-popup-editor-body">
            <div class="noptin-popup-editor-main">
                <div class="noptin-popup-editor-main-preview">
                    <div class="noptin-popup-notice noptin-is-error" v-show="hasError" v-html="Error"></div>
                    <div class="noptin-popup-notice noptin-is-success" v-show="hasSuccess" v-html="Success"></div>
                    <div  class="noptin-popup-wrapper">
                        <div  class="noptin-popup-form-wrapper" :style="{borderRadius: formRadius, backgroundColor: noptinFormBg, width: formWidth, minHeight: formHeight, borderColor: noptinFormBorderColor}">
                            <form @submit.prevent="updateCustomCss" :class="singleLine ? 'noptin-popup-single-line' : 'noptin-popup-new-line'">
                                <div class="noptin-popup-header" :class="image ? imagePos : 'no-image'">
                                    <div class="noptin-popup-header-text">
                                        <div v-if="!hideTitle" :style="{color:titleColor}" class="noptin-popup-form-heading" v-html="title"></div>
                                        <div v-if="!hideDescription"  :style="{color:descriptionColor}" class="noptin-popup-form-description" v-html="description"></div>
                                    </div>
                                    <div v-if="image" class="noptin-popup-header-image">
                                        <img :src="image" />
                                    </div>
                                </div>
                                <div class="noptin-popup-fields" :style="{display: singleLine? 'flex' : 'block'}">
                                    <input v-if="showingFullName" type="text" class="noptin-popup-field" placeholder="Name" :required="requireNameField" />
                                    <input v-if="showingSingleName" type="text" class="noptin-popup-field" placeholder="First Name" :required="requireNameField" />
                                    <input v-if="showingSingleName" type="text" class="noptin-popup-field" placeholder="Last Name" :required="requireNameField" />
                                    <input type="email" class="noptin-popup-field" placeholder="Email Address" required />
                                    <input :value="noptinButtonLabel" type="submit" :style="{backgroundColor:noptinButtonBg, color: noptinButtonColor}" :class="singleLine ? '' : 'noptin-popup-botton-' + buttonPosition" class="noptin-popup-submit"/>
                                </div>
                                <p v-if="!hideNote" :style="{ color: noteColor}" class="noptin-popup-note" v-html="note"></p>
                            <div style="border:1px solid rgba(6, 147, 227, 0.8);display:none;padding:10px;margin-top:10px" class="noptin_feedback_success"></div>
                            <div style="border:1px solid rgba(227, 6, 37, 0.8);display:none;padding:10px;margin-top:10px" class="noptin_feedback_error"></div>
                            </form>
                            <span v-if="!hideCloseButton" class="noptin-popup-close"  :class="closeButtonPos" title="close"><svg enable-background="new 0 0 24 24" id="Layer_1" version="1.0" viewBox="0 0 24 24" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g><path d="M12,2C6.5,2,2,6.5,2,12c0,5.5,4.5,10,10,10s10-4.5,10-10C22,6.5,17.5,2,12,2z M16.9,15.5l-1.4,1.4L12,13.4l-3.5,3.5   l-1.4-1.4l3.5-3.5L7.1,8.5l1.4-1.4l3.5,3.5l3.5-3.5l1.4,1.4L13.4,12L16.9,15.5z"/></g></svg></span> 
                        </div>
                    </div>
                </div>
            </div>
            <div class="noptin-popup-editor-sidebar">
                <div class="noptin-popup-editor-sidebar-header">
                    <ul>
                        <?php
                            foreach ( array_keys($sidebar) as $panel ) {
                                $_panel = ucfirst($panel);
                                echo "
                                    <li>
                                        <button 
                                            class='noptin-popup-editor-sidebar-{$panel}'
                                            :class=\"{ active: currentSidebarSection == '$panel' }\"
                                            @click=\"currentSidebarSection = '$panel'\">$_panel</button>
                                    </li>
                                ";
                            }
                        ?>
                    </ul>
                </div>
                <div class="noptin-popup-editor-sidebar-body">

                <?php
                    foreach ( $sidebar as $panel => $fields ) {
                        echo " <div class='noptin-popup-editor-{$panel}-fields'  v-show=\"'{$panel}'==currentSidebarSection\">";
                        foreach( $fields as $id=>$field ){
                            noptin_render_editor_field( $id, $field, $panel );
                        }
                        echo "</div>";
                    }
                ?>
                </div>
            </div>
        </div>
    </div>
</div>
<div id="noptin-popup-preview"></div>