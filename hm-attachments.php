<?php
/*
Plugin Name: HM Attachments
Plugin URI: 
Description: 
Author: 
Author URI: 
Version: 
License: GNU General Public License v2.0 or later
License URI: http://www.opensource.org/licenses/gpl-license.php
*/


class hmAttachments {

    public $config;

    public function __construct() {

        // i11n
        load_plugin_textdomain( 'hm-attachments', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

        add_action( 'admin_enqueue_scripts', array( $this, 'admin_js' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_css' ) );
        add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ) );
        add_action( 'admin_menu', array( $this, 'load_configuration' ) );
        add_action( 'save_post', array( $this, 'save_meta' ) );
        add_filter( 'wp_prepare_attachment_for_js',  array( $this, 'include_image_sizes_in_JSON' ), 10, 3 );

        // custom image size
        add_image_size( 'hm-attachments-thumbnail', 200, 200, true );

    }

    public function admin_js() {

        // This function loads in the required media files for the media manager.
        wp_enqueue_media();

        // Register, localize and enqueue our custom JS.
        wp_register_script( 'hm-attachments', plugins_url( '/js/hm-attachments.js', __FILE__ ), array( 'jquery', 'jquery-ui-sortable'  ), 0, true );
        wp_localize_script( 'hm-attachments', 'hm_attachments',
            array(
                'title'     => __( 'Upload or Choose Your Custom Image File', 'hm-attachments' ), 
                'button'    => __( 'Attach file', 'hm-attachments' )         
            )
        );
        wp_enqueue_script( 'hm-attachments' );

    }


    public function admin_css() {

        wp_register_style( 'hm-attachments', plugins_url( '/css/hm-attachments.css', __FILE__ ), 0, 'screen' );
        wp_enqueue_style( 'hm-attachments' );

    }


    public function register_meta_box() {

        add_meta_box( 'hm-attachments', __( 'HM Attachments', 'hm-attachments' ), array( $this, 'render_metabox' ), 'post', 'normal', 'high' );

    }


    public function load_configuration() {

        if( file_exists( TEMPLATEPATH . '/hm-attachments.json' ) ) {
            $this->config = json_decode( file_get_contents( get_template_directory_uri() . '/hm-attachments.json' ), true );
        } else {
            $this->config = array();
        }


    }

    public function get_attachments( $post_id ) {
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
            $order[$i] = $attachment['order'];
            $i++;
        }
        array_multisort( $order, SORT_ASC, $attachments );

        if( count( $attachments ) > 0 ) {
            return $attachments;
        } else {
            return false;
        }

    }


    public function render_metabox( $post ) {

        $attachments = $this->get_attachments( $post->ID );
        print_r( $attachments );

        wp_nonce_field( basename( __FILE__ ), 'hm_attachments_nonce' );
        echo '<div class="hm-attachments-posts">';

        $i = 0;
        if( $attachments ) {
    
            foreach( $attachments as $attachment ) {

                echo '<div class="hm-attachments-post" data-id="' . esc_attr( $attachment['temp_id'] ) . '">';
                echo wp_get_attachment_image( $attachment['id'], 'hm-attachments-thumbnail', true );
                echo '<input type="hidden" name="hm-attachment[' . $attachment['temp_id'] . '][id]" value="' . $attachment['id'] . '" class="id">';
                echo '<input type="hidden" name="hm-attachment[' . $attachment['temp_id'] . '][order]" value="' . esc_attr( $i ) . '" class="order">';

                // fields
                if( $this->config['fields'] ) {
                    foreach( $this->config['fields'] as $field_id => $field_properties ) {
                        include( 'inc/field-' . $field_properties['type'] . '.php' );
                    }
                } 
                echo '<a href="#" class="delete-link">' . __( 'Delete', 'hm-attachments' ) . '</a>';
                echo '</div>';

                $i++;
                // $order_max = ( $attachment['order'] > $order_max ) ? $attachment['order'] : $order_max;
            }

        }

        $attachment = null;

        // placeholder
        echo '<div class="hm-attachments-post hm-attachments-post-placeholder" data-id="{{temp_id}}">';
        echo '<img src="{{src}}" class="attachment-hm-attachments-thumbnail">';
        echo '<input type="hidden" name="hm-attachment[{{temp_id}}][id]" value="" class="id">';
        echo '<input type="hidden" name="hm-attachment[{{temp_id}}][order]" value="' . esc_attr( $i ) . '" class="order">';

        // fields
        if( $this->config['fields'] ) {
            foreach( $this->config['fields'] as $field_id => $field_properties ) {
                include( 'inc/field-' . $field_properties['type'] . '.php' );
            }
        } 

        echo '<a href="#" class="delete-link">' . __( 'Delete', 'hm-attachments' ) . '</a>';
        echo '</div>';
        echo '</div>';

        echo '<p><a href="#" class="hm-attachments-open-media button" title="' . esc_attr( __( 'Click Here to Open the Media Manager', 'hm_attachments' ) ) . '">' . __( 'Click Here to Open the Media Manager', 'hm_attachments' ) . '</a></p>';
    
    }


    public function save_meta( $post_id ) {

        if( wp_is_post_revision( $post_id ) ) {
            return;        
        }

        if( !current_user_can( 'edit_post' ) ) {
            return;
        }

        if( $_REQUEST['hm-attachment'] ) {

            // print_r( $_REQUEST['hm-attachment'] );

            // delete all data
            delete_post_meta( $post_id, 'hm-attachment' );
    
            foreach( $_REQUEST['hm-attachment'] as $temp_id => $data ) {

                if( $temp_id && $temp_id != '{{temp_id}}' ) {
                    $data = array( 
                        'id'        => $data['id'],
                        'order'     => $data['order'],
                        'fields'    => $data['fields']
                    );

                    // print_r( json_encode( $data ) );
                    // add_post_meta( $post_id, 'hm-attachment', json_encode( $data, JSON_UNESCAPED_UNICODE ) );
                    add_post_meta( $post_id, 'hm-attachment', json_encode( $data ) );

                }
            }

        }

    }

    public function include_image_sizes_in_JSON( $response, $attachment, $meta ){

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


}

// Instantiate the class.
$hm_attachments = new hmAttachments();