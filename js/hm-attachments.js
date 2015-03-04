var hmAttachements = ( function() { 

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

            } );

        settings.frame
            .on( 'select', function(){
                var attachments = settings.frame.state().get( 'selection' );

                console.log( attachments );

                attachments.map( function( attachment ) {
                    var attachment = attachment.toJSON();
                
                    console.log( attachment );
                    addPost( attachment );
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
            filterable: false,
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
            items: '> .hm-attachments-post',
            handle: '.attachment-hm-attachments-thumbnail',
            axis: 'y',
            // cancel: '.inline-edit-row',
            distance: 2,
            opacity: .5,
            tolerance: 'pointer',
            update: function() {
                SetMenuOrder();
            }
        } );
    }

    var SetMenuOrder = function() {
        var i = 1;

        settings.element.posts
            .find( '.hm-attachments-post' )
            .each( function() {
                jQuery( this )
                    .find( '.menu_order' )
                    .attr( 'value', i );
 
                i++;
            } );
    }

    var addPost = function( data ) {
        var post = settings.element.placeholder.clone();
        var menu_order = parseInt( post.find( '.menu_order' ).attr( 'value' ) );

        // populate fields  
        // image src
        post.find( 'img' ).attr( 'src', data.sizes['hm-attachments-thumbnail'].url );

        var fields = [
            'menu_order',
            'title',
            'caption',
            'description',
            'alt'
        ];

        // inputs
        for( var i = 0; i < fields.length; i++ ) {
            var input = post.find( '.' + fields[i] );
            console.log( input );
            input
                .attr( 'name', input.attr( 'name' ).replace( '{{id}}', data.id ) );

            if( data[ fields[i] ] ) {
                input
                    .attr( 'value', data[ fields[i] ] )
            }
        }

        // labels
        var labels = post.find( 'label' );
        labels.each( function () {
            var label = jQuery( this );
            label
                .attr( 'for', label.attr( 'for' ).replace( '{{id}}', data.id ) );
        } );


        // add to DOM
        post
            .removeClass( 'hm-attachments-post-placeholder' )
            .insertBefore( settings.element.placeholder );
    
        // increase image index 
        settings.element.placeholder
            .find( '.menu_order' )
            .attr( 'value', ( menu_order + 1 ) );
    }

    return {
        init: function() { init(); }
    }

} )()


jQuery( document ).ready( function( $ ) {
    hmAttachements.init();
} );