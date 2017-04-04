<?php

/*
Plugin Name: HM Attachments
Version: 0.21
Description: Simple post image management.
Plugin URI:
Author: Martin Wecke
Author URI: http://martinwecke.de/
GitHub Plugin URI: https://github.com/hatsumatsu/hm-attachments
GitHub Branch: master
*/


/**
 * Compatibility layer for pre-OOP versions
 */
require_once( 'inc/compatibility.php' );


/**
 * HMAttachments
 */
class HMAttachments {
    protected $settings;

    public function __construct() {
        // i11n        
        add_action( 'init', array( $this, 'loadI88n' ) );

        // load settings
        add_action( 'after_setup_theme', array( $this, 'loadSettings' ) );

        // add admin JS
        add_action( 'admin_enqueue_scripts', array( $this, 'adminJS' ) ); 

        // add admin CSS
        add_action( 'admin_enqueue_scripts', array( $this, 'adminCSS' ) );       

        // add image sizes
        add_action( 'after_setup_theme', array( $this, 'addImageSizes' ) );

        // register Mustache        
        add_action( 'init', array( $this, 'registerMustache' ) );

        // add Mustache templates     
        add_action( 'admin_footer', array( $this, 'addMustacheTemplates' ) );

        // add meta box
        add_action( 'add_meta_boxes', array( $this, 'addMetabox' ) );    

        // save_post hook
        add_action( 'save_post', array( $this, 'saveAttachments' ) ); 

        // include custom image size in JSON sent from media modal
        add_filter( 'wp_prepare_attachment_for_js',  array( $this, 'includeImageSizesInJSON' ), 10, 3 );
    }


    /**
     * i11n
     */
    public function loadI88n() {
        load_plugin_textdomain( 'hm-attachments', '/wp-content/plugins/hm-attachments/languages/' );        
    }


    /**
     * Load settings from filter 'hm-attachments/settings'
     */
    public function loadSettings() {
        // default settings
        $this->settings = array(
            'post_type' => array(
                'post',
                'projects'
            )
        );

        // apply custom settings
        $this->settings = apply_filters( 'hm-attachments/settings', $this->settings );
    }


    /**
     * Add custom image size to preview images in admin
     */
    public function addImageSizes() {
        add_image_size( 'hm-attachments-thumbnail', 200, 200, true );        
    }


    /**
     * Register admin JS
     */
    public function adminJS() {
        // Load required media files for the media manager
        wp_enqueue_media();

        // Register
        wp_register_script( 'hm-attachments-mustache', plugins_url( '/js/mustache.min.js', __FILE__ ), array( 'jquery', 'jquery-ui-sortable'  ), 0, true );
        wp_register_script( 'hm-attachments-admin', plugins_url( '/js/hm-attachments-admin.js', __FILE__ ), array( 'jquery', 'jquery-ui-sortable', 'hm-attachments-mustache'  ), 0, true );
        
        // Localize
        wp_localize_script( 'hm-attachments-admin', 'hmAttachmentsLocalization',
            array(
                'mediaModal' => array(
                    'title'     => __( 'Upload or choose images', 'hm-attachments' ), 
                    'button'    => __( 'Add images', 'hm-attachments' )
                ),
                'infoModal'  => array(
                    'title'     => __( 'Image details', 'hm-attachments' ),
                    'fields'    => array(
                        'title'     => __( 'Title', 'hm-attachments' )
                    ),
                    'button'    => array(
                        'save'      => __( 'Save' )
                    )
                ),
                'actions'   => array(
                    'edit'      => __( 'Edit', 'hm-attachments' ),
                    'delete'    => __( 'Delete', 'hm-attachments' )
                ),                
            )
        );

        // Enqueue
        wp_enqueue_script( 'hm-attachments-mustache' );
        wp_enqueue_script( 'hm-attachments-admin' );
    }


    /**
     * Register admin CSS
     */
    public function adminCSS() {
        wp_register_style( 'hm-attachments-admin', plugins_url( '/css/hm-attachments-admin.css', __FILE__ ), 0, 'screen' );
        wp_enqueue_style( 'hm-attachments-admin' );
    }


    /**
     * Register Mustache
     */
    public function registerMustache() {
        require plugin_dir_path( __FILE__ ) . 'lib/Mustache/Autoloader.php';
        Mustache_Autoloader::register();        
    }


    /**
     * Add the required Mustache templates to the footer
     * so they can be used by JS
     */
    public function addMustacheTemplates() {
?>
<script id="mustache-template--attachment" type="x-tmpl-mustache">
    <?php echo file_get_contents( plugin_dir_path( __FILE__ ) . 'templates/attachment.mustache' ); ?>
</script>
<?php
    }


