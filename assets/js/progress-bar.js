'use strict';

(function () {
	const color  = rpbSettings.colorOverride  || rpbSettings.color;
	const height = rpbSettings.heightOverride || rpbSettings.height;

	// Gradient mode — only active when the Pro plugin injects rpbSettings.gradient.
	const grad = (rpbSettings.gradient && rpbSettings.gradient.enabled) ? rpbSettings.gradient : null;

	let background;
	if (grad) {
		const angle = grad.direction === 'ltr' ? 90
		            : grad.direction === 'rtl' ? 270
		            : (parseInt(grad.angle, 10) || 90);
		background = 'linear-gradient(' + angle + 'deg,' + grad.colorStart + ',' + grad.colorEnd + ')';
	} else {
		background = color;
	}

	const position = rpbSettings.position || 'top';
	const zIndex   = rpbSettings.zIndex;

	// ---- Track (background pleine largeur) --------------------------------
	// Créé uniquement si backgroundColor est défini. Il s'affiche derrière la
	// barre de progression et donne la couleur de fond sur toute la largeur.
	let track = null;
	if (rpbSettings.backgroundColor) {
		track = document.createElement('div');
		track.id = 'rpb-bar-track';
		track.style.cssText = [
			'position:fixed',
			position === 'bottom' ? 'bottom:0' : 'top:0',
			'left:0',
			'width:100%',
			'height:' + height + 'px',
			'background:' + rpbSettings.backgroundColor,
			'opacity:' + (rpbSettings.opacity / 100),
			'z-index:' + zIndex,
			'pointer-events:none',
		].join(';');
	}

	// ---- Barre de progression (fill) --------------------------------------
	const bar = document.createElement('div');
	bar.id = 'rpb-bar';

	const cssProps = [
		'left:0',
		'width:0%',
		'height:' + height + 'px',
		'background:' + background,
		'opacity:' + (rpbSettings.opacity / 100),
		// z-index légèrement supérieur au track pour s'afficher par-dessus
		'z-index:' + (track ? String(parseInt(zIndex, 10) + 1) : zIndex),
		'transition:width 0.1s linear',
		'pointer-events:none',
	];
	if (grad) { cssProps.push('background-size:100vw 100%'); }

	// ---- Positionnement + insertion dans le DOM ---------------------------
	if (position === 'custom' && rpbSettings.customSelector) {
		const target = document.querySelector(rpbSettings.customSelector);
		if (target) {
			// Position sticky dans l'élément cible
			const stickyOverrides = 'position:sticky;top:0;bottom:';
			if (track) {
				track.style.cssText = track.style.cssText
					.replace('position:fixed', 'position:sticky')
					.replace(/top:0|bottom:0/, 'top:0');
				target.insertBefore(track, target.firstChild);
			}
			cssProps.push('position:sticky', 'top:0');
			bar.style.cssText = cssProps.join(';');
			target.insertBefore(bar, track ? track.nextSibling : target.firstChild);
		} else {
			// Fallback : position fixe en haut
			if (track) { document.body.prepend(track); }
			cssProps.push('position:fixed', 'top:0');
			bar.style.cssText = cssProps.join(';');
			document.body.prepend(bar);
		}
	} else {
		cssProps.push('position:fixed');
		cssProps.push(position === 'bottom' ? 'bottom:0' : 'top:0');
		bar.style.cssText = cssProps.join(';');
		if (track) { document.body.prepend(track); }
		document.body.prepend(bar);
	}

	// ---- RTL support ---------------------------------------------------------
	var isRtl = rpbSettings.isRtl === 'true'
		|| document.documentElement.dir === 'rtl'
		|| document.body.classList.contains('rtl');

	if (isRtl) {
		bar.style.left = 'auto';
		bar.style.right = '0';
		if (grad) { bar.style.backgroundPosition = 'right'; }
	}

	// ---- Sticky header offset -----------------------------------------------
	function rpbGetStickyOffset() {
		if (rpbSettings.autoSticky !== 'true') {
			return parseInt(rpbSettings.stickyOffset, 10) || 0;
		}
		var offset = 0;
		var elements = document.querySelectorAll('header, [class*="header"], nav, [class*="nav"]');
		elements.forEach(function (el) {
			var style = window.getComputedStyle(el);
			if ((style.position === 'fixed' || style.position === 'sticky')
				&& el.offsetHeight > 0
				&& el.getBoundingClientRect().top <= 1) {
				offset = Math.max(offset, el.offsetHeight);
			}
		});
		return offset;
	}

	function rpbApplyStickyOffset() {
		if (position === 'top') {
			var off = rpbGetStickyOffset();
			bar.style.top = off + 'px';
			if (track) { track.style.top = off + 'px'; }
			if (indicator && (rpbSettings.indicatorPosition === 'left' || rpbSettings.indicatorPosition === 'right')) {
				indicator.style.top = off + 'px';
			}
		}
	}
	window.addEventListener('resize', rpbApplyStickyOffset, { passive: true });

	// ---- Device display -----------------------------------------------------
	function rpbCheckDevice() {
		var isMobile = window.matchMedia(
			'(max-width: ' + (parseInt(rpbSettings.mobileBreakpoint, 10) - 1) + 'px)'
		).matches;
		var d = rpbSettings.deviceDisplay || 'all';
		if (d === 'desktop_only' && isMobile) return false;
		if (d === 'mobile_only' && !isMobile) return false;
		if (d === 'hidden_mobile' && isMobile) return false;
		return true;
	}

	var rpbDeviceVisible = rpbCheckDevice();
	if (!rpbDeviceVisible) {
		bar.style.display = 'none';
		if (track) { track.style.display = 'none'; }
	}

	var rpbMQ = window.matchMedia(
		'(max-width: ' + (parseInt(rpbSettings.mobileBreakpoint, 10) - 1) + 'px)'
	);
	rpbMQ.addEventListener('change', function () {
		rpbDeviceVisible = rpbCheckDevice();
		if (!rpbDeviceVisible) {
			bar.style.display = 'none';
			if (track) { track.style.display = 'none'; }
		} else {
			bar.style.display = 'block';
			if (track) { track.style.display = 'block'; }
			update();
		}
	});

	// ---- Indicateur de progression (badge flottant) -----------------------
	const indicatorType = rpbSettings.indicatorType || 'none';
	let indicator = null;

	if (indicatorType !== 'none') {
		// Force minimum height so the badge text is readable.
		// If size is fixed and large, the bar must accommodate it.
		var sizeSetting = rpbSettings.indicatorSize || 'auto';
		var fixedSize = sizeSetting === 'auto' ? 0 : (parseInt(sizeSetting, 10) || 0);
		const minH = Math.max(14, fixedSize + 4);
		const curH = parseInt(height, 10);
		if (curH < minH) {
			bar.style.height = minH + 'px';
			if (track) { track.style.height = minH + 'px'; }
		}

		var indPosition = rpbSettings.indicatorPosition || 'inside';
		// Compute indicator font size: 'auto' = scaled to bar height, else fixed px
		var effectiveBarH = Math.max(parseInt(bar.style.height, 10) || parseInt(height, 10), 14);
		var indFontSize;
		if (sizeSetting === 'auto') {
			indFontSize = Math.max(10, Math.min(effectiveBarH - 4, 18));
		} else {
			indFontSize = Math.max(8, Math.min(fixedSize, 32));
		}

		indicator = document.createElement('span');
		indicator.id = 'rpb-indicator';

		var effectiveBarHpx = Math.max(parseInt(bar.style.height, 10) || parseInt(height, 10), 14);
		var indStyles = [
			'font-size:' + indFontSize + 'px',
			'font-weight:700',
			'line-height:1',
			'color:#fff',
			'pointer-events:none',
			'white-space:nowrap',
			'text-shadow:0 1px 2px rgba(0,0,0,0.4)',
			'transition:opacity 0.2s ease',
			'display:flex',
			'align-items:center',
			'height:' + effectiveBarHpx + 'px',
		];

		if (indPosition === 'inside') {
			// Floats inside the bar fill, follows the right edge of the fill.
			// Uses flex centering and full bar height instead of top:50%/translateY
			// so that vertical centering works whether the bar is at top or bottom.
			indStyles.push('position:absolute');
			indStyles.push(isRtl ? 'left:6px' : 'right:6px');
			indStyles.push('top:0');
			indicator.style.cssText = indStyles.join(';');
			bar.appendChild(indicator);
		} else {
			// Pinned to the left or right edge of the viewport, at the bar's vertical position
			var pinSide = indPosition; // 'left' or 'right'
			indStyles.push('position:fixed');
			indStyles.push(position === 'bottom' ? 'bottom:0' : 'top:0');
			indStyles.push(pinSide + ':8px');
			indStyles.push('z-index:' + (parseInt(zIndex, 10) + 2));
			indicator.style.cssText = indStyles.join(';');
			document.body.appendChild(indicator);
		}
	}

	// Apply sticky offset now that indicator exists (so its top can be set too)
	rpbApplyStickyOffset();

	// Luminance helper — returns 0..1
	function rpbLuminance(hex) {
		if (!hex || hex.charAt(0) !== '#') { return 0; }
		hex = hex.replace('#', '');
		if (hex.length === 3) { hex = hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2]; }
		var r = parseInt(hex.substring(0,2), 16);
		var g = parseInt(hex.substring(2,4), 16);
		var b = parseInt(hex.substring(4,6), 16);
		return (0.299 * r + 0.587 * g + 0.114 * b) / 255;
	}

	// Fixed or auto indicator color
	const fixedIndicatorColor = (rpbSettings.indicatorColor && rpbSettings.indicatorColor !== 'auto')
		? rpbSettings.indicatorColor : null;

	// Decide indicator text color based on what's behind it
	function rpbIndicatorColor(progress) {
		if (fixedIndicatorColor) { return fixedIndicatorColor; }

		// Auto mode: adapt to what's behind the indicator.
		var barWidth = bar.offsetWidth;
		var indWidth = indicator ? indicator.offsetWidth : 0;
		var onFill   = barWidth > (indWidth + 12);

		var bgHex;
		if (onFill) {
			if (grad) {
				var l1 = rpbLuminance(grad.colorStart);
				var l2 = rpbLuminance(grad.colorEnd);
				return ((l1 + l2) / 2) > 0.6 ? '#000' : '#fff';
			}
			bgHex = color;
		} else {
			bgHex = rpbSettings.backgroundColor || '';
			if (!bgHex) {
				var bodyBg = window.getComputedStyle(document.body).backgroundColor;
				var m = bodyBg.match(/\d+/g);
				if (m && m.length >= 3) {
					var lum = (0.299 * (+m[0]) + 0.587 * (+m[1]) + 0.114 * (+m[2])) / 255;
					return lum > 0.6 ? '#000' : '#fff';
				}
				return '#000';
			}
		}
		return rpbLuminance(bgHex) > 0.6 ? '#000' : '#fff';
	}

	// ---- Logique de progression -------------------------------------------
	const MIN_SCROLLABLE = 100;
	const progressSource = rpbSettings.progressSource || 'content';

	// Find the content element for content-based progress
	let contentEl = null;
	if (progressSource === 'content') {
		contentEl = document.querySelector('.entry-content, .post-content, article .content, article');
	}

	const getProgress = () => {
		if (progressSource === 'content' && contentEl) {
			const rect = contentEl.getBoundingClientRect();
			const articleTop = rect.top + window.scrollY;
			const scrollableArticle = rect.height - window.innerHeight;
			if (scrollableArticle < MIN_SCROLLABLE) return -1;
			const scrolled = window.scrollY - articleTop;
			if (scrolled < 0) return 0;
			return Math.min(100, (scrolled / scrollableArticle) * 100);
		}
		const scrollable = document.documentElement.scrollHeight - window.innerHeight;
		if (scrollable < MIN_SCROLLABLE) return -1;
		return Math.min(100, (window.scrollY / scrollable) * 100);
	};

	// Sequential mode — only active when the Pro plugin injects these keys.
	const seq = rpbSettings.sequential ? {
		blockSize: Math.max(1, parseInt(rpbSettings.blockSize, 10) || 10),
		animated:  !!rpbSettings.animated,
		duration:  parseInt(rpbSettings.duration, 10) || 200,
	} : null;

	let readyForTransition = false;
	let ticking = false;

	const update = () => {
		if (!rpbDeviceVisible) return;

		const raw = getProgress();
		const hidden = raw < 0;
		bar.style.display = hidden ? 'none' : 'block';
		if (track) { track.style.display = hidden ? 'none' : 'block'; }

		if (raw >= 0) {
			const display = seq
				? Math.floor(raw / seq.blockSize) * seq.blockSize
				: raw;

			if (seq) {
				bar.style.transition = (seq.animated && readyForTransition)
					? 'width ' + seq.duration + 'ms ease'
					: 'none';
			}

			bar.style.width = display + '%';

			// Update indicator badge
			if (indicator) {
				if (raw >= 98) {
					indicator.style.opacity = '0';
				} else {
					indicator.style.opacity = '1';
					var indClr = rpbIndicatorColor(raw);
					indicator.style.color = indClr;
					indicator.style.textShadow = rpbLuminance(indClr) > 0.5
						? '0 1px 2px rgba(0,0,0,0.4)'
						: '0 1px 2px rgba(255,255,255,0.4)';
					var pct = Math.round(raw) + '%';
					if (indicatorType === 'percent') {
						indicator.textContent = pct;
					} else if (indicatorType === 'time' || indicatorType === 'both') {
						var wc = parseInt(rpbSettings.wordCount, 10) || 0;
						var wpm = parseInt(rpbSettings.wpm, 10) || 200;
						var prefix = (rpbSettings.timePrefix || '').trim();
						var suffix = (rpbSettings.timeSuffix || 'min restantes').trim();
						var totalSecondsLeft = (wc * (1 - raw / 100) / wpm) * 60;
						var core;
						if (totalSecondsLeft < 60) {
							core = '< 1';
						} else if (rpbSettings.timeFormat === 'minutes_seconds') {
							var m = Math.floor(totalSecondsLeft / 60);
							var s = Math.floor(totalSecondsLeft % 60);
							core = m + ':' + (s < 10 ? '0' : '') + s;
						} else {
							core = String(Math.ceil(totalSecondsLeft / 60));
						}
						var timeText = (prefix ? prefix + ' ' : '') + core + (suffix ? ' ' + suffix : '');
						indicator.textContent = indicatorType === 'both'
							? pct + ' \u00b7 ' + timeText
							: timeText;
					}
				}
			}
		}

		ticking = false;
		readyForTransition = true;
	};

	window.addEventListener('scroll', () => {
		if (!ticking) { requestAnimationFrame(update); ticking = true; }
	}, { passive: true });

	update();
})();
