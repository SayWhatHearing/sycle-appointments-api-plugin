!function($){jQuery(document).ready(function($){function e(){navigator.geolocation&&navigator.geolocation.getCurrentPosition(function(e){var a={lat:e.coords.latitude,lng:e.coords.longitude},t=new google.maps.Circle({center:a,radius:e.coords.accuracy});n.setBounds(t.getBounds())})}if($(".sycleapi").on("change",".sycle_apttype",function(e){var a=$("option:selected",this),t=a.attr("data-length"),n=a.attr("data-name");$(e.target).parents().find('input[name="sycle_aptname"]').val(n),$(e.target).parents().find('input[name="sycle_aptlength"]').val(t),$(".sycle-booking .sycle_booking_date").trigger("change")}),$(".sycleapi").on("change",".sycle-booking .sycle_booking_date",function(){var e=$(this).datepicker("getDate"),a=$.datepicker.formatDate("yy-mm-dd",e),t=$(this).parents().find("[name='sycle_aptlength']").val(),n=$(this).parents().find("[name='sycle_booking_token']").val(),c=$(this).parents().find("[name='sycle_clinic_id']").val(),s=$(this).parents().find("[name='sycle_apttype']").val(),l=$(this).parents().find("[name='sycle_aptname']").val();$(".sycle_timeresults").hide().html("Loading...").addClass("loader").fadeIn("fast"),jQuery.ajax({url:sycle_ajax_object.ajax_url,type:"post",data:{action:"sycle_get_open_slots",_ajax_nonce:sycle_ajax_object.sycle_nonce,sycle_aptname:l,sycle_apttype:s,sycle_clinic_id:c,sycle_booking_token:n,sycle_aptlength:t,sycle_selectedDate:a},success:function(e){var a=$.parseJSON(e);$(".sycle_timeresults").hide().removeClass("loader").html(a).show()}})}),jQuery(".sycle-booking").length&&(sycle_ajax_object.hasOwnProperty("sycle_nonce")||sycle_ajax_object.hasOwnProperty("ajax_url"))){$(".sycle-booking").validate({rules:{sycle_booking_date:{required:!0}}});var a=new Date;$(".sycle-booking .sycle_booking_date").datepicker({minDate:a,defaultDate:a,onSelect:function(e,a){e!==a.lastVal&&$(this).change()}}),$(".sycle-booking .sycle_booking_date").datepicker("setDate",a),$(".sycle-booking .sycle_booking_date").trigger("change")}jQuery(".sycleclinicslist").length&&(sycle_ajax_object.hasOwnProperty("sycle_nonce")||sycle_ajax_object.hasOwnProperty("ajax_url"))&&$(".sycleclinicslist").each(function(e,a){jQuery.ajax({url:sycle_ajax_object.ajax_url,type:"post",data:{action:"sycle_get_clinics_list",_ajax_nonce:sycle_ajax_object.sycle_nonce},success:function(e){var t=$.parseJSON(e);$.each(t.clinic_details,function(e,t){$(a).find(".clinicslist").append("<li>"+t+"</li>").hide().fadeIn(350)})}})}),jQuery(".sycleautocomplete").length&&$(".sycleautocomplete").on("click",function(e){e.preventDefault(),n=new google.maps.places.Autocomplete(document.getElementById("sycleautocomplete"),{types:["geocode"]}),n.addListener("place_changed",function(){var a=n.getPlace(),t=n.getPlace().address_components,c={},l=[s.streetNumber,s.streetName,s.zipCode,s.stateName],o=[];t.forEach(function(e){l.forEach(function(a){e.types[0]===a.type&&o.push(e[a.display])})}),jQuery.ajax({url:sycle_ajax_object.ajax_url,type:"post",data:{action:"sycle_get_search_results",_ajax_nonce:sycle_ajax_object.sycle_nonce,addressfield:t},success:function(a){var t=$.parseJSON(a);$(e.target).closest(".sycleapi").find(".clinicslist").empty(),$.each(t.clinic_details,function(a,t){$(e.target).closest(".sycleapi").find(".clinicslist").append('<li class="clinic">'+t+"</li>").hide().fadeIn(350)})}})})});var t,n,c={street_number:"short_name",route:"long_name",locality:"long_name",administrative_area_level_1:"short_name",country:"long_name",postal_code:"short_name"},s={streetNumber:{display:"short_name",type:"street_number"},streetName:{display:"long_name",type:"route"},cityName:{display:"long_name",type:"locality"},stateName:{display:"short_name",type:"administrative_area_level_1"},zipCode:{display:"short_name",type:"postal_code"}}})}(jQuery);