    /**
     * Add meta boxes
     */
    public function addMetabox() {
        add_meta_box( 'hm-attachments', __( 'Post images', 'hm-attachments' ), array( $this, 'renderMetabox' ), $this->settings['post_type'], 'normal', 'high' );
    }


    /**
     * Render meta box
     * @param  WP_Post $post post object
     */
    public function renderMetabox( $post ) {
        $attachments = $this->getAttachments( $post->ID );

        wp_nonce_field( basename( __FILE__ ), 'hm_attachments_nonce' );
        echo '<div class="hm-attachments-posts">';

        $i = 0;
        if( $attachments ) {
            foreach( $attachments as $attachment ) {
                $original = wp_get_attachment_image_src( $attachment['id'], 'full' );
                $thumbnail = wp_get_attachment_image_src( $attachment['id'], 'hm-attachments-thumbnail' );
                $small = wp_get_attachment_image_src( $attachment['id'], 'thumbnail' );

                // shorten filename if neccessary
                $filename = basename( $original[0] );
                if( mb_strlen( $filename ) > 19 ) {
                    $filename = mb_substr( $filename, 0, 8 ) . '...' . mb_substr( $filename, ( mb_strlen( $filename ) - 8 ), mb_strlen( $filename ) );
                }

                $attachment['order'] = $i;
                $attachment['filename'] = $filename;
                $attachment['width'] = $original[1];
                $attachment['height'] = $original[2];
                $attachment['src'] = $thumbnail[0];
                $attachment['srcSmall'] = $small[0];

                $data = array(
                    'attachment'    => $attachment,
                    'labels'        => array(
                        'actions'   => array(
                            'edit'      => __( 'Edit', 'hm-attachments' ),
                            'delete'    => __( 'Delete', 'hm-attachments' )
                        ),
                        'modal'     => array(
                            'title' => __( 'Image details', 'hm-attachments' ),
                            'fields' => array(
                                'title' => __( 'Title', 'hm-attachments' )
                            ),
                            'button' => array(
                                'save' => __( 'Save' )
                            )
                        )
                    )
                );

                // render attachment template
                $renderer = new Mustache_Engine;
                $template = file_get_contents( plugin_dir_path( __FILE__ ) . 'templates/attachment.mustache' );

                echo $renderer->render( $template, $data );               

                $i++;
            }
        }

        $attachment = null;

        // ADD NEW ITEM
        echo '<div class="hm-attachments-add">';
        echo '<a href="#" class="hm-attachments-open-media button" title="' . esc_attr( __( 'Add images', 'hm-attachments' ) ) . '">' . __( 'Add images', 'hm-attachments' ) . '</a>';
        echo '</div>';

        echo '</div>';    
    }


    /**
     * Save attachment data when post is saved
     * @param int $post_id post ID
     */
    public function saveAttachments( $post_id ) {
        if( wp_is_post_revision( $post_id ) ) {
            return;        
        }

        if( !current_user_can( 'edit_post' ) ) {
            return;
        }

        if( $_REQUEST['post_ID'] != $post_id ) {
            return;
        }

        if( !wp_verify_nonce( $_REQUEST['hm_attachments_nonce'], basename( __FILE__ ) ) ) {        
            return;
        }

        // delete all data
        delete_post_meta( $post_id, 'hm-attachment' );

        if( $_REQUEST['hm-attachment'] ) {

            foreach( $_REQUEST['hm-attachment'] as $temp_id => $data ) {

                if( $temp_id && $temp_id !== '{{temp_id}}' ) {

                    $id = ( $data['id'] ) ? $data['id'] : $temp_id;


                    $data = array( 
                        'id'        => $id,
                        'type'      => $data['type'],
                        'order'     => $data['order'],
                        'fields'    => $data['fields'],
                        'title'     => $data['title']
                    );

                    add_post_meta( $post_id, 'hm-attachment', json_encode( $data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE ) );
                }
            }
        }
    }


    /**
     * Add custom data to the JSON object 
     * sent from the WP media modal
     * @param  array $response     modal response
     * @param  WP_Post $attachment attachment object
     * @param  array $meta         attachment meta
     * @return array               modified modal response
     */
    public function includeImageSizesInJSON( $response, $attachment, $meta ){
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


    /**
     * Get attachment data by post ID
     * @param int $post_id post ID
     * @return array attachment data
     */
    public static function getAttachments( $post_id, $orderby = 'order' ) {
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
}

new HMAttachments();