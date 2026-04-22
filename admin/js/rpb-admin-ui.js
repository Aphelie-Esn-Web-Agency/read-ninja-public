'use strict';

(function () {

    // ---- Save-button feedback (triggered by ?settings-updated=true) ----------
    if ( window.location.search.indexOf( 'settings-updated=true' ) !== -1 ) {
        var btn = document.getElementById( 'submit' );
        if ( btn ) {
            var originalLabel = btn.value;
            btn.value = '\u2713 ' + ( window.rpbAdminUi && window.rpbAdminUi.saved || 'Réglages enregistrés' );
            btn.classList.add( 'rpb-save-success' );
            setTimeout( function () {
                btn.value = originalLabel;
                btn.classList.remove( 'rpb-save-success' );
            }, 2500 );
        }
    }

    // ---- Analytics table: client-side column sort ----------------------------
    var table = document.getElementById( 'rpb-analytics-table' );
    if ( table ) {
        var headers  = table.querySelectorAll( 'thead th[data-col]' );
        var tbody    = table.querySelector( 'tbody' );
        var sortCol  = -1;
        var sortDir  = 1;   // 1 = asc, -1 = desc

        headers.forEach( function ( th ) {
            th.addEventListener( 'click', function () {
                var col = parseInt( th.getAttribute( 'data-col' ), 10 );

                if ( sortCol === col ) {
                    sortDir *= -1;
                } else {
                    sortCol = col;
                    sortDir = 1;
                }

                // Update all header indicators
                headers.forEach( function ( h ) { h.removeAttribute( 'data-sort' ); } );
                th.setAttribute( 'data-sort', sortDir === 1 ? 'asc' : 'desc' );

                // Sort rows
                var rows = Array.prototype.slice.call( tbody.querySelectorAll( 'tr' ) );
                rows.sort( function ( a, b ) {
                    var aCell = a.cells[ col ];
                    var bCell = b.cells[ col ];
                    var aVal  = parseFloat( aCell ? ( aCell.getAttribute( 'data-value' ) || aCell.textContent ) : 0 ) || 0;
                    var bVal  = parseFloat( bCell ? ( bCell.getAttribute( 'data-value' ) || bCell.textContent ) : 0 ) || 0;
                    return ( aVal - bVal ) * sortDir;
                } );

                rows.forEach( function ( row ) { tbody.appendChild( row ); } );
            } );
        } );
    }

    // ---- Settings preview: gradient awareness + color field disabling --------

    var bar = document.getElementById( 'rpb-preview-bar' );
    if ( ! bar ) { return; }

    var s = ( typeof rpbAdminSettings !== 'undefined' && rpbAdminSettings.current ) ? rpbAdminSettings.current : {};

    function getVal( id, fallback ) {
        var el = document.getElementById( id );
        return ( el && el.value !== '' ) ? el.value : ( fallback !== undefined ? String( fallback ) : '' );
    }

    function getChecked( id, fallback ) {
        var el = document.getElementById( id );
        return el ? el.checked : !! fallback;
    }

    function updatePreview() {
        var container = document.getElementById( 'rpb-preview' );

        // --- Free settings ---
        var color    = getVal( 'rpb_color',    s.color    || '#1D9E75' );
        var height   = Math.max( 2, parseFloat( getVal( 'rpb_height', s.height || 4 ) ) );
        var opacity  = parseFloat( getVal( 'rpb_opacity', s.opacity || 100 ) ) / 100;
        var position = getVal( 'rpb_position', s.position || 'top' );

        // --- Fond de la barre : bande pleine largeur derrière la barre ---
        var bgColor = getVal( 'rpb_background_color', s.background_color || '' );
        var track   = document.getElementById( 'rpb-preview-track' );
        if ( bgColor ) {
            if ( ! track && container ) {
                track = document.createElement( 'div' );
                track.id = 'rpb-preview-track';
                container.insertBefore( track, container.firstChild );
            }
            if ( track ) {
                track.style.cssText = [
                    'position:absolute',
                    position !== 'bottom' ? 'top:0' : 'bottom:0',
                    'left:0',
                    'width:100%',
                    'height:' + height + 'px',
                    'background:' + bgColor,
                    'border-radius:0 2px 2px 0',
                    'pointer-events:none',
                ].join( ';' );
            }
        } else {
            if ( track ) { track.remove(); }
        }

        // --- Position custom : notice informatif ---
        var customSel   = getVal( 'rpb_custom_selector', s.custom_selector || '' );
        var notice      = document.getElementById( 'rpb-custom-pos-notice' );
        var placeholder = document.getElementById( 'rpb-preview-placeholder' );
        if ( position === 'custom' ) {
            if ( ! notice && placeholder ) {
                notice = document.createElement( 'p' );
                notice.id = 'rpb-custom-pos-notice';
                notice.style.cssText = 'margin:8px 0 0;font-size:12px;color:#757575;font-style:italic;line-height:1.4';
                placeholder.appendChild( notice );
            }
            if ( notice ) {
                notice.textContent = customSel
                    ? 'La barre sera positionnée dans \u00ab\u00a0' + customSel + '\u00a0\u00bb'
                    : 'Saisir un sélecteur CSS cible.';
            }
        } else {
            if ( notice ) { notice.remove(); }
        }

        // NOTE: bar styling (background, height, opacity, gradient) is owned by
        // settings-preview.js. This script only handles the free background track,
        // the custom-position notice, and disabling the solid color field when
        // gradient is active.
        var grad   = s.gradient || {};
        var gradOn = getChecked( 'rpb_gradient_enabled', grad.enabled );

        // --- Disable/re-enable the solid color field when gradient is active ---
        var colorEl = document.getElementById( 'rpb_color' );
        if ( colorEl ) {
            colorEl.disabled = gradOn;
            var tr = colorEl.closest( 'tr' );
            if ( tr ) { tr.style.opacity = gradOn ? '0.4' : ''; }
        }
    }

    // Expose globally pour le bouton Réinitialiser du fond de barre
    window.rpbUpdatePreview = updatePreview;

    // Run once on load, then on every relevant change
    updatePreview();

    [
        'rpb_color',
        'rpb_height',
        'rpb_opacity',
        'rpb_position',
        'rpb_background_color_picker',
        'rpb_custom_selector',
        'rpb_gradient_enabled',
        'rpb_gradient_color_start',
        'rpb_gradient_color_end',
        'rpb_gradient_direction',
        'rpb_gradient_angle',
    ].forEach( function ( id ) {
        var el = document.getElementById( id );
        if ( ! el ) { return; }
        el.addEventListener( 'input',  updatePreview );
        el.addEventListener( 'change', updatePreview );
    } );

} )();
