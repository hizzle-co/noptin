<tr class="form-field-row-<?php echo $id; ?> form-field-row-type-<?php echo $type; ?>">
    <th scope="row">
        <label for="custom_field_<?php echo $id; ?>"><?php echo $label; ?></label>
    </th>
    <td>
        <div>
            <textarea
                class="regular-textarea"
                name="noptin_custom_field[<?php echo $name; ?>]"
                id="custom_field_<?php echo $id; ?>"
                rows="4"
                style="width: 25em;"
            ><?php echo esc_textarea( wp_kses_post( $value ) ); ?></textarea>
        </div>
    </td>
</tr>
