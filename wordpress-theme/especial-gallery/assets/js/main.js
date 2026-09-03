/**
 * Especial Gallery — front-end behaviour.
 *
 * No framework and no build step. Everything here is one of two things: a
 * progressive enhancement over markup that already works without it, or the
 * bag, which genuinely has to live in the browser.
 *
 * Why the bag is client-side: WordPress storefronts live or die on page
 * caching, and a server-rendered bag makes every page uncacheable. Holding it
 * in localStorage means the product pages stay static for everyone while the
 * bag stays personal — and the server still prices every order from the
 * catalogue, so nothing here is trusted with money.
 */
( function () {
	'use strict';

	var data = window.egData || {};
	var i18n = data.i18n || {};
	var STORAGE_KEY = 'eg_bag_v1';

	/* ── bag state ──────────────────────────────────────────────────────── */

	var Bag = {
		/**
		 * Reads the bag, tolerating storage that is unavailable or corrupt.
		 * A private window or a full quota should mean "no bag", never a
		 * thrown error that takes the rest of the page down with it.
		 */
		read: function () {
			try {
				var raw = window.localStorage.getItem( STORAGE_KEY );
				var lines = raw ? JSON.parse( raw ) : [];
				return Array.isArray( lines ) ? lines.filter( Bag.valid ) : [];
			} catch ( error ) {
				return [];
			}
		},

		valid: function ( line ) {
			return line
				&& typeof line.slug === 'string'
				&& line.slug.length > 0
				&& typeof line.quantity === 'number'
				&& line.quantity > 0;
		},

		write: function ( lines ) {
			try {
				window.localStorage.setItem( STORAGE_KEY, JSON.stringify( lines ) );
			} catch ( error ) {
				// Private mode or quota exceeded: the bag simply is not persisted.
			}
			document.dispatchEvent( new CustomEvent( 'eg:bagchange', { detail: lines } ) );
		},

		key: function ( slug, variant ) {
			return slug + ':' + variant;
		},

		add: function ( line ) {
			var lines = Bag.read();
			var key = Bag.key( line.slug, line.variant_id );
			var existing = null;

			lines.forEach( function ( candidate ) {
				if ( Bag.key( candidate.slug, candidate.variant_id ) === key ) {
					existing = candidate;
				}
			} );

			if ( existing ) {
				existing.quantity += line.quantity;
			} else {
				lines.push( line );
			}

			Bag.write( lines );
		},

		setQuantity: function ( key, quantity ) {
			var lines = Bag.read().filter( function ( line ) {
				if ( Bag.key( line.slug, line.variant_id ) !== key ) {
					return true;
				}
				line.quantity = quantity;
				// A quantity driven to zero removes the line, which is what a
				// customer means when they press minus on the last one.
				return quantity > 0;
			} );

			Bag.write( lines );
		},

		remove: function ( key ) {
			Bag.write( Bag.read().filter( function ( line ) {
				return Bag.key( line.slug, line.variant_id ) !== key;
			} ) );
		},

		clear: function () {
			Bag.write( [] );
		},

		count: function () {
			return Bag.read().reduce( function ( total, line ) {
				return total + line.quantity;
			}, 0 );
		},

		/** Only what the server needs. Never a price. */
		payload: function () {
			return Bag.read().map( function ( line ) {
				return {
					slug: line.slug,
					variant_id: line.variant_id,
					quantity: line.quantity
				};
			} );
		}
	};

	/* ── helpers ────────────────────────────────────────────────────────── */

	function $( selector, scope ) {
		return ( scope || document ).querySelector( selector );
	}

	function $$( selector, scope ) {
		return Array.prototype.slice.call( ( scope || document ).querySelectorAll( selector ) );
	}

	function post( action, fields ) {
		var body = new FormData();
		body.append( 'action', action );
		body.append( 'nonce', data.nonce );

		Object.keys( fields || {} ).forEach( function ( key ) {
			body.append( key, fields[ key ] );
		} );

		return fetch( data.ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin' } )
			.then( function ( response ) {
				// Always resolve to a parseable object. A form that gets HTML
				// back where it expected JSON fails in a way nobody can debug.
				return response.json()
					.catch( function () {
						return { success: false, data: { error: i18n.genericFail } };
					} )
					.then( function ( json ) {
						return { ok: response.ok && json.success, payload: json.data || {} };
					} );
			} )
			.catch( function () {
				return { ok: false, payload: { error: i18n.genericFail } };
			} );
	}

	function looksLikeEmail( value ) {
		return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( String( value ).trim() );
	}

	function message( element, text, isError ) {
		if ( ! element ) {
			return;
		}
		element.textContent = text || '';
		if ( isError ) {
			element.setAttribute( 'data-error', 'true' );
		} else {
			element.removeAttribute( 'data-error' );
		}
	}

	/* ── the drawer ─────────────────────────────────────────────────────── */

	var Drawer = {
		lastFocused: null,

		open: function () {
			var drawer = $( '[data-eg-drawer]' );
			if ( ! drawer ) {
				return;
			}

			Drawer.lastFocused = document.activeElement;
			document.body.classList.add( 'eg-drawer-open' );
			drawer.setAttribute( 'aria-hidden', 'false' );

			$$( '[data-eg-bag-open]' ).forEach( function ( button ) {
				button.setAttribute( 'aria-expanded', 'true' );
			} );

			var close = $( '[data-eg-bag-close]', drawer );
			if ( close ) {
				close.focus();
			}
		},

		close: function () {
			var drawer = $( '[data-eg-drawer]' );
			if ( ! drawer ) {
				return;
			}

			document.body.classList.remove( 'eg-drawer-open' );
			drawer.setAttribute( 'aria-hidden', 'true' );

			$$( '[data-eg-bag-open]' ).forEach( function ( button ) {
				button.setAttribute( 'aria-expanded', 'false' );
			} );

			if ( Drawer.lastFocused && Drawer.lastFocused.focus ) {
				Drawer.lastFocused.focus();
			}
		},

		isOpen: function () {
			return document.body.classList.contains( 'eg-drawer-open' );
		},

		/** Keeps Tab inside the panel while it is open. */
		trap: function ( event ) {
			if ( event.key !== 'Tab' || ! Drawer.isOpen() ) {
				return;
			}

			var drawer = $( '[data-eg-drawer]' );
			var focusable = $$( 'a[href], button:not([disabled]), input, [tabindex]:not([tabindex="-1"])', drawer );

			if ( ! focusable.length ) {
				return;
			}

			var first = focusable[ 0 ];
			var last = focusable[ focusable.length - 1 ];

			if ( event.shiftKey && document.activeElement === first ) {
				event.preventDefault();
				last.focus();
			} else if ( ! event.shiftKey && document.activeElement === last ) {
				event.preventDefault();
				first.focus();
			}
		},

		render: function () {
			var drawer = $( '[data-eg-drawer]' );
			if ( ! drawer ) {
				return;
			}

			var lines = Bag.read();
			var list = $( '[data-eg-bag-lines]', drawer );
			var empty = $( '[data-eg-bag-empty]', drawer );
			var foot = $( '[data-eg-bag-foot]', drawer );

			if ( ! list ) {
				return;
			}

			list.innerHTML = '';

			if ( ! lines.length ) {
				empty.classList.remove( 'eg-is-hidden' );
				list.classList.add( 'eg-is-hidden' );
				foot.classList.add( 'eg-is-hidden' );
				return;
			}

			empty.classList.add( 'eg-is-hidden' );
			list.classList.remove( 'eg-is-hidden' );
			foot.classList.remove( 'eg-is-hidden' );

			lines.forEach( function ( line ) {
				list.appendChild( Drawer.line( line ) );
			} );

			// The subtotal shown here is indicative; the server recomputes every
			// figure at checkout, and that is the one anybody is charged.
			var subtotal = lines.reduce( function ( total, line ) {
				return total + ( line.price_cents || 0 ) * line.quantity;
			}, 0 );

			var target = $( '[data-eg-bag-subtotal]', drawer );
			if ( target ) {
				target.textContent = formatPrice( subtotal );
			}
		},

		/**
		 * Builds a bag line with DOM methods rather than an HTML string, so a
		 * product title containing a bracket cannot become markup.
		 */
		line: function ( line ) {
			var key = Bag.key( line.slug, line.variant_id );

			var item = document.createElement( 'li' );
			item.className = 'eg-line';

			if ( line.image ) {
				var mediaLink = document.createElement( 'a' );
				mediaLink.className = 'eg-line__media';
				mediaLink.href = line.url || '#';

				var image = document.createElement( 'img' );
				image.src = line.image;
				image.alt = '';
				image.setAttribute( 'aria-hidden', 'true' );
				image.loading = 'lazy';

				mediaLink.appendChild( image );
				item.appendChild( mediaLink );
			}

			var body = document.createElement( 'div' );
			body.className = 'eg-line__body';

			var titleLink = document.createElement( 'a' );
			titleLink.className = 'eg-link eg-line__title';
			titleLink.href = line.url || '#';
			titleLink.textContent = line.title;
			body.appendChild( titleLink );

			if ( line.variant_label ) {
				var variant = document.createElement( 'p' );
				variant.className = 'eg-line__variant';
				variant.textContent = line.variant_label;
				body.appendChild( variant );
			}

			var row = document.createElement( 'div' );
			row.className = 'eg-line__row';

			var quantity = document.createElement( 'div' );
			quantity.className = 'eg-qty';

			var minus = document.createElement( 'button' );
			minus.type = 'button';
			minus.textContent = '−';
			minus.setAttribute( 'aria-label', ( i18n.decrease || 'Decrease quantity of %s' ).replace( '%s', line.title ) );
			minus.addEventListener( 'click', function () {
				Bag.setQuantity( key, line.quantity - 1 );
			} );

			var value = document.createElement( 'span' );
			value.className = 'eg-tabular eg-qty__value';
			value.setAttribute( 'aria-live', 'polite' );
			value.textContent = String( line.quantity );

			var plus = document.createElement( 'button' );
			plus.type = 'button';
			plus.textContent = '+';
			plus.setAttribute( 'aria-label', ( i18n.increase || 'Increase quantity of %s' ).replace( '%s', line.title ) );
			plus.addEventListener( 'click', function () {
				Bag.setQuantity( key, line.quantity + 1 );
			} );

			quantity.appendChild( minus );
			quantity.appendChild( value );
			quantity.appendChild( plus );

			var total = document.createElement( 'span' );
			total.className = 'eg-tabular eg-line__total';
			total.textContent = formatPrice( ( line.price_cents || 0 ) * line.quantity );

			row.appendChild( quantity );
			row.appendChild( total );
			body.appendChild( row );

			var remove = document.createElement( 'button' );
			remove.type = 'button';
			remove.className = 'eg-link eg-line__remove';
			remove.textContent = i18n.remove || 'Remove';
			remove.addEventListener( 'click', function () {
				Bag.remove( key );
			} );
			body.appendChild( remove );

			item.appendChild( body );

			return item;
		}
	};

	/**
	 * Formats a price for the bag only.
	 *
	 * Deliberately simple: everything a customer is actually charged is
	 * formatted by PHP and arrives pre-rendered, so this never has to agree
	 * with the server about anything that matters.
	 */
	function formatPrice( cents ) {
		var amount = ( cents || 0 ) / 100;
		try {
			return new Intl.NumberFormat( document.documentElement.lang || 'en', {
				style: 'currency',
				currency: data.currency || 'USD',
				minimumFractionDigits: cents % 100 === 0 ? 0 : 2
			} ).format( amount );
		} catch ( error ) {
			return amount.toFixed( 2 );
		}
	}

	/* ── header count ───────────────────────────────────────────────────── */

	function renderCount() {
		var count = Bag.count();

		$$( '[data-eg-count]' ).forEach( function ( element ) {
			element.textContent = count > 0 ? String( count ) : '';
		} );

		$$( '[data-eg-bag-label]' ).forEach( function ( element ) {
			var noun = count === 1 ? ( i18n.item || 'item' ) : ( i18n.items || 'items' );
			element.textContent = ( i18n.openBag || 'Open bag' ) + ', ' + count + ' ' + noun;
		} );
	}

	/* ── product page ───────────────────────────────────────────────────── */

	function initBuy() {
		var buy = $( '[data-eg-buy]' );
		if ( ! buy ) {
			return;
		}

		var addButton = $( '[data-eg-add]', buy );
		var note = $( '[data-eg-buy-message]', buy );
		var single = $( '[data-eg-variant-single]', buy );

		function selected() {
			if ( single ) {
				return {
					id: single.value,
					label: single.getAttribute( 'data-eg-label' ) || ''
				};
			}

			var active = $( '[data-eg-variant][aria-pressed="true"]', buy );
			return active
				? { id: active.getAttribute( 'data-eg-variant' ), label: active.getAttribute( 'data-eg-label' ) || '' }
				: null;
		}

		$$( '[data-eg-variant]', buy ).forEach( function ( button ) {
			button.addEventListener( 'click', function () {
				$$( '[data-eg-variant]', buy ).forEach( function ( other ) {
					other.setAttribute( 'aria-pressed', 'false' );
				} );
				button.setAttribute( 'aria-pressed', 'true' );
			} );
		} );

		if ( ! addButton ) {
			return;
		}

		addButton.addEventListener( 'click', function () {
			var variant = selected();
			if ( ! variant || ! variant.id ) {
				return;
			}

			Bag.add( {
				slug: buy.getAttribute( 'data-eg-slug' ),
				variant_id: variant.id,
				variant_label: variant.label,
				title: buy.getAttribute( 'data-eg-title' ),
				image: buy.getAttribute( 'data-eg-image' ),
				url: window.location.href,
				// Held only so the drawer can show a running subtotal. The
				// server never reads it; it prices from the catalogue.
				price_cents: parseInt( buy.getAttribute( 'data-eg-price' ) || '0', 10 ),
				quantity: 1
			} );

			message( note, i18n.added, false );
			Drawer.open();
		} );
	}

	/* ── mailing list ───────────────────────────────────────────────────── */

	function initSubscribe() {
		$$( '[data-eg-subscribe]' ).forEach( function ( form ) {
			var note = $( '[data-eg-message]', form );
			var input = form.querySelector( 'input[type="email"]' );
			var button = form.querySelector( 'button[type="submit"]' );
			var isGate = form.classList.contains( 'eg-gate__form' );
			var busy = false;

			input.addEventListener( 'input', function () {
				if ( note && note.getAttribute( 'data-error' ) ) {
					message( note, '', false );
				}
			} );

			form.addEventListener( 'submit', function ( event ) {
				// Only intercept once this handler is attached. Before that, the
				// form posts natively and the identical validation runs in PHP.
				event.preventDefault();

				if ( busy ) {
					return;
				}

				if ( ! looksLikeEmail( input.value ) ) {
					message( note, i18n.emailError, true );
					input.focus();
					return;
				}

				busy = true;
				message( note, '', false );
				button.disabled = true;
				button.textContent = isGate ? ( i18n.entering || 'Entering' ) : ( i18n.adding || 'Adding' );

				var next = form.querySelector( 'input[name="next"]' );

				post( 'eg_subscribe', {
					email: input.value.trim(),
					next: next ? next.value : '/'
				} ).then( function ( result ) {
					if ( ! result.ok ) {
						message( note, result.payload.error || i18n.genericFail, true );
						busy = false;
						button.disabled = false;
						button.textContent = isGate ? ( i18n.enter || 'Enter' ) : ( i18n.signUp || 'Sign up' );
						return;
					}

					if ( isGate ) {
						// Fade the field out, then route. No confetti, no tick.
						form.setAttribute( 'data-status', 'success' );
						window.setTimeout( function () {
							window.location.assign( result.payload.redirect || '/' );
						}, 320 );
						return;
					}

					input.value = '';
					message( note, i18n.subscribed, false );
					busy = false;
					button.disabled = false;
					button.textContent = i18n.signUp || 'Sign up';
				} );
			} );
		} );
	}

	/* ── accordions ─────────────────────────────────────────────────────── */

	function initAccordions() {
		$$( '[data-eg-accordion]' ).forEach( function ( accordion ) {
			var trigger = $( '.eg-accordion__trigger', accordion );
			var sign = $( '.eg-accordion__sign', accordion );

			if ( ! trigger ) {
				return;
			}

			trigger.addEventListener( 'click', function () {
				var open = accordion.getAttribute( 'data-open' ) === 'true';
				accordion.setAttribute( 'data-open', open ? 'false' : 'true' );
				trigger.setAttribute( 'aria-expanded', open ? 'false' : 'true' );
				if ( sign ) {
					sign.textContent = open ? '+' : '−';
				}
			} );
		} );
	}

	/* ── checkout ───────────────────────────────────────────────────────── */

	function initCheckout() {
		var form = $( '[data-eg-checkout]' );
		if ( ! form ) {
			return;
		}

		var empty = $( '[data-eg-checkout-empty]' );
		var error = $( '[data-eg-checkout-error]', form );
		var submit = $( '[data-eg-checkout-submit]', form );
		var linesField = $( '[data-eg-checkout-lines]', form );
		var busy = false;

		function showBagState() {
			var hasLines = Bag.read().length > 0;

			form.classList.toggle( 'eg-is-hidden', ! hasLines );
			if ( empty ) {
				empty.classList.toggle( 'eg-is-hidden', hasLines );
			}

			// The no-JS post reads this field, so it has to carry the same bag.
			if ( linesField ) {
				linesField.value = JSON.stringify( Bag.payload() );
			}

			return hasLines;
		}

		function renderSummaryLines() {
			var list = $( '[data-eg-summary-lines]', form );
			if ( ! list ) {
				return;
			}

			list.innerHTML = '';

			Bag.read().forEach( function ( line ) {
				var item = document.createElement( 'li' );
				item.className = 'eg-line';

				if ( line.image ) {
					var media = document.createElement( 'div' );
					media.className = 'eg-line__media';
					var image = document.createElement( 'img' );
					image.src = line.image;
					image.alt = '';
					image.setAttribute( 'aria-hidden', 'true' );
					image.loading = 'lazy';
					media.appendChild( image );
					item.appendChild( media );
				}

				var body = document.createElement( 'div' );
				body.className = 'eg-line__body';

				var title = document.createElement( 'p' );
				title.className = 'eg-line__title';
				title.textContent = line.title;
				body.appendChild( title );

				var meta = document.createElement( 'p' );
				meta.className = 'eg-line__variant';
				meta.textContent = ( line.variant_label ? line.variant_label + ' · ' : '' ) + '× ' + line.quantity;
				body.appendChild( meta );

				item.appendChild( body );
				list.appendChild( item );
			} );
		}

		function refreshQuote() {
			if ( ! showBagState() ) {
				return;
			}

			renderSummaryLines();

			post( 'eg_checkout_quote', { lines: JSON.stringify( Bag.payload() ) } )
				.then( function ( result ) {
					var subtotal = $( '[data-eg-summary-subtotal]', form );
					var shipping = $( '[data-eg-summary-shipping]', form );
					var total = $( '[data-eg-summary-total]', form );
					var note = $( '[data-eg-summary-note]', form );

					if ( ! result.ok ) {
						message( error, result.payload.error || i18n.genericFail, true );
						if ( submit ) {
							submit.disabled = true;
						}
						return;
					}

					message( error, '', false );
					if ( submit ) {
						submit.disabled = false;
					}

					// Every figure arrives pre-formatted by the same code that
					// prices the real order, so the two cannot disagree.
					subtotal.textContent = result.payload.subtotal;
					shipping.textContent = result.payload.shipping;
					total.textContent = result.payload.total;

					if ( note ) {
						if ( result.payload.shipping_cents > 0 && result.payload.free_shipping_over_cents > 0 ) {
							note.textContent = ( i18n.freeShippingOver || 'Free shipping over %s' )
								.replace( '%s', result.payload.free_shipping_over );
							note.classList.remove( 'eg-is-hidden' );
						} else {
							note.classList.add( 'eg-is-hidden' );
						}
					}
				} );
		}

		function validate() {
			var valid = true;

			$$( '[data-eg-error]', form ).forEach( function ( slot ) {
				slot.textContent = '';
			} );

			$$( 'input[required]', form ).forEach( function ( input ) {
				var slot = $( '[data-eg-error="' + input.name + '"]', form );

				if ( ! input.value.trim() ) {
					if ( slot ) {
						slot.textContent = i18n.required || 'Required.';
					}
					valid = false;
					return;
				}

				if ( input.type === 'email' && ! looksLikeEmail( input.value ) ) {
					if ( slot ) {
						slot.textContent = i18n.emailError;
					}
					valid = false;
				}
			} );

			return valid;
		}

		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();

			if ( busy || ! Bag.read().length ) {
				return;
			}

			message( error, '', false );

			if ( ! validate() ) {
				return;
			}

			busy = true;
			submit.disabled = true;
			submit.textContent = i18n.placing || 'Placing order';

			var fields = { lines: JSON.stringify( Bag.payload() ) };

			$$( 'input[name]', form ).forEach( function ( input ) {
				if ( input.name !== 'lines' && input.name !== 'action' && input.name !== 'eg_nonce' ) {
					fields[ input.name ] = input.value;
				}
			} );

			post( 'eg_checkout', fields ).then( function ( result ) {
				if ( ! result.ok ) {
					message( error, result.payload.error || i18n.genericFail, true );
					busy = false;
					submit.disabled = false;
					submit.textContent = i18n.placeOrder || 'Place order';
					return;
				}

				if ( result.payload.kind === 'redirect' ) {
					window.location.assign( result.payload.url );
					return;
				}

				// Hand the reference to the confirmation page, THEN clear the
				// bag — in that order, so a failed navigation never loses it.
				var target = data.confirmed
					+ ( data.confirmed.indexOf( '?' ) === -1 ? '?' : '&' )
					+ 'order=' + encodeURIComponent( result.payload.order_id || '' )
					+ '&clear=1';

				window.location.assign( target );
			} );
		} );

		document.addEventListener( 'eg:bagchange', refreshQuote );
		refreshQuote();
	}

	/* ── confirmation ───────────────────────────────────────────────────── */

	function initConfirmed() {
		if ( ! $( '[data-eg-confirmed]' ) ) {
			return;
		}

		// The page has rendered, so the order is safely acknowledged and the
		// bag can go.
		if ( window.location.search.indexOf( 'order=' ) !== -1 || window.location.search.indexOf( 'clear=1' ) !== -1 ) {
			Bag.clear();
		}
	}

	/* ── boot ───────────────────────────────────────────────────────────── */

	function init() {
		renderCount();
		Drawer.render();

		$$( '[data-eg-bag-open]' ).forEach( function ( button ) {
			button.addEventListener( 'click', Drawer.open );
		} );

		$$( '[data-eg-bag-close]' ).forEach( function ( button ) {
			button.addEventListener( 'click', Drawer.close );
		} );

		document.addEventListener( 'keydown', function ( event ) {
			if ( event.key === 'Escape' && Drawer.isOpen() ) {
				Drawer.close();
				return;
			}
			Drawer.trap( event );
		} );

		document.addEventListener( 'eg:bagchange', function () {
			renderCount();
			Drawer.render();
		} );

		// A second tab changing the bag should be reflected here rather than
		// silently overwritten on the next write.
		window.addEventListener( 'storage', function ( event ) {
			if ( event.key === STORAGE_KEY ) {
				renderCount();
				Drawer.render();
			}
		} );

		initBuy();
		initSubscribe();
		initAccordions();
		initCheckout();
		initConfirmed();
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
