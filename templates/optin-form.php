<div  <?php echo noptin_form_template_wrapper_props(); ?>>
	<noptin-temp-form class="noptin-optin-form" <?php echo noptin_form_template_form_props(); ?>>
		<div class="noptin-video-container" :style="{borderRadius: formRadius}" v-if="noptinFormBgVideo" >
			<video  autoplay="" muted="" loop="" >
				<source :src="noptinFormBgVideo" type="video/mp4">
			</video>
		</div>
		<div class="noptin-form-header" :class="image ? imagePos : 'no-image'">
			<div class="noptin-form-header-text">
				<noptin-rich-text v-if="!hideTitle" :style="{color:titleColor}" class="noptin-form-heading" v-model="title" :text="title"></noptin-rich-text>
				<noptin-rich-text v-if="!hideDescription" :style="{color:descriptionColor}" class="noptin-form-description" v-model="description" :text="description"></noptin-rich-text>
			</div>
			<div v-if="image" class="noptin-form-header-image">
				<img :src="image" />
			</div>
		</div>
		<div class="noptin-form-footer">
			<div v-if="!hideFields" class="noptin-form-fields">
				<div  v-for="field in fields"  :key="field.key" class="noptin-optin-field-wrapper">
					<?php do_action( 'noptin_field_type_optin_markup' ); ?>
				</div>
				<input type="hidden" name="noptin_form_id" :value="id" />
				<input :value="noptinButtonLabel" type="submit"
					:style="{backgroundColor:noptinButtonBg, color: noptinButtonColor}"
					:class="singleLine ? '' : 'noptin-form-button-' + buttonPosition" class="noptin-form-submit" />
			</div>
			<div class="noptin-gdpr-checkbox-wrapper" v-if="gdprCheckbox && !hideFields">
				<label><input type='checkbox' value='1' name='noptin_gdpr_checkbox' required="required"/>{{gdprConsentText}}</label>
			</div>
			<noptin-rich-text v-if="!hideNote" :style="{ color: noteColor}" class="noptin-form-note" v-model="note" :text="note"></noptin-rich-text>
			<div style="border:1px solid rgba(6, 147, 227, 0.8);display:none;padding:10px;margin-top:10px"
				class="noptin_feedback_success"></div>
			<div style="border:1px solid rgba(227, 6, 37, 0.8);display:none;padding:10px;margin-top:10px"
				class="noptin_feedback_error"></div>
		</div>
		<span v-if="( !hideCloseButton && optinType=='popup' ) || optinType=='slide_in'" class="noptin-form-close" :class="closeButtonPos"
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
