'use strict';

(function () {
	if ( ! window.rpbAnalytics ) return;

	const { ajaxUrl, nonce, postId } = window.rpbAnalytics;

	if ( ! postId ) return;

	let maxDepth         = 0;
	let thresholdReached = false;
	let sent             = false;

	document.addEventListener( 'rpb:threshold-reached', function () {
		thresholdReached = true;
	} );

	window.addEventListener( 'scroll', function () {
		const scrollable = document.documentElement.scrollHeight - window.innerHeight;
		if ( scrollable <= 0 ) return;
		const depth = Math.min( 100, Math.round( ( window.scrollY / scrollable ) * 100 ) );
		if ( depth > maxDepth ) maxDepth = depth;
	}, { passive: true } );

	function sendData() {
		if ( sent ) return;
		sent = true;

		const params = new URLSearchParams( {
			action:            'rpb_track',
			nonce:             nonce,
			post_id:           postId,
			max_depth:         maxDepth,
			threshold_reached: thresholdReached ? '1' : '0',
		} );

		if ( navigator.sendBeacon ) {
			navigator.sendBeacon( ajaxUrl, params );
		} else {
			fetch( ajaxUrl, { method: 'POST', body: params, keepalive: true } );
		}
	}

	document.addEventListener( 'visibilitychange', function () {
		if ( document.visibilityState === 'hidden' ) sendData();
	} );

	window.addEventListener( 'pagehide', sendData );
	window.addEventListener( 'beforeunload', sendData );
})();
