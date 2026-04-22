<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RPB_Settings {

	const OPTION_KEY = 'rpb_settings';

	public static function defaults(): array {
		return [
			'color'            => '#1D9E75',
			'height'           => 4,
			'position'         => 'top',
			'background_color' => '',
			'custom_selector'  => '',
			'opacity'          => 100,
			'zindex'           => 9999,
			'on_posts'         => 1,
			'on_pages'         => 0,
			'hide_home'        => 1,
			'indicator_type'     => 'none',
			'indicator_position' => 'inside',
			'indicator_size'     => 'auto',
			'wpm'                => 200,
			'time_prefix'        => '',
			'time_suffix'        => 'min restantes',
			'time_format'        => 'minutes',
			'progress_source'  => 'content',
			'indicator_color'  => 'auto',
			'sticky_offset'    => 0,
			'device_display'   => 'all',
		];
	}

	public function init(): void {
		add_action( 'admin_menu',            [ $this, 'add_page' ] );
		add_action( 'admin_init',            [ $this, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
	}

	public function add_page(): void {
		add_menu_page(
			__( 'Read Ninja - Reading Progress Bar', 'read-ninja' ),
			__( 'Progress Bar', 'read-ninja' ),
			'manage_options',
			'read-ninja',
			[ $this, 'render_page' ],
			'dashicons-chart-line',
			80
		);
		add_submenu_page(
			'read-ninja',
			__( 'Read Ninja - Reading Progress Bar', 'read-ninja' ),
			__( 'Réglages', 'read-ninja' ),
			'manage_options',
			'read-ninja',
			[ $this, 'render_page' ]
		);
	}

	public function register_settings(): void {
		register_setting( 'rpb_settings_group', self::OPTION_KEY, [
			'sanitize_callback' => [ $this, 'sanitize' ],
		] );

		add_settings_section( 'rpb_style',   __( 'Style', 'read-ninja' ),     '__return_false', 'read-ninja' );
		add_settings_section( 'rpb_display', __( 'Affichage', 'read-ninja' ), '__return_false', 'read-ninja' );

		$this->add_field( 'color',            __( 'Couleur', 'read-ninja' ),              'rpb_style',   'render_color' );
		$this->add_field( 'height',           __( 'Hauteur', 'read-ninja' ),              'rpb_style',   'render_height' );
		$this->add_field( 'position',         __( 'Position', 'read-ninja' ),             'rpb_style',   'render_position' );
		$this->add_field( 'custom_selector',  __( 'Sélecteur CSS cible', 'read-ninja' ),  'rpb_style',   'render_custom_selector' );
		$this->add_field( 'background_color', __( 'Fond de la barre', 'read-ninja' ),     'rpb_style',   'render_background_color' );
		$this->add_field( 'opacity',          __( 'Opacité', 'read-ninja' ),              'rpb_style',   'render_opacity' );
		$this->add_field( 'zindex',           __( 'Z-index', 'read-ninja' ),              'rpb_style',   'render_zindex' );
		$this->add_field( 'sticky_offset',   __( 'Décalage sticky header', 'read-ninja' ), 'rpb_style', 'render_sticky_offset' );
		$this->add_field( 'on_posts',         __( 'Articles', 'read-ninja' ),             'rpb_display', 'render_on_posts' );
		$this->add_field( 'on_pages',         __( 'Pages', 'read-ninja' ),               'rpb_display', 'render_on_pages' );
		$this->add_field( 'hide_home',        __( "Masquer sur l'accueil", 'read-ninja' ), 'rpb_display', 'render_hide_home' );
		$this->add_field( 'device_display',   __( 'Affichage sur les appareils', 'read-ninja' ), 'rpb_display', 'render_device_display' );

		add_settings_section( 'rpb_progress', __( 'Progression', 'read-ninja' ), '__return_false', 'read-ninja' );
		$this->add_field( 'progress_source', __( 'Source de progression', 'read-ninja' ), 'rpb_progress', 'render_progress_source' );

		add_settings_section( 'rpb_indicator', __( 'Indicateur de progression', 'read-ninja' ), '__return_false', 'read-ninja' );
		$this->add_field( 'indicator_type',     __( "Type d'indicateur", 'read-ninja' ),        'rpb_indicator', 'render_indicator_type' );
		$this->add_field( 'indicator_position', __( "Position de l'indicateur", 'read-ninja' ), 'rpb_indicator', 'render_indicator_position' );
		$this->add_field( 'indicator_size',     __( "Taille du texte", 'read-ninja' ),         'rpb_indicator', 'render_indicator_size' );
		$this->add_field( 'indicator_color',    __( "Couleur de l'indicateur", 'read-ninja' ),  'rpb_indicator', 'render_indicator_color' );
		$this->add_field( 'wpm',                __( 'Mots par minute', 'read-ninja' ),          'rpb_indicator', 'render_wpm' );
		$this->add_field( 'time_format',        __( 'Format du temps', 'read-ninja' ),          'rpb_indicator', 'render_time_format' );
		$this->add_field( 'time_prefix',        __( 'Texte avant le temps', 'read-ninja' ),     'rpb_indicator', 'render_time_prefix' );
		$this->add_field( 'time_suffix',        __( 'Texte après le temps', 'read-ninja' ),     'rpb_indicator', 'render_time_suffix' );
	}

	public function sanitize( $input ): array {
		$input = is_array( $input ) ? $input : [];
		$d     = self::defaults();
		return [
			'color'            => sanitize_hex_color( $input['color'] ?? $d['color'] ) ?? $d['color'],
			'height'           => min( 20,    max( 2,     absint( $input['height']   ?? $d['height'] ) ) ),
			'position'         => in_array( $input['position'] ?? '', [ 'top', 'bottom', 'custom' ], true ) ? $input['position'] : 'top',
			'background_color' => sanitize_hex_color( $input['background_color'] ?? '' ) ?? '',
			'custom_selector'  => sanitize_text_field( $input['custom_selector'] ?? '' ),
			'opacity'          => min( 100,   max( 10,    absint( $input['opacity']  ?? $d['opacity'] ) ) ),
			'zindex'           => min( 99999, max( 1,     absint( $input['zindex']   ?? $d['zindex'] ) ) ),
			'on_posts'         => isset( $input['on_posts'] )  ? 1 : 0,
			'on_pages'         => isset( $input['on_pages'] )  ? 1 : 0,
			'hide_home'        => isset( $input['hide_home'] ) ? 1 : 0,
			'indicator_type'     => in_array( $input['indicator_type'] ?? '', [ 'none', 'percent', 'time', 'both' ], true ) ? $input['indicator_type'] : 'none',
			'indicator_position' => in_array( $input['indicator_position'] ?? '', [ 'inside', 'left', 'right' ], true ) ? $input['indicator_position'] : 'inside',
			'indicator_size'     => ( $input['indicator_size'] ?? 'auto' ) === 'auto' ? 'auto' : (string) min( 32, max( 8, absint( $input['indicator_size'] ?? 0 ) ) ),
			'wpm'                => min( 400, max( 100, absint( $input['wpm'] ?? $d['wpm'] ) ) ),
			'time_format'        => in_array( $input['time_format'] ?? '', [ 'minutes', 'minutes_seconds' ], true ) ? $input['time_format'] : 'minutes',
			'time_prefix'        => sanitize_text_field( $input['time_prefix'] ?? $d['time_prefix'] ),
			'time_suffix'        => sanitize_text_field( $input['time_suffix'] ?? $d['time_suffix'] ),
			'progress_source'  => in_array( $input['progress_source'] ?? '', [ 'page', 'content' ], true ) ? $input['progress_source'] : 'content',
			'indicator_color'  => ( $input['indicator_color'] ?? 'auto' ) === 'auto' ? 'auto' : ( sanitize_hex_color( $input['indicator_color'] ?? '' ) ?? 'auto' ),
			'sticky_offset'    => min( 500, max( 0, absint( $input['sticky_offset'] ?? 0 ) ) ),
			'device_display'   => in_array( $input['device_display'] ?? '', [ 'all', 'desktop_only', 'mobile_only', 'hidden_mobile' ], true ) ? $input['device_display'] : 'all',
		];
	}

	public function get_options(): array {
		return wp_parse_args( get_option( self::OPTION_KEY, [] ), self::defaults() );
	}

	public function enqueue_admin_assets( string $hook ): void {
		// WordPress builds the submenu hook from sanitize_title( $parent_MENU_TITLE ),
		// not from the parent slug. Match any hook that ends with `_page_rpb-analytics`
		// so we don't break if the parent menu_title changes.
		$is_settings  = ( $hook === 'toplevel_page_read-ninja' );
		$is_analytics = ( substr( $hook, -strlen( '_page_rpb-analytics' ) ) === '_page_rpb-analytics' );
		if ( ! $is_settings && ! $is_analytics ) {
			return;
		}

		wp_enqueue_style( 'rpb-admin', RPB_URL . 'admin/css/rpb-admin.css', [], RPB_VERSION );

		if ( $is_settings ) {
			// Preview enqueued first — rpb-admin-ui en dépend pour garantir l'ordre d'exécution.
			wp_enqueue_script( 'rpb-admin-preview', RPB_URL . 'admin/js/settings-preview.js', [], RPB_VERSION, true );

			/**
			 * Permet au plugin Pro d'ajouter ses propres réglages (gradient…)
			 * dans l'objet rpbAdminSettings passé au JS de preview.
			 *
			 * @param array $settings  Réglages de base du plugin gratuit.
			 */
			$preview_settings = (array) apply_filters( 'rpb_admin_preview_settings', $this->get_options() );
			wp_localize_script( 'rpb-admin-preview', 'rpbAdminSettings', [ 'current' => $preview_settings ] );

			wp_enqueue_script( 'rpb-admin-ui', RPB_URL . 'admin/js/rpb-admin-ui.js', [ 'rpb-admin-preview' ], RPB_VERSION, true );
			wp_enqueue_script( 'rpb-admin-settings-toggles', RPB_URL . 'assets/js/admin/admin-settings-toggles.js', [ 'rpb-admin-ui' ], RPB_VERSION, true );
		} else {
			// Page Analytics : rpb-admin-ui sans dépendance preview.
			wp_enqueue_script( 'rpb-admin-ui', RPB_URL . 'admin/js/rpb-admin-ui.js', [], RPB_VERSION, true );
		}

		wp_localize_script( 'rpb-admin-ui', 'rpbAdminUi', [
			'saved' => __( 'Réglages enregistrés', 'read-ninja' ),
		] );
	}

	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification
		$tab      = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';
		$base_url = admin_url( 'admin.php?page=read-ninja' );

		/**
		 * Filtre les onglets de la page Settings.
		 * Le plugin Pro ajoute 'advanced' via ce filtre.
		 *
		 * @param array<string,string> $tabs  Slug => label.
		 */
		$tabs = (array) apply_filters( 'rpb_settings_tabs', [
			'general' => '<span class="dashicons dashicons-admin-settings"></span> ' . __( 'Général', 'read-ninja' ),
		] );

		// Fallback si l'onglet demandé n'existe pas
		if ( ! array_key_exists( $tab, $tabs ) ) {
			$tab = 'general';
		}
		?>
		<div class="wrap rpb-admin-page">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<div class="rpb-page-header">
				<div class="rpb-page-header__icon">
					<span class="dashicons dashicons-chart-line"></span>
				</div>
				<div class="rpb-page-header__info">
					<div class="rpb-page-header__title">Read Ninja - Reading Progress Bar</div>
					<div class="rpb-page-header__badges">
						<span class="rpb-badge rpb-badge--version">v<?php echo esc_html( RPB_VERSION ); ?></span>
						<?php if ( rpb_is_pro() ) : ?>
						<span class="rpb-badge rpb-badge--pro">
							<span class="dashicons dashicons-yes-alt" style="font-size:12px;width:12px;height:12px;line-height:1"></span>
							<?php esc_html_e( 'Pro actif', 'read-ninja' ); ?>
						</span>
						<?php else : ?>
						<span class="rpb-badge rpb-badge--free"><?php esc_html_e( 'Version gratuite', 'read-ninja' ); ?></span>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<nav class="nav-tab-wrapper">
				<?php foreach ( $tabs as $slug => $label ) : ?>
				<a href="<?php echo esc_url( add_query_arg( 'tab', $slug, $base_url ) ); ?>"
				   class="nav-tab <?php echo $tab === $slug ? 'nav-tab-active' : ''; ?>">
					<?php echo wp_kses( $label, [ 'span' => [ 'class' => true, 'style' => true ] ] ); ?>
				</a>
				<?php endforeach; ?>
			</nav>

			<?php if ( $tab === 'general' ) : ?>
			<div class="rpb-settings-layout">

				<aside class="rpb-preview-sidebar">
					<div class="rpb-preview-sidebar__card">
						<div class="rpb-preview-sidebar__title">
							<span class="dashicons dashicons-visibility"></span>
							<?php esc_html_e( 'Aperçu en direct', 'read-ninja' ); ?>
						</div>
						<div id="rpb-preview-placeholder"></div>
						<p class="rpb-preview-sidebar__hint">
							<?php esc_html_e( 'La barre se met à jour en temps réel.', 'read-ninja' ); ?>
						</p>
					</div>
				</aside>

				<div class="rpb-settings-main">
					<form method="post" action="options.php" class="rpb-settings-form">
						<?php
						settings_fields( 'rpb_settings_group' );
						do_settings_sections( 'read-ninja' );
						submit_button( __( 'Enregistrer les réglages', 'read-ninja' ) );
						?>
					</form>

					<?php
					/**
					 * Point d'accroche pour les sections Pro dans l'onglet général.
					 * Le plugin Pro peut y ajouter du contenu.
					 */
					do_action( 'rpb_settings_section_pro' );
					?>
				</div><!-- .rpb-settings-main -->

			</div><!-- .rpb-settings-layout -->

			<?php else : ?>
				<?php
				/**
				 * Contenu d'un onglet custom ajouté via rpb_settings_tabs.
				 * Le plugin Pro hookera rpb_render_tab_advanced.
				 */
				do_action( 'rpb_render_tab_' . $tab );
				?>
			<?php endif; ?>
		</div>
		<?php
	}

	// --- Render methods ---------------------------------------------------

	public function render_color(): void {
		$opts = $this->get_options();
		printf(
			'<input type="color" id="rpb_color" name="rpb_settings[color]" value="%s">',
			esc_attr( $opts['color'] )
		);
	}

	public function render_height(): void {
		$opts = $this->get_options();
		printf(
			'<input type="range" id="rpb_height" name="rpb_settings[height]" min="2" max="20" step="1" value="%1$s" oninput="this.nextElementSibling.value=this.value"> <output>%1$s</output> px',
			esc_attr( (string) $opts['height'] )
		);
	}

	public function render_position(): void {
		$opts = $this->get_options();
		$cur  = $opts['position'];
		?>
		<select id="rpb_position" name="rpb_settings[position]"
			onchange="rpbToggleCustomSelector(this.value)">
			<option value="top"    <?php selected( $cur, 'top' ); ?>><?php esc_html_e( 'Haut', 'read-ninja' ); ?></option>
			<option value="bottom" <?php selected( $cur, 'bottom' ); ?>><?php esc_html_e( 'Bas', 'read-ninja' ); ?></option>
			<option value="custom" <?php selected( $cur, 'custom' ); ?>><?php esc_html_e( 'Personnalisée', 'read-ninja' ); ?></option>
		</select>
		<?php
	}

	public function render_custom_selector(): void {
		$opts = $this->get_options();
		?>
		<input type="text" id="rpb_custom_selector" name="rpb_settings[custom_selector]"
			value="<?php echo esc_attr( $opts['custom_selector'] ); ?>"
			placeholder="<?php esc_attr_e( '.ma-classe ou #mon-id', 'read-ninja' ); ?>"
			style="width:220px">
		<p class="description">
			<?php esc_html_e( 'La barre sera insérée comme premier enfant de l\'élément ciblé. Si le sélecteur ne correspond à aucun élément, la barre se positionne en haut par défaut.', 'read-ninja' ); ?>
		</p>
		<?php
	}

	public function render_background_color(): void {
		$opts = $this->get_options();
		$val  = $opts['background_color'];
		?>
		<input type="hidden" id="rpb_background_color" name="rpb_settings[background_color]"
			value="<?php echo esc_attr( $val ); ?>">
		<input type="color" id="rpb_background_color_picker"
			value="<?php echo esc_attr( $val ?: '#ffffff' ); ?>"
			style="<?php echo $val ? '' : 'opacity:0.4'; ?>"
			oninput="document.getElementById('rpb_background_color').value=this.value;this.style.opacity='1'">
		<button type="button"
			style="margin-left:8px;font-size:12px;background:none;border:none;cursor:pointer;text-decoration:underline;color:#757575;padding:0"
			onclick="document.getElementById('rpb_background_color').value='';var p=document.getElementById('rpb_background_color_picker');p.value='#ffffff';p.style.opacity='0.4';window.rpbUpdatePreview&&window.rpbUpdatePreview()">
			<?php esc_html_e( 'Réinitialiser', 'read-ninja' ); ?>
		</button>
		<?php
	}

	public function render_opacity(): void {
		$opts = $this->get_options();
		printf(
			'<input type="range" id="rpb_opacity" name="rpb_settings[opacity]" min="10" max="100" step="5" value="%1$s" oninput="this.nextElementSibling.value=this.value"> <output>%1$s</output> %%',
			esc_attr( (string) $opts['opacity'] )
		);
	}

	public function render_zindex(): void {
		$opts = $this->get_options();
		printf(
			'<input type="number" id="rpb_zindex" name="rpb_settings[zindex]" min="1" max="99999" value="%s">',
			esc_attr( (string) $opts['zindex'] )
		);
	}

	public function render_sticky_offset(): void {
		$opts   = $this->get_options();
		$val    = (int) $opts['sticky_offset'];
		$isAuto = $val === 0;
		?>
		<label style="margin-right:16px">
			<input type="radio" name="rpb_sticky_offset_mode" value="auto"
				<?php checked( $isAuto ); ?>
				onchange="rpbToggleStickyOffset(this.value)">
			<?php esc_html_e( 'Automatique', 'read-ninja' ); ?>
		</label>
		<label>
			<input type="radio" name="rpb_sticky_offset_mode" value="manual"
				<?php checked( ! $isAuto ); ?>
				onchange="rpbToggleStickyOffset(this.value)">
			<?php esc_html_e( 'Valeur fixe', 'read-ninja' ); ?>
		</label>
		<span id="rpb_sticky_offset_wrap" style="margin-left:12px;<?php echo $isAuto ? 'display:none' : ''; ?>">
			<input type="number" id="rpb_sticky_offset_input" min="1" max="500" step="1"
				value="<?php echo esc_attr( $isAuto ? '60' : (string) $val ); ?>"
				style="width:80px"
				oninput="document.getElementById('rpb_sticky_offset').value=this.value"> px
		</span>
		<input type="hidden" id="rpb_sticky_offset" name="rpb_settings[sticky_offset]"
			value="<?php echo esc_attr( (string) $val ); ?>">
		<p class="description">
			<?php esc_html_e( "« Automatique » détecte la hauteur du header fixe/sticky de votre thème. « Valeur fixe » applique un décalage en pixels.", 'read-ninja' ); ?>
		</p>
		<?php
	}

	public function render_device_display(): void {
		$opts    = $this->get_options();
		$cur     = $opts['device_display'];
		$options = [
			'all'           => __( 'Tous les appareils', 'read-ninja' ),
			'desktop_only'  => __( 'Desktop uniquement (masqué sur mobile)', 'read-ninja' ),
			'mobile_only'   => __( 'Mobile uniquement (masqué sur desktop)', 'read-ninja' ),
			'hidden_mobile' => __( 'Masqué sur mobile', 'read-ninja' ),
		];
		echo '<select id="rpb_device_display" name="rpb_settings[device_display]">';
		foreach ( $options as $val => $label ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $val ),
				selected( $cur, $val, false ),
				esc_html( $label )
			);
		}
		echo '</select>';
		echo '<p class="description">' . esc_html__( 'Breakpoint mobile : largeur < 768px', 'read-ninja' ) . '</p>';
	}

	public function render_progress_source(): void {
		$opts = $this->get_options();
		$cur  = $opts['progress_source'];
		$sources = [
			'content' => __( "Hauteur de l'article", 'read-ninja' ),
			'page'    => __( 'Hauteur de la page', 'read-ninja' ),
		];
		foreach ( $sources as $val => $label ) {
			printf(
				'<label style="margin-right:16px"><input type="radio" name="rpb_settings[progress_source]" value="%s" %s> %s</label>',
				esc_attr( $val ),
				checked( $cur, $val, false ),
				esc_html( $label )
			);
		}
		echo '<p class="description">' . esc_html__( "« Hauteur de l'article » mesure la progression sur le contenu uniquement, « Hauteur de la page » sur l'ensemble de la page.", 'read-ninja' ) . '</p>';
	}

	public function render_on_posts(): void {
		$opts = $this->get_options();
		printf(
			'<label class="rpb-toggle"><input type="checkbox" id="rpb_on_posts" name="rpb_settings[on_posts]" value="1" %s><span class="rpb-toggle-track"></span></label>',
			checked( $opts['on_posts'], 1, false )
		);
	}

	public function render_on_pages(): void {
		$opts = $this->get_options();
		printf(
			'<label class="rpb-toggle"><input type="checkbox" id="rpb_on_pages" name="rpb_settings[on_pages]" value="1" %s><span class="rpb-toggle-track"></span></label>',
			checked( $opts['on_pages'], 1, false )
		);
	}

	public function render_hide_home(): void {
		$opts = $this->get_options();
		printf(
			'<label class="rpb-toggle"><input type="checkbox" id="rpb_hide_home" name="rpb_settings[hide_home]" value="1" %s><span class="rpb-toggle-track"></span></label>',
			checked( $opts['hide_home'], 1, false )
		);
	}

	public function render_indicator_type(): void {
		$opts = $this->get_options();
		$cur  = $opts['indicator_type'];
		$types = [
			'none'    => __( 'Aucun', 'read-ninja' ),
			'percent' => __( 'Pourcentage', 'read-ninja' ),
			'time'    => __( 'Temps restant', 'read-ninja' ),
			'both'    => __( 'Les deux', 'read-ninja' ),
		];
		foreach ( $types as $val => $label ) {
			printf(
				'<label style="margin-right:16px"><input type="radio" name="rpb_settings[indicator_type]" value="%s" %s onchange="rpbToggleIndicatorFields(this.value)"> %s</label>',
				esc_attr( $val ),
				checked( $cur, $val, false ),
				esc_html( $label )
			);
		}
		echo '<p class="description" style="margin-top:4px">';
		echo esc_html( '"42%" ' ) . esc_html__( 'ou', 'read-ninja' ) . esc_html( ' "3 min restantes" ' ) . esc_html__( 'ou', 'read-ninja' ) . esc_html( ' "42% · 3 min restantes"' );
		echo '</p>';
	}

	public function render_indicator_position(): void {
		$opts = $this->get_options();
		$cur  = $opts['indicator_position'];
		$positions = [
			'inside' => __( 'Flottant dans la barre', 'read-ninja' ),
			'left'   => __( 'À gauche', 'read-ninja' ),
			'right'  => __( 'À droite', 'read-ninja' ),
		];
		echo '<select id="rpb_indicator_position" name="rpb_settings[indicator_position]">';
		foreach ( $positions as $val => $label ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $val ),
				selected( $cur, $val, false ),
				esc_html( $label )
			);
		}
		echo '</select>';
		echo '<p class="description">' . esc_html__( "« Flottant » suit le bord de la barre remplie. « Gauche » et « Droite » fixent l'indicateur aux extrémités.", 'read-ninja' ) . '</p>';
	}

	public function render_indicator_size(): void {
		$opts   = $this->get_options();
		$val    = $opts['indicator_size'];
		$isAuto = $val === 'auto';
		?>
		<label style="margin-right:16px">
			<input type="radio" name="rpb_indicator_size_mode" value="auto"
				<?php checked( $isAuto ); ?>
				onchange="rpbToggleIndicatorSize(this.value)">
			<?php esc_html_e( 'Automatique', 'read-ninja' ); ?>
		</label>
		<label>
			<input type="radio" name="rpb_indicator_size_mode" value="custom"
				<?php checked( ! $isAuto ); ?>
				onchange="rpbToggleIndicatorSize(this.value)">
			<?php esc_html_e( 'Taille fixe', 'read-ninja' ); ?>
		</label>
		<span id="rpb_indicator_size_input_wrap" style="margin-left:12px;<?php echo $isAuto ? 'display:none' : ''; ?>">
			<input type="number" id="rpb_indicator_size_input" min="8" max="32" step="1"
				value="<?php echo esc_attr( $isAuto ? '12' : (string) $val ); ?>"
				style="width:80px"
				oninput="document.getElementById('rpb_indicator_size').value=this.value"> px
		</span>
		<input type="hidden" id="rpb_indicator_size" name="rpb_settings[indicator_size]"
			value="<?php echo esc_attr( $val ); ?>">
		<p class="description">
			<?php esc_html_e( "« Automatique » adapte la taille du texte à la hauteur de la barre. « Taille fixe » applique une valeur en pixels (8–32).", 'read-ninja' ); ?>
		</p>
		<?php
	}

	public function render_indicator_color(): void {
		$opts  = $this->get_options();
		$val   = $opts['indicator_color'];
		$isAuto = $val === 'auto';
		?>
		<label style="margin-right:16px">
			<input type="radio" name="rpb_indicator_color_mode" value="auto"
				<?php checked( $isAuto ); ?>
				onchange="rpbToggleIndicatorColor(this.value)">
			<?php esc_html_e( 'Automatique', 'read-ninja' ); ?>
		</label>
		<label>
			<input type="radio" name="rpb_indicator_color_mode" value="custom"
				<?php checked( ! $isAuto ); ?>
				onchange="rpbToggleIndicatorColor(this.value)">
			<?php esc_html_e( 'Couleur fixe', 'read-ninja' ); ?>
		</label>
		<span id="rpb_indicator_color_picker_wrap" style="margin-left:12px;<?php echo $isAuto ? 'display:none' : ''; ?>">
			<input type="color" id="rpb_indicator_color_picker"
				value="<?php echo esc_attr( $isAuto ? '#ffffff' : $val ); ?>"
				oninput="document.getElementById('rpb_indicator_color').value=this.value">
		</span>
		<input type="hidden" id="rpb_indicator_color" name="rpb_settings[indicator_color]"
			value="<?php echo esc_attr( $val ); ?>">
		<p class="description">
			<?php esc_html_e( "« Automatique » adapte la couleur du texte en fonction de l'arrière-plan pour rester lisible.", 'read-ninja' ); ?>
		</p>
		<?php
	}

	public function render_wpm(): void {
		$opts = $this->get_options();
		printf(
			'<input type="number" id="rpb_wpm" name="rpb_settings[wpm]" min="100" max="400" value="%s" style="width:80px">',
			esc_attr( (string) $opts['wpm'] )
		);
		echo '<p class="description">' . esc_html__( 'Moyenne adulte : 200 mots/min', 'read-ninja' ) . '</p>';
	}

	public function render_time_format(): void {
		$opts   = $this->get_options();
		$cur    = $opts['time_format'];
		$prefix = trim( $opts['time_prefix'] );
		$suffix = trim( $opts['time_suffix'] );
		$pre    = $prefix !== '' ? $prefix . ' ' : '';
		$suf    = $suffix !== '' ? ' ' . $suffix : '';
		$ex_min = $pre . '3' . $suf;
		$ex_ms  = $pre . '3:12' . $suf;
		?>
		<fieldset id="rpb_time_format">
			<label style="display:block;margin-bottom:6px">
				<input type="radio" name="rpb_settings[time_format]" value="minutes" <?php checked( $cur, 'minutes' ); ?>>
				<?php esc_html_e( 'Minutes uniquement', 'read-ninja' ); ?>  —  <code id="rpb_time_format_example_min"><?php echo esc_html( $ex_min ); ?></code>
			</label>
			<label style="display:block;margin-bottom:6px">
				<input type="radio" name="rpb_settings[time_format]" value="minutes_seconds" <?php checked( $cur, 'minutes_seconds' ); ?>>
				<?php esc_html_e( 'Minutes et secondes', 'read-ninja' ); ?>  —  <code id="rpb_time_format_example_ms"><?php echo esc_html( $ex_ms ); ?></code>
			</label>
		</fieldset>
		<?php
	}

	public function render_time_prefix(): void {
		$opts = $this->get_options();
		printf(
			'<input type="text" id="rpb_time_prefix" name="rpb_settings[time_prefix]" value="%s" style="width:180px" oninput="rpbUpdateTimeFormatExamples()">',
			esc_attr( $opts['time_prefix'] )
		);
		echo '<p class="description">' . esc_html__( 'Texte affiché avant le temps. Exemple : « Encore » → « Encore 3 min restantes ».', 'read-ninja' ) . '</p>';
	}

	public function render_time_suffix(): void {
		$opts = $this->get_options();
		printf(
			'<input type="text" id="rpb_time_suffix" name="rpb_settings[time_suffix]" value="%s" style="width:180px" oninput="rpbUpdateTimeFormatExamples()">',
			esc_attr( $opts['time_suffix'] )
		);
		echo '<p class="description">' . esc_html__( 'Texte affiché après le temps. Exemple : « min restantes ».', 'read-ninja' ) . '</p>';
	}

	// --- Private helpers --------------------------------------------------

	private function add_field( string $id, string $label, string $section, string $callback ): void {
		add_settings_field(
			'rpb_' . $id,
			$label,
			[ $this, $callback ],
			'read-ninja',
			$section
		);
	}
}
