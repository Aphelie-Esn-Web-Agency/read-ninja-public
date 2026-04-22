'use strict';

(function () {
	if ( ! window.rpbThreshold ) return;

	const settings = window.rpbThreshold;

	if ( ! settings.enabled || settings.disabled ) return;

	const percent   = Math.min( 100, Math.max( 1, parseInt( settings.percent ) || 75 ) );
	let   triggered = false;

	function onThresholdReached() {
		if ( triggered ) return;
		triggered = true;

		// Événement DOM personnalisé pour les développeurs
		document.dispatchEvent( new CustomEvent( 'rpb:threshold-reached', {
			bubbles: true,
			detail:  { percent: percent },
		} ) );

		// Action configurée
		switch ( settings.action ) {
			case 'scroll-to-top':
				window.scrollTo( { top: 0, behavior: 'smooth' } );
				break;

			case 'show-element':
				if ( settings.selector ) {
					const el = document.querySelector( settings.selector );
					if ( el ) el.classList.remove( 'rpb-hidden' );
				}
				break;

			// 'custom-callback' : géré par l'écouteur rpb:threshold-reached
		}
	}

	function checkProgress() {
		if ( triggered ) return;
		const scrollable = document.documentElement.scrollHeight - window.innerHeight;
		if ( scrollable <= 0 ) return;
		if ( ( window.scrollY / scrollable ) * 100 >= percent ) onThresholdReached();
	}

	window.addEventListener( 'scroll', checkProgress, { passive: true } );

	// Vérification initiale (page déjà scrollée au chargement)
	checkProgress();
})();
