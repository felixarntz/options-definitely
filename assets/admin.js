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
			'max-width': '500px'
		},
		closeOnSelect: false,
		formatResult: formatSelect2,
		formatSelection: formatSelect2,
		escapeMarkup: function(m) { return m; },
		minimumResultsForSearch: 8
	};

	$( 'select' ).select2( select2_args );

	// datetimepicker setup
	var dtp_datetimepicker_args = {
		lang: _wpod_admin.language,
		formatDate: 'Y-m-d',
		formatTime: 'H:i',
		dayOfWeekStart: _wpod_admin.start_of_week
	};

	var dtp_datetime_args = $.extend({
		format: _wpod_admin.date_format + ' ' + _wpod_admin.time_format,
		onShow: function( ct, $input ) {
			var helper = '';
			if ( $input.attr( 'min' ) ) {
				helper = $input.attr( 'min' ).split( ' ' );
				if ( helper.length === 2 ) {
					this.setOptions({
						minDate: helper[0],
						minTime: helper[1]
					});
				} else if( helper.length === 1 ) {
					this.setOptions({
						minDate: helper[0]
					});
				}
			}

			if ( $input.attr( 'max' ) ) {
				helper = $input.attr( 'max' ).split( ' ' );
				if ( helper.length === 2 ) {
					this.setOptions({
						maxDate: helper[0],
						maxTime: helper[1]
					});
				} else if( helper.length === 1 ) {
					this.setOptions({
						maxDate: helper[0]
					});
				}
			}

			if ( $input.attr( 'step' ) ) {
				this.setOptions({
					step: parseInt( $input.attr( 'step' ) )
				});
			}
		}
	}, dtp_datetimepicker_args );

	var dtp_date_args = $.extend({
		format: _wpod_admin.date_format,
		timepicker: false,
		onShow: function( ct, $input ) {
			if ( $input.attr( 'min' ) ) {
				this.setOptions({
					minDate: $input.attr('min')
				});
			}

			if ( $input.attr( 'max' ) ) {
				this.setOptions({
					maxDate: $input.attr('max')
				});
			}
		}
	}, dtp_datetimepicker_args );

	var dtp_time_args = $.extend({
		format: _wpod_admin.time_format,
		datepicker: false,
		onShow: function( ct, $input ) {
			if ( $input.attr( 'min' ) ) {
				this.setOptions({
					minTime: $input.attr('min')
				});
			}

			if ( $input.attr( 'max' ) ) {
				this.setOptions({
					maxTime: $input.attr('max')
				});
			}

			if ( $input.attr( 'step' ) ) {
				this.setOptions({
					step: parseInt( $input.attr( 'step' ) )
				});
			}
		}
	}, dtp_datetimepicker_args );

	$( 'input.dtp-datetime' ).datetimepicker( dtp_datetime_args );
	$( 'input.dtp-date' ).datetimepicker( dtp_date_args );
	$( 'input.dtp-time' ).datetimepicker( dtp_time_args );

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
	if ( typeof wp.media !== 'undefined' ) {
		var _custom_media = true;

		var _orig_send_attachment = wp.media.editor.send.attachment;

		$( '.form-table' ).on( 'click', '.media-button', function() {
			var $button = $( this );

			var search_id = $button.attr( 'id' ).replace( '-media-button', '' );

			_custom_media = true;

			wp.media.editor.send.attachment = function( props,attachment ) {
				if ( _custom_media ) {
					$( '#' + search_id ).val( attachment.id );
					var name = attachment.url.split( '/' );
					name = name[ name.length - 1 ];
					$( '#' + search_id + '-media-title' ).val( name );
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
				var limit = parseInt( $parent.data( 'limit' ));
				var id_prefix = $parent.data( 'parent-slug' ) + '-' + $parent.data( 'slug' );
				var key = $parent.find( '.repeatable-row' ).length;

				if ( typeof _wpod_admin.repeatable_field_templates[ id_prefix ] !== 'undefined' ) {
					var output = _wpod_admin.repeatable_field_templates[ id_prefix ].replace( /{{KEY}}/g, key ).replace( /{{KEY_PLUSONE}}/g, key + 1 );

					$parent.append( output ).find( 'select' ).select2( select2_args );
					$parent.find( 'input.dtp-datetime' ).datetimepicker( dtp_datetime_args );
					$parent.find( 'input.dtp-date' ).datetimepicker( dtp_date_args );
					$parent.find( 'input.dtp-time' ).datetimepicker( dtp_time_args );

					if ( limit > 0 && limit === key + 1 ) {
						$parent.find( '.new-repeatable-button' ).hide();
					}
				}

				e.preventDefault();
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
