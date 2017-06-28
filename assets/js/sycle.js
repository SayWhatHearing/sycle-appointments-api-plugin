/*global sycle_ajax_object:true*/
jQuery(document).ready(function($) {
/*
REFS:
http://jqueryvalidation.org/documentation/

*/
	console.log('[Sycle] JS loaded'); // todo remove in prod




	/*
	This is to update two hidden fields in the form of each clinic listing. This is to parse two values, based what appointment type the customer is selecting. Two values are pased from the select data- attributes and the hidden input fields are updated.

	This reduces further calls to API by copying data from attributes embedded in the <select> this code changes the values of hidden input fields
	*/
	$('.sycleapi').on('change','.sycle_apttype',function(event){
		var optionSelected = $("option:selected", this);
		var length = optionSelected.attr('data-length');
		var name = optionSelected.attr('data-name');
		$(event.target).parents().find('input[name="sycle_aptname"]').val(name);
		$(event.target).parents().find('input[name="sycle_aptlength"]').val(length);
		$(".sycle-booking .sycle_booking_date").trigger("change");
		// todo - detect if change
	});







	// ***** BOOOKING FORM *****
	// Using .on() since this was added to DOM after load
	$('.sycleapi').on('change','.sycle-booking .sycle_booking_date',function(event){
		var dateTypeVar = $(this).datepicker('getDate'); // returns date object
		var selectedDate = $.datepicker.formatDate('yy-mm-dd', dateTypeVar);
		var sycle_aptlength 			= $(this).parents().find("[name='sycle_aptlength']").val();
		var sycle_booking_token 	= $(this).parents().find("[name='sycle_booking_token']").val();
		var sycle_clinic_id 			= $(this).parents().find("[name='sycle_clinic_id']").val();
		var sycle_apttype			 		= $(this).parents().find("[name='sycle_apttype']").val();
		var sycle_aptname					= $(this).parents().find("[name='sycle_aptname']").val();

		$('.sycle_timeresults').hide().html('Loading...').addClass('loader').fadeIn('fast');

		jQuery.ajax({
			url : sycle_ajax_object.ajax_url,
			type : 'post',
			data : {
				action : 'sycle_get_open_slots',
				_ajax_nonce: sycle_ajax_object.sycle_nonce,
				sycle_aptname : sycle_aptname,
				sycle_apttype : sycle_apttype,
				sycle_clinic_id : sycle_clinic_id,
				sycle_booking_token : sycle_booking_token,
				sycle_aptlength : sycle_aptlength,
				sycle_selectedDate : selectedDate
			},
			success : function( response ) {
				var parsed = $.parseJSON(response);
				$('.sycle_timeresults').hide().removeClass('loader').html(parsed).show();
			},
			error: function(error){
						console.log("Error:"); // todo remove in prod
						console.log(error); // todo remove in prod
					}
				}); // end ajax

	});
	/*
	Lars - really would have liked to get this to work - Maybe Michael knows?
	Gist of it, apparently using $.ajax() you cannot send a body in the request when doing GET - so with GET, the data is parsed as parameters, which Sycle ignores.
	With POST - Sycle rejects the request completely.
	AFAIK - That means we have to parse all requests to sycle via PHP functions in plugin instead, adding overhead.

	*/

// TODO:

	// Detects the [syclebooking] shortcode outputs
	if( jQuery('.sycle-booking').length ) {
		if ( (sycle_ajax_object.hasOwnProperty("sycle_nonce")) || (sycle_ajax_object.hasOwnProperty("ajax_url")) ) {

			$( ".sycle-booking" ).validate({

    rules: {
        sycle_booking_date: {
            required: true
        }
    }
});

			// Triggers the change event on selecting a new date, but only if a -different- date has been chosen
			var Today = new Date();
			$(".sycle-booking .sycle_booking_date").datepicker({
				minDate: Today,
				defaultDate: Today,
				onSelect: function(d,i){
					if(d !== i.lastVal){
						$(this).change();
					}
				}
			});
			// set the current date
			$(".sycle-booking .sycle_booking_date").datepicker('setDate', Today);

			// fake the change event to get the first list of time slots
			$(".sycle-booking .sycle_booking_date").trigger("change");

		} // Hasownproperty
	} // if( jQuery('.sycleclinicslist').length )



	// Detects the [sycleclinicslist] shortcode output, reads nonce and gets back a list of clinics
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


	// Detects the [sycle] autocomplete
	// Location autocomplete shortcode communication
	if( jQuery('.sycleautocomplete').length ) { // if the location autocomplete is shown.
		$( ".sycleautocomplete" ).on( "click", function(e) {

			e.preventDefault();

			autocomplete = new google.maps.places.Autocomplete(
				/** @type {!HTMLInputElement} */(document.getElementById('sycleautocomplete')),
				{types: ['geocode']});


			//autocomplete.addListener('place_changed', fillInAddress);
			autocomplete.addListener('place_changed', function() {
				// var data = $("#search_form").serialize();
				var place = autocomplete.getPlace();
				var place_components = autocomplete.getPlace().address_components;

				var response = {};


				var streetAddr = [addrComponents.streetNumber, addrComponents.streetName, addrComponents.zipCode, addrComponents.stateName];
				var streetAddrDisplay = [];

				place_components.forEach(function(placeComponent) {
					streetAddr.forEach(function(streetAddrComponent) {
						if (placeComponent.types[0] === streetAddrComponent.type) {
							streetAddrDisplay.push(placeComponent[streetAddrComponent.display]);
						}
					});
				});

				jQuery.ajax({
					url : sycle_ajax_object.ajax_url,
					type : 'post',
					data : {
						action : 'sycle_get_search_results',
						_ajax_nonce: sycle_ajax_object.sycle_nonce,
						addressfield: place_components
					},
					success : function( response ) {
						var clinics = $.parseJSON(response);
						$(e.target).closest('.sycleapi').find('.clinicslist').empty(); // resets results

						$.each( clinics.clinic_details, function( key, clinic ) {
							$(e.target).closest('.sycleapi').find('.clinicslist').append('<li class="clinic">'+clinic+'</li>').hide().fadeIn(350);
						});
					},
					error: function(error){
					console.log("Error:"); // todo remove in prod
					console.log(error); // todo remove in prod
				}
			}); // end ajax




				//return false;
			}); // place_changed


			 // geolocate();

			//var addressfield = $(".syclefindcloseclinic .sycleautocomplete").val();


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


		var addrComponents = {
			streetNumber: {
				display: 'short_name',
				type: 'street_number'
			},
			streetName: {
				display: 'long_name',
				type: 'route'
			},
			cityName: {
				display: 'long_name',
				type: 'locality'
			},
			stateName: {
				display: 'short_name',
				type: 'administrative_area_level_1'
			},
			zipCode: {
				display: 'short_name',
				type: 'postal_code'
			}
		};





		function fillInAddress() {
			// LARS - TODO - find better solution, instead of storing in html objects - perhaps hidden input fields?

			// Get the place details from the autocomplete object.
			var place = autocomplete.getPlace();

			// for (var component in componentForm) {
			// 	document.getElementById(component).value = '';
			// 	document.getElementById(component).disabled = false;
			// }

			// Get each component of the address from the place details
			// and fill the corresponding field on the form.
			for (var i = 0; i < place.address_components.length; i++) {
				var addressType = place.address_components[i].types[0];
				if (componentForm[addressType]) {
					var val = place.address_components[i][componentForm[addressType]];
					console.log(addressType+' '+val);
					//console.log(this);
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


		});