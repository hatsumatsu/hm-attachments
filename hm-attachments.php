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

        add_filter( 'ajax_query_attachments_args', array( $this, 'modify_media_uploader_query' ) );


        // custom image size
        add_image_size( 'hm-attachments-thumbnail', 200, 200, true );

    }

    public function admin_js() {

        // This function loads in the required media files for the media manager.
        wp_enqueue_media();

        // Register, localize and enqueue our custom JS.
        wp_register_script( 'hm-attachments', plugins_url( '/js/hm-attachments.js', __FILE__ ), array( 'jquery' ), 0, true );
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


    public function render_metabox( $post ) {
        global $post_id;

        $attachments = get_posts( 
            array(
                'posts_per_page' => -1,
                'post_type' => 'attachment',
                'post_parent' => $post->ID,
                'orderby' => 'menu_order',
                'order' => 'ASC'
                )
            );

        // print_r( $attachments );

        if( $attachments ) {

            echo '<div class="hm-attachments-posts">';

            wp_nonce_field( basename( __FILE__ ), 'hm_attachments_nonce' );

            $menu_order_max = 0;

            foreach( $attachments as $attachment ) {
                echo '<div class="hm-attachments-post" data-id="' . esc_attr( $attachment->ID ) . '">';

                echo wp_get_attachment_image( $attachment->ID, 'hm-attachments-thumbnail', true );

                // menu order
                echo '<input type="hidden" name="hm-attachments[' . $attachment->ID . '][menu_order]" value="' . esc_attr( $attachment->menu_order ) . '" class="menu_order">';

                // title
                if( $this->config['fields']['title'] ) {
                    echo '<label for="hm-attachments[' . $attachment->ID . '][title]">' . __( 'Title', 'hm-attachments' ) . '</label>';
                    echo '<input type="text" name="hm-attachments[' . $attachment->ID . '][title]" value="' . esc_attr( $attachment->post_title ) . '" class="title">';
                } 

                // caption
                if( $this->config['fields']['caption'] ) {
                    echo '<label for="hm-attachments[' . $attachment->ID . '][caption]">' . __( 'Caption', 'hm-attachments' ) . '</label>';
                    echo '<input type="text" name="hm-attachments[' . $attachment->ID . '][caption]" value="' . esc_attr( $attachment->post_excerpt ) . '" class="caption">';
                } 

                // description
                if( $this->config['fields']['description'] ) {
                    echo '<label for="hm-attachments[' . $attachment->ID . '][description]">' . __( 'Description', 'hm-attachments' ) . '</label>';                    
                    echo '<textarea name="hm-attachments[' . $attachment->ID . '][description]" class="description">' . esc_html( $attachment->post_content ) . '</textarea>';
                } 

                // alt text
                if( $this->config['fields']['alt'] ) {
                    echo '<label for="hm-attachments[' . $attachment->ID . '][meta][_wp_attachment_image_alt]">' . __( 'Alternative text', 'hm-attachments' ) . '</label>';                    
                    $value = ( get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true ) ) ? get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true ) : '';
                    echo '<input type="text" name="hm-attachments[' . $attachment->ID . '][meta][_wp_attachment_image_alt]" value="' . esc_attr( $value ) . '" class="alt">';
                } 

                // delete
                echo '<input type="checkbox" name="hm-attachments[' . $attachment->ID . '][delete]" class="delete">';
                
                echo '</div>';

                $menu_order_max = ( $attachment->menu_order > $menu_order_max ) ? $attachment->menu_order : $menu_order_max;
            }

            // placeholder
            echo '<div class="hm-attachments-post hm-attachments-post-new hm-attachments-post-placeholder" data-id="{{id}}">';
            echo '<img src="{{src}}">';
            echo '<input type="hidden" class="menu_order" name="hm-attachments[{{id}}][menu_order]" value="' . ( $menu_order_max + 1 ) . '">';
            if( $this->config['fields']['title'] ) {
                echo '<label for="hm-attachments[{{id}}][title]">' . __( 'Title', 'hm-attachments' ) . '</label>';
                echo '<input type="text" class="title" name="hm-attachments[{{id}}][title]" value="">';
            } 
            if( $this->config['fields']['caption'] ) {
                echo '<label for="hm-attachments[{{id}}][caption]">' . __( 'Caption', 'hm-attachments' ) . '</label>';
                echo '<input type="text" class="caption" name="hm-attachments[{{id}}][caption]" value="">';
            } 
            if( $this->config['fields']['description'] ) {
                echo '<label for="hm-attachments[{{id}}][description]">' . __( 'Description', 'hm-attachments' ) . '</label>';
                echo '<textarea class="description" name="hm-attachments[{{id}}][description]"></textarea>';
            } 
            if( $this->config['fields']['alt'] ) {
                echo '<label for="hm-attachments[{{id}}][meta][_wp_attachment_image_alt]">' . __( 'Alternative text', 'hm-attachments' ) . '</label>';
                echo '<input type="text" class="alt" name="hm-attachments[{{id}}][meta][_wp_attachment_image_alt]" value="">';
            } 
            echo '<input type="checkbox" name="hm-attachments[{{id}}][delete]">';
            echo '</div>';
            // -----

            echo '</div>';
        }

        // print_r( $this->config );

        echo '<p><a href="#" class="hm-attachments-open-media button" title="' . esc_attr( __( 'Click Here to Open the Media Manager', 'hm_attachments' ) ) . '">' . __( 'Click Here to Open the Media Manager', 'hm_attachments' ) . '</a></p>';
    }


    public function save_meta( $post_id ) {

        if( wp_is_post_revision( $post_id ) ) {
            return;        
        }

        if( !current_user_can( 'edit_post' ) ) {
            return;
        }

        if( $_REQUEST['hm-attachments'] ) {
    
            foreach( $_REQUEST['hm-attachments'] as $id => $data ) {

                if( !$data['delete'] ) {
                    // save post

                    // post attributes
                    $post = array(
                        'ID' => $id,
                        'post_parent' => $post_id
                        );

                    // menu_order
                    if( intval( $data['menu_order'] ) ) {
                        $post['menu_order'] = intval( $data['menu_order'] );
                    }

                    // title
                    if( $data['title'] ) {
                        $post['post_title'] = $data['title'];
                    }

                    // caption
                    if( $data['caption'] ) {
                        $post['post_excerpt'] = $data['caption'];
                    }

                    // caption
                    if( $data['description'] ) {
                        $post['post_content'] = $data['description'];
                    }

                    // print_r( $post );

                    remove_action( 'save_post', array( $this, 'save_meta' ) );
                    wp_update_post( $post );
                    remove_action( 'save_post', array( $this, 'save_meta' ) );
                    
                    // meta attributes
                    
                    // print_r( $data['meta'] );
                    if( $data['meta'] ) {
                        foreach( $data['meta'] as $key => $value ) {
                            update_post_meta( $id, $key, $value );
                        }
                    }   

                } else {
                    // delete post
                    // TODO: decide whether to delete or detach the file from the post.
                    wp_delete_attachment( $id, false );

                }     

            }

        }

        // print_r( $_REQUEST['hm-attachments'] );

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

    public function modify_media_uploader_query( $query ) {
        $query['post_parent'] = $_POST['post_id'];
        
        return $query;
    }


}

// Instantiate the class.
$hm_attachments = new hmAttachments();