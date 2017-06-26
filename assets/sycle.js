jQuery(document).ready(function($) {

	console.log('[Sycle] JS loaded'); // todo remove in prod

/* Lars - really would have liked to get this to work - Maybe Michael knows?
	Gist of it, apparently using $.ajax() you cannot send a body in the request when doing GET - so with GET, the data is parsed as parameters, which Sycle ignores.
	With POST - Sycle rejects the request completely.
	*/

	// Detects the [cycleclinicslist] shortcode output, reads nonce and gets back a list of clinics
	if( jQuery('.sycleclinicslist').length ) {
		if ( (sycle_ajax_object.hasOwnProperty("sycle_nonce")) || (sycle_ajax_object.hasOwnProperty("ajax_url")) ) {
			$( ".sycleclinicslist" ).each( function( index, element ){
				jQuery.ajax({
					url : sycle_ajax_object.ajax_url,
					type : 'post',
					data : {
						action : 'sycle_get_clinics_list',
						_ajax_nonce: sycle_ajax_object.sycle_nonce
					},
					success : function( response ) {
						var clinics = $.parseJSON(response);
						$.each( clinics.clinic_details, function( key, clinic ) {
							$(element).find('.clinicslist').append('<li>'+clinic+'</li>').hide().fadeIn(350);
						});
					},
					error: function(error){
						console.log("Error:"); // todo remove in prod
						console.log(error); // todo remove in prod
					}
				}); // end ajax
			}); // end foreach
		} // Hasownproperty
	} // if( jQuery('.sycleclinicslist').length )








	// Location autocomplete shortcode communication
	if( jQuery('.sycleautocomplete').length ) { // if the location autocomplete is shown.
		$( ".sycleautocomplete" ).on( "click", function(e) {

			e.preventDefault();
			// initAutocomplete();
			autocomplete = new google.maps.places.Autocomplete(
				/** @type {!HTMLInputElement} */(document.getElementById('sycleautocomplete')),
				{types: ['geocode']});

			var place = autocomplete.getPlace();

			autocomplete.addListener('place_changed', fillInAddress);
			 //                     geolocate();

			 var addressfield = $(".syclefindcloseclinic .sycleautocomplete").val();

			jQuery.ajax({
				url : sycle_ajax_object.ajax_url,
				type : 'post',
				data : {
					action : 'sycle_get_search_results',
					_ajax_nonce: sycle_ajax_object.sycle_nonce,
					addressfield: addressfield
				},
				success : function( response ) {
					var clinics = $.parseJSON(response);

					$(e.target).closest('.sycleapi').find('.clinicslist').empty(); // resets results

					$.each( clinics.clinic_details, function( key, clinic ) {
						//console.log('resp '+clinic);
						$(e.target).closest('.sycleapi').find('.clinicslist').append('<li>'+clinic+'</li>').hide().fadeIn(350);
			});
				},
				error: function(error){
					console.log("Error:"); // todo remove in prod
					console.log(error); // todo remove in prod
				}
			}); // end ajax
		});
	} // if the location autocomplete is shown.










		// REF: https://developers.google.com/maps/documentation/javascript/examples/places-autocomplete-addressform
		var placeSearch, autocomplete;
		var componentForm = {
			street_number: 'short_name',
			route: 'long_name',
			locality: 'long_name',
			administrative_area_level_1: 'short_name',
			country: 'long_name',
			postal_code: 'short_name'
		};

		function initAutocomplete() {
			// Create the autocomplete object, restricting the search to geographical
			// location types.
			autocomplete = new google.maps.places.Autocomplete(
				/** @type {!HTMLInputElement} */(document.getElementById('autocomplete')),
				{types: ['geocode']});

			// When the user selects an address from the dropdown, populate the address
			// fields in the form.
			autocomplete.addListener('place_changed', fillInAddress);
		}

		function fillInAddress() {
			// LARS - TODO - find better solution, instead of storing in html objects - perhaps hidden input fields?

			// Get the place details from the autocomplete object.
			var place = autocomplete.getPlace();

			for (var component in componentForm) {
				document.getElementById(component).value = '';
				document.getElementById(component).disabled = false;
			}

			// Get each component of the address from the place details
			// and fill the corresponding field on the form.
			for (var i = 0; i < place.address_components.length; i++) {
				var addressType = place.address_components[i].types[0];
				if (componentForm[addressType]) {
					var val = place.address_components[i][componentForm[addressType]];
					document.getElementById(addressType).value = val;
				}
			}
		}

			// Bias the autocomplete object to the user's geographical location,
			// as supplied by the browser's 'navigator.geolocation' object.
			function geolocate() {
				if (navigator.geolocation) {
					navigator.geolocation.getCurrentPosition(function(position) {
						var geolocation = {
							lat: position.coords.latitude,
							lng: position.coords.longitude
						};
						var circle = new google.maps.Circle({
							center: geolocation,
							radius: position.coords.accuracy
						});
						autocomplete.setBounds(circle.getBounds());
					});
				}
			}


/*
<input id="searchTextField" type="text" size="50" placeholder="Anything you want!">

https://developers.google.com/maps/documentation/javascript/examples/places-autocomplete-addressform

	// This example displays an address form, using the autocomplete feature
			// of the Google Places API to help users fill in the information.

			// This example requires the Places library. Include the libraries=places
			// parameter when you first load the API. For example:
			// <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&libraries=places">

			var placeSearch, autocomplete;
			var componentForm = {
				street_number: 'short_name',
				route: 'long_name',
				locality: 'long_name',
				administrative_area_level_1: 'short_name',
				country: 'long_name',
				postal_code: 'short_name'
			};

			function initAutocomplete() {
				// Create the autocomplete object, restricting the search to geographical
				// location types.
				autocomplete = new google.maps.places.Autocomplete(
						(document.getElementById('autocomplete')),
						{types: ['geocode']});

				// When the user selects an address from the dropdown, populate the address
				// fields in the form.
				autocomplete.addListener('place_changed', fillInAddress);
			}

			function fillInAddress() {
				// Get the place details from the autocomplete object.
				var place = autocomplete.getPlace();

				for (var component in componentForm) {
					document.getElementById(component).value = '';
					document.getElementById(component).disabled = false;
				}

				// Get each component of the address from the place details
				// and fill the corresponding field on the form.
				for (var i = 0; i < place.address_components.length; i++) {
					var addressType = place.address_components[i].types[0];
					if (componentForm[addressType]) {
						var val = place.address_components[i][componentForm[addressType]];
						document.getElementById(addressType).value = val;
					}
				}
			}

			// Bias the autocomplete object to the user's geographical location,
			// as supplied by the browser's 'navigator.geolocation' object.
			function geolocate() {
				if (navigator.geolocation) {
					navigator.geolocation.getCurrentPosition(function(position) {
						var geolocation = {
							lat: position.coords.latitude,
							lng: position.coords.longitude
						};
						var circle = new google.maps.Circle({
							center: geolocation,
							radius: position.coords.accuracy
						});
						autocomplete.setBounds(circle.getBounds());
					});
				}
			}


https://developers.google.com/maps/documentation/javascript/places-autocomplete#add_autocomplete

When a user selects a place from the predictions attached to the autocomplete text field, the service fires a place_changed event. You can call getPlace() on the Autocomplete object, to retrieve a PlaceResult object.


*/

});