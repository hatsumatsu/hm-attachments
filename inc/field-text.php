<?php    
	$temp_id = ( $attachment['temp_id'] ) ? $attachment['temp_id'] : '{{temp_id}}';

	echo '<label for="hm-attachment[' . $temp_id . '][fields][' . $field_id . ']">' . $field_properties['label'] . '</label>';
    echo '<input type="text" name="hm-attachment[' . $temp_id . '][fields][' . $field_id . ']" value="' . esc_attr( $attachment['fields'][$field_id] ) . '">'; 
