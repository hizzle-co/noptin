<?php

defined( 'ABSPATH' ) || exit;

$senders = get_noptin_email_senders();

/**
 * @var Noptin_Automated_Email $campaign
 */

?>
<div class="submitbox" id="submitpost">

	<div class="noptin-publishing-actions">

		<p>
			<label>
				<strong class="noptin-label-span"><?php esc_html_e( 'Status', 'newsletter-optin-box' ); ?></strong>
				<select name="noptin_automation[status]" style="width: 100%;">
					<option <?php selected( $campaign->is_published() ); ?> value='publish'><?php _e( 'Active', 'newsletter-optin-box' ); ?></option>
					<option <?php selected( ! $campaign->is_published() ); ?> value='draft'><?php _e( 'Disabled', 'newsletter-optin-box' ); ?></option>
				</select>
			</label>
		</p>

		<?php if ( $campaign->is_mass_mail() ) : ?>
            <div class="noptin-select-email-sender senders-<?php echo count( $senders ); ?>">
                <label style="display:<?php echo 1 < count( $senders ) ? 'block' : 'none'; ?>; width:100%;" class="noptin-margin-y noptin-email-senders-label">
                    <strong><?php _e( 'Sends to', 'newsletter-optin-box' ); ?></strong>
                    <select name="noptin_automation[email_sender]" class="noptin-email_sender" style="display:block; width:100%;">
                        <?php foreach ( $senders as $key => $label ) : ?>
                            <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $campaign->get_sender() ); ?>><?php echo esc_html( $label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <?php foreach ( array_keys( $senders ) as $_sender ) : ?>
                <div class="noptin-sender-options noptin-margin-y sender-<?php echo esc_attr( $_sender ); ?>" style="display:<?php echo $_sender == $campaign->get_sender() ? 'block' : 'none'; ?>;">
                    <?php echo do_action( "noptin_sender_options_$_sender", $campaign ); ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

		<?php do_action( 'noptin_automation_publishing_actions', $campaign ); ?>

		<?php if ( $campaign->exists() ) : ?>
			<p>
				<?php
					printf(
						'%1$s <b>%2$s</b>',
						esc_html__( 'Created:', 'newsletter-optin-box' ),
						wp_kses_post( noptin_format_date( $campaign->created ) )
					);
				?>
			</p>
		<?php endif; ?>

	</div>

	<div id="major-publishing-actions">
		<?php if ( $campaign->exists() && current_user_can( 'delete_post', $campaign->id ) ) : ?>
			<div id="delete-action">
				<a class="noptin-delete-campaign submitdelete deletion" data-redirect="<?php echo esc_url( remove_query_arg( array( 'sub_section', 'campaign' ) ) ); ?>" data-id="<?php echo (int) $campaign->id; ?>" href="#">
					<?php echo _e( 'Delete Permanently', 'newsletter-optin-box' ); ?>
				</a>
			</div>
		<?php endif; ?>

		<?php do_action( 'noptin_automation_major_publishing_actions', $campaign ); ?>

		<div id="publishing-action">
			<span class="spinner"></span>
			<?php submit_button( __( 'Save', 'newsletter-optin-box' ), 'primary', 'submit', false ); ?>
		</div>
		<div class="clear"></div>
	</div>
</div>
