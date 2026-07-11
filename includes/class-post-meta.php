<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class READNINJA_Post_Meta {

	const META_KEY = '_readninja_display_override';

	public function init(): void {
		add_action( 'add_meta_boxes',              [ $this, 'add_metabox' ] );
		add_action( 'save_post',                   [ $this, 'save_metabox' ] );
		add_action( 'init',                        [ $this, 'register_meta' ], 1 );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_gutenberg' ] );
	}

	public function register_meta(): void {
		$args = [
			'type'              => 'string',
			'single'            => true,
			'default'           => '',
			'show_in_rest'      => true,
			'revisions_enabled' => false,
			'auth_callback'     => function () {
				return current_user_can( 'edit_posts' );
			},
		];
		foreach ( [ '', 'post', 'page' ] as $post_type ) {
			register_post_meta( $post_type, self::META_KEY, $args );
			register_post_meta( $post_type, '_readninja_color_override', $args );
			register_post_meta( $post_type, '_readninja_color_type', $args );
			register_post_meta( $post_type, '_readninja_gradient_color_start', $args );
			register_post_meta( $post_type, '_readninja_gradient_color_end', $args );
			register_post_meta( $post_type, '_readninja_height_override', $args );
			register_post_meta( $post_type, '_readninja_disable_threshold', $args );
			register_post_meta( $post_type, '_readninja_background_color', $args );
			register_post_meta( $post_type, '_readninja_position_override', $args );
			register_post_meta( $post_type, '_readninja_custom_selector', $args );
		}
	}

	public function add_metabox(): void {
		add_meta_box(
			'readninja-display-override',
			__( 'Read Ninja - Reading Progress Bar', 'read-ninja' ),
			[ $this, 'render_metabox' ],
			[ 'post', 'page' ],
			'side',
			'default'
		);
	}

	public function render_metabox( \WP_Post $post ): void {
		wp_nonce_field( 'readninja_override_nonce', 'readninja_override_nonce_field' );

		$value = get_post_meta( $post->ID, self::META_KEY, true );
		$opts  = [
			''     => __( 'Hériter des réglages globaux', 'read-ninja' ),
			'show' => __( 'Toujours afficher', 'read-ninja' ),
			'hide' => __( 'Toujours masquer', 'read-ninja' ),
		];
		echo '<select name="' . esc_attr( self::META_KEY ) . '" style="width:100%">';
		foreach ( $opts as $val => $label ) {
			echo '<option value="' . esc_attr( $val ) . '"' . selected( $value, $val, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';

		// --- Fond de la barre ----------------------------------------------------
		$bg_color = get_post_meta( $post->ID, '_readninja_background_color', true );
		?>
		<p style="margin:12px 0 0">
			<label style="font-weight:500;font-size:12px;display:block;margin-bottom:4px">
				<?php esc_html_e( 'Fond de la barre', 'read-ninja' ); ?>
			</label>
			<input type="hidden" id="readninja_bg_meta" name="_readninja_background_color"
				value="<?php echo esc_attr( $bg_color ); ?>">
			<input type="color" id="readninja_bg_meta_picker"
				value="<?php echo esc_attr( $bg_color ?: '#ffffff' ); ?>"
				style="<?php echo $bg_color ? '' : 'opacity:0.4'; ?>"
				oninput="document.getElementById('readninja_bg_meta').value=this.value;this.style.opacity='1'">
			<button type="button"
				style="font-size:11px;background:none;border:none;cursor:pointer;text-decoration:underline;color:#757575;padding:0 0 0 6px"
				onclick="document.getElementById('readninja_bg_meta').value='';var p=document.getElementById('readninja_bg_meta_picker');p.value='#ffffff';p.style.opacity='0.4'">
				<?php esc_html_e( 'Réinitialiser', 'read-ninja' ); ?>
			</button>
		</p>

		<?php
		// --- Position override ---------------------------------------------------
		$pos_override = get_post_meta( $post->ID, '_readninja_position_override', true );
		$custom_sel   = get_post_meta( $post->ID, '_readninja_custom_selector', true );
		?>
		<p style="margin:12px 0 0">
			<label style="font-weight:500;font-size:12px;display:block;margin-bottom:4px">
				<?php esc_html_e( 'Position', 'read-ninja' ); ?>
			</label>
			<select name="_readninja_position_override" style="width:100%"
				onchange="document.getElementById('readninja-meta-custom-sel').style.display=this.value==='custom'?'block':'none'">
				<option value="" <?php selected( $pos_override, '' ); ?>><?php esc_html_e( 'Hériter de la configuration', 'read-ninja' ); ?></option>
				<option value="top" <?php selected( $pos_override, 'top' ); ?>><?php esc_html_e( 'Haut (fixe)', 'read-ninja' ); ?></option>
				<option value="bottom" <?php selected( $pos_override, 'bottom' ); ?>><?php esc_html_e( 'Bas (fixe)', 'read-ninja' ); ?></option>
				<option value="custom" <?php selected( $pos_override, 'custom' ); ?>><?php esc_html_e( 'Personnalisée', 'read-ninja' ); ?></option>
			</select>
		</p>
		<p id="readninja-meta-custom-sel" style="margin:6px 0 0;<?php echo $pos_override === 'custom' ? '' : 'display:none'; ?>">
			<input type="text" name="_readninja_custom_selector"
				value="<?php echo esc_attr( $custom_sel ); ?>"
				placeholder="<?php esc_attr_e( '.ma-classe ou #mon-id', 'read-ninja' ); ?>"
				style="width:100%;font-size:12px">
		</p>

		<?php
		if ( readninja_is_pro() ) {
			$disable_threshold = get_post_meta( $post->ID, '_readninja_disable_threshold', true );
			echo '<p style="margin:12px 0 0"><label><input type="checkbox" name="_readninja_disable_threshold" value="1" '
				. checked( $disable_threshold, '1', false )
				. '> ' . esc_html__( 'Désactiver le threshold trigger pour cet article', 'read-ninja' ) . '</label></p>';
		}
	}

	public function save_metabox( int $post_id ): void {
		if (
			! isset( $_POST['readninja_override_nonce_field'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['readninja_override_nonce_field'] ) ), 'readninja_override_nonce' ) ||
			( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ||
			! current_user_can( 'edit_post', $post_id )
		) {
			return;
		}

		$allowed   = [ '', 'show', 'hide' ];
		$raw_value = isset( $_POST[ self::META_KEY ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::META_KEY ] ) ) : '';
		$value     = in_array( $raw_value, $allowed, true ) ? $raw_value : '';
		update_post_meta( $post_id, self::META_KEY, $value );

		// Background color
		$bg = sanitize_hex_color( wp_unslash( $_POST['_readninja_background_color'] ?? '' ) ) ?? '';
		update_post_meta( $post_id, '_readninja_background_color', $bg );

		// Position override
		$pos_allowed = [ '', 'top', 'bottom', 'custom' ];
		$raw_pos     = isset( $_POST['_readninja_position_override'] ) ? sanitize_text_field( wp_unslash( $_POST['_readninja_position_override'] ) ) : '';
		$pos         = in_array( $raw_pos, $pos_allowed, true ) ? $raw_pos : '';
		update_post_meta( $post_id, '_readninja_position_override', $pos );

		// Custom selector (seulement si position = custom)
		$sel = $pos === 'custom' ? sanitize_text_field( wp_unslash( $_POST['_readninja_custom_selector'] ?? '' ) ) : '';
		update_post_meta( $post_id, '_readninja_custom_selector', $sel );

		if ( readninja_is_pro() ) {
			update_post_meta(
				$post_id,
				'_readninja_disable_threshold',
				isset( $_POST['_readninja_disable_threshold'] ) ? '1' : ''
			);
		}
	}

	public function enqueue_gutenberg(): void {
		wp_enqueue_script(
			'readninja-gutenberg-sidebar',
			READNINJA_URL . 'gutenberg/build/index.js',
			[ 'wp-plugins', 'wp-editor', 'wp-components', 'wp-data', 'wp-i18n', 'wp-element' ],
			READNINJA_VERSION,
			true
		);
		wp_localize_script( 'readninja-gutenberg-sidebar', 'readninjaSettings', [
			'isPro' => readninja_is_pro() ? 1 : 0,
		] );
		wp_set_script_translations(
			'readninja-gutenberg-sidebar',
			'read-ninja',
			READNINJA_PATH . 'languages'
		);
	}
}
