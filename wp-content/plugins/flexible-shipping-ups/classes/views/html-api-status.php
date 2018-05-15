<tr valign="top">
    <th scope="row" class="titledesc">
		<?php echo $this->get_tooltip_html( $data ); ?>
        <label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
    </th>
    <td class="forminp">
        <legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
        <span class="<?php echo esc_attr( $data['class'] ); ?>" id="<?php echo esc_attr( $field_key ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>">
                            <?php echo $data['default']; ?>
                        </span>
		<?php echo $this->get_description_html( $data ); ?>
        </fieldset>
    </td>
</tr>
