"use strict";

jQuery( document ).ready( function ( $ ) {
	// Read cached start and end dates from local storage
	var cached_start_date = localStorage.getItem( 'gd_booking_start_date' ) ? localStorage.getItem( 'gd_booking_start_date' ) : '';
	var cached_end_date = localStorage.getItem( 'gd_booking_end_date' ) ? localStorage.getItem( 'gd_booking_end_date' ) : '';

	/**
	 * Formats a date object to YYYY-MM-DD
	 *
	 * @param {Date} date
	 */
	function formatDate( date ) {
		var month = '' + ( date.getMonth() + 1 ),
			day = '' + date.getDate(),
			year = date.getFullYear();
		if ( month.length < 2 ) {
			month = '0' + month;
		}
		if ( day.length < 2 ) {
			day = '0' + day;
		}
		return [year, month, day].join( '-' );
	}

	// Watch for room changes.
	$( '.geodir-booking-room-select' ).on( 'change', function () {

		var select = $( this );
		var id = select.closest( '.geodir-booking-form-rooms-container' ).data( 'parent' );
		var value = select.val();
		var wrappers = $( '.geodir-booking-form-rooms-container__availability[data-parent="' + id + '"]' );

		wrappers.each( function () {
			if ( value ) {
				$(this).find( '.geodir-booking-form-room-container' ).hide();
				$(this).find( '.geodir-booking-form-room-container[data-id="' + value + '"]' ).show();
				$(this).find( '.geodir-booking-room-select' ).val( value );
			}
		} );

		// Trigger geodir_booking_change_room event.
		$( 'body' ).trigger( 'geodir_booking_change_room', [id, value] );
	} );
	$( '.geodir-booking-room-select' ).change();

	$( '.geodir-booking-availability-wrapper' ).each( function () {
		var app = $( this );
		var app_id = app.attr( 'id' );
		var app_data = app.data( 'js_data' );
		if (app.find('.geodir-booking-availability > .my-3').length && jQuery(window).width() < 700 && jQuery(window).width() > 576) {
			app.find('.geodir-booking-availability > .my-3').css({'overflow-x':'auto','overflow-y':'hidden','padding':'7px'});
		}

		// Init app.
		Vue.createApp( {
			data: function data() {
				return Object.assign( {}, app.data( 'js_data' ), {
					start_date: app_data.start_date || cached_start_date,
					end_date: app_data.end_date || cached_end_date
				} );
			},
			computed: {
				selectedRange: function selectedRange() {
					if ( !this.start_date || !this.end_date ) {
						return '';
					}

					// Format dates to F j, Y
					var start_date = new Date( this.start_date ).toLocaleDateString( 'en-US', {
						month: 'long',
						day: 'numeric',
						year: 'numeric'
					} );
					var end_date = new Date( this.end_date ).toLocaleDateString( 'en-US', {
						month: 'long',
						day: 'numeric',
						year: 'numeric'
					} );

					// If start and end date are the same, return only one date.
					if ( start_date === end_date ) {
						return start_date;
					}
					return start_date + ' - ' + end_date;
				}
			},
			methods: {
				// Clear dates.
				clearDates: function clearDates() {
					this.start_date = '';
					this.end_date = '';
					this.initFlatpickr();
				},
				// Destroy flatpickr.
				destroyFlatpickr: function destroyFlatpickr() {
					// Maybe clear the existing instance.
					var flatpk = app.find( '.geodir-booking-availability__date_picker' );
					if ( flatpk[0] && flatpk[0]._flatpickr ) {
						flatpk[0]._flatpickr.destroy();
					}
				},
				// Init flatpickr.
				initFlatpickr: function initFlatpickr() {
					var _this = this;
					this.destroyFlatpickr();

					// Create a flatpickr instance to select a date range.
					// And exclude the dates that are not available.
					app.find( '.geodir-booking-availability__date_picker' ).flatpickr( {
						// Cannot select past dates.
						minDate: 'today',
						// Allow bookings for up to a year in advance.
						maxDate: new Date().fp_incr( 365 ),
						// Set the default date.
						defaultDate: [this.start_date, this.end_date],
						// We're picking a date range.
						mode: 'range',
						// Display 2 months at a time.
						showMonths: jQuery(window).width() <= 576 ? 1 : 2,
						// Display the calendar inline.
						inline: true,
						// Set disabled dates.
						disable: [
							/**
							 * Disable booked dates.
							 *
							 * @param {Date} date
							 */
							function ( date ) {
								return _this.disabled_dates.indexOf( formatDate( date ) ) > -1;
							}],
						// Set start and end dates on change.
						onChange: function onChange( selectedDates ) {
							if ( selectedDates[0] ) {
								_this.start_date = formatDate( selectedDates[0] );
							}
							if ( selectedDates[1] ) {
								_this.end_date = formatDate( selectedDates[1] );
							} else if ( selectedDates[0] ) {
								_this.end_date = formatDate( new Date( selectedDates[0] ).fp_incr( 1 ) );
							} else {
								_this.end_date = '';
							}

							// Save dates to local storage.
							localStorage.setItem( 'gd_booking_start_date', _this.start_date );
							localStorage.setItem( 'gd_booking_end_date', _this.end_date );
						}
					} );
				}
			},
			// Watch start and end dates.
			watch: {
				start_date: function start_date() {
					window.geodir_booking_dates = window.geodir_booking_dates || {};
					window.geodir_booking_dates[this.listing_id] = {
						start_date: this.start_date,
						end_date: this.end_date
					};
					if ( window.gd_booking_dates_changed ) {
						window.gd_booking_dates_changed( this.listing_id, this.start_date, this.end_date );
					}
				},
				end_date: function end_date() {
					window.geodir_booking_dates = window.geodir_booking_dates || {};
					window.geodir_booking_dates[this.listing_id] = {
						start_date: this.start_date,
						end_date: this.end_date
					};
					if ( window.gd_booking_dates_changed ) {
						window.gd_booking_dates_changed( this.listing_id, this.start_date, this.end_date );
					}
				}
			},
			mounted: function mounted() {
				// Init flatpickr.
				this.initFlatpickr();

				// Refresh flatpickr when the tab is clicked.
				$( 'body' ).on( 'shown.bs.tab', this.initFlatpickr );
				$( 'body' ).on( 'geodir_booking_change_room', this.initFlatpickr );
			},
			unmounted: function unmounted() {
				this.destroyFlatpickr();
			}
		} ).mount( "#".concat( app_id ) );
	} );
} );
