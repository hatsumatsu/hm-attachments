# HM Attachment

Simple post image management.


### Settings

Use the `hm-attachments/settings` filter inside your `functions.php`. Right now there is only the option top set the supported post types.

`````
function hm_attachments_settings( $settings ) {
    $settings = array(
        'post_type' => array(
            'post',
            'my_post_type',
            'may_other_post_type'
        )
    );

    return $settings;
}

add_filter( 'hm-attachments/settings', 'hm_attachments_settings' );
`````

### Get attachments 

`````
getAttachments( $post_id, $orderby );
`````

`$post_id` the post ID to fetch attachments from

`$orderby` the field to order the attachments. can be `order` or `id`.