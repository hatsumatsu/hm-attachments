var hmAttachments = ( function() { 
    var settings = {
        selector: {
            posts:      '.hm-attachments-posts',
            post:       '.hm-attachments-post',
            linkDelete: '.hm-attachments-post .delete-link',
            linkEdit:   '.hm-attachments-post .edit-link',
            linkSave:   '.hm-attachments-post-info-save',
            linkAdd:    '.hm-attachments-open-media'
        }
    };

    var init = function() {
        build();

        bindEventHandlers();

        makeSortable();
    }

    var bindEventHandlers = function() {
        jQuery( document )
            .on( 'click', settings.selector.linkAdd, function( event ) {
                event.preventDefault();

                if( !settings.frame ) {
                    build();
                } 
                
                settings.frame.open();

            } )
            .on( 'click', settings.selector.linkDelete, function( event ) {
                event.preventDefault();

                var post = jQuery( this ).closest( settings.selector.post );
                deletePost( post );
            } )
            .on( 'click', settings.selector.linkEdit, function( event ) {
                event.preventDefault();

                var post = jQuery( this ).closest( settings.selector.post );

                jQuery( 'html' )
                    .addClass( 'hm-attachments-show-info' );

                post
                    .addClass( 'show-info' );
            } )
            .on( 'click', settings.selector.linkSave, function( event ) {
                event.preventDefault();

                var post = jQuery( this ).closest( settings.selector.post );

                post
                    .removeClass( 'show-info' );

                jQuery( 'html' )
                    .removeClass( 'hm-attachments-show-info' );
            } );

        settings.frame
            .on( 'select', function(){
                var attachments = settings.frame.state().get( 'selection' );

                console.log( attachments );

                attachments.map( function( attachment ) {
                    var attachment = attachment.toJSON();
                
                    addPost( attachment );
                    jQuery( settings.selector.posts ).sortable( 'refresh' );
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
        console.log( hmAttachmentsLocalization );

        settings.frame = wp.media.frames.hmAttachmentsLocalization = wp.media( {
            className:  'media-frame hm-attachments-media-frame',
            frame:      'select',
            multiple:   true,
            content:    'upload',
            sidebar:    false,
            title:      hmAttachmentsLocalization.mediaModal.title,
            library: {
                type: 'image'
            },
            states: [
                new wp.media.controller.Library( {
                    library:   wp.media.query( { type: 'image' } ),
                    multiple:  true,
                    priority:  20,
                    filterable: 'all'
                } )
            ],                
            button: {
                text: hmAttachmentsLocalization.mediaModal.button
            }
        } );        
    }

    var makeSortable = function() {
        jQuery( settings.selector.posts ).sortable( {
            items:          settings.selector.post + '.sortable',
            containment:    settings.selector.posts,
            handle:         '.hm-attachments-preview',
            distance:       2,
            opacity:        .5,
            tolerance:      'pointer',
            update:         function() {
                SetOrder();
            }
        } );
    }

    var SetOrder = function() {
        var i = 0;

        jQuery( settings.selector.posts )
            .find( settings.selector.post )
            .each( function() {
                jQuery( this )
                    .find( '.order' )
                    .attr( 'value', i );

                i++;
            } );
    }

    var addPost = function( data ) {
        console.log( data );

        var order = jQuery( settings.selector.post ).length;

        var temp_id = ( new Date().getTime() ).toString( 16 );

        // filename
        if( data.sizes['full'].url ) {
            var filename = data.filename; 
            if( filename.length > 19 ) {
                filename = filename.substring( 0, 8 ) + '...' + filename.substring( ( filename.length - 9 ), ( filename.length - 1 ) );
            }
        }        

        // prepare data for Mustache rendering
        var _data = {
            attachment: {
                id:         data.id,
                temp_id:    temp_id,
                src:        ( data.sizes['hm-attachments-thumbnail'] ) ? data.sizes['hm-attachments-thumbnail'].url : '',
                filename:   filename,
                width:      data.width,
                height:     data.height,
                order:      order
            },
            labels: {
                actions: {
                    edit: hmAttachmentsLocalization.actions.edit,
                    delete: hmAttachmentsLocalization.actions.delete
                },
                modal: {
                    title: hmAttachmentsLocalization.infoModal.title,
                    fields: {
                        title: hmAttachmentsLocalization.infoModal.fields.title
                    },
                    button: {
                        save: hmAttachmentsLocalization.infoModal.button.save
                    }
                }                 
            }
        }

        // render Mustache template
        var template = jQuery( '#mustache-template--attachment' ).html();
        Mustache.parse( template );
        var rendered = Mustache.render( template, _data );

        console.log( rendered );

        // add to DOM
        jQuery( rendered )
            .insertAfter( jQuery( settings.selector.post ).last() );
    }

    var deletePost = function( post ) {
        post.remove();
    }

    return {
        init: function() { init(); }
    }
} )();


jQuery( document ).ready( function( $ ) {
    hmAttachments.init();
} );