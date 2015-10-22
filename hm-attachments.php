<?php
namespace HM\Attachments;

/*
Plugin Name: HM Attachments
Version: 0.1
Description: Simple Media attachment management.
Plugin URI:
Author: Martin Wecke, HATSUMATSU
Author URI: http://hatsumatsu.de/
*/


/**
 * i11n
 *
 */
load_plugin_textdomain( 'hm-attachments', '/wp-content/plugins/hm-attachments/languages/' );


/**
 * Add custom image size to preview images in admin
 */
add_image_size( 'hm-attachments-thumbnail', 200, 200, true );


/**
 * Register admin JS
 */
function admin_js() {
    // This function loads in the required media files for the media manager.
    wp_enqueue_media();

    // Register, localize and enqueue our custom JS.
    wp_register_script( 'hm-attachments-admin', plugins_url( '/js/hm-attachments-admin.js', __FILE__ ), array( 'jquery', 'jquery-ui-sortable'  ), 0, true );
    wp_localize_script( 'hm-attachments-admin', 'hm_attachments',
        array(
            'title'     => __( 'Upload or Choose images', 'hm-attachments' ), 
            'button'    => __( 'Add images', 'hm-attachments' )         
        )
    );
    wp_enqueue_script( 'hm-attachments-admin' );
}

add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\admin_js' );


/**
 * Register admin CSS
 */
function admin_css() {
    wp_register_style( 'hm-attachments-admin', plugins_url( '/css/hm-attachments-admin.css', __FILE__ ), 0, 'screen' );
    wp_enqueue_style( 'hm-attachments-admin' );
}

add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\admin_css' );


/**
 * Add meta boxes
 */
function add_metabox() {
    add_meta_box( 'hm-attachments', __( 'Post Media', 'hm-attachments' ), __NAMESPACE__ . '\render_metabox', 'post', 'normal', 'high' );
}

add_action( 'add_meta_boxes', __NAMESPACE__ . '\add_metabox' );


/**
 * Get attachment data by post ID
 * @param int $post_id post ID
 * @return array attachment data
 */
function get_attachments( $post_id, $orderby = 'order' ) {
    $data = get_post_meta( $post_id, 'hm-attachment', false );

    $attachments = array();
    foreach( $data as $item ) {
        $item = json_decode( $item, true );
        $item['temp_id'] = uniqid();
        $attachments[] = $item;
    }

    // sort by order
    $order = array();
    $i = 0;
    foreach( $attachments as $attachment ) {
        $order[$i] = $attachment[$orderby];
        $i++;
    }
    array_multisort( $order, SORT_ASC, $attachments );

    if( count( $attachments ) > 0 ) {
        return $attachments;
    } else {
        return false;
    }

}


/**
 * Render meta box
 * @param  WP_Post $post post object
 */
