import { registerPlugin }                                                          from '@wordpress/plugins';
import { PluginDocumentSettingPanel }                                               from '@wordpress/editor';
import { SelectControl, ToggleControl, RangeControl, PanelRow, TextControl, __experimentalDivider as Divider } from '@wordpress/components';
import { useSelect, useDispatch }                                                   from '@wordpress/data';
import { __ }                                                                       from '@wordpress/i18n';

const META_KEY              = '_rpb_display_override';
const COLOR_KEY             = '_rpb_color_override';
const COLOR_TYPE_KEY        = '_rpb_color_type';
const GRAD_START_KEY        = '_rpb_gradient_color_start';
const GRAD_END_KEY          = '_rpb_gradient_color_end';
const HEIGHT_KEY            = '_rpb_height_override';
const DISABLE_THRESHOLD_KEY = '_rpb_disable_threshold';
const BG_COLOR_KEY          = '_rpb_background_color';
const POSITION_KEY          = '_rpb_position_override';
const CUSTOM_SEL_KEY        = '_rpb_custom_selector';

const PICKER_STYLE = { width: '40px', height: '30px', border: 'none', cursor: 'pointer', padding: '0' };
const RESET_STYLE  = { fontSize: '11px', color: '#757575', background: 'none', border: 'none', cursor: 'pointer', padding: '0', textDecoration: 'underline' };
const LABEL_STYLE  = { fontSize: '11px', color: '#1e1e1e' };

