( function( $ ) {
	"use strict";

	$(document).on('click', '.rek-notice .button-primary', function( e ) {

		if ( 'install-activate' === $(this).data('action') && ! $( this ).hasClass('init') ) {
			var $self = $(this),
				$href = $self.attr('href');

			if ( 'true' === $self.data('freemius') ) {
				$href.replace('wpr-addons','wpr-templates-kit')
			}

			$self.addClass('init');

			$self.html('Installing Templates Kit Library <span class="dot-flashing"></span>');

			var elementorData = {
				'action' : 'rek_install_activate_elementor',
				'nonce' : rek_localize.elementor_nonce
			};

			// Send Request.
			$.post( rek_localize.ajax_url, elementorData, function( response ) {

				if ( response.success ) {
					console.log('elementor installed');

					// Both Plugins Installed
					if ( true === response.data.plugins_updated ) {
						setTimeout(function() {
							$self.html('Redirecting to Templates Kit Library <span class="dot-flashing"></span>');

							setTimeout( function() {
								window.location = $href;
							}, 1000 );
						}, 500);

						console.log('royal addons installed');

						return false;
					}

					var royalAddonsData = {
						'action' : 'rek_install_activate_royal_addons',
						'nonce' : rek_localize.royal_addons_nonce
					};

					$.post( rek_localize.ajax_url, royalAddonsData, function( response ) {
						if ( response.success ) {

							var elementorRedirect = {
								'action' : 'rek_cancel_elementor_redirect',
							};

							$.post( rek_localize.ajax_url, elementorRedirect, function( response ) {
								console.log('royal addons installed');

								setTimeout(function() {
									$self.html('Redirecting to Templates Kit page <span class="dot-flashing"></span>');

									setTimeout( function() {
										window.location = $href;
									}, 1000 );
								}, 500);
							});

						}
					});

				}

			} ).fail( function( xhr, textStatus, e ) {
				$(this).parent().after( `<div class="plugin-activation-warning">${rek_localize.failed_message}</div>` );
			} );

			e.preventDefault();
		}
	} );

} )( jQuery );
