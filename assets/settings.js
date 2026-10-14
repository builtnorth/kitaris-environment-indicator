/**
 * Settings → Environment Indicator: core colour pickers, and a badge preview
 * per environment that follows its label and colour as they change.
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

			// Only a full hex colour reaches the preview; anything else shows the default.
			function setDot( value ) {
				var valid = /^#([0-9a-f]{3}){1,2}$/i.test( value || '' );
				preview.style.setProperty( '--kitaris-ei-dot', valid ? value : defaultColor );
			}

			$label.on( 'input', function () {
				$text.text( $label.val().trim() || $text.data( 'fallback' ) );
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
