<tr class="form-field-row-<?php echo $id; ?> form-field-row-type-<?php echo $type; ?>">
    <th scope="row">
        <label for="custom_field_<?php echo $id; ?>"><?php echo $label; ?></label>
    </th>
    <td>
        <div>
            <input
                type="text"
                class="regular-text"
                name="noptin_custom_field[<?php echo $name; ?>]"
                id="custom_field_<?php echo $id; ?>"
                value="<?php echo esc_attr( $value ); ?>"
            >
        </div>
    </td>
</tr>