<?php defined( 'ABSPATH' ) || exit; ?>
<div class="noptin-optin-form-wrapper"  :class="imageMain ? imageMainPos : 'no-image'">
	<noptin-temp-form class="noptin-optin-form" @submit.prevent :class="singleLine ? 'noptin-form-single-line' : 'noptin-form-new-line'">
		<div class="noptin-form-header" :class="image ? imagePos : 'no-image'">
			<div class="noptin-form-header-text">
				<contenteditable v-if="!hidePrefix" :style="'color:' + prefixColor + ';' + prefixTypography.generated + prefixAdvanced.generated" :class="prefixAdvanced.classes" tag="div" class="noptin-form-prefix" contenteditable @input="updateValue( 'prefix', $event )" :value="prefix" :noHTML="false" />
				<contenteditable v-if="!hideTitle" :style="'color:' + titleColor + ';' + titleTypography.generated + titleAdvanced.generated" :class="titleAdvanced.classes" tag="div" contenteditable class="noptin-form-heading" :value="title" @input="updateValue( 'title', $event )" :noHTML="false" />
				<contenteditable v-if="!hideDescription" :style="'color:' + descriptionColor + ';' + descriptionTypography.generated + descriptionAdvanced.generated" :class="descriptionAdvanced.classes" tag="div" class="noptin-form-description" contenteditable @input="updateValue( 'description', $event )" :value="description" :noHTML="false" />
			</div>
			<div v-if="image" class="noptin-form-header-image">
				<img :src="image" />
			</div>
		</div>
		<div class="noptin-form-footer">
			<div v-if="!hideFields" class="noptin-form-fields">
				<div  v-for="field in fields"  :key="field.key" class="noptin-optin-field-wrapper" :class="'noptin-optin-field-wrapper-' + field.type.type">
					<?php do_action( 'noptin_field_type_optin_markup' ); ?>
				</div>
				<div class="noptin-gdpr-checkbox-wrapper" style="margin-bottom: 10px;" v-if="gdprCheckbox && ! singleLine">
					<label><input type='checkbox' value='1' name='noptin_gdpr_checkbox' required="required"/><span v-html="gdprConsentText"></span></label>
				</div>
				<input type="hidden" name="noptin_form_id" :value="id" />
				<input :value="noptinButtonLabel" type="submit"
					:style="{backgroundColor:noptinButtonBg, color: noptinButtonColor}"
					:class="singleLine ? '' : 'noptin-form-button-' + buttonPosition" class="noptin-form-submit" />
			</div>
			<div class="noptin-gdpr-checkbox-wrapper" style="margin-top: 10px;" v-if="gdprCheckbox && !hideFields && singleLine">
				<label><input type='checkbox' value='1' name='noptin_gdpr_checkbox' required="required"/><span v-html="gdprConsentText"></span></label>
			</div>
			<contenteditable v-if="!hideNote" :style="'color:' + noteColor + ';' + noteTypography.generated + noteAdvanced.generated" :class="noteAdvanced.classes" tag="div" class="noptin-form-note" contenteditable @input="updateValue( 'note', $event )" :value="note" :noHTML="false" />
			<div style="border:1px solid rgba(6, 147, 227, 0.8);display:none;padding:10px;margin-top:10px"
				class="noptin_feedback_success"></div>
			<div style="border:1px solid rgba(227, 6, 37, 0.8);display:none;padding:10px;margin-top:10px"
				class="noptin_feedback_error"></div>
		</div>
		<span v-if="optinType=='popup' || optinType=='slide_in'" class="noptin-popup-close"
		title="close"><svg enable-background="new 0 0 24 24" id="Layer_1" version="1.0" viewBox="0 0 24 24"
			xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
			<g>
				<path
					:fill="descriptionColor"
					d="M12,2C6.5,2,2,6.5,2,12c0,5.5,4.5,10,10,10s10-4.5,10-10C22,6.5,17.5,2,12,2z M16.9,15.5l-1.4,1.4L12,13.4l-3.5,3.5   l-1.4-1.4l3.5-3.5L7.1,8.5l1.4-1.4l3.5,3.5l3.5-3.5l1.4,1.4L13.4,12L16.9,15.5z" />
			</g>
		</svg>
	</span>
	</noptin-temp-form>
	<div v-if="imageMain" class="noptin-form-main-image">
		<img :src="imageMain" />
	</div>
</div>
