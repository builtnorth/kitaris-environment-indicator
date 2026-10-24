/**
 * Settings → Environment Indicator: core color pickers, and a badge preview
 * per environment that follows its label and color as they change.
 */
( function ( $ ) {
	$( function () {
		$( '#kitaris-ei-reset' ).on( 'click', function ( event ) {
			// eslint-disable-next-line no-alert
			if ( ! window.confirm( $( this ).data( 'confirm' ) ) ) {
				event.preventDefault();
			}
		} );

		$( '.kitaris-ei-row' ).each( function () {
			var $row = $( this );
			var preview = $row.find( '.kitaris-ei-preview' ).get( 0 );
			var $text = $row.find( '.kitaris-ei-preview__text' );
			var $label = $row.find( '.kitaris-ei-label' );
			var $color = $row.find( '.kitaris-ei-color' );
			var defaultColor = $color.data( 'default-color' );

			// Only a full hex color reaches the preview; anything else shows the default.
			function setDot( value ) {
				var valid = /^#([0-9a-f]{3}){1,2}$/i.test( value || '' );
				preview.style.setProperty( '--kitaris-ei-dot', valid ? value : defaultColor );
			}

			// The label as sanitize_text_field() saves it: no tags, no %XX
			// octets, whitespace collapsed.
			function cleanLabel( value ) {
				return String( value || '' )
					.replace( /<[^>]*>/g, '' )
					.replace( /%[a-f0-9]{2}/gi, '' )
					.replace( /\s+/g, ' ' )
					.trim();
			}

			$label.on( 'input', function () {
				$text.text( cleanLabel( $label.val() ) || $text.data( 'fallback' ) );
			} );

			$color.wpColorPicker( {
				defaultColor: defaultColor,
				change: function ( event, ui ) {
					setDot( ui.color.toString() );
				},
				clear: function () {
					setDot( '' );
				},
			} );

			// The "Default" button sets the value without a picker change.
			$color.on( 'change', function () {
				setDot( $color.val() );
			} );
		} );
	} );
} )( jQuery );
