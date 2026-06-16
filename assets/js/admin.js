/**
 * Surcharge — admin settings enhancements (vanilla JS, no jQuery).
 *
 * Progressive enhancement only; the form works without JS (the first row is
 * always present and saved on submit). JS adds repeatable fee rows (add /
 * remove) with automatic name/id re-indexing.
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

	function wireRow( row ) {
		var remove = row.querySelector( '.surcharge-fee__remove' );
		if ( remove && ! remove.dataset.scWired ) {
			remove.dataset.scWired = '1';
			remove.addEventListener( 'click', function () {
				removeRow( row );
			} );
		}
		wireReadout( row );
	}

	/* ---- Till readout: mirror the Type select on the receipt stamp -- */

	function syncReadout( row, stamp ) {
		var type = row.querySelector( '.surcharge-fee__type' );
		var readout = row.querySelector( '.surcharge-fee__readout' );
		if ( ! type ) {
			return;
		}
		// Drive the amount help text (fixed vs percentage) off the row.
		row.setAttribute( 'data-type', type.value );
		if ( ! readout ) {
			return;
		}
		var glyphEl = readout.querySelector( '.surcharge-fee__readout-glyph' );
		if ( ! glyphEl ) {
			return;
		}
		var glyph =
			type.value === 'percent'
				? readout.dataset.percentGlyph
				: readout.dataset.fixedGlyph;
		if ( glyphEl.textContent === glyph ) {
			return;
		}
		glyphEl.textContent = glyph;
		if ( stamp ) {
			readout.classList.remove( 'is-stamping' );
			// Reflow so the animation can replay on each change.
			void readout.offsetWidth;
			readout.classList.add( 'is-stamping' );
		}
	}

	function wireReadout( row ) {
		var type = row.querySelector( '.surcharge-fee__type' );
		if ( ! type || type.dataset.scReadout ) {
			return;
		}
		type.dataset.scReadout = '1';
		type.addEventListener( 'change', function () {
			syncReadout( row, true );
		} );
		syncReadout( row, false );
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
					el.id = newId;
					if ( label ) {
						label.setAttribute( 'for', newId );
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

	function clearRow( row ) {
		row.querySelectorAll( 'input' ).forEach( function ( el ) {
			if ( el.type === 'checkbox' ) {
				el.checked = el.name.indexOf( '[enabled]' ) !== -1;
			} else {
				el.value = '';
			}
		} );
	}

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
		clearRow( row );
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
			clearRow( row );
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
} )();