function render_metabox( $post ) {
    $attachments = get_attachments( $post->ID );
    // print_r( $attachments );

    wp_nonce_field( basename( __FILE__ ), 'hm_attachments_nonce' );
    echo '<div class="hm-attachments-posts">';

    $i = 0;
    if( $attachments ) {

        foreach( $attachments as $attachment ) {
            $original = wp_get_attachment_image_src( $attachment['id'], 'full' );
            $filename = basename( $original[0] );
            if( mb_strlen( $filename ) > 19 ) {
                $filename = mb_substr( $filename, 0, 8 ) . '...' . mb_substr( $filename, ( mb_strlen( $filename ) - 8 ), mb_strlen( $filename ) );
            }

            echo '<div class="hm-attachments-post sortable" data-id="' . esc_attr( $attachment['temp_id'] ) . '" data-type="' . esc_attr( $attachment['type'] ) . '">';
            echo '<input type="hidden" name="hm-attachment[' . $attachment['temp_id'] . '][id]" value="' . esc_attr( $attachment['id'] ) . '" class="id">';
            echo '<input type="hidden" name="hm-attachment[' . $attachment['temp_id'] . '][order]" value="' . esc_attr( $i ) . '" class="order">';                
            echo '<input type="hidden" name="hm-attachment[' . $attachment['temp_id'] . '][type]" value="' . esc_attr( $attachment['type'] ) . '" class="type">';                


            echo wp_get_attachment_image( $attachment['id'], 'hm-attachments-thumbnail', true, array( 'class' => 'hm-attachments-preview hm-attachments-preview-image' ) );

            // label
            echo '<div class="hm-attachments-post-label">';
            echo '<p class="meta meta--filename">' . $filename . '</p>';              
            echo '<p class="meta meta--dimensions">' . $original[1] . '&thinsp;&times;&thinsp;' . $original[2] . 'px</p>';  
            echo '</div>';

            // actions 
            echo '<div class="hm-attachments-post-actions">';
            echo '<a href="#" class="edit-link">' . __( 'Edit', 'hm-attachments' ) . '</a>';
            echo '<a href="#" class="delete-link">' . __( 'Delete', 'hm-attachments' ) . '</a>';
            echo '</div>';

            // info 
            echo '<div class="hm-attachments-post-info">';
            echo '<div class="hm-attachments-post-info-header">';    
            echo '<h4>' . __( 'Image details', 'hm-theme' ) . '</h4>';
            echo '</div>';
            echo '<label>' . __( 'Title', 'hm-theme' ) . '</label>';
            echo '<input type="text" name="hm-attachment[' . $attachment['temp_id'] . '][fields][title]" value="' . esc_attr( $attachment['fields']['title'] ) . '">'; 
            echo '<div class="hm-attachments-post-info-footer">';    
            echo '<a href="#" class="button button-primary hm-attachments-post-info-save">' . __( 'Save' ) . '</a>';    
            echo '</div>';            
            echo '</div>';


            echo '</div>';

            $i++;
            // $order_max = ( $attachment['order'] > $order_max ) ? $attachment['order'] : $order_max;
        }

    }

    $attachment = null;

    // PLACEHOLDERS
    // image
    echo '<div class="hm-attachments-post sortable hm-attachments-post-placeholder" data-id="{{temp_id}}" data-type="image">';
    echo '<img src="{{src}}" class="hm-attachments-thumbnail hm-attachments-preview hm-attachments-preview-image">';
    echo '<input type="hidden" name="hm-attachment[{{temp_id}}][id]" value="" class="id">';
    echo '<input type="hidden" name="hm-attachment[{{temp_id}}][order]" value="' . esc_attr( $i ) . '" class="order">';
    echo '<input type="hidden" name="hm-attachment[{{temp_id}}][type]" value="image" class="type">';


    // label
    echo '<div class="hm-attachments-post-label">';
    echo '<p class="meta meta--filename">{{filename}}</p>';      
    echo '<p class="meta meta--dimensions">{{width}}&thinsp;&times;&thinsp;{{height}}px</p>';  
    echo '</div>';

    // actions
    echo '<div class="hm-attachments-post-actions">';
    echo '<a href="#" class="edit-link">' . __( 'Edit', 'hm-attachments' ) . '</a>';
    echo '<a href="#" class="delete-link">' . __( 'Delete', 'hm-attachments' ) . '</a>';
    echo '</div>';

    // info
    echo '<div class="hm-attachments-post-info">';
    echo '<div class="hm-attachments-post-info-header">';    
    echo '<h4>' . __( 'Image details', 'hm-theme' ) . '</h4>';  
    echo '</div>';
    echo '<label>' . __( 'Title', 'hm-theme' ) . '</label>';
    echo '<input type="text" name="hm-attachment[{{temp_id}}][fields][title]" value="">'; 
    echo '<div class="hm-attachments-post-info-footer">';    
    echo '<a href="#" class="button button-primary hm-attachments-post-info-save">' . __( 'Save' ) . '</a>';    
    echo '</div>';    
    echo '</div>'; 

    echo '</div>';


    // ADD NEW ITEM
    echo '<div class="hm-attachments-post hm-attachments-post-add">';
    echo '<a href="#" class="hm-attachments-open-media button" title="' . esc_attr( __( 'Add Media', 'hm_attachments' ) ) . '">' . __( 'Add Media', 'hm_attachments' ) . '</a>';
    // echo '<a href="#" class="hm-attachments-add-text button" title="' . esc_attr( __( 'Add Text', 'hm_attachments' ) ) . '">' . __( 'Add Text', 'hm_attachments' ) . '</a>';
    echo '</div>';

    echo '</div>';    
}


/**
 * Save attachment data when post is saved
 * @param  int $post_id post ID
 */
function save_meta( $post_id ) {
    if( wp_is_post_revision( $post_id ) ) {
        return;        
    }

    if( !current_user_can( 'edit_post' ) ) {
        return;
    }

    if( $_REQUEST['hm-attachment'] ) {

        // delete all data
        delete_post_meta( $post_id, 'hm-attachment' );

        foreach( $_REQUEST['hm-attachment'] as $temp_id => $data ) {

            if( $temp_id && $temp_id != '{{temp_id}}' ) {

                $id = ( $data['id'] ) ? $data['id'] : $temp_id;


                $data = array( 
                    'id'        => $id,
                    'type'      => $data['type'],
                    'order'     => $data['order'],
                    'fields'    => $data['fields'],
                    'title'     => $data['title']
                );

                add_post_meta( $post_id, 'hm-attachment', json_encode( $data, JSON_UNESCAPED_UNICODE ) );
            }
        }
    }
}

add_action( 'save_post', __NAMESPACE__ . '\save_meta' );


/**
 * Add custom data to the JSON object 
 * handed over by the WP media modal
 * @param  array $response     modal response
 * @param  WP_Post $attachment attachment object
 * @param  array $meta         attachment meta
 * @return array               modified modal response
 */
function include_image_sizes_in_JSON( $response, $attachment, $meta ){
    $sizes = array( 'hm-attachments-thumbnail' ) ;

    foreach( $sizes as $size ) {

        if( isset( $meta['sizes'][ $size ] ) ) {
            $attachment_url = wp_get_attachment_url( $attachment->ID );
            $base_url = str_replace( wp_basename( $attachment_url ), '', $attachment_url );
            $size_meta = $meta['sizes'][ $size ];

            $response['sizes'][ $size ] = array(
                'height'        => $size_meta['height'],
                'width'         => $size_meta['width'],
                'url'           => $base_url . $size_meta['file'],
                'orientation'   => $size_meta['height'] > $size_meta['width'] ? 'portrait' : 'landscape',
            );
        }

    }

    return $response;
}

add_filter( 'wp_prepare_attachment_for_js',  __NAMESPACE__ . '\include_image_sizes_in_JSON', 10, 3 );