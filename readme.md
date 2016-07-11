# HM Attachment

Simple post image management.


### Settings

Use the `hm-attachments/settings` filter inside your `functions.php`. Right now there is only one settings for the supported post types. Put the fo

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

add_filter( 'hm-attachments/settings', 'hma_settings' );
`````

### Get attachments 

`$post_id` the post ID to fetch attachments from
`orderby` the field to order the attachments. can be `order` or `id`.

`````
getAttachments( $post_id, $orderby )
`````
