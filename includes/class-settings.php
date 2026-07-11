<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class READNINJA_Settings {

	const OPTION_KEY = 'readninja_settings';

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
		register_setting( 'readninja_settings_group', self::OPTION_KEY, [
			'sanitize_callback' => [ $this, 'sanitize' ],
		] );

		add_settings_section( 'readninja_style',   __( 'Style', 'read-ninja' ),     '__return_false', 'read-ninja' );
		add_settings_section( 'readninja_display', __( 'Affichage', 'read-ninja' ), '__return_false', 'read-ninja' );

		$this->add_field( 'color',            __( 'Couleur', 'read-ninja' ),              'readninja_style',   'render_color' );
		$this->add_field( 'height',           __( 'Hauteur', 'read-ninja' ),              'readninja_style',   'render_height' );
		$this->add_field( 'position',         __( 'Position', 'read-ninja' ),             'readninja_style',   'render_position' );
		$this->add_field( 'custom_selector',  __( 'Sélecteur CSS cible', 'read-ninja' ),  'readninja_style',   'render_custom_selector' );
		$this->add_field( 'background_color', __( 'Fond de la barre', 'read-ninja' ),     'readninja_style',   'render_background_color' );
		$this->add_field( 'opacity',          __( 'Opacité', 'read-ninja' ),              'readninja_style',   'render_opacity' );
		$this->add_field( 'zindex',           __( 'Z-index', 'read-ninja' ),              'readninja_style',   'render_zindex' );
		$this->add_field( 'sticky_offset',   __( 'Décalage sticky header', 'read-ninja' ), 'readninja_style', 'render_sticky_offset' );
		$this->add_field( 'on_posts',         __( 'Articles', 'read-ninja' ),             'readninja_display', 'render_on_posts' );
		$this->add_field( 'on_pages',         __( 'Pages', 'read-ninja' ),               'readninja_display', 'render_on_pages' );
		$this->add_field( 'hide_home',        __( "Masquer sur l'accueil", 'read-ninja' ), 'readninja_display', 'render_hide_home' );
		$this->add_field( 'device_display',   __( 'Affichage sur les appareils', 'read-ninja' ), 'readninja_display', 'render_device_display' );

		add_settings_section( 'readninja_progress', __( 'Progression', 'read-ninja' ), '__return_false', 'read-ninja' );
		$this->add_field( 'progress_source', __( 'Source de progression', 'read-ninja' ), 'readninja_progress', 'render_progress_source' );

		add_settings_section( 'readninja_indicator', __( 'Indicateur de progression', 'read-ninja' ), '__return_false', 'read-ninja' );
		$this->add_field( 'indicator_type',     __( "Type d'indicateur", 'read-ninja' ),        'readninja_indicator', 'render_indicator_type' );
		$this->add_field( 'indicator_position', __( "Position de l'indicateur", 'read-ninja' ), 'readninja_indicator', 'render_indicator_position' );
		$this->add_field( 'indicator_size',     __( "Taille du texte", 'read-ninja' ),         'readninja_indicator', 'render_indicator_size' );
		$this->add_field( 'indicator_color',    __( "Couleur de l'indicateur", 'read-ninja' ),  'readninja_indicator', 'render_indicator_color' );
		$this->add_field( 'wpm',                __( 'Mots par minute', 'read-ninja' ),          'readninja_indicator', 'render_wpm' );
		$this->add_field( 'time_format',        __( 'Format du temps', 'read-ninja' ),          'readninja_indicator', 'render_time_format' );
		$this->add_field( 'time_prefix',        __( 'Texte avant le temps', 'read-ninja' ),     'readninja_indicator', 'render_time_prefix' );
		$this->add_field( 'time_suffix',        __( 'Texte après le temps', 'read-ninja' ),     'readninja_indicator', 'render_time_suffix' );
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
		// not from the parent slug. Match any hook that ends with `_page_readninja-analytics`
		// so we don't break if the parent menu_title changes.
		$is_settings  = ( $hook === 'toplevel_page_read-ninja' );
		$is_analytics = ( substr( $hook, -strlen( '_page_readninja-analytics' ) ) === '_page_readninja-analytics' );
		if ( ! $is_settings && ! $is_analytics ) {
			return;
		}

		wp_enqueue_style( 'readninja-admin', READNINJA_URL . 'admin/css/readninja-admin.css', [], READNINJA_VERSION );

		if ( $is_settings ) {
			// Preview enqueued first — readninja-admin-ui en dépend pour garantir l'ordre d'exécution.
			wp_enqueue_script( 'readninja-admin-preview', READNINJA_URL . 'admin/js/settings-preview.js', [], READNINJA_VERSION, true );

			/**
			 * Permet au plugin Pro d'ajouter ses propres réglages (gradient…)
			 * dans l'objet readninjaAdminSettings passé au JS de preview.
			 *
			 * @param array $settings  Réglages de base du plugin gratuit.
			 */
			$preview_settings = (array) apply_filters( 'readninja_admin_preview_settings', $this->get_options() );
			wp_localize_script( 'readninja-admin-preview', 'readninjaAdminSettings', [ 'current' => $preview_settings ] );

			wp_enqueue_script( 'readninja-admin-ui', READNINJA_URL . 'admin/js/readninja-admin-ui.js', [ 'readninja-admin-preview' ], READNINJA_VERSION, true );
			wp_enqueue_script( 'readninja-admin-settings-toggles', READNINJA_URL . 'assets/js/admin/admin-settings-toggles.js', [ 'readninja-admin-ui' ], READNINJA_VERSION, true );
		} else {
			// Page Analytics : readninja-admin-ui sans dépendance preview.
			wp_enqueue_script( 'readninja-admin-ui', READNINJA_URL . 'admin/js/readninja-admin-ui.js', [], READNINJA_VERSION, true );
		}

		wp_localize_script( 'readninja-admin-ui', 'readninjaAdminUi', [
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
		$tabs = (array) apply_filters( 'readninja_settings_tabs', [
			'general' => '<span class="dashicons dashicons-admin-settings"></span> ' . __( 'Général', 'read-ninja' ),
		] );

		// Fallback si l'onglet demandé n'existe pas
		if ( ! array_key_exists( $tab, $tabs ) ) {
			$tab = 'general';
		}
		?>
		<div class="wrap readninja-admin-page">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<div class="readninja-page-header">
				<div class="readninja-page-header__icon">
					<span class="dashicons dashicons-chart-line"></span>
				</div>
				<div class="readninja-page-header__info">
					<div class="readninja-page-header__title">Read Ninja - Reading Progress Bar</div>
					<div class="readninja-page-header__badges">
						<span class="readninja-badge readninja-badge--version">v<?php echo esc_html( READNINJA_VERSION ); ?></span>
						<?php if ( readninja_is_pro() ) : ?>
						<span class="readninja-badge readninja-badge--pro">
							<span class="dashicons dashicons-yes-alt" style="font-size:12px;width:12px;height:12px;line-height:1"></span>
							<?php esc_html_e( 'Pro actif', 'read-ninja' ); ?>
						</span>
						<?php else : ?>
						<span class="readninja-badge readninja-badge--free"><?php esc_html_e( 'Version gratuite', 'read-ninja' ); ?></span>
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
			<div class="readninja-settings-layout">

				<aside class="readninja-preview-sidebar">
					<div class="readninja-preview-sidebar__card">
						<div class="readninja-preview-sidebar__title">
							<span class="dashicons dashicons-visibility"></span>
							<?php esc_html_e( 'Aperçu en direct', 'read-ninja' ); ?>
						</div>
						<div id="readninja-preview-placeholder"></div>
						<p class="readninja-preview-sidebar__hint">
							<?php esc_html_e( 'La barre se met à jour en temps réel.', 'read-ninja' ); ?>
						</p>
					</div>
				</aside>

				<div class="readninja-settings-main">
					<form method="post" action="options.php" class="readninja-settings-form">
						<?php
						settings_fields( 'readninja_settings_group' );
						do_settings_sections( 'read-ninja' );
						submit_button( __( 'Enregistrer les réglages', 'read-ninja' ) );
						?>
					</form>

					<?php
					/**
					 * Point d'accroche pour les sections Pro dans l'onglet général.
					 * Le plugin Pro peut y ajouter du contenu.
					 */
					do_action( 'readninja_settings_section_pro' );
					?>
				</div><!-- .readninja-settings-main -->

			</div><!-- .readninja-settings-layout -->

			<?php else : ?>
				<?php
				/**
				 * Contenu d'un onglet custom ajouté via readninja_settings_tabs.
				 * Le plugin Pro hookera readninja_render_tab_advanced.
				 */
				do_action( 'readninja_render_tab_' . $tab );
				?>
			<?php endif; ?>
		</div>
		<?php
	}

	// --- Render methods ---------------------------------------------------

	public function render_color(): void {
		$opts = $this->get_options();
		printf(
			'<input type="color" id="readninja_color" name="readninja_settings[color]" value="%s">',
			esc_attr( $opts['color'] )
		);
	}

	public function render_height(): void {
		$opts = $this->get_options();
		printf(
			'<input type="range" id="readninja_height" name="readninja_settings[height]" min="2" max="20" step="1" value="%1$s" oninput="this.nextElementSibling.value=this.value"> <output>%1$s</output> px',
			esc_attr( (string) $opts['height'] )
		);
	}

	public function render_position(): void {
		$opts = $this->get_options();
		$cur  = $opts['position'];
		?>
		<select id="readninja_position" name="readninja_settings[position]"
			onchange="readninjaToggleCustomSelector(this.value)">
			<option value="top"    <?php selected( $cur, 'top' ); ?>><?php esc_html_e( 'Haut', 'read-ninja' ); ?></option>
			<option value="bottom" <?php selected( $cur, 'bottom' ); ?>><?php esc_html_e( 'Bas', 'read-ninja' ); ?></option>
			<option value="custom" <?php selected( $cur, 'custom' ); ?>><?php esc_html_e( 'Personnalisée', 'read-ninja' ); ?></option>
		</select>
		<?php
	}

	public function render_custom_selector(): void {
		$opts = $this->get_options();
		?>
		<input type="text" id="readninja_custom_selector" name="readninja_settings[custom_selector]"
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
		<input type="hidden" id="readninja_background_color" name="readninja_settings[background_color]"
			value="<?php echo esc_attr( $val ); ?>">
		<input type="color" id="readninja_background_color_picker"
			value="<?php echo esc_attr( $val ?: '#ffffff' ); ?>"
			style="<?php echo $val ? '' : 'opacity:0.4'; ?>"
			oninput="document.getElementById('readninja_background_color').value=this.value;this.style.opacity='1'">
		<button type="button"
			style="margin-left:8px;font-size:12px;background:none;border:none;cursor:pointer;text-decoration:underline;color:#757575;padding:0"
			onclick="document.getElementById('readninja_background_color').value='';var p=document.getElementById('readninja_background_color_picker');p.value='#ffffff';p.style.opacity='0.4';window.readninjaUpdatePreview&&window.readninjaUpdatePreview()">
			<?php esc_html_e( 'Réinitialiser', 'read-ninja' ); ?>
		</button>
		<?php
	}

	public function render_opacity(): void {
		$opts = $this->get_options();
		printf(
			'<input type="range" id="readninja_opacity" name="readninja_settings[opacity]" min="10" max="100" step="5" value="%1$s" oninput="this.nextElementSibling.value=this.value"> <output>%1$s</output> %%',
			esc_attr( (string) $opts['opacity'] )
		);
	}

	public function render_zindex(): void {
		$opts = $this->get_options();
		printf(
			'<input type="number" id="readninja_zindex" name="readninja_settings[zindex]" min="1" max="99999" value="%s">',
			esc_attr( (string) $opts['zindex'] )
		);
	}

	public function render_sticky_offset(): void {
		$opts   = $this->get_options();
		$val    = (int) $opts['sticky_offset'];
		$isAuto = $val === 0;
		?>
		<label style="margin-right:16px">
			<input type="radio" name="readninja_sticky_offset_mode" value="auto"
				<?php checked( $isAuto ); ?>
				onchange="readninjaToggleStickyOffset(this.value)">
			<?php esc_html_e( 'Automatique', 'read-ninja' ); ?>
		</label>
		<label>
			<input type="radio" name="readninja_sticky_offset_mode" value="manual"
				<?php checked( ! $isAuto ); ?>
				onchange="readninjaToggleStickyOffset(this.value)">
			<?php esc_html_e( 'Valeur fixe', 'read-ninja' ); ?>
		</label>
		<span id="readninja_sticky_offset_wrap" style="margin-left:12px;<?php echo $isAuto ? 'display:none' : ''; ?>">
			<input type="number" id="readninja_sticky_offset_input" min="1" max="500" step="1"
				value="<?php echo esc_attr( $isAuto ? '60' : (string) $val ); ?>"
				style="width:80px"
				oninput="document.getElementById('readninja_sticky_offset').value=this.value"> px
		</span>
		<input type="hidden" id="readninja_sticky_offset" name="readninja_settings[sticky_offset]"
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
		echo '<select id="readninja_device_display" name="readninja_settings[device_display]">';
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
				'<label style="margin-right:16px"><input type="radio" name="readninja_settings[progress_source]" value="%s" %s> %s</label>',
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
			'<label class="readninja-toggle"><input type="checkbox" id="readninja_on_posts" name="readninja_settings[on_posts]" value="1" %s><span class="readninja-toggle-track"></span></label>',
			checked( $opts['on_posts'], 1, false )
		);
	}

	public function render_on_pages(): void {
		$opts = $this->get_options();
		printf(
			'<label class="readninja-toggle"><input type="checkbox" id="readninja_on_pages" name="readninja_settings[on_pages]" value="1" %s><span class="readninja-toggle-track"></span></label>',
			checked( $opts['on_pages'], 1, false )
		);
	}

	public function render_hide_home(): void {
		$opts = $this->get_options();
		printf(
			'<label class="readninja-toggle"><input type="checkbox" id="readninja_hide_home" name="readninja_settings[hide_home]" value="1" %s><span class="readninja-toggle-track"></span></label>',
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
				'<label style="margin-right:16px"><input type="radio" name="readninja_settings[indicator_type]" value="%s" %s onchange="readninjaToggleIndicatorFields(this.value)"> %s</label>',
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
		echo '<select id="readninja_indicator_position" name="readninja_settings[indicator_position]">';
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
			<input type="radio" name="readninja_indicator_size_mode" value="auto"
				<?php checked( $isAuto ); ?>
				onchange="readninjaToggleIndicatorSize(this.value)">
			<?php esc_html_e( 'Automatique', 'read-ninja' ); ?>
		</label>
		<label>
			<input type="radio" name="readninja_indicator_size_mode" value="custom"
				<?php checked( ! $isAuto ); ?>
				onchange="readninjaToggleIndicatorSize(this.value)">
			<?php esc_html_e( 'Taille fixe', 'read-ninja' ); ?>
		</label>
		<span id="readninja_indicator_size_input_wrap" style="margin-left:12px;<?php echo $isAuto ? 'display:none' : ''; ?>">
			<input type="number" id="readninja_indicator_size_input" min="8" max="32" step="1"
				value="<?php echo esc_attr( $isAuto ? '12' : (string) $val ); ?>"
				style="width:80px"
				oninput="document.getElementById('readninja_indicator_size').value=this.value"> px
		</span>
		<input type="hidden" id="readninja_indicator_size" name="readninja_settings[indicator_size]"
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
			<input type="radio" name="readninja_indicator_color_mode" value="auto"
				<?php checked( $isAuto ); ?>
				onchange="readninjaToggleIndicatorColor(this.value)">
			<?php esc_html_e( 'Automatique', 'read-ninja' ); ?>
		</label>
		<label>
			<input type="radio" name="readninja_indicator_color_mode" value="custom"
				<?php checked( ! $isAuto ); ?>
				onchange="readninjaToggleIndicatorColor(this.value)">
			<?php esc_html_e( 'Couleur fixe', 'read-ninja' ); ?>
		</label>
		<span id="readninja_indicator_color_picker_wrap" style="margin-left:12px;<?php echo $isAuto ? 'display:none' : ''; ?>">
			<input type="color" id="readninja_indicator_color_picker"
				value="<?php echo esc_attr( $isAuto ? '#ffffff' : $val ); ?>"
				oninput="document.getElementById('readninja_indicator_color').value=this.value">
		</span>
		<input type="hidden" id="readninja_indicator_color" name="readninja_settings[indicator_color]"
			value="<?php echo esc_attr( $val ); ?>">
		<p class="description">
			<?php esc_html_e( "« Automatique » adapte la couleur du texte en fonction de l'arrière-plan pour rester lisible.", 'read-ninja' ); ?>
		</p>
		<?php
	}

	public function render_wpm(): void {
		$opts = $this->get_options();
		printf(
			'<input type="number" id="readninja_wpm" name="readninja_settings[wpm]" min="100" max="400" value="%s" style="width:80px">',
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
		<fieldset id="readninja_time_format">
			<label style="display:block;margin-bottom:6px">
				<input type="radio" name="readninja_settings[time_format]" value="minutes" <?php checked( $cur, 'minutes' ); ?>>
				<?php esc_html_e( 'Minutes uniquement', 'read-ninja' ); ?>  —  <code id="readninja_time_format_example_min"><?php echo esc_html( $ex_min ); ?></code>
			</label>
			<label style="display:block;margin-bottom:6px">
				<input type="radio" name="readninja_settings[time_format]" value="minutes_seconds" <?php checked( $cur, 'minutes_seconds' ); ?>>
				<?php esc_html_e( 'Minutes et secondes', 'read-ninja' ); ?>  —  <code id="readninja_time_format_example_ms"><?php echo esc_html( $ex_ms ); ?></code>
			</label>
		</fieldset>
		<?php
	}

	public function render_time_prefix(): void {
		$opts = $this->get_options();
		printf(
			'<input type="text" id="readninja_time_prefix" name="readninja_settings[time_prefix]" value="%s" style="width:180px" oninput="readninjaUpdateTimeFormatExamples()">',
			esc_attr( $opts['time_prefix'] )
		);
		echo '<p class="description">' . esc_html__( 'Texte affiché avant le temps. Exemple : « Encore » → « Encore 3 min restantes ».', 'read-ninja' ) . '</p>';
	}

	public function render_time_suffix(): void {
		$opts = $this->get_options();
		printf(
			'<input type="text" id="readninja_time_suffix" name="readninja_settings[time_suffix]" value="%s" style="width:180px" oninput="readninjaUpdateTimeFormatExamples()">',
			esc_attr( $opts['time_suffix'] )
		);
		echo '<p class="description">' . esc_html__( 'Texte affiché après le temps. Exemple : « min restantes ».', 'read-ninja' ) . '</p>';
	}

	// --- Private helpers --------------------------------------------------

	private function add_field( string $id, string $label, string $section, string $callback ): void {
		add_settings_field(
			'readninja_' . $id,
			$label,
			[ $this, $callback ],
			'read-ninja',
			$section
		);
	}
}
