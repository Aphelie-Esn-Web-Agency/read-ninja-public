'use strict';

(function () {
	var s = rpbAdminSettings.current;

	// ---- Build preview container -------------------------------------------

	var container = document.createElement('div');
	container.id = 'rpb-preview';
	container.style.cssText = [
		'position:relative',
		'overflow:hidden',
		'background:#f0f0f0',
		'padding:20px',
		'border-radius:4px',
		'height:140px',
	].join(';');

	var bar = document.createElement('div');
	bar.id = 'rpb-preview-bar';

	// Free indicator badge (inside bar)
	var badge = document.createElement('span');
	badge.id = 'rpb-preview-badge';
	badge.style.display = 'none';
	bar.appendChild(badge);

	container.appendChild(bar);

	// Fake content lines
	[40, 100, 70, 90, 55].forEach(function (w) {
		var line = document.createElement('div');
		line.style.cssText = 'background:#ccc;height:8px;width:' + w + '%;margin:8px 0;border-radius:2px';
		container.appendChild(line);
	});

	var placeholder = document.getElementById('rpb-preview-placeholder');
	if (placeholder) { placeholder.appendChild(container); }

	// ---- Helpers -----------------------------------------------------------

	// Read field value from DOM; fall back to `fallback` if field absent.
	function fieldVal(name, fallback) {
		var el = document.querySelector('[name="' + name + '"]');
		return el ? el.value : (fallback !== undefined ? fallback : '');
	}

	// Read checkbox state from DOM; fall back to `fallback` if field absent.
	function fieldChecked(name, fallback) {
		var el = document.querySelector('[name="' + name + '"]');
		return el ? el.checked : !!fallback;
	}

	// Read checked radio value from DOM.
	function fieldRadio(name, fallback) {
		var el = document.querySelector('[name="' + name + '"]:checked');
		return el ? el.value : (fallback || '');
	}

	// ---- Main render -------------------------------------------------------

	function apply() {

		// -- Free settings --
		var color    = fieldVal('rpb_settings[color]',    s.color    || '#1D9E75');
		var height   = Math.max(2, parseFloat(fieldVal('rpb_settings[height]',  s.height  || 4)));
		var position = fieldVal('rpb_settings[position]', s.position || 'top');
		var opacity  = parseFloat(fieldVal('rpb_settings[opacity]', s.opacity  || 100)) / 100;
		// 'custom' treated as top for preview purposes
		var isTop    = position !== 'bottom';

		// -- Gradient (Pro) --
		var grad    = s.gradient || {};
		var gradOn  = fieldChecked('rpb_pro_gradient[enabled]', grad.enabled);
		var background;

		if (gradOn) {
			var c1  = fieldVal('rpb_pro_gradient[color_start]', grad.color_start || '#3b82f6');
			var c2  = fieldVal('rpb_pro_gradient[color_end]',   grad.color_end   || '#8b5cf6');
			var dir = fieldVal('rpb_pro_gradient[direction]',   grad.direction   || 'ltr');
			var ang = dir === 'ltr' ? 90
			        : dir === 'rtl' ? 270
			        : (parseInt(fieldVal('rpb_pro_gradient[angle]', grad.angle || 90), 10) || 90);
			background = 'linear-gradient(' + ang + 'deg,' + c1 + ',' + c2 + ')';
		} else {
			background = color;
		}

		// -- Free indicator badge: read settings BEFORE building bar styles
		// so the bar height can grow to fit a large fixed text size.
		var indType   = fieldRadio('rpb_settings[indicator_type]', s.indicator_type || 'none');
		var indActive = indType !== 'none';

		var sizeMode = fieldRadio('rpb_indicator_size_mode',
			(s.indicator_size && s.indicator_size !== 'auto') ? 'custom' : 'auto');
		var sizeInputEl = document.getElementById('rpb_indicator_size_input');
		var indFixedSize = 0;
		if (sizeMode === 'custom') {
			indFixedSize = sizeInputEl
				? (parseInt(sizeInputEl.value, 10) || 0)
				: (parseInt(s.indicator_size, 10) || 0);
		}

		// Force minimum bar height when indicator is active
		if (indActive) {
			var minH = Math.max(14, indFixedSize + 4);
			if (height < minH) { height = minH; }
		}

		// Apply bar styles individually so we don't risk a stale cssText reset.
		// `height` is the LAST property set so it always wins, and uses setProperty
		// with priority 'important' to defeat any external CSS rule.
		bar.style.position      = 'absolute';
		bar.style.left          = '0';
		bar.style.right         = '';
		bar.style.top           = isTop ? '0' : '';
		bar.style.bottom        = isTop ? '' : '0';
		bar.style.width         = '60%';
		bar.style.background    = background;
		bar.style.opacity       = String(opacity);
		bar.style.borderRadius  = '0 2px 2px 0';
		bar.style.transition    = 'background 0.15s,height 0.15s,opacity 0.15s';
		bar.style.backgroundSize = gradOn ? ((container.offsetWidth || 222) + 'px 100%') : '';
		bar.style.setProperty('height', height + 'px', 'important');

		if (indActive) {
			// Compute effective font size
			var indFontSize;
			if (sizeMode === 'auto') {
				indFontSize = Math.max(10, Math.min(height - 4, 18));
			} else {
				indFontSize = Math.max(8, Math.min(indFixedSize, 32));
			}

			// Determine contrast color from bar fill
			function previewLuminance(c) {
				if (!c || c.charAt(0) !== '#') { return 0; }
				var h = c.replace('#', '');
				if (h.length === 3) { h = h[0]+h[0]+h[1]+h[1]+h[2]+h[2]; }
				return (0.299*parseInt(h.substring(0,2),16) + 0.587*parseInt(h.substring(2,4),16) + 0.114*parseInt(h.substring(4,6),16)) / 255;
			}
			// Fixed or auto indicator color
			var indColorVal = fieldVal('rpb_settings[indicator_color]', s.indicator_color || 'auto');
			var badgeColor;
			if (indColorVal !== 'auto') {
				badgeColor = indColorVal;
			} else if (gradOn) {
				var c1l = previewLuminance(fieldVal('rpb_pro_gradient[color_start]', '#3b82f6'));
				var c2l = previewLuminance(fieldVal('rpb_pro_gradient[color_end]', '#8b5cf6'));
				badgeColor = ((c1l + c2l) / 2) > 0.6 ? '#000' : '#fff';
			} else {
				badgeColor = previewLuminance(color) > 0.6 ? '#000' : '#fff';
			}

			// Build sample text — simulate 60% read on a ~1000 word article
			var samplePct = '60%';
			var indPrefix = (fieldVal('rpb_settings[time_prefix]', s.time_prefix || '') || '').trim();
			var indSuffix = (fieldVal('rpb_settings[time_suffix]', s.time_suffix || 'min restantes') || '').trim();
			var indFormat = fieldRadio('rpb_settings[time_format]', s.time_format || 'minutes');
			// Simulated remaining: 400 words left at user WPM
			var indWpm    = Math.max(100, parseInt(fieldVal('rpb_settings[wpm]', s.wpm || 200), 10) || 200);
			var secLeft   = (400 / indWpm) * 60;
			var sampleCore;
			if (secLeft < 60) {
				sampleCore = '< 1';
			} else if (indFormat === 'minutes_seconds') {
				var mm = Math.floor(secLeft / 60);
				var ss = Math.floor(secLeft % 60);
				sampleCore = mm + ':' + (ss < 10 ? '0' : '') + ss;
			} else {
				sampleCore = String(Math.ceil(secLeft / 60));
			}
			var sampleTime = (indPrefix ? indPrefix + ' ' : '') + sampleCore + (indSuffix ? ' ' + indSuffix : '');

			var badgeText;
			if (indType === 'percent')    { badgeText = samplePct; }
			else if (indType === 'time')  { badgeText = sampleTime; }
			else if (indType === 'both')  { badgeText = samplePct + ' \u00b7 ' + sampleTime; }

			badge.textContent = badgeText;
			var badgeShadow = previewLuminance(badgeColor) > 0.5
				? '0 1px 2px rgba(0,0,0,0.4)'
				: '0 1px 2px rgba(255,255,255,0.4)';

			// Reparent badge based on indicator position
			var indPos = fieldVal('rpb_settings[indicator_position]', s.indicator_position || 'inside');
			if (indPos === 'inside') {
				if (badge.parentNode !== bar) { bar.appendChild(badge); }
			} else {
				if (badge.parentNode !== container) { container.appendChild(badge); }
			}

			var badgeStyles = [
				'position:absolute',
				'font-size:' + indFontSize + 'px',
				'font-weight:700',
				'line-height:1',
				'color:' + badgeColor,
				'pointer-events:none',
				'white-space:nowrap',
				'text-shadow:' + badgeShadow,
				'display:flex',
				'align-items:center',
				'height:' + height + 'px',
			];
			if (indPos === 'inside') {
				badgeStyles.push('right:6px');
				badgeStyles.push(isTop ? 'top:0' : 'bottom:0');
			} else {
				// Pinned to the preview container edge, at bar's vertical position
				badgeStyles.push(indPos + ':8px');
				badgeStyles.push(isTop ? 'top:0' : 'bottom:0');
			}
			badge.style.cssText = badgeStyles.join(';');
		} else {
			badge.style.display = 'none';
		}

	}

	apply();

	// ---- Event delegation on the settings form -----------------------------
	// Catches every input/change on every field, including dynamically inserted ones,
	// and re-renders the preview. Robust to field ordering and Pro extensions.
	var form = document.querySelector('.rpb-settings-form') || document.querySelector('form[action*="options.php"]');
	if (form) {
		form.addEventListener('input',  apply);
		form.addEventListener('change', apply);
	}

	// Also listen on the indicator color picker (no name attribute)
	var indPicker = document.getElementById('rpb_indicator_color_picker');
	if (indPicker) {
		indPicker.addEventListener('input', apply);
	}
	var indSizeInput = document.getElementById('rpb_indicator_size_input');
	if (indSizeInput) {
		indSizeInput.addEventListener('input', apply);
	}

})();
