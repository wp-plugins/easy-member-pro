if(typeof jQuery == "function") {
	jQuery(document).ready(function($) {
		$('.wpemp_login_form').submit(function(event){
			event.preventDefault();
			post_id = $(this).attr('id').split("-")[1];
			$("#wpemp_login_note-" + post_id).html("");
			if($('#wpemp_user-' + post_id).val() == '' || $('#wpemp_pass-' + post_id).val() == '')
			{
				$("#wpemp_login_note-" + post_id).html("Please enter Username and Password");
				return false;
			}
			ajaxurl = $('#wpemp_login_form-' + post_id).attr('action');
			var data = {
				wpemp: 'login',
				wpemp_user: $('#wpemp_user-' + post_id).val(),
				wpemp_pass: $('#wpemp_pass-' + post_id).val(),
				_ajax_nonce: $('#ajax_nonce-' + post_id).val()
			};
			$("#wpemp_loader-" + post_id).css("display", "inline");
			response = null;
			jQuery.post(ajaxurl, data, function(response) {
				if(response)
				{
					$("#wpemp_loader-" + post_id).css("display", "none");
					if(response != '0')
						location.href=location.href;
					else
						$("#wpemp_login_note-" + post_id).html(response);
					response = null;
				}
				else
				{
					$("#wpemp_login_note-" + post_id).html("Error getting response");
					$("#wpemp_loader-" + post_id).css("display", "none");
				}
			});
		return false;
		});
		$(".wpemp_link_login").click(function(event) {
			event.preventDefault();
			post_id = $(this).attr('id').split("-")[1];
			if(typeof(post_id) == "undefined") return false;
			if ($('#wpemp_login_div-'+post_id).is(":hidden")) {
				$("#wpemp_link_forgot-"+post_id).show();
				$('#wpemp_login_div-'+post_id).slideDown("slow");
			} else {
				$("#wpemp_link_forgot-"+post_id).hide();
				$('#wpemp_login_div-'+post_id).slideUp("fast");
			}
		});
	});
}
