var hmAttachments = ( function() { 

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
            .on( 'click', '.hm-attachments-add-text', function( e ) {
                e.preventDefault();
                
                addText();
            } )
            .on( 'click', '.hm-attachments-post .delete-link', function( e ) {
                e.preventDefault();

                var post = jQuery( this ).closest( '.hm-attachments-post' );
                deletePost( post );
            } )
            .on( 'click', '.hm-attachments-post .edit-link', function( e ) {
                e.preventDefault();

                var post = jQuery( this ).closest( '.hm-attachments-post' );
                if( post.hasClass( 'show-info' ) ) {
                    hideInfo( post );
                } else {
                    hideAllInfo();
                    showInfo( post );
                }
            } );;

        settings.frame
            .on( 'select', function(){
                var attachments = settings.frame.state().get( 'selection' );

                console.log( attachments );

                attachments.map( function( attachment ) {
                    var attachment = attachment.toJSON();
                
                    console.log( attachment );
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

        var post = settings.element.placeholder.filter( '[data-type="image"]' ).first().clone();
        var order = parseInt( post.find( '.order' ).attr( 'value' ) );

        var temp_id = ( new Date().getTime() ).toString( 16 );

        console.log( post );

        // populate fields  

        // attachmentb ID
        post
            .find( '.id' )
            .attr( 'value', data.id );

        // temp id
        post
            .attr( 'data-id', post.attr( 'data-id' ).replace( '{{temp_id}}', temp_id ) );        

        // image src
        post.find( 'img' ).attr( 'src', data.sizes['hm-attachments-thumbnail'].url );

        // inputs
        post.find( 'input, textarea' ).each( function() {
            var input = jQuery( this );

            input
                .attr( 'name', input.attr( 'name' ).replace( '{{temp_id}}', temp_id ) );
        } );

        // labels
        var labels = post.find( 'label' );
        labels.each( function () {
            var label = jQuery( this );
            label
                .attr( 'for', label.attr( 'for' ).replace( '{{temp_id}}', temp_id ) );
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

    var addText = function( data ) {
        console.log( 'hmAttachments.addText()' );
        console.log( data );

        var post = settings.element.placeholder.filter( '[data-type="text"]' ).first().clone();
        var order = parseInt( post.find( '.order' ).attr( 'value' ) );

        var temp_id = ( new Date().getTime() ).toString( 16 );

        // populate fields  

        // attachmentb ID
        // post
        //     .find( '.id' )
        //     .attr( 'value', data.id );

        // temp id
        post
            .attr( 'data-id', post.attr( 'data-id' ).replace( '{{temp_id}}', temp_id ) );        

        // image src
        // post.find( 'img' ).attr( 'src', data.sizes['hm-attachments-thumbnail'].url );

        // inputs
        post.find( 'input, textarea' ).each( function() {
            var input = jQuery( this );

            input
                .attr( 'name', input.attr( 'name' ).replace( '{{temp_id}}', temp_id ) );
        } );

        // labels
        var labels = post.find( 'label' );
        labels.each( function () {
            var label = jQuery( this );
            label
                .attr( 'for', label.attr( 'for' ).replace( '{{temp_id}}', temp_id ) );
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

    var showInfo = function( post ) {
        var posts = post.closest( '.hm-attachments-posts' );
        post.addClass( 'show-info' );
        posts.addClass( 'show-info' );  

        var height = post.find( '.fields' ).outerHeight();
        console.log( height );
        post
            .css( {
                'paddingBottom': height + 'px'
            } )
            .find( '.hm-attachments-post-info' )
            .css( {
                'height': height + 'px'
            } );      
    }

    var hideInfo = function( post ) {

        var posts = post.closest( '.hm-attachments-posts' );
        post.removeClass( 'show-info' );
        posts.removeClass( 'show-info' );  

        post
            .css( {
                'paddingBottom': '0px'
            } )
            .find( '.hm-attachments-post-info' )
            .css( {
                'height': '0px'
            } );      
    }

    var hideAllInfo = function() {
        var posts = jQuery( '.hm-attachments-post.show-info' );

        posts.each( function() {
            hideInfo( jQuery( this ) );
        } );

    }


    return {
        init: function() { init(); }
    }

} )()


jQuery( document ).ready( function( $ ) {
    hmAttachments.init();
} );