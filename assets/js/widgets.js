jQuery(document).ready(function($) {

	jQuery(document).on("change", ".wpc-search-widget select", function(e) {

		var ac = jQuery("option:selected", this).data('action')
		  , op = jQuery("option:selected", this)
		  , fm = op.closest('form.wpcajx2')
		  , tk = {}
		  , it = jQuery('input[type="text"]', fm);

		jQuery('option', this).removeAttr('selected');
		op.attr("selected", "selected");
		it.attr('value', it.val());
		tk.action = ac.ajax_action;
		fm.attr('action', ac.action);
		fm.attr('data-task', JSON.stringify(tk));

		var fm = op.closest('form.wpcajx2')
		  , htm = jQuery(fm).prop('outerHTML');
		
		fm.replaceWith(htm);
		e.preventDefault();

	});

	var wpc_users_widget_loading = function( done ) {
		if( ! done ) {
			jQuery('.wpc-users-widget .wpc-loading2').remove();
			var widget = jQuery('.wpc-users-widget');
			jQuery('form input', widget).prop('disabled', 'disabled');
			widget.addClass('loading');
			widget.append('<div class="wpc-loading2 wpc-loading-dots"><span class="wpc-loading-dots">..</span></div>');
		} else {
			jQuery('form input', widget).prop('disabled', false);
			jQuery('.wpc-users-widget').removeClass('loading');
			jQuery('.wpc-users-widget .wpc-loading2').remove();
		}
	}

});