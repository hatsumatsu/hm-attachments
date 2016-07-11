var hmAttachments = ( function() { 
	var settings = {
		selector: {
			posts:      '.hm-attachments-posts',
			post:       '.hm-attachments-post',
			add: 		'.hm-attachments-add',
			linkDelete: '.hm-attachments-post .delete-link',
			linkEdit:   '.hm-attachments-post .edit-link',
			linkSave:   '.hm-attachments-post-info-save',
			linkAdd:    '.hm-attachments-open-media'
		}
	};


	/**
	 * Initialization
	 */
	var init = function() {
		build();

		bindEventHandlers();

		makeSortable();
	}


	/**
	 * Bind event handlers
	 */
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

				attachments.map( function( attachment ) {
					var attachment = attachment.toJSON();
				
					addPost( attachment );
					jQuery( settings.selector.posts ).sortable( 'refresh' );
				} );
		} );
	}


	/**
	 * Build a new media modal instance
	 * See https://github.com/thomasgriffin/New-Media-Image-Uploader
	 */
	var build = function() {
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


	/**
	 * Make attachment posts sortable
	 */
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


	/**
	 * Set values of order <input>'s
	 */
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


	/**
	 * Add a new attachment post
	 * @param {array} data attachment data sent from mdeia modal
	 */
	var addPost = function( data ) {
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

		// add to DOM
		jQuery( rendered )
			.insertBefore( jQuery( settings.selector.add ).first() );
	}


	/**
	 * Remove attachment post
	 * @param {array} data attachment data sent from mdeia modal
	 */
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