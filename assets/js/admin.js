/**
 * Surcharge — admin settings enhancements (vanilla JS, no jQuery).
 *
 * Progressive enhancement only; the form works without JS (the first row is
 * always present and saved on submit). JS adds:
 *  - Repeatable fee rows (add / remove) with automatic name/id re-indexing.
 *  - Accessible help tooltips via the native Popover API, with an inline
 *    fallback span when Popover is unsupported.
 *  - A live amount suffix that shows the currency symbol or "%" per fee type.
 *
 * Enqueued deferred in the footer; runs after DOM parse.
 */
( function () {
	'use strict';

	var root = document.querySelector( '.surcharge-admin' );
	if ( ! root ) {
		return;
	}
	root.classList.add( 'is-enhanced' );

	var list = root.querySelector( '#surcharge-fees' );
	var addBtn = root.querySelector( '#surcharge-add-fee' );
	var template = root.querySelector( '#surcharge-fee-template' );
	var currency = list ? list.getAttribute( 'data-currency' ) || '' : '';

	/* ---- Tooltips ---------------------------------------------------- */

	var supportsPopover =
		typeof HTMLElement !== 'undefined' &&
		HTMLElement.prototype.hasOwnProperty( 'popover' );

	function wireTooltips( scope ) {
		scope.querySelectorAll( '.surcharge-help' ).forEach( function ( trigger ) {
			if ( trigger.dataset.scWired ) {
				return;
			}
			trigger.dataset.scWired = '1';

			var tip = document.getElementById(
				trigger.getAttribute( 'aria-describedby' ) || ''
			);
			if ( ! tip || ! supportsPopover ) {
				return;
			}

			var show = function () {
				try {
					position( trigger, tip );
					tip.showPopover();
				} catch ( e ) {}
			};
			var hide = function () {
				try {
					tip.hidePopover();
				} catch ( e ) {}
			};
			trigger.addEventListener( 'mouseenter', show );
			trigger.addEventListener( 'focus', show );
			trigger.addEventListener( 'mouseleave', hide );
			trigger.addEventListener( 'blur', hide );
			trigger.addEventListener( 'keydown', function ( e ) {
				if ( e.key === 'Escape' ) {
					hide();
				}
			} );
		} );
	}

	function position( trigger, tip ) {
		var r = trigger.getBoundingClientRect();
		tip.style.position = 'fixed';
		tip.style.margin = '0';
		tip.style.insetBlockStart = Math.round( r.bottom + 8 ) + 'px';
		tip.style.insetInlineStart =
			Math.round(
				Math.min( r.left, document.documentElement.clientWidth - 300 )
			) + 'px';
	}

	/* ---- Amount suffix (currency / %) ------------------------------- */

	function updateSuffix( row ) {
		var type = row.querySelector( '.surcharge-fee__type' );
		var suffix = row.querySelector( '.surcharge-fee__amount-suffix' );
		if ( ! type || ! suffix ) {
			return;
		}
		suffix.textContent = type.value === 'percent' ? '%' : currency;
	}

	function wireRow( row ) {
		var type = row.querySelector( '.surcharge-fee__type' );
		if ( type && ! type.dataset.scWired ) {
			type.dataset.scWired = '1';
			type.addEventListener( 'change', function () {
				updateSuffix( row );
			} );
		}
		updateSuffix( row );

		var remove = row.querySelector( '.surcharge-fee__remove' );
		if ( remove && ! remove.dataset.scWired ) {
			remove.dataset.scWired = '1';
			remove.addEventListener( 'click', function () {
				removeRow( row );
			} );
		}

		wireTooltips( row );
	}

	/* ---- Re-indexing ------------------------------------------------ */

	function reindex() {
		if ( ! list ) {
			return;
		}
		var rows = list.querySelectorAll( '.surcharge-fee' );
		rows.forEach( function ( row, i ) {
			row.setAttribute( 'data-index', String( i ) );
			row.querySelectorAll( '[name]' ).forEach( function ( el ) {
				el.name = el.name.replace(
					/\[fees\]\[\d+\]/,
					'[fees][' + i + ']'
				);
			} );
			// Keep id/for pairs unique per row to preserve label association.
			row.querySelectorAll( '[id]' ).forEach( function ( el ) {
				var newId = el.id.replace(
					/surcharge-fee-\d+/,
					'surcharge-fee-' + i
				);
				if ( newId !== el.id ) {
					var label = row.querySelector(
						'[for="' + cssEscape( el.id ) + '"]'
					);
					var desc = row.querySelector(
						'[aria-describedby="' + cssEscape( el.id ) + '"]'
					);
					el.id = newId;
					if ( label ) {
						label.setAttribute( 'for', newId );
					}
					if ( desc ) {
						desc.setAttribute( 'aria-describedby', newId );
					}
				}
			} );
		} );
	}

	function cssEscape( value ) {
		if ( window.CSS && CSS.escape ) {
			return CSS.escape( value );
		}
		return value.replace( /([^\w-])/g, '\\$1' );
	}

	/* ---- Add / remove ----------------------------------------------- */

	function addRow() {
		if ( ! list || ! template ) {
			return;
		}
		var holder = document.createElement( 'div' );
		holder.innerHTML = template.innerHTML;
		var row = holder.querySelector( '.surcharge-fee' );
		if ( ! row ) {
			return;
		}
		// Clear values from the cloned template.
		row.querySelectorAll( 'input' ).forEach( function ( el ) {
			if ( el.type === 'checkbox' ) {
				el.checked = el.name.indexOf( '[enabled]' ) !== -1;
			} else {
				el.value = '';
			}
		} );
		list.appendChild( row );
		reindex();
		wireRow( row );
		var first = row.querySelector( 'input[type="text"]' );
		if ( first ) {
			first.focus();
		}
	}

	function removeRow( row ) {
		if ( ! list ) {
			return;
		}
		var rows = list.querySelectorAll( '.surcharge-fee' );
		if ( rows.length <= 1 ) {
			// Keep at least one row; just clear it instead of removing.
			row.querySelectorAll( 'input' ).forEach( function ( el ) {
				if ( el.type === 'checkbox' ) {
					el.checked = el.name.indexOf( '[enabled]' ) !== -1;
				} else {
					el.value = '';
				}
			} );
			updateSuffix( row );
			return;
		}
		row.parentNode.removeChild( row );
		reindex();
	}

	if ( addBtn ) {
		addBtn.addEventListener( 'click', addRow );
	}

	// Wire the initial rows.
	if ( list ) {
		list.querySelectorAll( '.surcharge-fee' ).forEach( wireRow );
	}
	wireTooltips( root );
} )();
