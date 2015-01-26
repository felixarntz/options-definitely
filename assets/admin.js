jQuery(document).ready(function($) {

	// select2 setup
	function formatSelect2( option ) {
		var $option = $( option.element );

		if ( $option.data().hasOwnProperty( 'image' ) ) {
			return '<div class="option-box" style="background-image:url(' + $option.data( 'image' ) + ');"></div>' + option.text;
		} else if ( $option.data().hasOwnProperty( 'color' ) ) {
			return '<div class="option-box" style="background-color:#' + $option.data( 'color' ) + ';"></div>' + option.text;
		} else {
			return option.text;
		}
	}

	var select2_args = {
		containerCss : {
			'width': '100%',
			'max-width': '300px'
		},
		closeOnSelect: false,
		formatResult: formatSelect2,
		formatSelection: formatSelect2,
		escapeMarkup: function(m) { return m; },
		minimumResultsForSearch: 8
	};

	$( 'select' ).select2( select2_args );

	// viewer handling for fields without visible output
	$( '.form-table' ).on( 'change', 'input[type="range"], input[type="color"]', function() {
		$( '#' + $(this).attr( 'id' ) + '-' + $( this ).attr( 'type' ) + '-viewer' ).val( $( this ).val() );
	});

	$( '.form-table' ).on( 'change', 'input[class="range-viewer"], input[class="color-viewer"]', function() {
		$( this ).next( 'input' ).val( $( this ).val() );
	});

	// radio handling
	$( '.form-table' ).on( 'click', '.radio-group .radio div', function() {
		var input_id = $( this ).attr( 'id' ).replace( '-asset', '' );

		$( this ).parent().parent().find( '.radio div' ).removeClass( 'checked' );

		$( this ).addClass( 'checked' );

		$( '#' + input_id ).prop( 'checked', true );
	});

	$( '.form-table' ).on( 'change', '.radio-group .radio input', function() {
		$( this ).parent().parent().find( '.radio div' ).removeClass( 'checked' );
	});

	// multibox handling
	$( '.form-table' ).on( 'click', '.checkbox-group .checkbox div', function() {
		var input_id = $( this ).attr( 'id' ).replace( '-asset', '' );

		if ( $( this ).hasClass( 'checked' ) ) {
			$( this ).removeClass( 'checked' );

			$( '#' + input_id ).prop( 'checked', false );
		} else {
			$( this ).addClass( 'checked' );

			$( '#' + input_id ).prop( 'checked', true );
		}
	});

	// media uploader
	if ( wp.media !== null ) {
		var _custom_media = true;

		var _orig_send_attachment = wp.media.editor.send.attachment;

		$( '.form-table' ).on( 'click', '.media-button', function() {
			var $button = $( this );

			var search_id = $button.attr( 'id' ).replace( '-media-button', '' );

			_custom_media = true;

			wp.media.editor.send.attachment = function( props,attachment ) {
				if ( _custom_media ) {
					$( '#' + search_id ).val( attachment.id );
					$( '#' + search_id + '-media-title' ).val( attachment.title );
					if ( attachment.type === 'image' ) {
						if ( $( '#' + search_id + '-media-image' ).length > 0 ) {
							$( '#' + search_id + '-media-image' ).attr( 'src', attachment.url );
						} else {
							$( '#' + search_id + '-media-button' ).after( '<img id="' + search_id + '-media-image" class="media-image" src="' + attachment.url + '" />' );
						}
					} else {
						if ( $( '#' + search_id + '-media-link' ).length > 0 ) {
							$( '#' + search_id + '-media-link' ).attr( 'href', attachment.url );
						} else {
							$( '#' + search_id + '-media-button' ).after( '<a id="' + search_id + '-media-link" class="media-link" href="' + attachment.url + '" target="_blank">' + _wpod_admin.localized_open_file + '</a>' );
						}
					}
				} else {
					return _orig_send_attachment.apply( this, [ props, attachment ] );
				}
			};

			wp.media.editor.open( $button );

			return false;
		});

		$( '.add_media' ).on( 'click', function() {
			_custom_media = false;
		});
	}

	// repeatable fields
	if ( $( '.repeatable' ).length > 0 && _wpod_admin !== undefined ) {
		$( '.repeatable' ).each(function() {
			$( this ).on( 'click', '.new-repeatable-button', function( e ) {
				var $parent = $( '#' + e.delegateTarget.id );

				var data = {
					action: _wpod_admin.action_add_repeatable,
					nonce: _wpod_admin.nonce,
					slug: $parent.data( 'slug' ),
					parent_slug: $parent.data( 'parent-slug' ),
					key: $parent.find( '.repeatable-row' ).length
				};

				e.preventDefault();

				$.post( ajaxurl, data, function( response ) {
					if ( response !== '' && $parent.find( '.repeatable-row' ).length === data.key ) {
						var limit = parseInt( $( '#' + e.delegateTarget.id ).data( 'limit' ));

						$parent.append( response ).find( 'select' ).select2( select2_args );

						if ( limit > 0 && limit === $( '#' + e.delegateTarget.id ).find( '.repeatable-row' ).length ) {
							$( '#' + e.delegateTarget.id ).find( '.new-repeatable-button' ).hide();
						}
					}
				});
			});
			$( this ).on( 'click', '.remove-repeatable-button', function( e ) {
				var $parent = $( '#' + e.delegateTarget.id );

				var $rows = $parent.find( '.repeatable-row' );

				var number = parseInt( $( this ).data( 'number' ) ) + 1;

				e.preventDefault();

				$rows.filter( ':nth-child(' + ( number + 1 ) + ')' ).remove();

				$rows.filter( ':gt(' + ( number - 1 ) + ')' ).each(function() {
					var $row = $( this );

					var number = parseInt( $row.find( '.remove-repeatable-button' ).data( 'number' ) );

					var target = number - 1;

					$row.find( 'span:first' ).html( $row.find( 'span:first' ).html().replace( ( number + 1 ).toString(), ( target + 1 ).toString() ) );

					$row.find( '.repeatable-col input, .repeatable-col select, .repeatable-col img, .repeatable-col a' ).each(function() {
						if ( $( this ).attr( 'id' ) ) {
							$( this ).attr( 'id', $( this ).attr( 'id' ).replace( number.toString(), target.toString() ) );
						}

						if ( $( this ).attr( 'name' ) ) {
							$( this ).attr( 'name', $( this ).attr( 'name' ).replace( number.toString(), target.toString() ) );
						}
					});

					$row.find('.remove-repeatable-button').data('number', target.toString());
				});

				var limit = parseInt( $( '#' + e.delegateTarget.id ).data( 'limit' ) );

				if ( limit > 0 && limit > $( '#' + e.delegateTarget.id ).find( '.repeatable-row' ).length) {
					$( '#' + e.delegateTarget.id ).find( '.new-repeatable-button' ).show();
				}
			});
		});
	}

});
