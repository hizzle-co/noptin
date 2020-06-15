<?php

	$trigger     = esc_attr( $triggerPopup );
	$after_click = esc_attr( $cssClassOfClick );
	$on_scroll   = esc_attr( $scrollDepthPercentage );
	$delay       = esc_attr( $timeDelayDuration );
	$class       = esc_attr( "noptin-slide-from-$slideDirection" );
	$session     = (bool) $DisplayOncePerSession;
	$styles      = array(
		'border-color'     => $noptinFormBorderColor,
		'border-width'     => $borderSize,
		'background-color' => $noptinFormBg,
		'background-image' => "url('$noptinFormBgImg')",
		'border-radius'    => $formRadius,
		'width'            => $formWidth,
		'min-height'       => $formHeight,
		'color'            => $descriptionColor,
	);

	if( 'popup' !== $optinType && 'slide_in' !== $optinType ) {
		$styles['width'] = '100%';
	}

	if ( is_numeric( $styles['width'] ) ) {
		$styles['width'] = $styles['width'] . 'px';
	}

	if ( is_numeric( $styles['min-height'] ) ) {
		$styles['min-height'] = $styles['min-height'] . 'px';
	}

	if ( is_numeric( $styles['border-radius'] ) ) {
		$styles['border-radius'] = $styles['border-radius'] . 'px';
	}

	if ( empty( $noptinFormBgImg ) ) {
		unset( $styles['background-image'] );
	}

	$wrapper_styles = '';
	foreach ( $styles as $prop => $val ) {
		$val = esc_attr( $val );
		$wrapper_styles .= " $prop:$val;";
	}
?>
<div style='<?php echo $wrapper_styles; ?>' data-trigger='<?php echo $trigger ?>' data-after-click='<?php echo $after_click ?>' data-on-scroll='<?php echo $on_scroll ?>' data-after-delay='<?php echo $delay ?>' data-once-per-session='<?php echo $session ?>' class='noptin-optin-form-wrapper <?php echo $class ?>'>
	<form class="noptin-optin-form <?php echo $singleLine ? 'noptin-form-single-line' : 'noptin-form-new-line' ?>">

		<?php if ( ! empty( $noptinFormBgVideo ) ) { ?>
			<div class="noptin-video-container" style="border-radius: <?php echo esc_html( $formRadius ); ?>">
				<video  autoplay="" muted="" loop="" >
					<source src="<?php echo $noptinFormBgVideo; ?>" type="video/mp4">
				</video>
			</div>
		<?php } ?>

		<div class="noptin-form-header <?php echo ! empty( $image ) ? esc_attr( $imagePos ) : 'no-image' ?>">

			<div class="noptin-form-header-text">
				<?php if ( ! $hideTitle ) { ?>
					<div style="color:<?php echo esc_attr( $titleColor ); ?>" class="noptin-form-heading"><?php echo $title; ?></div>
				<?php } ?>
				<?php if ( ! $hideDescription ) { ?>
					<div style="color:<?php echo esc_attr( $descriptionColor ); ?>" class="noptin-form-description"><?php echo $description; ?></div>
				<?php } ?>
			</div>

			<?php if ( ! empty( $image ) ) { ?>
				<div class="noptin-form-header-image">
					<img src="<?php echo $image; ?>" />
				</div>
			<?php } ?>

		</div>

		<div class="noptin-form-footer">

			<?php if ( ! $hideFields ) { ?>
				<div class="noptin-form-fields">

					<?php foreach( $fields as $field ) { ?>
						<div class="noptin-optin-field-wrapper noptin-optin-field-wrapper-<?php echo esc_attr( $field['type']['type']) ?>">
							<?php do_action( 'noptin_field_type_frontend_optin_markup', $field, $data ); ?>
						</div>
					<?php } ?>

					<?php if ( $gdprCheckbox && ! $singleLine ) { ?>
						<div class="noptin-gdpr-checkbox-wrapper" style="margin-bottom: 10px;">
							<label><input type='checkbox' value='1' name='noptin_gdpr_checkbox' required="required"/><span><?php echo $gdprConsentText; ?></span></label>
						</div>
					<?php } ?>

					<input type="hidden" name="noptin_form_id" value="<?php echo esc_attr( $id ) ?>" />

					<?php
						$value    = esc_attr( $noptinButtonLabel );
						$bg_color = esc_attr( $noptinButtonBg );
						$color    = esc_attr( $noptinButtonColor );
						$class    = '';

						if ( ! $singleLine ) {
							$class = esc_attr( "noptin-form-button-$buttonPosition" );
						}
					?>
					<input value="<?php echo $value; ?>" type="submit"
						style="background-color: <?php echo $bg_color; ?>; color: <?php echo $color; ?>;"
						class="noptin-form-submit <?php echo $class; ?>" />
				</div>
			<?php } ?>

			<?php if ( $gdprCheckbox && ! $hideFields && $singleLine ) { ?>
				<div class="noptin-gdpr-checkbox-wrapper" style="margin-bottom: 10px;">
					<label><input type='checkbox' value='1' name='noptin_gdpr_checkbox' required="required"/><span><?php echo $gdprConsentText; ?></span></label>
				</div>
			<?php } ?>

			<?php if ( ! $hideNote ) { ?>
				<div style="color:<?php echo esc_attr( $noteColor ); ?>" class="noptin-form-note"><?php echo $note; ?></div>
			<?php } ?>

			<div style="border:1px solid rgba(6, 147, 227, 0.8);display:none;padding:10px;margin-top:10px"
				class="noptin_feedback_success"></div>
			<div style="border:1px solid rgba(227, 6, 37, 0.8);display:none;padding:10px;margin-top:10px"
				class="noptin_feedback_error"></div>
		</div>

		<?php if ( 'popup' === $optinType || 'slide_in' === $optinType ) { ?>
			<span class="noptin-popup-close" :class="closeButtonPos"
				title="close"><svg enable-background="new 0 0 24 24" id="Layer_1" version="1.0" viewBox="0 0 24 24"
					xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
					<g>
						<path
							:fill="descriptionColor"
							d="M12,2C6.5,2,2,6.5,2,12c0,5.5,4.5,10,10,10s10-4.5,10-10C22,6.5,17.5,2,12,2z M16.9,15.5l-1.4,1.4L12,13.4l-3.5,3.5   l-1.4-1.4l3.5-3.5L7.1,8.5l1.4-1.4l3.5,3.5l3.5-3.5l1.4,1.4L13.4,12L16.9,15.5z" />
					</g>
				</svg>
			</span>
		<?php } ?>	
	</form>

	<?php if ( $imageMain ) { ?>
		<div v-if="imageMain" class="noptin-form-main-image">
			<img :src="imageMain" />
		</div>
	<?php } ?>

</div>