const RPBPanel = () => {
	const postType = useSelect( s => s( 'core/editor' ).getCurrentPostType(), [] );
	const postId   = useSelect( s => s( 'core/editor' ).getCurrentPostId(), [] );

	const record = useSelect(
		s => s( 'core' ).getEditedEntityRecord( 'postType', postType, postId ),
		[ postType, postId ]
	);

	const metaValue        = record?.meta?.[ META_KEY ]              ?? '';
	const colorType        = record?.meta?.[ COLOR_TYPE_KEY ]        ?? '';
	const colorValue       = record?.meta?.[ COLOR_KEY ]             ?? '';
	const gradStartValue   = record?.meta?.[ GRAD_START_KEY ]        ?? '';
	const gradEndValue     = record?.meta?.[ GRAD_END_KEY ]          ?? '';
	const heightValue      = record?.meta?.[ HEIGHT_KEY ]            ?? '';
	const disableThreshold = record?.meta?.[ DISABLE_THRESHOLD_KEY ] ?? '';
	const bgColorValue     = record?.meta?.[ BG_COLOR_KEY ]          ?? '';
	const positionValue    = record?.meta?.[ POSITION_KEY ]          ?? '';
	const customSelValue   = record?.meta?.[ CUSTOM_SEL_KEY ]        ?? '';

	const { editEntityRecord } = useDispatch( 'core' );

	const setMeta = ( patch ) => editEntityRecord( 'postType', postType, postId, {
		meta: { ...record?.meta, ...patch },
	} );

	const onChange           = ( val ) => setMeta( { [ META_KEY ]: val } );
	const onColorChange      = ( val ) => setMeta( { [ COLOR_KEY ]: val } );
	const onGradStartChange  = ( val ) => setMeta( { [ GRAD_START_KEY ]: val } );
	const onGradEndChange    = ( val ) => setMeta( { [ GRAD_END_KEY ]: val } );
	const onHeightChange     = ( val ) => setMeta( { [ HEIGHT_KEY ]: val != null ? String( val ) : '' } );
	const onDisableThreshold = ( bool ) => setMeta( { [ DISABLE_THRESHOLD_KEY ]: bool ? '1' : '' } );
	const onBgColorChange    = ( val ) => setMeta( { [ BG_COLOR_KEY ]: val } );
	const onPositionChange   = ( val ) => setMeta( { [ POSITION_KEY ]: val, ...( val !== 'custom' ? { [ CUSTOM_SEL_KEY ]: '' } : {} ) } );
	const onCustomSelChange  = ( val ) => setMeta( { [ CUSTOM_SEL_KEY ]: val } );

	const onColorTypeChange = ( val ) => {
		const patch = { [ COLOR_TYPE_KEY ]: val };
		// Pre-fill default values when switching mode so the picker is never empty.
		if ( val === 'solid'    && ! colorValue    ) patch[ COLOR_KEY ]     = '#1D9E75';
		if ( val === 'gradient' && ! gradStartValue ) patch[ GRAD_START_KEY ] = '#3b82f6';
		if ( val === 'gradient' && ! gradEndValue   ) patch[ GRAD_END_KEY ]   = '#8b5cf6';
		setMeta( patch );
	};

	return (
		<PluginDocumentSettingPanel
			name="rpb-display-override"
			title={ __( 'Read Ninja - Reading Progress Bar', 'read-ninja' ) }
		>
			<SelectControl
				value={ metaValue }
				onChange={ onChange }
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				options={ [
					{ value: '',     label: __( 'Hériter des réglages globaux', 'read-ninja' ) },
					{ value: 'show', label: __( 'Toujours afficher',            'read-ninja' ) },
					{ value: 'hide', label: __( 'Toujours masquer',             'read-ninja' ) },
				] }
			/>

			{ /* ---- Fond de la barre (gratuit) ---- */ }
			<PanelRow>
				<label htmlFor="rpb-bg-color" style={ LABEL_STYLE }>
					{ __( 'Fond de la barre', 'read-ninja' ) }
				</label>
				<input
					id="rpb-bg-color"
					type="color"
					value={ bgColorValue || '#ffffff' }
					onChange={ e => onBgColorChange( e.target.value ) }
					style={ { ...PICKER_STYLE, opacity: bgColorValue ? 1 : 0.4 } }
				/>
			</PanelRow>
			{ bgColorValue && (
				<PanelRow>
					<button
						onClick={ () => setMeta( { [ BG_COLOR_KEY ]: '' } ) }
						style={ RESET_STYLE }
					>
						{ __( 'Réinitialiser le fond', 'read-ninja' ) }
					</button>
				</PanelRow>
			) }

			{ /* ---- Position override (gratuit) ---- */ }
			<SelectControl
				label={ __( 'Position', 'read-ninja' ) }
				value={ positionValue }
				onChange={ onPositionChange }
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				options={ [
					{ value: '',       label: __( 'Hériter de la configuration', 'read-ninja' ) },
					{ value: 'top',    label: __( 'Haut (fixe)',                 'read-ninja' ) },
					{ value: 'bottom', label: __( 'Bas (fixe)',                  'read-ninja' ) },
					{ value: 'custom', label: __( 'Personnalisée',               'read-ninja' ) },
				] }
			/>
			{ positionValue === 'custom' && (
				<TextControl
					label={ __( 'Sélecteur CSS cible', 'read-ninja' ) }
					value={ customSelValue }
					onChange={ onCustomSelChange }
					placeholder={ __( '.ma-classe ou #mon-id', 'read-ninja' ) }
					__nextHasNoMarginBottom
				/>
			) }

			{ window.rpbSettings?.isPro && (
				<>
					<Divider />

					{ /* ---- Couleur ---- */ }
					<SelectControl
						label={ __( 'Couleur', 'read-ninja' ) }
						value={ colorType }
						onChange={ onColorTypeChange }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						options={ [
							{ value: '',         label: __( 'Hériter de la configuration', 'read-ninja' ) },
							{ value: 'solid',    label: __( 'Couleur fixe personnalisée',  'read-ninja' ) },
							{ value: 'gradient', label: __( 'Dégradé personnalisé',        'read-ninja' ) },
						] }
					/>

					{ colorType === 'solid' && (
						<>
							<PanelRow>
								<label htmlFor="rpb-color-override" style={ LABEL_STYLE }>
									{ __( 'Couleur', 'read-ninja' ) }
								</label>
								<input
									id="rpb-color-override"
									type="color"
									value={ colorValue || '#1D9E75' }
									onChange={ e => onColorChange( e.target.value ) }
									style={ PICKER_STYLE }
								/>
							</PanelRow>
							<PanelRow>
								<button
									onClick={ () => setMeta( { [ COLOR_TYPE_KEY ]: '', [ COLOR_KEY ]: '' } ) }
									style={ RESET_STYLE }
								>
									{ __( 'Réinitialiser la couleur', 'read-ninja' ) }
								</button>
							</PanelRow>
						</>
					) }

					{ colorType === 'gradient' && (
						<>
							<PanelRow>
								<label htmlFor="rpb-grad-start" style={ LABEL_STYLE }>
									{ __( 'Couleur de départ', 'read-ninja' ) }
								</label>
								<input
									id="rpb-grad-start"
									type="color"
									value={ gradStartValue || '#3b82f6' }
									onChange={ e => onGradStartChange( e.target.value ) }
									style={ PICKER_STYLE }
								/>
							</PanelRow>
							<PanelRow>
								<label htmlFor="rpb-grad-end" style={ LABEL_STYLE }>
									{ __( 'Couleur de fin', 'read-ninja' ) }
								</label>
								<input
									id="rpb-grad-end"
									type="color"
									value={ gradEndValue || '#8b5cf6' }
									onChange={ e => onGradEndChange( e.target.value ) }
									style={ PICKER_STYLE }
								/>
							</PanelRow>
							<PanelRow>
								<button
									onClick={ () => setMeta( { [ COLOR_TYPE_KEY ]: '', [ GRAD_START_KEY ]: '', [ GRAD_END_KEY ]: '' } ) }
									style={ RESET_STYLE }
								>
									{ __( 'Réinitialiser le dégradé', 'read-ninja' ) }
								</button>
							</PanelRow>
						</>
					) }

					{ /* ---- Hauteur ---- */ }
					<RangeControl
						label={ __( 'Hauteur personnalisée (px)', 'read-ninja' ) }
						value={ heightValue ? parseInt( heightValue ) : undefined }
						onChange={ val => onHeightChange( val ) }
						min={ 2 }
						max={ 20 }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					{ heightValue && (
						<PanelRow>
							<button
								onClick={ () => onHeightChange( '' ) }
								style={ RESET_STYLE }
							>
								{ __( 'Réinitialiser la hauteur', 'read-ninja' ) }
							</button>
						</PanelRow>
					) }

					<Divider />

					{ /* ---- Threshold ---- */ }
					<ToggleControl
						label={ __( 'Désactiver le threshold trigger', 'read-ninja' ) }
						help={ __( 'Empêche le déclenchement automatique sur cet article.', 'read-ninja' ) }
						checked={ Boolean( disableThreshold ) }
						onChange={ onDisableThreshold }
						__nextHasNoMarginBottom
					/>
				</>
			) }
		</PluginDocumentSettingPanel>
	);
};

registerPlugin( 'rpb-display-override', { render: RPBPanel } );
