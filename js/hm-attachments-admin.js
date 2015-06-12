var HMattachments = ( function() { 

    var settings = {
        element: {}
    };

    var init = function() {

        settings.element.posts = jQuery( '.hm-attachments-posts' );
        settings.element.placeholder = settings.element.posts.find( '.hm-attachments-post-placeholder' );

        build();
        bindEventHandlers();
        makeSortable();
    }

    var bindEventHandlers = function() {
        
        jQuery( document )
            .on( 'click', '.hm-attachments-open-media', function( e ) {
                e.preventDefault();

                if( !settings.frame ) {
                    build();
                } 
                
                settings.frame.open();

            } )
            .on( 'click', '.hm-attachments-post .delete-link', function( e ) {
                e.preventDefault();

                var post = jQuery( this ).closest( '.hm-attachments-post' );
                deletePost( post );
            } )
            .on( 'click', '.hm-attachments-post .edit-link', function( e ) {
                e.preventDefault();

                var post = jQuery( this ).closest( '.hm-attachments-post' );

                jQuery( 'body' )
                    .addClass( 'hm-attachments-show-info' );

                post
                    .addClass( 'show-info' );
            } )
            .on( 'click', '.hm-attachments-post-info-save', function( e ) {
                e.preventDefault();

                var post = jQuery( this ).closest( '.hm-attachments-post' );

                post
                    .removeClass( 'show-info' );

                jQuery( 'body' )
                    .removeClass( 'hm-attachments-show-info' );
            } );

        settings.frame
            .on( 'select', function(){
                var attachments = settings.frame.state().get( 'selection' );

                console.log( attachments );

                attachments.map( function( attachment ) {
                    var attachment = attachment.toJSON();
                
                    // console.log( attachment );
                    addPost( attachment );
                    settings.element.posts.sortable( 'refresh' );
                } );

        } );
    }

    var build = function() {
        /**
         * The media frame doesn't exist let, so let's create it with some options.
         *
         * This options list is not exhaustive, so I encourage you to view the
         * wp-includes/js/media-views.js file to see some of the other default
         * options that can be utilized when creating your own custom media workflow.
         */
        settings.frame = wp.media.frames.hm_attachments = wp.media( {
            className: 'media-frame hm-attachments-media-frame',
            frame: 'select',
            multiple: true,
            content: 'upload',
            filterable: true,
            sidebar: false,
            title: hm_attachments.title,
            library: {
                type: 'image'
            },
            button: {
                text:  hm_attachments.button
            }
        } );        
    }

    var makeSortable = function() {

        settings.element.posts.sortable( {
            items: '.hm-attachments-post.sortable',
            containment: '.hm-attachments-posts',
            handle: '.hm-attachments-preview',
            // axis: 'y',
            // cancel: '.inline-edit-row',
            distance: 2,
            opacity: .5,
            tolerance: 'pointer',
            update: function() {
                SetOrder();
            }
        } );
    }

    var SetOrder = function() {
        var i = 0;

        settings.element.posts
            .find( '.hm-attachments-post' )
            .each( function() {
                jQuery( this )
                    .find( '.order' )
                    .attr( 'value', i );
 
                i++;
            } );
    }

    var addPost = function( data ) {
        console.log( 'hmAttachments.addPost()' );
        console.log( data );

        var post = settings.element.placeholder.clone();
        var order = parseInt( post.find( '.order' ).attr( 'value' ) );

        var temp_id = ( new Date().getTime() ).toString( 16 );

        console.log( post );

        // populate fields  

        // attachment ID
        post
            .find( '.id' )
            .attr( 'value', data.id );

        // temp id
        post
            .attr( 'data-id', post.attr( 'data-id' ).replace( '{{temp_id}}', temp_id ) );        

        // image src
        if( data.sizes['hm-attachments-thumbnail'] ) {
            post.find( 'img' ).attr( 'src', data.sizes['hm-attachments-thumbnail'].url );
        }

        // image dimensions
        if( data.width && data.height ) {
            var meta = post.find( '.meta--dimensions' );
            meta.text( meta.text().replace( '{{width}}', data.width ).replace( '{{height}}', data.height ) );
        }

        // inputs
        post.find( 'input' ).each( function() {
            var input = jQuery( this );

            input
                .attr( 'name', input.attr( 'name' ).replace( '{{temp_id}}', temp_id ) );
        } );

        // add to DOM
        post
            .removeClass( 'hm-attachments-post-placeholder' )
            .insertBefore( settings.element.placeholder.first() );
    
        // increase image index 
        settings.element.placeholder
            .find( '.order' )
            .attr( 'value', ( order + 1 ) );
    }

    var deletePost = function( post ) {
        post.remove();
    }

    return {
        init: function() { init(); }
    }

} )()


jQuery( document ).ready( function( $ ) {
    HMattachments.init();
} );