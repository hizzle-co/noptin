<style id="popupCustomCSS"></style>
<div class="noptin-popup-designer">
    
    <div id="noptin-popups-app" v-if="!isPreviewShowing">
        <div class="noptin-popup-editor-header" tabindex="-1">
            <div class="noptin-popup-editor-title">{{headerTitle}} &mdash; {{optinName}}</div>
            <div class="noptin-popup-editor-toolbar">
                <button @click="previewPopup()" type="button" class="button button-secondary noptin-popup-editor-header-button">{{previewText}}</button>
                <button @click="save()" type="button" class="button button-primary noptin-popup-editor-header-button">{{saveText}}</button>
            </div>
        </div>
        <div class="noptin-divider"></div>
        <div class="noptin-popup-editor-body">
            <div class="noptin-popup-editor-main">
                <div class="noptin-popup-editor-main-preview">
                    <div class="noptin-popup-notice noptin-is-error" v-if="hasError">{{Error}}</div>
                    <div class="noptin-popup-notice noptin-is-error" v-if="hasSuccess">{{Success}}</div>
                    <div  class="noptin-popup-wrapper"  :style="custom_css">
                        <div  class="noptin-popup-form-wrapper" :style="{backgroundColor: noptinFormBg, width: formWidth, minHeight: formHeight, borderColor: noptinFormBorderColor}">
                            <form :class="singleLine ? 'noptin-popup-single-line' : 'noptin-popup-new-line'">
                                <div v-if="!noptin_hide_title" :style="{color:noptin_title_color}" class="noptin-popup-form-heading" v-html="noptin_title_text"></div>
                                <div v-if="!noptin_hide_description"  :style="{color:noptin_description_color}" class="noptin-popup-form-description" v-html="noptin_description_text"></div>
                                <div class="noptin-popup-fields" :style="{display: singleLine? 'flex' : 'block'}">
                                    <input v-if="showingFullName" type="text" class="noptin-popup-field" placeholder="Full Names" :required="requireNameField" />
                                    <input v-if="showingSingleName" type="text" class="noptin-popup-field" placeholder="First Name" :required="requireNameField" />
                                    <input v-if="showingSingleName" type="text" class="noptin-popup-field" placeholder="Last Name" :required="requireNameField" />
                                    <input type="email" class="noptin-popup-field" placeholder="Email Address" required />
                                    <input :value="noptinButtonLabel" type="submit" :style="{backgroundColor:noptinButtonBg, color: noptinButtonColor}" :class="singleLine ? '' : 'noptin-popup-botton-' + buttonPosition" class="noptin-popup-submit"/>
                                    <input type="hidden" class="noptin_form_redirect"/>
                                </div>
                                <p v-if="!noptin_hide_note" :style="{ color: noptin_note_color}" class="noptin-popup-note" v-html="noptin_note_text"></p>
                            <div style="border:1px solid rgba(6, 147, 227, 0.8);display:none;padding:10px;margin-top:10px" class="noptin_feedback_success"></div>
                            <div style="border:1px solid rgba(227, 6, 37, 0.8);display:none;padding:10px;margin-top:10px" class="noptin_feedback_error"></div>
                            </form>
                            <span v-if="!hideCloseButton" class="noptin-popup-close" title="close"><svg enable-background="new 0 0 24 24" id="Layer_1" version="1.0" viewBox="0 0 24 24" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g><path d="M12,2C6.5,2,2,6.5,2,12c0,5.5,4.5,10,10,10s10-4.5,10-10C22,6.5,17.5,2,12,2z M16.9,15.5l-1.4,1.4L12,13.4l-3.5,3.5   l-1.4-1.4l3.5-3.5L7.1,8.5l1.4-1.4l3.5,3.5l3.5-3.5l1.4,1.4L13.4,12L16.9,15.5z"/></g></svg></span> 
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
                        echo " <div class='noptin-popup-editor-{$panel}-fields'  v-if=\"'{$panel}'==currentSidebarSection\">";
                        foreach( $fields as $id=>$field ){
                            noptin_render_editor_field( $id, $field );
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