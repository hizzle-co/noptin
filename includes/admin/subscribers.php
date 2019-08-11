<div class="wrap">
	<div class="noptin-subscribers-header">
        <div class="noptin-col-2">
            <img src="<?php echo $logo_url;?>" style="width: 100px;max-width: 100%;"/>
        </div>
        <div class="noptin-col-10">
            <h1><?php esc_html_e('Noptin', 'noptin')?></h1>
            <p class="noptin-big"><?php esc_html_e('View and download your email subscribers.', 'noptin')?></p>
        </div>
    </div>
    <div class="noptin-divider"></div>
    <div class="noptin-download-section">
        <div class="noptin-col-2 noptin-offset-10">
            <a href="<?php echo $download_url;?>" class="button button-primary noptin-download">Download CSV</a>
        </div>
    </div>

    <table class="wp-list-table widefat fixed striped posts">
	<thead>
	<tr>
        <td id="cb" class="manage-column column-cb check-column"><input id="cb-select-all-1" type="checkbox"></td>
        <th scope="col" id="title"><?php esc_html_e('Email Address', 'noptin')?></th>
        <th scope="col" id="author"><?php esc_html_e('First Name', 'noptin')?></th>
        <th scope="col" id="categories"><?php esc_html_e('Last Name', 'noptin')?></th>
        <th scope="col" id="date"><?php esc_html_e('Subscribed On', 'noptin')?></th>
    </tr>
	</thead>

	<tbody id="the-list">

    <?php foreach( $subscribers as $subscriber ){ ?>
        <tr>
             <th scope="row" class="check-column">
                <input type="checkbox" name="email[]" value="<?php echo esc_attr($subscriber->id); ?>">
            </th>
            <td><?php echo sanitize_text_field($subscriber->email); ?></td>
            <td><?php echo sanitize_text_field($subscriber->first_name); ?></td>
            <td><?php echo sanitize_text_field($subscriber->second_name); ?></td>
            <td><?php echo sanitize_text_field($subscriber->date_created); ?></td>
        </tr>
       <?php }?>

	</tbody>
	<tfoot>
    <td id="cb2" class="manage-column column-cb check-column">
        <input id="cb-select-all-1" type="checkbox"></td>
        <th scope="col" id="title"><?php esc_html_e('Email Address', 'noptin')?></th>
        <th scope="col" id="author"><?php esc_html_e('First Name', 'noptin')?></th>
        <th scope="col" id="categories"><?php esc_html_e('Last Name', 'noptin')?></th>
        <th scope="col" id="date"><?php esc_html_e('Subscribed On', 'noptin')?></th>
    </tr>
	</tfoot>

</table>
</div>
