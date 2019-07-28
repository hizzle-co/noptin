<style id="popupCustomCSS"></style>
<div class="noptin-popup-designer-loader"><div class="noptin-spinner"></div></div>
<div class="noptin-popup-designer">
    <div id="noptin-popups-app2">
        <div class="noptin-popup-editor-header" tabindex="-1">
            <div class="noptin-popup-editor-title">{{headerTitle}} &mdash; {{optinName}} </div>
        </div>
        <div class="noptin-divider"></div>
        <div class="noptin-popup-editor-body">
            <div class="noptin-popup-editor-main">
                <div class="noptin-popup-editor-main-preview">
                    <?php foreach ( $steps as $step=>$fields ) { ?>
                        <div v-show="currentStep=='<?php echo $step; ?>'" class="noptin-form-editor-step">
                            <?php 
                                foreach ( $fields as $id => $field ) {
                                    noptin_render_editor_field( $id, $field, $step );         
                                }
                            ?>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/x-template" id="noptinFormTemplate">
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
    </div>
</script>
