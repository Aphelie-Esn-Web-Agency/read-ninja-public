<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class READNINJA_Enqueue {

	public function init(): void {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ] );
	}

	public function enqueue(): void {
		$opts    = ( new READNINJA_Settings() )->get_options();
		$post_id = is_singular() ? get_the_ID() : 0;

		// --- Décision d'affichage ------------------------------------------------
		$display = $this->should_display_base( $opts );

		/**
		 * Filtre la décision d'affichage de la barre.
		 *
		 * @param bool $display  Décision courante (free logic).
		 * @param int  $post_id  ID du post courant, 0 si non-singulier.
		 */
		$display = (bool) apply_filters( 'readninja_should_display', $display, $post_id );

		if ( ! $display ) {
			return;
		}

		// --- CSS -----------------------------------------------------------------
		wp_enqueue_style(
			'readninja-progress-bar',
			READNINJA_URL . 'assets/css/progress-bar.css',
			[],
			READNINJA_VERSION
		);

		// --- Config de la barre --------------------------------------------------
		$config = [
			'color'    => $opts['color'],
			'height'   => (string) $opts['height'],
			'position' => $opts['position'],
			'opacity'  => (string) $opts['opacity'],
			'zIndex'   => (string) $opts['zindex'],
		];

		// Fond de la barre (plugin gratuit)
		if ( $opts['background_color'] ) {
			$config['backgroundColor'] = $opts['background_color'];
		}

		// Sélecteur personnalisé (plugin gratuit)
		if ( $opts['position'] === 'custom' && $opts['custom_selector'] ) {
			$config['customSelector'] = $opts['custom_selector'];
		}

		// Overrides par article (plugin gratuit)
		if ( $post_id ) {
			$bg = get_post_meta( $post_id, '_readninja_background_color', true );
			if ( $bg !== '' ) {
				$config['backgroundColor'] = $bg;
			}

			$pos_override = get_post_meta( $post_id, '_readninja_position_override', true );
			if ( $pos_override !== '' ) {
				$config['position'] = $pos_override;
				if ( $pos_override === 'custom' ) {
					$sel = get_post_meta( $post_id, '_readninja_custom_selector', true );
					$config['customSelector'] = $sel ?: '';
				} else {
					unset( $config['customSelector'] );
				}
			}
		}

		/**
		 * Filtre la configuration JS de la barre avant l'injection.
		 * Permet au plugin Pro d'ajouter colorOverride, heightOverride, etc.
		 *
		 * @param array $config  Tableau de configuration.
		 */
		$config = (array) apply_filters( 'readninja_bar_config', $config );

		// --- Barre JS principale -------------------------------------------------
		wp_enqueue_script(
			'readninja-progress-bar',
			READNINJA_URL . 'assets/js/progress-bar.js',
			[],
			READNINJA_VERSION,
			[ 'strategy' => 'defer', 'in_footer' => true ]
		);

		// --- Source de progression ------------------------------------------------
		$config['progressSource'] = $opts['progress_source'] ?? 'content';

		// --- Sticky header offset ------------------------------------------------
		$sticky = (int) ( $opts['sticky_offset'] ?? 0 );
		$config['stickyOffset'] = (string) $sticky;
		$config['autoSticky']   = $sticky === 0 ? 'true' : 'false';

		// --- Device display ------------------------------------------------------
		$config['deviceDisplay']    = $opts['device_display'] ?? 'all';
		$config['mobileBreakpoint'] = '768';

		// --- RTL -----------------------------------------------------------------
		$config['isRtl'] = is_rtl() ? 'true' : 'false';

		// --- Indicateur de progression -------------------------------------------
		$indicator_type = $opts['indicator_type'] ?? 'none';
		$config['indicatorType']     = $indicator_type;
		$config['indicatorPosition'] = $opts['indicator_position'] ?? 'inside';
		$config['indicatorSize']     = $opts['indicator_size'] ?? 'auto';
		$config['indicatorColor']    = $opts['indicator_color'] ?? 'auto';

		if ( $indicator_type !== 'none' ) {
			$config['wpm']        = (string) ( (int) ( $opts['wpm'] ?? 200 ) );
			$config['timeFormat'] = $opts['time_format'] ?? 'minutes';
			$config['timePrefix'] = $opts['time_prefix'] ?? '';
			$config['timeSuffix'] = $opts['time_suffix'] ?? 'min restantes';
			$config['wordCount']  = '0';
			if ( in_array( $indicator_type, [ 'time', 'both' ], true ) && is_singular() ) {
				$content = get_the_content( null, false );
				$config['wordCount'] = (string) str_word_count( wp_strip_all_tags( $content ) );
			}
		}

		wp_localize_script( 'readninja-progress-bar', 'readninjaSettings', $config );

		/**
		 * Action déclenchée après l'enqueue de la barre.
		 * Le plugin Pro l'utilise pour charger ses propres scripts (analytics, threshold).
		 *
		 * @param int $post_id  ID du post courant, 0 si non-singulier.
		 */
		do_action( 'readninja_after_bar_render', $post_id );
	}

	/**
	 * Logique d'affichage libre (sans conditions Pro).
	 */
	private function should_display_base( array $opts ): bool {
		// Override par article (prioritaire sur tout le reste)
		if ( is_singular() ) {
			$override = get_post_meta( get_the_ID(), READNINJA_Post_Meta::META_KEY, true );
			if ( $override === 'show' ) return true;
			if ( $override === 'hide' ) return false;
		}

		// Page d'accueil : hide_home seul décide
		if ( is_front_page() ) {
			return empty( $opts['hide_home'] );
		}

		if ( is_singular( 'post' ) && ! empty( $opts['on_posts'] ) ) return true;
		if ( is_singular( 'page' ) && ! empty( $opts['on_pages'] ) ) return true;

		return false;
	}
}
