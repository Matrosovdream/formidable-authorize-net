function frmTransAdminJS() {

	function toggleSub() {
		var val  = this.value;
		var show = val === 'recurring';
		slideOpts( this, show, '.frm_trans_sub_opts' );
		toggleOpts( this, ! show, '.frm_gateway_no_recur' );
	}

	function slideOpts( opt, show, c ) {
		var opts = jQuery( opt ).closest( '.frm_form_action_settings' ).find( c );
		if ( show ) {
			opts.slideDown( 'fast' );
		} else {
			opts.slideUp( 'fast' );
		}
	}

	function toggleOpts( opt, show, c ) {
		var opts = jQuery( opt ).closest( '.frm_form_action_settings' ).find( c );
		if ( show ) {
			opts.show();
		} else {
			opts.hide();
		}
	}

	function toggleGateway() {
		if ( ! this.checked && 'checkbox' !== this.type ) {
			return;
		}

		const gateway  = this.value;
		const settings = jQuery( this ).closest( '.frm_form_action_settings' );

		toggleOpts( this, this.checked, '.show_' + gateway );

		settings.get( 0 ).querySelectorAll( '.frm_gateway_opt input' ).forEach(
			function( input ) {
				if ( input.checked ) {
					return;
				}

				const gatewaySettings = settings.get( 0 ).querySelectorAll( '.show_' + input.value );
				gatewaySettings.forEach(
					function( setting ) {
						if ( ! hasClass( setting.className, 'show_' + gateway ) ) {
							setting.style.display = 'none';
						}
					}
				);

				if ( 'radio' === this.type ) {
					const hookArgs = { gateway: input.value, checked: false, settings };
					wp.hooks.doAction( 'frm_trans_toggled_gateway', hookArgs );
				}
			}
		);

		const hookArgs = { gateway, checked: this.checked, settings };
		wp.hooks.doAction( 'frm_trans_toggled_gateway', hookArgs );
	}

	function hasClass( thisClass, showClasses ) {
		var theseClasses = thisClass.split( /\s+/ );
		theseClasses = theseClasses.filter(
			function( n ) {
				return showClasses.indexOf( n ) != -1;
			}
		);
		return theseClasses.length >= 1;
	}

	function toggleShipping() {
		slideOpts( this, this.checked, '.frm_trans_shipping' );
	}

	function addAfterPayRow() {
		const id     = jQuery( this ).data( 'emailkey' );
		const formId = document.getElementById( 'form_id' ).value;

		let rowNum   = 0;
		if ( jQuery( '#frm_form_action_' + id + ' .frmtrans_after_pay_row' ).length ) {
			rowNum = 1 + parseInt( jQuery( '#frm_form_action_' + id + ' .frmtrans_after_pay_row:last' ).attr( 'id' ).replace( 'frmtrans_after_pay_row_' + id + '_', '' ) );
		}

		jQuery.ajax({
			type: 'POST',
			url: ajaxurl,
			data: {
				action: 'frmtrans_after_pay',
				email_id: id,
				form_id: formId,
				row_num: rowNum,
				nonce: frmGlobal.nonce
			},
			success: function( html ) {
				const $addButton = jQuery( document.getElementById( 'frmtrans_after_pay_' + id ) );
				$addButton.fadeOut( 'slow', function() {
					const $logicRow = $addButton.next( '.frmtrans_after_pay_rows' );
					$logicRow.find( 'tbody' ).append( html );
					$logicRow.fadeIn( 'slow' );
				});
			}
		});
		return false;
	}

	function runAjaxLink( e ) {
		var $link, href, loadingImage, handleConfirmedClick;

		e.preventDefault();

		$link = jQuery( this );
		handleConfirmedClick = ( e ) => {
			e.preventDefault();

			href = $link.attr( 'href' );
			loadingImage = document.createElement( 'span' );
			loadingImage.className = 'frm-loading-img';
			$link.replaceWith( loadingImage );
			jQuery.ajax({
				type: 'GET',
				url: href,
				data: {
					nonce: frm_trans_vars.nonce
				},
				success: function( html ) {
					jQuery( loadingImage ).replaceWith( html );
				}
			});
		};

		if ( ! e.currentTarget.dataset.frmverify ) {
			handleConfirmedClick( e );
			return;
		}

		jQuery( '#frm-confirmed-click' ).one( 'click', handleConfirmedClick );

		// prevent handleConfirmedClick from triggering when the current modal is closed so that it won't be run by other elements
		const unbindHandleConfirmedClick = ( e ) => {
			if ( e.target.matches( '.ui-widget-overlay, .dismiss' ) ) {
				jQuery( '#frm-confirmed-click' ).unbind( 'click', handleConfirmedClick );
				document.removeEventListener( 'click', unbindHandleConfirmedClick );
			}
		};

		document.addEventListener( 'click', unbindHandleConfirmedClick );

		return false;
	}

	return {
		init: function() {
			var actions = document.getElementById( 'frm_notification_settings' );
			if ( actions !== null ) {
				jQuery( actions ).on( 'change', '.frm_trans_type', toggleSub );
				jQuery( '.frm_form_settings' ).on( 'click', '.frm_add_trans_logic', addAfterPayRow );
				jQuery( '.frm_form_settings' ).on( 'change', '.frm_gateway_opt input', toggleGateway );
				jQuery( '.frm_form_settings' ).on( 'click', '.frm_trans_shipping_box', toggleShipping );
			}

			document.querySelectorAll( '.frm_trans_ajax_link' ).forEach(
				link => link.addEventListener(
					'click',
					function( event ) {
						runAjaxLink.bind( link )( event );
					}
				)
			);
		}
	};
}

var frmTransAdmin = frmTransAdminJS();
jQuery( document ).ready( frmTransAdmin.init );
