jQuery(document).ready(function ($) {

	$(document).on(
		"click",
		"span.wpc-media-uploader",
		function(e){
			var custom_uploader;
	        e.preventDefault();
	        if (custom_uploader) {
	            custom_uploader.open();
	            return;
	        }
	        custom_uploader = wp.media.frames.file_frame = wp.media({
	            title: 'Choose Image',
	            button: {
	                text: 'Choose Image'
	            },
	            multiple: false
	        });
	        custom_uploader.on('select', function() {
	            attachment = custom_uploader.state().get('selection').first().toJSON();
	            $( $('span.wpc-media-uploader').attr('data-target') ).val(attachment.url);
	            $( $('span.wpc-media-uploader').attr('data-target') ).trigger("change");
	        });
	        custom_uploader.open();
    	}
    );


	jQuery(document).on("click", "a.wpc-admin-ajax", function(e) {

		e.preventDefault();
		var d = jQuery(this).data('task');

		if( ! d.loadURL ) {
			return;
		}

		if( d.confirm > '' ) {
			try {
				if( ! confirm(eval(d.confirm)) ) { return; }
			} catch(e) {
				if( ! confirm(d.confirm) ) { return; }			
			}
		}

		if( ! d.success ) {
			d.success = 'html';
		}

		if( ! d.onSuccess ) {
			d.onSuccess = 'alert';
		}

		jQuery.get(d.loadURL, function(data) {

			var success;

			switch( d.success ) {

				case 'html':
					success = data > '';
					break;

				case 'html=1':
					success = "1" == data.toString();
					break;

				case 'html=0':
					success = "0" == data.toString();
					break;

				case 'html=D':
					success = /^\d+$/.test(data);
					break;

				default:
					success = false;
					break;

			}

			if( success ) {

				switch( d.onSuccess ) {
					
					case 'alert':

						if( d.successAlert ) {
							alert(d.successAlert);
							return;
						} else {
							alert("Operation successfully completed!");
						}

						break;

					case 'redirect':

						if( d.successAlert ) {
							alert(d.successAlert);
						}

						if( d.successRedir ) {
							window.location.replace(d.successRedir);
							return;
						} else {
							window.location.reload();
						}

						break;

					case 'removeItem':

						if( d.removeItem ) {
							$(d.removeItem).fadeOut(200, function() {
								$(this).remove();
							});
							return;
						}

						break;

					default:
						break;
				}

			} else {
				if( d.failAlert ) {
					alert(d.failAlert);
					return;
				} else {
					alert("Something went wrong, please try again");
				}
			}

		})
		.fail(function() {
			
			if( d.failAlert ) {
				alert(d.failAlert);
				return;
			} else {
				alert("Something went wrong, please try again");
			}

		});


	});


	jQuery(document).on("submit", "form.wpc-admin-ajax", function(e) {
		console.log(e);
		return;
	});

});