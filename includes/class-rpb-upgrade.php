<?php
/**
 * RPB Upgrade — Pro upsell logic for the free plugin.
 *
 * Loaded only in admin context, only when Pro is not active.
 * Handles 5 upsell levers: locked features in settings, contextual notice,
 * Analytics tab promo, plugin footer, plugin row links.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'rpb_is_plugin_page' ) ) {
	/**
	 * Returns true if the current admin screen is the ReadNinja settings page.
	 */
	function rpb_is_plugin_page(): bool {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}
		$screen = get_current_screen();
		if ( ! $screen ) {
			return false;
		}
		return $screen->id === 'toplevel_page_read-ninja';
	}
}

class RPB_Upgrade {

	const PRO_URL     = 'https://read-ninja.com/pro';
	const DOCS_URL    = 'https://read-ninja.com/docs';
	const SUPPORT_URL = 'https://wordpress.org/support/plugin/read-ninja/';

	public function init(): void {
		// Levier 1 — Locked Pro features as rows in the settings table
		add_action( 'admin_init', [ $this, 'register_pro_features_section' ] );

		// Levier 2 — Contextual admin notice (one-time, AJAX dismiss)
		add_action( 'admin_init', [ $this, 'maybe_set_activation_time' ] );
		add_action( 'admin_notices', [ $this, 'render_admin_notice' ] );
		add_action( 'wp_ajax_rpb_dismiss_notice', [ $this, 'ajax_dismiss_notice' ] );

		// Levier 3 — Analytics promo tab
		add_filter( 'rpb_settings_tabs', [ $this, 'add_analytics_tab' ], 5 );
		add_action( 'rpb_render_tab_analytics', [ $this, 'render_analytics_tab' ] );

		// Levier 4 — Footer bar on plugin pages
		add_action( 'admin_footer', [ $this, 'render_footer' ] );

		// Levier 5 — Plugin listing links
		if ( defined( 'RPB_BASENAME' ) ) {
			add_filter( 'plugin_action_links_' . RPB_BASENAME, [ $this, 'plugin_action_links' ] );
		}
		add_filter( 'plugin_row_meta', [ $this, 'plugin_row_meta' ], 10, 2 );

		// Asset enqueue (CSS, only on plugin pages)
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	// =====================================================================
	// Levier 1 — Locked Pro features in the Settings table
	// =====================================================================

	public function register_pro_features_section(): void {
		add_settings_section(
			'rpb_pro_features',
			'<span class="dashicons dashicons-star-filled" style="color:#00C9A7;vertical-align:middle"></span> ' . esc_html__( 'Fonctionnalités Pro', 'read-ninja' ),
			[ $this, 'render_pro_features_intro' ],
			'read-ninja'
		);

		foreach ( $this->get_pro_features() as $key => $feature ) {
			add_settings_field(
				'rpb_pro_feature_' . $key,
				wp_kses_post( $feature['label'] ) . ' <span class="rpb-pro-badge">PRO</span>',
				[ $this, 'render_pro_feature_field' ],
				'read-ninja',
				'rpb_pro_features',
				[
					'class'       => 'rpb-pro-locked-row',
					'description' => $feature['description'],
					'control'     => $feature['control'],
				]
			);
		}
	}

	public function render_pro_features_intro(): void {
		echo '<p style="color:#64748b;font-size:13px;margin:0 0 4px">';
		echo esc_html__( 'Ces fonctionnalités sont disponibles dans ReadNinja Pro. Cliquez sur « Débloquer » pour les activer.', 'read-ninja' );
		echo '</p>';
	}

	public function render_pro_feature_field( array $args ): void {
		$description = isset( $args['description'] ) ? (string) $args['description'] : '';
		$control     = isset( $args['control'] ) ? (string) $args['control'] : 'checkbox';
		?>
		<div class="rpb-pro-locked">
			<?php if ( 'select' === $control ) : ?>
				<select disabled>
					<option><?php esc_html_e( 'Aperçu Pro', 'read-ninja' ); ?></option>
				</select>
			<?php elseif ( 'color' === $control ) : ?>
				<input type="color" value="#3b82f6" disabled>
				<input type="color" value="#8b5cf6" disabled>
			<?php else : ?>
				<input type="checkbox" disabled>
			<?php endif; ?>
			<p class="description"><?php echo esc_html( $description ); ?></p>
			<a href="<?php echo esc_url( self::PRO_URL ); ?>"
			   target="_blank" rel="noopener noreferrer"
			   class="rpb-unlock-link">
				<?php esc_html_e( 'Débloquer avec Pro →', 'read-ninja' ); ?>
			</a>
		</div>
		<?php
	}

	private function get_pro_features(): array {
		return [
			'gradient'   => [
				'label'       => __( 'Barre en dégradé', 'read-ninja' ),
				'description' => __( 'Remplacez la couleur unie par un dégradé de 2 couleurs avec angle personnalisable.', 'read-ninja' ),
				'control'     => 'color',
			],
			'sequential' => [
				'label'       => __( 'Avancement séquentiel', 'read-ninja' ),
				'description' => __( 'Faites avancer la barre par paliers fixes (5, 10, 25 %) avec animation fluide.', 'read-ninja' ),
				'control'     => 'select',
			],
			'threshold'  => [
				'label'       => __( 'Threshold trigger', 'read-ninja' ),
				'description' => __( 'Déclenchez une action JS personnalisée à un seuil de lecture (75 %, 90 %…).', 'read-ninja' ),
				'control'     => 'checkbox',
			],
			'conditions' => [
				'label'       => __( "Conditions d'affichage avancées", 'read-ninja' ),
				'description' => __( 'Ciblez les CPT, archives, taxonomies et templates FSE de votre choix.', 'read-ninja' ),
				'control'     => 'checkbox',
			],
			'analytics'  => [
				'label'       => __( 'Analytics de lecture', 'read-ninja' ),
				'description' => __( 'Mesurez la profondeur de lecture et le taux de complétion par article.', 'read-ninja' ),
				'control'     => 'checkbox',
			],
			'overrides'  => [
				'label'       => __( 'Personnalisation par article', 'read-ninja' ),
				'description' => __( "Définissez la couleur, la hauteur ou le dégradé spécifique d'un article.", 'read-ninja' ),
				'control'     => 'checkbox',
			],
		];
	}

	// =====================================================================
	// Levier 2 — Admin notice
	// =====================================================================

	public function maybe_set_activation_time(): void {
		if ( ! get_option( 'rpb_activated_at' ) ) {
			update_option( 'rpb_activated_at', time() );
		}
	}

	public function render_admin_notice(): void {
		if ( ! rpb_is_plugin_page() ) {
			return;
		}
		if ( get_option( 'rpb_notice_dismissed' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$activated = (int) get_option( 'rpb_activated_at', 0 );
		if ( ! $activated || ( time() - $activated ) > 7 * DAY_IN_SECONDS ) {
			return;
		}

		$nonce = wp_create_nonce( 'rpb_dismiss_notice' );
		?>
		<div class="notice notice-info is-dismissible rpb-notice-upgrade" data-rpb-nonce="<?php echo esc_attr( $nonce ); ?>">
			<div class="rpb-notice-content">
				<svg class="rpb-notice-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
					<circle cx="12" cy="12" r="2.5"/>
					<path d="M12 2 L13.5 10 L22 12 L13.5 14 L12 22 L10.5 14 L2 12 L10.5 10 Z"/>
				</svg>
				<p class="rpb-notice-text">
					<?php esc_html_e( "Merci d'utiliser ReadNinja ! Débloquez les analytics de lecture, les dégradés et le ciblage avancé avec ReadNinja Pro.", 'read-ninja' ); ?>
				</p>
				<a href="<?php echo esc_url( self::PRO_URL ); ?>" target="_blank" rel="noopener noreferrer" class="rpb-notice-cta">
					<?php esc_html_e( 'Découvrir Pro →', 'read-ninja' ); ?>
				</a>
			</div>
		</div>
		<script>
		(function () {
			var notice = document.querySelector('.rpb-notice-upgrade');
			if (!notice) return;
			notice.addEventListener('click', function (e) {
				if (!e.target.classList.contains('notice-dismiss')) return;
				var nonce = notice.getAttribute('data-rpb-nonce');
				var data = new FormData();
				data.append('action', 'rpb_dismiss_notice');
				data.append('_ajax_nonce', nonce);
				if (typeof window.fetch === 'function') {
					window.fetch(window.ajaxurl, { method: 'POST', credentials: 'same-origin', body: data });
				}
			});
		})();
		</script>
		<?php
	}

	public function ajax_dismiss_notice(): void {
		check_ajax_referer( 'rpb_dismiss_notice' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( null, 403 );
		}
		update_option( 'rpb_notice_dismissed', 1 );
		wp_send_json_success();
	}

	// =====================================================================
	// Levier 3 — Analytics promo tab
	// =====================================================================

	public function add_analytics_tab( array $tabs ): array {
		$tabs['analytics'] = '<span class="dashicons dashicons-chart-bar"></span> ' . esc_html__( 'Analytics', 'read-ninja' );
		return $tabs;
	}

	public function render_analytics_tab(): void {
		?>
		<div class="rpb-analytics-promo">
			<div class="rpb-analytics-promo__header">
				<span class="dashicons dashicons-chart-bar rpb-analytics-promo__icon"></span>
				<h2 class="rpb-analytics-promo__title">
					<?php esc_html_e( 'Analytics de lecture — ReadNinja Pro', 'read-ninja' ); ?>
				</h2>
			</div>
			<ul class="rpb-analytics-promo__benefits">
				<li><?php esc_html_e( "Voyez jusqu'où scrollent vos lecteurs sur chaque article", 'read-ninja' ); ?></li>
				<li><?php esc_html_e( 'Identifiez vos contenus les plus engageants', 'read-ninja' ); ?></li>
				<li><?php esc_html_e( 'Optimisez vos articles grâce aux données réelles', 'read-ninja' ); ?></li>
			</ul>

			<div class="rpb-analytics-fake">
				<div class="rpb-analytics-fake__data" aria-hidden="true">
					<div class="rpb-analytics-fake__row">
						<span>Guide SEO 2025</span>
						<span>Scroll moyen : 73%</span>
						<span>142 lectures</span>
					</div>
					<div class="rpb-analytics-fake__row">
						<span>10 astuces WordPress</span>
						<span>Scroll moyen : 68%</span>
						<span>98 lectures</span>
					</div>
					<div class="rpb-analytics-fake__row">
						<span>Comparatif hébergeurs 2025</span>
						<span>Scroll moyen : 81%</span>
						<span>67 lectures</span>
					</div>
					<div class="rpb-analytics-fake__row">
						<span>Tutoriel Gutenberg</span>
						<span>Scroll moyen : 55%</span>
						<span>43 lectures</span>
					</div>
				</div>
				<div class="rpb-analytics-overlay">
					<h3 class="rpb-analytics-overlay__title">
						<?php esc_html_e( 'Débloquez les analytics', 'read-ninja' ); ?>
					</h3>
					<a href="<?php echo esc_url( self::PRO_URL ); ?>" target="_blank" rel="noopener noreferrer" class="rpb-analytics-overlay__cta">
						<?php esc_html_e( 'Passer à ReadNinja Pro — dès 29€/an', 'read-ninja' ); ?>
					</a>
					<p class="rpb-analytics-overlay__sub">
						<?php esc_html_e( 'Satisfait ou remboursé 30 jours', 'read-ninja' ); ?>
					</p>
				</div>
			</div>
		</div>
		<?php
	}

	// =====================================================================
	// Levier 4 — Footer bar
	// =====================================================================

	public function render_footer(): void {
		if ( ! rpb_is_plugin_page() ) {
			return;
		}
		?>
		<div class="rpb-admin-footer">
			<span class="rpb-admin-footer__bolt">⚡</span>
			<?php esc_html_e( 'ReadNinja Pro — Analytics · Dégradés · Ciblage avancé · Support prioritaire', 'read-ninja' ); ?>
			<a href="<?php echo esc_url( self::PRO_URL ); ?>" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( 'En savoir plus →', 'read-ninja' ); ?>
			</a>
		</div>
		<?php
	}

	// =====================================================================
	// Levier 5 — Plugin listing links
	// =====================================================================

	public function plugin_action_links( array $links ): array {
		$upgrade = sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer" style="color:#00C9A7;font-weight:600">⭐ %s</a>',
			esc_url( self::PRO_URL ),
			esc_html__( 'Passer à Pro', 'read-ninja' )
		);
		array_unshift( $links, $upgrade );
		return $links;
	}

	public function plugin_row_meta( array $links, string $file ): array {
		if ( ! defined( 'RPB_BASENAME' ) || $file !== RPB_BASENAME ) {
			return $links;
		}
		$links[] = sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_url( self::DOCS_URL ),
			esc_html__( 'Documentation', 'read-ninja' )
		);
		$links[] = sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_url( self::SUPPORT_URL ),
			esc_html__( 'Support', 'read-ninja' )
		);
		return $links;
	}

	// =====================================================================
	// Assets
	// =====================================================================

	public function enqueue_assets(): void {
		if ( ! rpb_is_plugin_page() ) {
			return;
		}
		wp_enqueue_style(
			'rpb-admin-upgrade',
			RPB_URL . 'assets/css/rpb-admin-upgrade.css',
			[ 'rpb-admin' ],
			RPB_VERSION
		);
	}
}
