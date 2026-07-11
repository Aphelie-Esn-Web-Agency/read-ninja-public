'use strict';

/**
 * Read Ninja — Pro upsell admin notice dismissal
 *
 * WordPress core adds the dismiss (×) button for any `.is-dismissible`
 * notice and hides it on click, but it never persists that dismissal.
 * This listens for that click and calls `readninja_dismiss_notice` so the
 * notice does not reappear on the next page load.
 */
(function () {
	document.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '.notice-dismiss' );
		if ( ! button ) { return; }

		var notice = button.closest( '.readninja-notice-upgrade' );
		if ( ! notice ) { return; }

		var nonce = notice.getAttribute( 'data-readninja-nonce' );
		if ( ! nonce || typeof window.ajaxurl === 'undefined' ) { return; }

		var data = new FormData();
		data.append( 'action', 'readninja_dismiss_notice' );
		data.append( '_wpnonce', nonce );

		window.fetch( window.ajaxurl, {
			method: 'POST',
			body: data,
			credentials: 'same-origin',
		} );
	} );
})();
