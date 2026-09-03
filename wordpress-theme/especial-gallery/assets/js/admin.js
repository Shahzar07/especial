/**
 * Admin behaviour: the product gallery picker and the category image picker.
 *
 * Both drive wp.media, so the shop owner gets the media library they already
 * know rather than a bespoke uploader that behaves differently.
 */
( function ( $ ) {
	'use strict';

	var strings = window.egAdmin || {};

	/**
	 * Repaints the gallery preview from the hidden field, which is the single
	 * source of truth — the preview is only ever a rendering of it.
	 */
	function renderGallery( ids ) {
		var preview = $( '#eg_gallery_preview' );
		preview.empty();

		ids.forEach( function ( id ) {
			wp.media.attachment( id ).fetch().then( function () {
				var attachment = wp.media.attachment( id ).toJSON();
				var url = attachment.sizes && attachment.sizes.thumbnail
					? attachment.sizes.thumbnail.url
					: attachment.url;

				preview.append(
					$( '<span/>', { 'class': 'eg-gallery-preview__item', 'data-id': id } )
						.append( $( '<img/>', { src: url, alt: '' } ) )
				);
			} );
		} );
	}

	$( function () {
		var field = $( '#eg_gallery' );

		if ( field.length ) {
			var frame = null;

			$( '#eg_gallery_choose' ).on( 'click', function ( event ) {
				event.preventDefault();

				// Rebuilt each time so the current selection is preselected
				// rather than the one from whenever the frame was first opened.
				frame = wp.media( {
					title: strings.chooseImages,
					button: { text: strings.useImages },
					library: { type: 'image' },
					multiple: true
				} );

				frame.on( 'select', function () {
					var ids = frame.state().get( 'selection' ).map( function ( attachment ) {
						return attachment.id;
					} );

					field.val( ids.join( ',' ) );
					renderGallery( ids );
				} );

				frame.open();
			} );

			$( '#eg_gallery_clear' ).on( 'click', function ( event ) {
				event.preventDefault();
				field.val( '' );
				$( '#eg_gallery_preview' ).empty();
			} );
		}

		var termField = $( '#eg_term_image' );

		if ( termField.length ) {
			$( '#eg_term_image_choose' ).on( 'click', function ( event ) {
				event.preventDefault();

				var termFrame = wp.media( {
					title: strings.chooseImage,
					button: { text: strings.useImage },
					library: { type: 'image' },
					multiple: false
				} );

				termFrame.on( 'select', function () {
					var attachment = termFrame.state().get( 'selection' ).first().toJSON();
					var url = attachment.sizes && attachment.sizes.thumbnail
						? attachment.sizes.thumbnail.url
						: attachment.url;

					termField.val( attachment.id );
					$( '#eg_term_image_preview' ).html( $( '<img/>', { src: url, alt: '' } ) );
				} );

				termFrame.open();
			} );
		}
	} );
}( jQuery ) );
