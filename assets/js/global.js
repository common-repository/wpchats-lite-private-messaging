jQuery(document).ready(function ($) {

	if( "boolean" !== typeof wpc_functions_js_loaded ) {
		var s = document.createElement("script")
		  , b = document.body;
		s.type = "text/javascript";
		s.src = wpc.settings.path_to_assets + 'js/functions.js';
		b.appendChild(s);
	}
	if ( "undefined" === typeof eval( wpc.settings.ajax.disable_multiple_send ) ) { wpc.settings.ajax.disable_multiple_send = false; }
	else { wpc.settings.ajax.disable_multiple_send = eval( wpc.settings.ajax.disable_multiple_send ); }

	$(document).on('submit', '#_wpc_mform', function(e){

		var FormData = $(this).serializeArray()
		  , _m = FormData[0].value
		  , _uq = Math.floor(Math.random() * 999)
		  , form = $(this);


		if( ! ( _m.match(/[a-zA-Z]+/g) > '' ) && _m.length < 1 ) {
			alert(wpc.feedback.err_sending);
			e.preventDefault();
			return;
		}

		jQuery('.wpc_contents').append( wpc_append_message( _uq, _m ) );
		wpc_sound_notif(wpc.settings.path_to_assets + 'sound/sending.mp3');
		//$('body').append('<div class="wpc_do_scroll"></div>');
		jQuery('#_wpc_mform > textarea').val('');
		jQuery('.wpc-seen-notice').hide();

		if ( wpc.settings.ajax.disable_multiple_send ) {
			jQuery( 'textarea', form ).prop('disabled', 'disabled');
			jQuery( '#_wpc_send_button', form ).val('Sending ..');
		}

		wpc_scroll({scrollSpeed: 0, nbvps : 1});

	    $.ajax({
            url: wpc.admin.ajax,
            data: FormData,
            type: 'post',
            success: function(response){

				var _data = JSON.parse(response),
					_tar = jQuery('#mt-'+_uq),
					_psel = '#mt-'+_uq;

				jQuery('.wpc-seen-notice').hide();

				if( typeof _data.message !== "undefined" ) {

					var _html = $(_psel).prop('outerHTML');

					_html = _html
					  .replace(/{{message_id}}/g, _data.message.id)
					  .replace(/{{sender_slug}}/g, _data.message.recipient_slug);

					$(_psel).replaceWith(_html);
					$( '.wpc-more', _psel ).removeAttr( 'style' );
					$( '.message-content-text', _psel ).html( wpc_jq_output_message( _data.message.content ) );
					$( '.wpc-time-int', _psel ).attr( 'data-int', _data.message.date );
					$('span.autosave-note').html('');
					jQuery('#mt-'+_uq).attr('id', 'message-'+_data.message.id);
					wpc_scroll({scrollSpeed: 0, nbvps : 1});
					$('.wpc-load-more a').attr('data-reload', '1');
					$('.wpc-messages .no-messages').fadeOut(100);
					$('.single-pm').attr('data-pm-id', _data.message.pm_id);
			
					wpc_sound_notif(wpc.settings.path_to_assets + 'sound/sent.mp3');
				
				} else {
					console.log( 'Error sending message', 'Could not send message, probably due to its length, content, or the PHP script preventing so.' );
	            	alert( wpc.feedback.err_sending );
					jQuery('#_wpc_mform > textarea').val(_m);
					_tar.remove();
				}

				if ( wpc.settings.ajax.disable_multiple_send ) {
					jQuery( 'textarea', form ).prop('disabled', false);
					jQuery( '#_wpc_send_button', form ).val('Send');
				}

            },
            error: function() {
				console.log( 'Error sending message', 'Could not send message, probably due to its length, content, or the PHP script preventing so.' );
            	
            	if ( wpc.settings.ajax.disable_multiple_send ) {
					jQuery( 'textarea', form ).prop('disabled', false);
					jQuery( '#_wpc_send_button', form ).val('Send');
				}

            	alert( wpc.feedback.err_sending );
				jQuery('#_wpc_mform > textarea').val(_m);
				jQuery('#mt-'+_uq).remove();
            }
	    });

	    e.preventDefault();
	});

	window.setInterval(function () {
		
		if( $.trim(jQuery('#_wpc_mform > textarea').val()) == 0 ) {
			jQuery('#_wpc_mform > input[type="submit"]').prop("disabled", "disabled");
		} else {
			jQuery('#_wpc_mform > input[type="submit"]').prop("disabled", false);
		}

		if( window.location.href.indexOf('?done=') > 0 ) {
			var _url = window.location.href;
			window.history.pushState(null, null, _url.substring(0, _url.indexOf( '?done' )) );
		}

		if( window.location.href.indexOf('_wpc_strip_uri') > 0 ) {
			var _url = window.location.href;
			window.history.pushState(null, null, _url.substring(0, Math.floor(_url.indexOf( '_wpc_strip_uri' ) - 1)) );
		}

		if( ! $(".wpc-modal").is(":visible") && jQuery('body').hasClass('wpcnofy') ) {
		    $('body').removeClass('wpcnofy');
		}

		wpc_remove_ajax_duplicates();

		jQuery('div[role="wpc-credits"]').each(function(i) {
		  if(i > 0) { jQuery(this).remove(); }
		});

		if ( jQuery('.single-message').length ) {
			if ( jQuery('.single-message').last().hasClass('their') ) {
				jQuery('p.wpc-seen-notice').hide();
			}
		}

	}, 400);

	var wpcJsonInt = window.setInterval(function () {

		if ( ! ( eval(wpc.cur_user.id) > 0 ) ) {
			clearInterval(wpcJsonInt);
			return;
		}

		var api_url = wpc.admin.ajax + '?action=wpc_json';

		if( jQuery( '.single-pm' ).length ) {
			if( ! jQuery( '.single-pm' ).hasClass("marking_unread") ) {
				api_url += '&current=' + jQuery( '.single-pm' ).attr('data-pm-id');
			}
		}

		api_url += '&pms=';

		if( jQuery( '.single-pm' ).length ) {
			api_url += jQuery( '.single-pm' ).attr('data-pm-id')+',';
		}

		if( jQuery( '.wpc-archive .message-snippet.unread.sent' ).length ) {
			jQuery( '.wpc-archive .message-snippet.unread.sent' ).each(function(i,element) {
				api_url += jQuery(element).attr('data-pm-id') + ',';
			});
		}
		if( jQuery( '.single-pm' ).length ) {
			if ( jQuery('.single-message.mine.ajax').length && jQuery('.single-message.ajax').last().hasClass('mine') )
				api_url += '&last_item=' + ( jQuery('.single-message.mine.ajax').last().attr('id').replace(/message-/g,'').replace(/mt-/g,'') );
			api_url += "&wpc_recipient="+jQuery('.single-pm').data('recipient-slug');

			/*if ( ! ( jQuery(".single-message").last().hasClass('mine') || jQuery(".single-message").last().hasClass('ajax') ) ) {
				if ( jQuery(".single-message.their").not('.ajax').length && jQuery('p.wpc-seen-notice').is(":hidden") ) {
					api_url += "&last_received=" + jQuery(".single-message.their").not('.ajax').last().attr('id').replace(/message-/g,'');
				}
			} */

		}
		
		$.get( api_url, function(data) {
			window.wpcJSON = JSON.parse(data);

			wpc.create_event('wpc_got_JSON', wpcJSON);

			$('._wpc-online-count').html( wpcJSON.online.count );

			$.each(wpcJSON.online.users, function(index, object) {
				$('span.wpc-time-int.user-'+object.id).attr('data-int', object.int).html('online now');
				$('span.wpc-time-int.user-'+object.id).closest('.wpc-u-status').removeClass('offline').addClass('online');
			});

			$.each(wpcJSON.notifications.unreadExcerpts, function(index, object) {

				if ( $('.wpc-archive').length )
				{

					if( $('#pm-'+object.pm_id).length == 0 ) {

						var _args = {
							"pm_id": object.pm_id,
							"avatar": object.sender_avatar,
							"user_id": object.sender_id,
							"user_name": object.sender,
							"user_status_int": object.sender_int,
							"user_status": '1' == object.sender_online ? 'online now' : 'online ' + object.sender_status_inner + ' ago',
							"online_class": '1' == object.sender_online ? 'online' : 'offline',
							"unread_count": object.unread_count,
							"sent_int": object.date,
							"sent_date": object.date_diff,
							"excerpt": object.excerpt,
							"link": wpc.settings.path_to_messages+object.sender_slug+'/',
							"user_slug": object.sender_slug
						}

						$('.wpc-conversations').append( wpc_append_snippet( _args ) );
						var _html = $('#pm-'+object.pm_id).prop('outerHTML');
						$('#pm-'+object.pm_id).remove();
						$('.wpc-conversations').children(":first").before( _html );
						jQuery('#pm-'+object.pm_id).hide().fadeIn(300).addClass('wpc-shake1');
						setTimeout(function(){
							jQuery('#pm-'+object.pm_id).removeClass('wpc-shake1');
						},500);


					} else {
						
						if( parseInt( $('#pm-'+object.pm_id).attr('data-last-message') ) == parseInt( object.message_id ) )
							return;

						var _target = $('#pm-'+object.pm_id);
						$('.content-text', '#pm-'+object.pm_id).html(' "'+object.excerpt+'"');
						$('.contact-date .wpc-time-int', _target).attr('data-int', object.date);
						var _diff = $('.contact-date .wpc-time-int', _target).attr('data-before') > '' ? $('.contact-date .wpc-time-int', _target).attr('data-before') + ' ' : '';
						_diff += object.date_diff;
						_diff += $('.contact-date .wpc-time-int', _target).attr('data-after') > '' ? ' ' + $('.contact-date .wpc-time-int', _target).attr('data-after') : '';
						$('.contact-date .wpc-time-int', _target).html( _diff );

						$('.contact-date .count', _target).html( '(' + object.unread_count + ')' ).show();

						_target.removeClass('read').addClass('unread').removeClass('sent').addClass('received');
						_target.attr('data-last-message', object.message_id);						
						$('.wpc-conversations').children(":first").before( _target.prop('outerHTML') );
						_target.remove();
						jQuery('#pm-'+object.pm_id).hide().fadeIn(500).addClass('wpc-shake1');
						setTimeout(function(){
							jQuery('#pm-'+object.pm_id).removeClass('wpc-shake1');
						},500);


					}

				}

				else if ( $('.single-pm').length )
				{
					wpc_sidebar_icon( object );

					if( $('.single-pm[data-pm-id="'+object.pm_id+'"]').length == 0 ) {
						if( jQuery('.wpc-nmtc').length == 0 ) {
							jQuery('body').append('<div class="wpc-nmtc"></div>');
						}
						var container = jQuery('.wpc-nmtc');
						container.show();
						if( jQuery( '.wpc-nmt#'+object.message_id ).length == 0 ) {
							if( jQuery('.wpc-nmt.user-'+object.sender_id).length ) {
								jQuery( '.wpc-nmt.user-'+object.sender_id, container ).fadeOut().fadeIn(100, function() {
									$(this).replaceWith(wpc_newm_lighbox_html( object ));
								});
							} else {
								container.append(wpc_newm_lighbox_html(object));
								var t = jQuery( '.wpc-nmt#'+object.message_id );
								t.hide();
								t.addClass("wpc-shake1");
								t.fadeIn();
							}
						}
					}

					return; // for single messages, handled by the following foreach loop.
				}

				else
				{
					
					if( jQuery('.wpc-nmtc').length == 0 ) {
						jQuery('body').append('<div class="wpc-nmtc"></div>');
					}

					var container = jQuery('.wpc-nmtc');
					container.show();

					if( jQuery( '.wpc-nmt#'+object.message_id ).length == 0 ) {
						if( jQuery('.wpc-nmt.user-'+object.sender_id).length ) {
							jQuery( '.wpc-nmt.user-'+object.sender_id, container ).fadeOut().fadeIn(100, function() {
								$(this).replaceWith(wpc_newm_lighbox_html( object ));
							});
						} else {
							container.append(wpc_newm_lighbox_html(object));
							var t = jQuery( '.wpc-nmt#'+object.message_id );
							t.hide();
							t.addClass("wpc-shake1");
							t.fadeIn();
						}
					}

				}

				wpc_sound_notif(wpc.settings.path_to_assets + 'sound/bubble.mp3');

			});


			$.each(wpcJSON.notifications.unread, function(index, object) {

				if ( $('.single-pm').length && parseInt($('.single-pm').attr('data-pm-id')) == parseInt(object.pm_id) )
				{ // we are in the current conversation
					
					if( ! ( $('#message-'+object.message_id).length == 0 ) )
						return; // appended before
					
					args = {
						"id": object.message_id,
						"link": object.sender_link,
						"avatar": object.sender_avatar,
						"name": object.sender,
						"message": object.content,
						"date": object.date,
						"date_diff": object.date_diff,
						"slug": object.sender_slug
					}
					$('.wpc_contents').append( wpc_append_received_message(args) );

					$(".wpc-messages").animate({
			    		scrollTop: $(".wpc-messages")[0].scrollHeight
					}, 0);

					$('.wpc_contents img').one('load',function() {
						$(".wpc-messages").animate({
				    		scrollTop: $(".wpc-messages")[0].scrollHeight
						}, 0);
				    }); // for images

					var unreadCount = $('.wpcajx-unread-count').attr('data-tar-unread-count') > '' ? parseInt( $('.wpcajx-unread-count').attr('data-tar-unread-count') ) : 0;
				    $('.wpcajx-unread-count').attr('data-tar-unread-count', Math.floor(unreadCount + 1));
				    var unreadCount = $('.wpcajx-unread-count').attr('data-unread-count') > '' ? parseInt( $('.wpcajx-unread-count').attr('data-unread-count') ) : 0;
				    $('.wpcajx-unread-count').attr('data-unread-count', Math.floor(unreadCount + 1));

				    wpc_new_message_alert();

				    wpc_scroll({scrollSpeed:0, scrollTo: '#message-'+object.message_id, maths: '-'+(window.innerHeight/2)});
					jQuery('.wpc-seen-notice').hide();
					setTimeout(function(){
						jQuery('.wpc-u-icon.usr-'+object.sender_id+' .wpc-count').fadeOut(200);
					},5000)
					wpc.create_event('wpc_new_message_added', object);

				    // If viewing current conversation, just append message and count. Else, append the tooltip

				    // jQuery('.wpc-u-icon .wpc-count span').html('+3').closest('.wpc-count').show().closest('.wpc-u-icon').addClass('wpc-shake1')
				   /* $('.wpc-sdnwm').remove();
				    var t = $('<div class="wpc-sdnwm"><div><h3>New message</h3><p>"'+object.excerpt+'"</p><span>x</span></div> <a href="#"></a></div>')
				      , p = $('.wpcs-sidebar .wpc-u-icon.usr-'+object.sender_id);

					$('body').append(t);
					
					var t = $('.wpc-sdnwm');
					
					t.css({
					  "top": p.offset().top + ( p.height()/2 ) - ( t.height()/1.95 ),
					  "left": p.offset().left + ( 1.75 * p.width() )
					});*/

					/*if( jQuery('.wpcs-sidebar').length ) {

						console.log(object);
						
						if( jQuery('.wpcs-sidebar .wpc-u-icon.usr-'+object.sender_id).length ) {
							
							var icon = jQuery('.wpcs-sidebar .wpc-u-icon.usr-'+object.sender_id).prop('outerHTML');
							jQuery('.wpcs-sidebar .wpc-u-icon.usr-'+object.sender_id).remove();
							jQuery('.wpcs-sidebar').children().first().before(icon);

						} else {

							var icon = '<div class="wpc-u-icon usr-'+object.sender_id+'" title="">';
							icon += '<span class="wpc-count" style="display: none;"><span></span></span>';
							icon += '<img alt="" src="'+object.sender_avatar+'" class="avatar avatar-40 photo" height="40" width="40">';
							icon += '<a href="'+wpc.settings.path_to_messages+object.sender_slug+'/" class="wpcajx2" data-task="{&quot;loadURL&quot;: &quot;'+wpc.admin.ajax+'?action=wpc&amp;wpc_messages=1&amp;wpc_recipient='+object.sender_slug+'&quot;}"></a>';
							icon += '</div>';

							if( jQuery('.wpcs-sidebar').children().length ) {
								jQuery('.wpcs-sidebar').children().first().before( icon );
							} else {
								jQuery('.wpcs-sidebar').append(icon);
							}

						}
					}*/
					
				} else if ( $('.single-pm').length ) { // we are in another conversation
					if( jQuery('.wpc-nmtc').length == 0 ) {
						jQuery('body').append('<div class="wpc-nmtc"></div>');
					}

					var container = jQuery('.wpc-nmtc');
					container.show();

					if( jQuery( '.wpc-nmt#'+object.message_id ).length == 0 ) {
						if( jQuery('.wpc-nmt.user-'+object.sender_id).length ) {
							jQuery( '.wpc-nmt.user-'+object.sender_id, container ).fadeOut().fadeIn(100, function() {
								$(this).replaceWith(wpc_newm_lighbox_html( object ));
							});
						} else {
							container.append(wpc_newm_lighbox_html(object));
							var t = jQuery( '.wpc-nmt#'+object.message_id );
							t.hide();
							t.addClass("wpc-shake1");
							t.fadeIn();
						}
					}
				}

				wpc_sound_notif(wpc.settings.path_to_assets + 'sound/bubble.mp3');
				wpc.counter.set( { pm_id: object.pm_id } );
				wpc_put_title();

			});

			if( wpcJSON.reads.length && jQuery('.single-message').last().hasClass('mine') ) {
				$.each(wpcJSON.reads, function(index, object) {
					if( $('.single-pm[data-pm-id="'+object.pm_id+'"]').length ) {
						if ( jQuery('p.wpc-seen-notice').length && jQuery('p.wpc-seen-notice').is(":visible") )
							return;
						var notice = jQuery('.single-pm[data-pm-id="'+object.pm_id+'"] p.wpc-seen-notice');
						if( notice.length ) {
							var span = jQuery('span', notice);
							span.attr('data-int', object.int);
							var html = span.attr('data-before') + ' %s ' + span.attr('data-after');
							span.text( html.replace(/%s/g, object.diff) );
							notice.show('fast');
						}
					}
					else if ( $('.wpc-archive').length ) {
						//jQuery( '.wpc-archive .message-snippet.read.sent' ).removeClass("read").addClass("unread");
						jQuery( "#pm-"+object.pm_id+'.sent' ).removeClass("unread").addClass("read");
					}
				});
			} else {
				if( jQuery('.single-pm').length ) {
					jQuery('p.wpc-seen-notice').hide();
				}
			}

		});

	}, parseInt(wpc.settings.ajax.json_interval));

	var wpc_sidebar_icon = function( object ) {
		if( jQuery('.wpcs-sidebar').length ) {

			if( ! ( jQuery('.wpcs-sidebar').is(':visible') ) ) {
				return;
			}
			
			if( jQuery('.wpcs-sidebar .wpc-u-icon.usr-'+object.sender_id).length ) {
					
				var isFirst = jQuery('.wpcs-sidebar').children().first().hasClass('usr-'+object.sender_id)
				  , icon = jQuery('.wpcs-sidebar .wpc-u-icon.usr-'+object.sender_id).prop('outerHTML');
				
				if( isFirst ) {
					jQuery('.wpcs-sidebar .wpc-u-icon.usr-'+object.sender_id).remove();
				} else {
					jQuery('.wpcs-sidebar .wpc-u-icon.usr-'+object.sender_id).slideUp(200, function() {
						jQuery(this).remove();
					});					
				}

				if( jQuery('.wpcs-sidebar .wpc-u-icon').length > 0 ) {
					jQuery('.wpcs-sidebar').children().first().before(icon);
					jQuery('.wpcs-sidebar').children().first().hide();
				} else {
					jQuery('.wpcs-sidebar').append(icon);
					jQuery('.wpcs-sidebar').children().first().hide();
				}
				
				var icon = jQuery('.wpcs-sidebar .wpc-u-icon.usr-'+object.sender_id);
				$('.wpc-count', icon).children('span').text('+'+object.unread_count).closest('.wpc-count').show();
				
				if( isFirst ) {
					$(icon).show();
					jQuery(icon).addClass('wpc-shake1');
					setTimeout(function() { jQuery(icon).removeClass('wpc-shake1'); },1000);
					wpc_sidebar_tooltip(object);
				} else {
					$(icon).slideDown();
					setTimeout(function() {
						jQuery(icon).addClass('wpc-shake1');
						wpc_sidebar_tooltip(object);
					}, 400 );
					setTimeout(function() { jQuery(icon).removeClass('wpc-shake1'); }, 1400);
				}

			} else {

				var icon = '<div class="wpc-u-icon usr-'+object.sender_id+'" title="'+object.sender_name+'">';
				icon += '<span class="wpc-count"><span>+'+object.unread_count+'</span></span>';
				icon += '<img alt="" src="'+object.sender_avatar+'" class="avatar avatar-40 photo" height="40" width="40">';
				icon += '<a href="'+wpc.settings.path_to_messages+object.sender_slug+'/" class="wpcajx2" data-task="{&quot;loadURL&quot;: &quot;'+wpc.admin.ajax+'?action=wpc&amp;wpc_messages=1&amp;wpc_recipient='+object.sender_slug+'&quot;}"></a>';
				icon += '</div>';

				if( jQuery('.wpcs-sidebar').children().length ) {
					jQuery('.wpcs-sidebar').children().first().before( icon );
				} else {
					jQuery('.wpcs-sidebar').append(icon);
				}
				var icon = jQuery('.wpcs-sidebar .wpc-u-icon.usr-'+object.sender_id);
				jQuery(icon).hide().slideDown('fast');

				setTimeout(function() {
					jQuery(icon).addClass('wpc-shake1');
					setTimeout(function() {
						jQuery(icon).removeClass('wpc-shake1');
					},1000);
					wpc_sidebar_tooltip(object);
				},500);

			}

		}
	}

	var wpc_sidebar_tooltip = function( object ) {

		if( jQuery('.wpcs-sidebar').length == 0 ) { return; }
		if( ! ( jQuery('.wpcs-sidebar').is(':visible') ) ) { return; }

		$('.wpc-sdnwm').remove();
	    var t = $('<div class="wpc-sdnwm wpc-shake1"><div><h3>'+wpc_translate('New message')+'</h3><p>'+object.sender_short_name+': "'+object.excerpt+'"</p><span>x</span></div><a href="'+wpc.settings.path_to_messages+object.sender_slug+'/" class="wpcajx2" data-task="{&quot;loadURL&quot;: &quot;'+wpc.admin.ajax+'?action=wpc&amp;wpc_messages=1&amp;wpc_recipient='+object.sender_slug+'&quot;}"></a></div>')
	      , p = $('.wpcs-sidebar .wpc-u-icon.usr-'+object.sender_id);


		$('body').append(t);
		
		var t = $('.wpc-sdnwm');
		
		t.css({
		  "top": p.offset().top + ( p.height()/2 ) - ( t.height()/1.95 ),
		  "left": p.offset().left + ( 1.75 * p.width() )
		});

		t.hide().fadeIn(500);

		setTimeout(function() {t.removeClass('wpc-shake1');}, 1000);

	}

	$(document).on('click', '.wpc-sdnwm > div span', function(e){
		$('.wpc-sdnwm').fadeOut(200,function(){$('.wpc-sdnwm').remove()});
	});

	$(document).mouseup(function (e){
	    var container = $(".wpc-sdnwm");
	    if (!container.is(e.target) && container.has(e.target).length === 0) {
	        container.fadeOut(200,function(){container.remove()});
	    }

	    if( $('.single-pm').length ) {
	    	wpc.counter.unset( { pm_id: parseInt( $('.single-pm').attr('data-pm-id') ) } );
	    	wpc_put_title();
	    }

	});


	$(document).on('click', 'a.wpcajx', function(e){

		var _action = $(this).attr('data-action');
		var _href = $(this).attr('href');
		var _loadIn = $(this).attr('data-load-in') > '' ? $(this).attr('data-load-in') : '.wpc';

		if( !($(_loadIn).length) ) {
			window.location.href = _href;
			return;
		}
		
		switch( _action ) {

			case 'view-profile':

				if ( ! ( _href.indexOf(wpc.settings.path_to_users) > -1 ) ) {
					window.location.replace(_href);
					return;
				}

				var _slug = $(this).attr('data-slug');
				wpc_load( wpc.admin.ajax + '?action=wpc&wpc_users=1&wpc_user='+_slug, _href, {"scrollTo": ".wpc"} );

				break;

			case 'load-conversation':

				var _slug = $(this).attr('data-slug');
				var _scroll = {"scrollTo": ".wpc"}
				wpc_load( wpc.admin.ajax + '?action=wpc&wpc_messages=1&wpc_recipient='+_slug, _href, _scroll);

				break;

			case 'load-users':

				var _tar = $(this).attr('data-users');
				var requestUrl = wpc.admin.ajax + '?action=wpc&wpc_users=1';
				
				if( 'online' == _tar )
					requestUrl += '&wpc_online_users=1';

				if( 'blocked' == _tar )
					requestUrl += '&wpc_blocked_users=1';

				wpc_load( requestUrl, _href, {"scrollTo": ".wpc"} );

				break;

			case 'edit-profile':

				var _slug = $(this).attr('data-slug');
				wpc_load( wpc.admin.ajax + '?action=wpc&wpc_users=1&wpc_user='+_slug+'&wpc_edit_user=1', _href, {"scrollTo": ".wpc"} );

				break;

			case 'block':

				if( confirm( wpc.conf.block_u ) ) {

					var _user = $(this).attr('data-user');
					window.wpcThis = $(this);
					$.get( wpc.admin.ajax + '?action=wpc_actions&do=block&user='+_user, function(data) {
						if( '1' == data ) {
							wpcThis.attr('data-action', 'unblock');
							wpcThis.attr('href', wpcThis.attr('href').replace('do=block', 'do=unblock'));
							wpcThis.html( 'Unblock' );
							var _countSpan = $('._wpc-blocked-count');
							_countSpan.html( parseInt( _countSpan.html() ) + 1 );
						}
					});

				}

				break;

			case 'unblock':

				var _user = $(this).attr('data-user');
				window.wpcThis = $(this);
				$.get( wpc.admin.ajax + '?action=wpc_actions&do=unblock&user='+_user, function(data) {
					if( '1' == data ) {
						wpcThis.attr('data-action', 'block');
						wpcThis.attr('href', wpcThis.attr('href').replace('do=unblock', 'do=block'));
						wpcThis.html( 'Block' );
						var _countSpan = $('._wpc-blocked-count');
						_countSpan.html( parseInt( _countSpan.html() ) - 1 );
					} else {
						alert(wpc.feedback.err_general);
					}
				})

				.fail(function() {
					alert(wpc.feedback.err_general);
				});

				break;

			case 'delete':

				var _m = $(this).attr('data-message'),
					_slug = $(this).attr('data-slug'),
					_url = wpc.admin.ajax + '?action=wpc_actions&do=delete[]&wpc_messages=1&wpc_recipient=' + _slug;

				if( parseInt(_m) > 0 ) {
					_url = _url.replace('[]', '&m='+_m);
					var _dialog = wpc.conf.del_m;
				} else {
					_url = _url.replace('[]', '');
					var _dialog = wpc.conf.del_c;
				}

				if( confirm(_dialog) ) {

					$.get( _url, function(data) {
						if( '1' == data ) {

							if( parseInt(_m) > 0 ) {

								$('#message-' + parseInt(_m)).fadeOut(600, function() {
									$(this).remove();
								});

							} else {
								wpc_load( wpc.admin.ajax + '?action=wpc&wpc_messages=1&done=delete', wpc.settings.path_to_messages );
							}

						} else {
							alert(wpc.feedback.err_general);
						}
					})

					.fail(function() {
						alert(wpc.feedback.err_general);
					});

				}

				break;

			case 'report':

				var _m = $(this).attr('data-message');
				var _slug = $(this).attr('data-slug');

				if( parseInt( _m ) > 0 ) {
					wpc_load( wpc.admin.ajax + '?action=wpc&wpc_messages=1&wpc_recipient='+_slug+'&wpc_report=1&wpc_report_message='+_m, $(this).attr('href') );
				} else {
					wpc_load( wpc.admin.ajax + '?action=wpc&wpc_messages=1&wpc_recipient='+_slug+'&wpc_report=1', $(this).attr('href') );
				}

				break;

			case 'load-moderation':

				wpc_load( wpc.admin.ajax + '?action=wpc&wpc_mod=1', _href, {"scrollTo": ".wpc"} );

				break;

			case 'load-messages':

				wpc_load( wpc.admin.ajax + '?action=wpc&wpc_messages=1', _href, {"scrollTo": ".wpc"} );
				
				break;

			case 'mod-delete-message':

				if( confirm( wpc.conf.del_m ) ) {

					var _id = $(this).attr('data-id');
					var _by = $(this).attr('data-by');
					_href = _href.replace( _href.substring( _href.indexOf('?_action') ), '' );

					$.get( wpc.admin.ajax+'?action=wpc_actions&do=moderation&_action=delete&_id='+_id+'&_by='+_by, function(data) {
						if( '1' == data ) {			
							wpc_load( wpc.admin.ajax + '?action=wpc&wpc_mod=1&done=m-delete', _href, {"scrollTo": ".wpc"} );
						} else {
							alert(wpc.feedback.err_general);
						}
					})

					.fail(function() {
						alert(wpc.feedback.err_general);
					});

				}

				break;

			case 'mod-delete-conversation':

				if( confirm( wpc.conf.del_c ) ) {

					var _id = $(this).attr('data-id');
					var _by = $(this).attr('data-by');
					_href = _href.replace( _href.substring( _href.indexOf('?_action') ), '' );

					$.get( wpc.admin.ajax+'?action=wpc_actions&do=moderation&_action=delete-conversation&_id='+_id+'&_by='+_by, function(data) {
						if( '1' == data ) {			
							wpc_load( wpc.admin.ajax + '?action=wpc&wpc_mod=1&done=c-delete', _href, {"scrollTo": ".wpc"} );
						} else {
							alert(wpc.feedback.err_general);
						}
					})

					.fail(function() {
						alert(wpc.feedback.err_general);
					});

				}

				break;

			case 'mod-delete-report':

				if( confirm( wpc.conf.del_r ) ) {

					var _id = $(this).attr('data-id');
					var _by = $(this).attr('data-by');
					_href = _href.replace( _href.substring( _href.indexOf('?_action') ), '' );

					$.get( wpc.admin.ajax+'?action=wpc_actions&do=moderation&_action=delete-report&_id='+_id+'&_by='+_by, function(data) {
						if( '1' == data ) {			
							wpc_load( wpc.admin.ajax + '?action=wpc&wpc_mod=1&done=report-delete', _href, {"scrollTo": ".wpc"} );
						} else {
							alert(wpc.feedback.err_general);
						}
					})

					.fail(function() {
						alert(wpc.feedback.err_general);
					});

				}

				break;

			case 'mod-ban':

				if( confirm( wpc.conf.ban_u ) ) {

					var _id = $(this).attr('data-id');
					_href = _href.replace( _href.substring( _href.indexOf('?_action') ), '' );

					$.get( wpc.admin.ajax+'?action=wpc_actions&do=moderation&_action=ban&_id='+_id, function(data) {
						if( '1' == data ) {			
							wpc_load( wpc.admin.ajax + '?action=wpc&wpc_mod=1&done=ban', _href, {"scrollTo": ".wpc"} );
						} else {
							alert(wpc.feedback.err_general);
						}
					})

					.fail(function() {
						alert(wpc.feedback.err_general);
					});

				}

				break;

			case 'mod-unban':

				if( confirm( wpc.conf.unban_u ) ) {

					var _id = $(this).attr('data-id');
					_href = _href.replace( _href.substring( _href.indexOf('?_action') ), '' );

					$.get( wpc.admin.ajax+'?action=wpc_actions&do=moderation&_action=unban&_id='+_id, function(data) {
						if( '1' == data ) {			
							wpc_load( wpc.admin.ajax + '?action=wpc&wpc_mod=1&done=unban', _href, {"scrollTo": ".wpc"} );
						} else {
							alert(wpc.feedback.err_general);
						}
					})

					.fail(function() {
						alert(wpc.feedback.err_general);
					});

				}

				break;

			case 'paginate-users':

				var _users = $(this).attr('data-users'),
					_q = $(this).attr('data-q'),
					_p = $(this).attr('data-page'),
					_sort = $(this).attr('data-sort'),
					requestUrl = wpc.admin.ajax + '?action=wpc&wpc_users=1&wpc_paged='+_p;

				switch( _users ) {
					case 'all':
						if( _q > '' )
							requestUrl += '&q='+wpc_parse_search_query(_q);
						if( _sort > '' )
							requestUrl += '&sort='+_sort;
						wpc_load( requestUrl, _href );
						break;
					case 'blocked':
						requestUrl += '&wpc_blocked_users=1';
						if( _q > '' )
							requestUrl += '&q='+wpc_parse_search_query(_q);
						if( _sort > '' )
							requestUrl += '&sort='+_sort;
						wpc_load( requestUrl, _href );
						break;
					case 'online':
						requestUrl += '&wpc_online_users=1';
						if( _q > '' )
							requestUrl += '&q='+wpc_parse_search_query(_q);
						if( _sort > '' )
							requestUrl += '&sort='+_sort;
						wpc_load( requestUrl, _href );
						break;
					default:
						window.location.href = _href;
						break;
				}

				break;

			case 'paginate-messages':

				var _q = $(this).attr('data-q'),
					_p = $(this).attr('data-page'),
					_r = $(this).attr('data-slug'),
					_l = $(this).attr('data-last'),
					requestUrl = wpc.admin.ajax + '?action=wpc&wpc_messages=1&wpc_recipient='+_r+'&q=[q]&wpc_paged='+_p
				  , _e = $(this);

				if( parseInt( _p ) <= 0 )
					return false;

				if( _q > '' ) { requestUrl = requestUrl.replace( '[q]', wpc_parse_search_query(_q) ); } 
				else { requestUrl = requestUrl.replace( '&q=[q]', '' ); }

				if( '1' == $(this).attr('data-reload') ) {
					var _scroll = {"scrollTo": ".wpc"}
					_href = _href.replace( _href.substring( _href.indexOf('page/') ), '' );
					wpc_load( wpc.admin.ajax + '?action=wpc&wpc_messages=1&wpc_recipient='+_r, _href, _scroll);
					//$(this).removeAttr('data-reload');
					return false;
				}

				_e.text( wpc_translate('loading')+' ..');

				$.get( requestUrl, function(data) {
					if( '0' !== data ) {
						$('body').append('<div class="wpc_pagi_data" style="display:none"></div>');
						$('.wpc_pagi_data').html(data);
						var _html = $('.wpc_pagi_data .wpc_contents').html();
						$('.wpc_pagi_data').remove();
						$(".wpc .wpc_contents").children(":first").before( _html );
						var _next = Math.floor( parseInt(_p) + 1 );
						if( _next > parseInt( _l ) ) {
							$('.wpc-load-more').fadeOut(200);
						} else {
							$('.wpc-load-more a').attr('data-page', _next);
							$('.wpc-load-more a').attr( 'href', _href.replace( '/page/'+_p+'/', '/page/'+_next+'/' ) )
						}
						history.pushState(null, null, _href);
						wpc_after_ajax_done();
						_e.text( wpc_translate('load more') );
					} else {
						alert(wpc.feedback.err_general);
						_e.text( wpc_translate('load more') );
					}

				})

				.fail(function() {
					alert(wpc.feedback.err_general);
					_e.text( wpc_translate('load more') );
				});

				break;

			case 'forward':
				var mid = $(this).attr('data-message')
				    , _href = $(this).attr('href')
				    , ttl = document.title
				    , nttl = wpc_translate('Forward Message');
				if( jQuery('.wpc-modal .wpc-fwd.m-'+mid).length ) {
					jQuery('.wpc-modal .wpc-fwd.m-'+mid).closest('.wpc-modal').fadeIn(200);
					jQuery('.wpc-modal .wpc-fwd.m-'+mid+' input[type="text"]').focus();
					var d = jQuery('.wpc-modal').data('task');
					if( d.onLoadTitle ) document.title = d.onLoadTitle;

				} else {
					var url = wpc.admin.ajax+'?action=wpc_actions&do=fwd&wpc_messages=1&wpc_recipient=[r]&wpc_forward_message=[m]';
					url = url.replace('[r]', $(this).attr('data-slug')).replace('[m]', $(this).attr('data-message'));
					wpc_say_loading();
					$.get(url, function(html) {
						wpcLoadModal(html, _href.replace( _href.substring( _href.indexOf('forward/') ), '' ), nttl, ttl);
						wpc_say_loading(1);
						jQuery('.wpc-md-qu-ico').removeAttr('onclick');
						jQuery('.wpc-md-qu-ico').each(function() {
							jQuery(this).attr("onclick", jQuery(this).attr('data-jq-onclick'));
							jQuery(this).removeAttr('data-jq-onclick');
						});
					});
				}
				history.pushState(null, null, _href);

				break;

			case 'add-mod':

				var uid = parseInt( $(this).attr('data-user') );

				if( confirm( wpc.conf.add_mod ) ) {}

				wpc.admin.ajax + '?action=wpc&wpc_mod=1&wpc_mods=1&add=[uid]';

				break;

			case 'remove-mod':

				var uid = parseInt( $(this).attr('data-user') );

				if( confirm( wpc.conf.remove_mod ) ) {
					$.get( wpc.admin.ajax + '?action=wpc&wpc_mod=1&wpc_mods=1&remove='+uid, function(data) {

						if( '1' == data ) {
							jQuery('.wpc-u-snippet.user-'+uid).fadeOut();
						} else {
							alert(wpc.feedback.err_general);
						}

					})
					.fail(function() {
						alert(wpc.feedback.err_general);
					});
				}

				;

				break;

			default:
				window.location.href = _href;
				break;

		}

		e.preventDefault();

	});

	$(document).on('submit', 'form.wpcajx', function(e){
	
		var _action = $(this).attr('data-action');
		var _url = $(this).attr('action');
		
		switch( _action ) {

			case 'fwd-message':

				var data = $(this).serializeArray();

				for( i in data ) {
					if( 'message_id' == data[i].name ) {
						var mid = data[i].value;
					} else if( 'user_id' == data[i].name ) {
						var uid = data[i].value;
					} else if( 'user_slug' == data[i].name ) {
						var uslug = data[i].value;
					} else if( '_wpc_nonce' == data[i].name ) {
						var nonce = data[i].value;
					}
				}

				if( ! ( parseInt(mid) > 0 ) || ! ( parseInt(uid) > 0 ) ) {
					alert(wpc.feedback.err_general);
					return;
				}

				jQuery('.wpc-fwd').closest('.wpc-modal').fadeOut(200);
				wpc_say_loading();

				jQuery.get(wpc.admin.ajax+'?action=wpc_actions&do=fwd-message&mid='+mid+'&uid='+uid+'&_wpc_nonce='+nonce, function(data) {
					if( '0' !== data ) {
						wpc_load( wpc.admin.ajax + '?action=wpc&wpc_messages=1&wpc_recipient='+uslug, wpc.settings.path_to_messages+uslug+'/', {"scrollTo": ".wpc"});
					} else {
						alert(wpc.feedback.err_fwd);
						wpc_say_loading(1);
					}
				})
				.fail(function() {
					alert(wpc.feedback.err_fwd);
					wpc_say_loading(1);
				});

				break;

			case 'search-users':

				var _tar = $(this).attr('data-users'),
					_q = $( 'input[type="text"]', this).val(),
					requestUrl = wpc.admin.ajax + '?action=wpc&wpc_users=1[]&q='+wpc_parse_search_query( _q );
					

				if( 'all' == _tar ) {
					requestUrl = requestUrl.replace('[]', '');
				}
				if( 'blocked' == _tar ) {
					requestUrl = requestUrl.replace('[]', '&wpc_blocked_users=1');
				}
				if( 'online' == _tar ) {
					requestUrl = requestUrl.replace('[]', '&wpc_online_users=1');
				}
					
				_url += '?q=' + wpc_parse_search_query( _q );
				wpc_load( requestUrl, _url );

				break;

			case 'report':

				var requestUrl = wpc.admin.ajax + '?action=wpc_actions&do=report&wpc_messages=1&wpc_recipient=[slug]&wpc_report=1&wpc_report_message=[id]&wpc_report_body=[report]',
					_slug = $(this).attr('data-slug'),
					_m = $(this).attr('data-message'),
					_report = $( 'textarea', this).val();

				if( $.trim(_report) == 0 ) {
					alert(wpc.feedback.empty_rep);
				} else {

					if( parseInt( _m ) > 0 ) {
						requestUrl = requestUrl.replace('[slug]', _slug)
							.replace('[id]', _m)
							.replace('[report]', encodeURIComponent(_report));

					} else {
						requestUrl = requestUrl.replace('[slug]', _slug)
							.replace('&wpc_report_message=[id]', '')
							.replace('[report]', encodeURIComponent(_report));
					}

					wpc_say_loading();

					$.get( requestUrl, function(data) {
						if( '1' == data ) {
							
							wpc_say_loading(true);

							var __url = wpc.admin.ajax + '?action=wpc&wpc_messages=1&wpc_recipient=[slug]&done=[type]-report';
							_url = _url.replace( _url.substring( _url.indexOf('report/') ), '' );
							__url = __url.replace( '[slug]', _slug );

							if( parseInt( _m ) > 0 ) {
								__url = __url.replace( '[type]', 'm' );
								wpc_load( __url, _url );
							} else {
								__url = __url.replace( '[type]', 'c' );
								wpc_load( __url, _url );
							}

						} else {
							alert(wpc.feedback.err_general);
							wpc_say_loading(true);
						}
					})

					.fail(function() {
						alert(wpc.feedback.err_general);
						wpc_say_loading(true);
					});

				}

				break;

			case 'delete-report':

				var requestUrl = wpc.admin.ajax + '?action=wpc_actions&do=report&wpc_messages=1&wpc_recipient=[slug]&wpc_report=1&wpc_report_message=[id]&wpc_delete_report=1',
					_slug = $(this).attr('data-slug'),
					_m = $(this).attr('data-message');

				requestUrl = requestUrl.replace('[slug]', _slug);

				if( parseInt( _m ) > 0 ) {
					requestUrl = requestUrl.replace('[id]', _m);
				} else {
					requestUrl = requestUrl.replace('&wpc_report_message=[id]', _m);
				}

				wpc_say_loading();

				$.get( requestUrl, function(data) {
					if( '1' == data ) {
						
						wpc_say_loading(true);

						var __url = wpc.admin.ajax + '?action=wpc&wpc_messages=1&wpc_recipient=[slug]&done=[type]-report-delete';
						_url = _url.replace( _url.substring( _url.indexOf('report/') ), '' );
						__url = __url.replace( '[slug]', _slug );

						if( parseInt( _m ) > 0 ) {
							
							__url = __url.replace( '[type]', 'm' );
							wpc_load( __url, _url );

						} else {
							// when for conversation
						}

					} else {
						alert(wpc.feedback.err_general);
						wpc_say_loading(true);
					}
				})

				.fail(function() {
					alert(wpc.feedback.err_general);
					wpc_say_loading(true);
				});

				break;

			case 'upload':

				wpc_say_loading();
    			
    			var _input = $('input[type="file"]', this)
			      , file_data = $(_input).prop('files')[0]
			      , form_data = new FormData()
				  , _tar = $(this).attr('data-target')
				  , _form = $(this);
			    form_data.append('file', file_data);

			    $(_input).prop('disabled', 'disabled');
			    $('input[type="submit"]', this).prop('disabled', 'disabled');

			    $.ajax({
		            url: wpc.admin.ajax+'?action=wpc_upload',
		            dataType: 'text',
		            cache: false,
		            contentType: false,
		            processData: false,
		            data: form_data,                         
		            type: 'post',
		            success: function(response){
		            	wpc_say_loading(true);
		                if( '0' !== response ) {
		                	var _bf = $(_tar).val() > '' ? ' ' : '';
							$(_tar).val( $(_tar).val() +_bf+ '[img]' + response + '[/img]' );
							$(_tar).trigger('change');
							$(_form).trigger('reset');
		                } else {
		                	alert(wpc.feedback.err_upload);
							$(_form).trigger('reset');
		                }
		                $(_input).prop('disabled', false);
			    		$('input[type="submit"]', _form).prop('disabled', false);
			    		$('.wpc-img-cont').fadeOut(200);
		            },
		            error: function() {
		               	alert(wpc.feedback.err_upload);
		            	wpc_say_loading(true);
						$(_form).trigger('reset');
						$(_input).prop('disabled', false);
			    		$('input[type="submit"]', _form).prop('disabled', false);
		            }
			    });

			    return false

			    break;

			case 'sort-users':

				var _users = $(this).attr('data-users'),
					_sort = $('select', this).val(),
					requestUrl = wpc.admin.ajax + '?action=wpc&wpc_users=1',
					_href = wpc.settings.path_to_users,
					_q = $('input[name="q"]') > '' ? $('input[name="q"]').val() : '';

				// if( _sort.length <= 0 )
				//	return false;

				switch( _users ) {
					case 'all': // ?q=[] ?sort=[]
						if( _q > '' ) {
							requestUrl += '&q='+wpc_parse_search_query(_q);
							_href += '?q='+wpc_parse_search_query(_q);
						}
						if( _sort > '' ) {
							requestUrl += '&sort='+_sort;
							_href += _q > '' ? '&sort=' : '?sort=';
							_href += _sort;
						}
						wpc_load( requestUrl, _href );
						break;
					case 'blocked':
						requestUrl += '&wpc_blocked_users=1';
						if( _q > '' ) {
							requestUrl += '&q='+wpc_parse_search_query(_q);
							_href += '?q='+wpc_parse_search_query(_q);
						}
						if( _sort > '' ) {
							requestUrl += '&sort='+_sort;
							_href += _q > '' ? '&sort=' : '?sort=';
							_href += _sort;
						}
						wpc_load( requestUrl, _href );
						break;
					case 'online':
						requestUrl += '&wpc_online_users=1';
						if( _q > '' ) {
							requestUrl += '&q='+wpc_parse_search_query(_q);
							_href += '?q='+wpc_parse_search_query(_q);
						}
						if( _sort > '' ) {
							requestUrl += '&sort='+_sort;
							_href += _q > '' ? '&sort=' : '?sort=';
							_href += _sort;
						}
						wpc_load( requestUrl, _href );
						break;
				}

				break;

				case 'update-profile':

					wpc_say_loading();

				   	var form_data = new FormData(),
						_tar = $(this).attr('data-target'),
						_user = $(this).attr('data-user');

				    $.ajax({
			            url: wpc.admin.ajax+'?action=wpc_actions&do=profile-update&wpc_user='+_user,
			            data: $(this).serializeArray(),
			            type: 'post',
			            success: function(response){

							wpc_load(
								wpc.admin.ajax + '?action=wpc&wpc_users=1&wpc_user='+_user+'&wpc_edit_user=1&done=edit-profile',
								wpc.settings.path_to_users + _user + '/edit/',
								{"scrollTo": ".wpc"}
							);

			            },
			            error: function() {
			            	alert(wpc.feedback.err_general);
			            	wpc_say_loading(true);
			            }
				    });

					break;

			case 'fwd-search':

				var q = wpc_parse_search_query( this.querySelector('input[type="text"]').value )
				   , m = $(this).attr('data-message')
				   , r = $(this).attr('data-slug');
				wpc_say_loading();
				jQuery.get(
					wpc.admin.ajax+'?action=wpc_actions&do=fwd&wpc_messages=1&wpc_recipient='+r+'&wpc_forward_message='+m+'&q='+q,
					function(html) {
						jQuery('.wpc-fwd').replaceWith(html);
						wpc_say_loading(1);
						jQuery('.wpc-md-qu-ico').removeAttr('onclick');
						jQuery('.wpc-md-qu-ico').each(function() {
							jQuery(this).attr("onclick", jQuery(this).attr('data-jq-onclick'));
							jQuery(this).removeAttr('data-jq-onclick');
						});
					}
				)
				.fail(function() {
					wpc_say_loading(1);
				});


				break;

		}

		e.preventDefault();

	});

	$('select.wpc-u-switch').removeAttr('onchange');

	$(document).on('change', 'select.wpc-u-switch', function(e){
	
		var _filter = $(this).val(),
			_url = wpc.settings.path_to_users,
			requestUrl = wpc.admin.ajax + '?action=wpc&wpc_users=1[]',
			_q = $(this).attr('data-q');

		if( 'all' == _filter || 'online' == _filter || 'blocked' == _filter ) {

			if( 'all' == _filter ) {
				requestUrl = requestUrl.replace('[]', '');
			}

			if( 'blocked' == _filter ) {
				requestUrl = requestUrl.replace('[]', '&wpc_blocked_users=1');
				_url += 'blocked/';
			}

			if( 'online' == _filter ) {
				requestUrl = requestUrl.replace('[]', '&wpc_online_users=1');
				_url += 'online/';
			}

			if( _q > '' ) {
				requestUrl += '&q='+wpc_parse_search_query(_q);
				_url += '?q='+wpc_parse_search_query(_q);
			}
				
			wpc_load( requestUrl, _url );

			e.preventDefault();

		}

	});

	$.fn.extend({
		wpc_load: function() {
			wpc_load( url, selector = $(this) );
		}
	});

	function wpc_load( url, requestUrl, scroll, selector ) {

		if( ! url ) { return; }

		if( "string" !== typeof selector ) {
			selector = '.wpc';
		}

		wpc_say_loading();

		$.get( url, function(data) {
			$(selector).replaceWith( data );
		})

		.done(function() {
			
			wpc_say_loading(true);
	
			wpc_put_title();

			if( requestUrl > '' ) {
				history.pushState(null, null, requestUrl);
			}

			wpc_after_ajax_done();

			if( scroll.scrollTo ) {

				if( "string" !== typeof scroll.speed )
					scroll.speed = 1000;

				if( "string" !== typeof scroll.parent )
					scroll.parent = "html, body";

				if( "string" !== typeof scroll.minus )
					scroll.minus = 40;

				$(scroll.parent).animate({
		            scrollTop: $(scroll.scrollTo).offset().top - scroll.minus
		        }, scroll.speed);

				if( $(".wpc-messages").is(":visible") ) {
			        $('.wpc_contents img').one('load',function() {
						$(".wpc-messages").animate({
				    		scrollTop: $(".wpc-messages")[0].scrollHeight
						}, 0);
				    }); // for images
			    }

			}

		})

		.fail(function() {
			alert(wpc.feedback.err_load);
			wpc_say_loading(true);
		});

	}(false, '', {"scrollTo": false}, '.wpc')

	var wpc_after_ajax_done = function() {
		wpc_remove_ajax_duplicates();
		$('select.wpc-u-switch').removeAttr('onchange');
		$.each($('a.wpcajx'), function (index, object) {
			if( $(this).attr('onclick') > '' ) {
				$(this).attr('data-onclick', $(this).attr('onclick'));
				$(this).removeAttr('onclick');
			}
		});
		$.each($('form.wpcajx'), function (index, object) {
			if( $(this).attr('onchange') > '' ) {
				$(this).attr('data-onchange', $(this).attr('onchange'));
				$(this).removeAttr('onchange');
			}
		});
		$('.wpc-tooltip-cont').fadeOut();
		$('.wpc-load-more a').text('load more');
		jQuery('a.wpcajx2').each(function() {
			var t = jQuery(this);
			if( t.attr('onclick') > '' && ! t.hasClass('wckevts') ) {
				t.attr('data-onclick', t.attr('onclick'));
				t.removeAttr('onclick');
			}
		});
		jQuery('form.wpcajx2').each(function() {
			var t = jQuery(this);
			if( t.attr('onsubmit') > '' ) {
				t.attr('data-onsubmit', t.attr('onsubmit'));
				t.removeAttr('onsubmit');
			}
		});
		jQuery('.wpc-md-qu-ico').each(function() {
			jQuery(this).attr("onclick", jQuery(this).attr('data-jq-onclick'));
			jQuery(this).removeAttr('data-jq-onclick');
		});
		wpcRemoveEvts();
		jQuery('.single-message p.attachement-unavailable').each(function(i,elem){
			if ( $(this).closest('.message-content-text').children().length < 2 ) {
				$(this).closest('.single-message').addClass('empty-message');
			}
		});
		wpc.create_event('wpc_after_ajax_done');
	}

	window.setInterval( function() {
	    
	    var _loader = document.querySelector('.wpc-loading span');
	    if( $('.wpc-loading').is(":visible") ) {
		    if ( _loader.innerHTML.length > 2 ) 
		        _loader.innerHTML = "";
		    else 
		        _loader.innerHTML += ".";
		}

		var loaderDots = document.querySelector('span.wpc-loading-dots');
	    if( $('div.wpc-loading-dots').is(":visible") ) {
		    if ( loaderDots.innerHTML.length > 2 ) 
		        loaderDots.innerHTML = "";
		    else 
		        loaderDots.innerHTML += ".";
		}

	}, 500);

	function wpc_new_message_alert() {
		// first off, update the title to show (+1) before window title
		wpc_put_title();

	}

	function wpc_exit_new_message_alert( dropNum ) {

		if( $('.wpcajx-unread-count').length == 0 )
			return;

		if( ! ( $('.wpcajx-unread-count').attr('data-tar-unread-count') > '' ) )
			return;

		var _count = parseInt( $('.wpcajx-unread-count').attr('data-unread-count') );

		if( ! ( _count > 0 ) )
			return;

		_count = Math.floor( _count - parseInt( dropNum ) );

		if( ! ( _count > 0 ) )
		{
			// drop unseen notifications as all are read
			$('.wpcajx-unread-count').attr('data-unread-count', '');
			$('.wpcajx-unread-count').attr('data-tar-unread-count', '');
		}

		else
		{
			// update unseen notifications
			$('.wpcajx-unread-count').attr('data-unread-count', _count);
			$('.wpcajx-unread-count').attr('data-tar-unread-count', '');
		}

		wpc_put_title();

	}

	window.addEventListener('load', function() {

		if( "string" !== typeof _wpcTitle ) {
			var _title = $('<textarea />').html(wpc.settings.title).text();
			window._wpcTitle = document.title.replace( _title, '' );
		}

		if( window.location.href.indexOf('?done=') > 0 ) {
			var _url = window.location.href;
			window.history.pushState(null, null, _url.substring(0, _url.indexOf( '?done' )) );
		}

		if( window.location.href.indexOf('_wpc_strip_uri') > 0 ) {
			var _url = window.location.href;
			window.history.pushState(null, null, _url.substring(0, Math.floor(_url.indexOf( '_wpc_strip_uri' ) - 1)) );
		}

	}, false);

	window.wpcAjaxLoaded = true;

	$(document).on('click', 'label.wpc-del-rep', function(e){

		$('form#wpc-del-rep').removeAttr('onsubmit');

		if( confirm(wpc.conf.del_r) ) {
			$('form#wpc-del-rep').submit();
		}

		e.preventDefault();

	});

	$(function() {

		if( $('.wpc') > '' ) {

			wpc_remove_ajax_duplicates();
			if ( $('.wpc').length ) {
				$("html, body").animate({
				    scrollTop: $('.wpc').offset().top - 80
				}, 1000);
			}

		}

		if( $('.wpc-messages').is(':visible') ) {

			$("html, body").animate({
	            scrollTop: $(".wpc-messages").offset().top - 80
	        }, 1000);

	        setTimeout(function() {
	        	$(".wpc-messages").animate({
				    scrollTop: $(".wpc-messages")[0].scrollHeight
				}, 0);
	        }, 1000);

		}

		$('.wpc-load-more a').html('load more');
		jQuery('.wpc-md-qu-ico').removeAttr('onclick');
		jQuery('.wpc-md-qu-ico').each(function() {
			jQuery(this).attr("onclick", jQuery(this).attr('data-jq-onclick'));
			jQuery(this).removeAttr('data-jq-onclick');
		});
		jQuery('a.wpcajx2').each(function() {
			var t = jQuery(this);
			if( t.attr('onclick') > '' && ! t.hasClass('wckevts') ) {
				t.attr('data-onclick', t.attr('onclick'));
				t.removeAttr('onclick');
			}
		});
		jQuery('form.wpcajx2').each(function() {
			var t = jQuery(this);
			if( t.attr('onsubmit') > '' ) {
				t.attr('data-onsubmit', t.attr('onsubmit'));
				t.removeAttr('onsubmit');
			}
		});
	});

	$.each($('a.wpcajx'), function (index, object) {
		if( $(this).attr('onclick') > '' ) {
			$(this).attr('data-onclick', $(this).attr('onclick'));
			$(this).removeAttr('onclick');
		}
	});
	$.each($('form.wpcajx'), function (index, object) {
		if( $(this).attr('onchange') > '' ) {
			$(this).attr('data-onchange', $(this).attr('onchange'));
			$(this).removeAttr('onchange');
		}
	});

	window.setInterval(function () {
		window._wpcTimeInts = [];
		$(".wpc-time-int").each(function(){
			var _int = $(this).attr('data-int'),
				_bf = $(this).attr('data-before'),
				_af = $(this).attr('data-after');
			window._wpcTimeInts.push(_int)
		});
		if( _wpcTimeInts.length ) {	
			$.ajax({
			    type: 'POST',
			    data: {action: 'wpc_time', ints: _wpcTimeInts},
			    url: wpc.admin.ajax,
			    success: function(data){
			    	var ints = JSON.parse(data);
					$.each(ints.diffs, function (index, object) {
						$(".wpc-time-int").each(function(){
							if( object.int == parseInt( $(this).attr('data-int') ) ) {
								var _bf = $(this).attr('data-before'),
									_af = $(this).attr('data-after');
									_message = object.message;
								if( _bf > '' )
									_message = _bf + ' ' + _message;
								if( _af > '' )
									_message = _message + ' ' + _af;
								$(this).html( _message );
								$(this).closest('.wpc-u-status').removeClass('online');
							}
						});
					});
			    }
			});
		}
	}, wpc.settings.ajax.time_update_interval);

	/* emoji */

	$(document).on("click", ".wpc-add-emo", function (){
    	$('.wpc-img-cont').fadeOut(200);
		var _tar = $(this).attr('data-target');
		if( $(_tar).is(":visible") ) {
			$(_tar).fadeOut(200);
		} else {
			var _pos = $(this).offset();
			if( $(this).length )
				$(_tar).css({"top": Math.floor( ( _pos.top - $(_tar).outerHeight() ) - 25 ), "left": Math.floor( ( $('body').hasClass('wpc-rtl') ? _pos.left-15 : _pos.left-265 ) )});
			$(_tar).fadeIn(200);
		}
	});

	$(".wpc-emo-container input").focusout(function(){
	    if( !($('.wpc-emo-container').is(":hover")) )
			$('.wpc-emo-container').fadeOut(200);
	});

	$( ".wpc-emo-container" ).hover(
		function() {
		}, function() {
			if( !($('.wpc-emo-container input').is(":focus")) )
				$(this).fadeOut(200);
		}
	);

	$(document).on('keyup', '.wpc-emo-container input', function(){
		var s = $(this).val().toLowerCase();
		$('.wpc-emo-container > img').each(function() {
			if( $(this).attr('alt').toLowerCase().indexOf(s) >= 0 ) {
				$(this).slideDown();
			} else {
				$(this).slideUp();
			}
		});
		setTimeout(function() {
			var _pos = $('.wpc-add-emo').offset();
			if( $('.wpc-add-emo').length )
				$('.wpc-emo-container').css({"top": Math.floor( ( _pos.top - $('.wpc-emo-container').outerHeight() ) - 25 ), "left": Math.floor(_pos.left-265)});
		}, 500);
		return false;
	});

	$( ".wpc-emo-container > img" ).click(
		function() {
			var _bf = $('#_wpc_mform textarea').val() > '' ? ' ' : '';
			$('#_wpc_mform textarea').val( $('#_wpc_mform textarea').val() + _bf + $(this).attr('title') );
			$('#_wpc_mform textarea').trigger('change');
			$('.wpc-emo-container').fadeOut(200);
		}
	);

	window.wpcKeyStrokes = '';

	$(document).keyup(function(e) {
	    if (e.keyCode == 27) {
	        $('.wpc-emo-container').fadeOut(200);
	        $('.wpc-img-cont').fadeOut(200);
	        wpcCloseModal();
	        if( $(".wpc-messages").is(":visible") ) {
		    	$('.single-message').removeClass('newm');
	    		wpc_exit_new_message_alert( parseInt( $('.wpcajx-unread-count').attr('data-tar-unread-count') ) );
	    	}

	    }

	    if( $('.single-pm').length ) {
	    	wpc.counter.unset( { pm_id: parseInt( $('.single-pm').attr('data-pm-id') ) } );
	    	wpc_put_title();
	    }

	   /* // r = 82
	    // t = 84
	    // l = 76

	    wpcKeyStrokes += e.keyCode + ',';

	    if ( wpcKeyStrokes.indexOf('82,84,76') > -1 ) {
	    	wpcKeyStrokes = '';
	    	if( $('#_wpc_mform #__rtl').length == 0 ) {
	    		$('#_wpc_mform').append('<input id="__rtl" type="hidden" name="rtl" value="1" />');
	    		$('#_wpc_mform textarea').css({"direction": "rtl"});
	    	}

	    }

	    else if ( wpcKeyStrokes.indexOf('76,84,82') > -1 ) {
	    	wpcKeyStrokes = '';
	    	if( ! ( $('#_wpc_mform #__rtl').length == 0 ) ) {
	    		$('#_wpc_mform #__rtl').remove();
	    		$('#_wpc_mform textarea').css({"direction": "ltr"});
	    	}

	    }*/

	});

	$(document).on( "click", "html, body", function() {
		if( $(".wpc-messages").is(":visible") ) {
		    $('.single-message').removeClass('newm');
	    	wpc_exit_new_message_alert( parseInt( $('.wpcajx-unread-count').attr('data-tar-unread-count') ) );
	    }
	});

	var wpcLoadModal = function( content, onExitHref, onLoadTitle, onExitTitle, onLoadHref, key ) {

		if( key ) {
			if( jQuery('.wpc-modal.'+key).length ) {
				modal = jQuery('.wpc-modal.'+key);
				var target = jQuery('.wm-contents', modal)
				    , _top = modal.offset().top
				target.css({"height": Math.floor( jQuery(window).height() - 200 )});
				_top = Math.floor( jQuery(window).height() / 2 );
				_top = Math.floor( _top - ( Math.floor( target.innerHeight() / 2 ) ) );
				jQuery('body').addClass("wpcnofy");
				target.css({"top": _top, "max-height": target.height(), "height": "auto"});
				target.css({"top": "20%"}); // -50%
				//target.hide().fadeIn(700);
				modal.fadeIn(500);
				target.animate({
			        top: _top
			    }, 500);
				if( onLoadTitle > '' ) document.title = onLoadTitle;
				if( onLoadHref > '' ) history.pushState(null, null, onLoadHref);
				wpc.create_event('wpc_modal_fired');
				wpc.create_event('wpc_modal_loaded');
				return;
			}
		}

		jQuery('.wpc-modal').remove();
		var parent = jQuery('.wpc').length ? jQuery('.wpc') : jQuery('body');
		jQuery(parent).append('<div class="wpc-modal" style="display:none"><div class="wm-contents"><div></div><span class="wm-close">X</span></div></div>');
		var modal = jQuery('.wpc-modal')
		  , att = '';
		att += onExitHref ? '"onExitHref": "'+onExitHref+'"' : '';
		att += onLoadHref ? ',"onLoadHref": "'+onLoadHref+'"' : '';
		att += onLoadTitle ? ',"onLoadTitle": "'+onLoadTitle+'"' : '';
		att += onExitTitle ? ', "onExitTitle": "'+onExitTitle+'"' : '';
		modal.attr('data-task', '{'+att+'}');
		if(key) { modal.addClass(key); }
		jQuery('.wm-contents > div', modal).html(content);
		modal = jQuery('.wpc-modal');
		var target = jQuery('.wm-contents', modal)
		    , _top = modal.offset().top
		target.css({"height": Math.floor( jQuery(window).height() - 200 )});
		_top = Math.floor( jQuery(window).height() / 2 );
		_top = Math.floor( _top - ( Math.floor( target.innerHeight() / 2 ) ) );
		jQuery('body').addClass("wpcnofy");
		target.css({"top": _top, "max-height": target.height(), "height": "auto"});
		target.css({"top": "20%"}); // -50%
		//target.hide().fadeIn(700);
		modal.fadeIn(500);
		target.animate({
	        top: _top
	    }, 500);
		wpc.create_event('wpc_modal_fired');
		if( onLoadTitle > '' ) document.title = onLoadTitle;
		if( onLoadHref > '' ) history.pushState(null, null, onLoadHref);
	}

	var wpcCloseModal = function() {
		if( $(".wpc-modal").is(":visible") ) {
			var d = jQuery(".wpc-modal").data('task');
        	if( d.onExitHref > '' ) history.pushState(null, null, d.onExitHref);
        	if( d.onExitTitle > '' ) document.title = d.onExitTitle;
        	$('.wpc-modal').fadeOut(500);
	        $('.wpc-modal .wm-contents').animate({
		        top: '0'
		    }, 500, function() {
		        //$('.wpc-modal').fadeOut();
		        jQuery('body').removeClass("wpcnofy");
		    });
		    wpc.create_event('wpc_modal_closed');
        }
	}

	$(document).on("submit", ".wpc-img-cont #url", function (e){
		var _img = $('input[type="text"]', this).val();
		if(_img == 0 || _img === null || _img.indexOf('http') < 0 || _img.length <= 7 ) {
			if(_img !== null)
				alert(wpc.feedback.err_general);
			return false;
		}
		var _tar = $(this).attr('data-input');
		var _bf = $(_tar).val() > '' ? ' ' : '';
		$(_tar).val( $(_tar).val() +_bf+ '[img]' + _img + '[/img]' );
		$(_tar).trigger('change');
		$(this).trigger('reset');
		$('.wpc-img-cont').fadeOut(200);
		e.preventDefault();
	});

    $(document).on("click", ".wpc-add-img", function (){
    	$('.wpc-emo-container').fadeOut(200);
    	$('.wpc-img-cont.main').removeClass('dragging');
		var _tar = $(this).attr('data-target');
		if( $(_tar).is(":visible") ) {
			$(_tar).fadeOut(200);
		} else {
			var _pos = $(this).offset();
			if( $(this).length )
				$(_tar).css({"top": Math.floor( ( _pos.top - $(_tar).outerHeight() ) - 25 ), "left": ( $('body').hasClass('wpc-rtl') ? _pos.left-15 : _pos.left-230 )});
			$(_tar).fadeIn(200);
		}
    	jQuery('.on-add-img-toggle').toggle('fast');
	});

	$(".wpc-img-cont input").focusout(function(){
	    if( !($('.wpc-img-cont').is(":hover")) )
			$('.wpc-img-cont').fadeOut(200);
	});
	$( ".wpc-img-cont" ).hover(
		function() {
		}, function() {
			if( !($('.wpc-img-cont input').is(":focus")) )
				$(this).fadeOut(200);
		}
	);

	$(document).on("dragover", ".wpc", function (e) {
		if( $('.single-pm').length == 0 )
			return;
		$(this).addClass('dragging');
		$('.wpc-img-cont').addClass('dragging');
		var _tar = '.wpc-img-cont.main',
			_pos = $('.wpc-add-img').offset();
		if( $('.wpc-add-img').length )
			$(_tar).css({"top": Math.floor( ( _pos.top - $(_tar).outerHeight() ) - 25 ), "left": Math.floor(_pos.left-230)});
		$('.wpc-img-cont').fadeIn(200);
		$('.wpc-img-cont input[type="file"]').trigger('focus');
	});

	$(document).on("drop", '.wpc-img-cont input[type="file"]', function (e) {
		var _file = e.originalEvent.dataTransfer.files;
		if( _file.length > 0 ) {
			if( _file[0].type.indexOf('image') >= 0 ) {
				var _tar = '.wpc-img-cont.main',
					_pos = $('.wpc-add-img').offset();
				if( $('.wpc-add-img').length )
					$(_tar).css({"top": Math.floor( ( _pos.top - $(_tar).outerHeight() ) - 25 ), "left": Math.floor(_pos.left-230)});
			} else {
				alert(wpc.feedback.err_drag);
				$('.wpc-img-cont').fadeOut(200);
			}
		} else {
			$('.wpc-img-cont').fadeOut(200);
		}

		$('.wpc').removeClass('dragging');
		$('.wpc-img-cont').removeClass('dragging');

	});

	$(document).on("change", 'form#wpc-upload', function (e) {
		$('.wpc-img-cont.main').removeClass('dragging');
		var _tar = '.wpc-img-cont.main',
			_pos = $('.wpc-add-img').offset();
		$(_tar).css({"top": Math.floor( ( _pos.top - $(_tar).outerHeight() ) - 25 ), "left": Math.floor(_pos.left-230)});
	});

	/* autosave */

	$(document).on('change', 'form.wpcinput', function(e){

		if ( ! eval( wpc.settings.ajax.autosave ) ) return;

		var form = $(this),
			pm = form.attr('data-pm-id'),
			val = $('textarea', form).val(),
			field = $('span.autosave-note');

		if( $.trim(jQuery('textarea', form).val()) == 0 ) {
			// return; Allow users to delete drafts
		}

		if( parseInt(pm) > 0 ) {
			field.html(wpc_translate('auto-saving ..'));
			$('#_wpc_mform').addClass('auto-saving');

			$.ajax({
			    url: wpc.admin.ajax,
			    data: {action:'wpc_actions', do:'autosave', pm_id:pm, text: val},
			    type: 'post',
			    success: function(data){
			    	//$('textarea', form).val(data);
					field.html(wpc_translate('auto-saved'));
					$('#_wpc_mform').removeClass('auto-saving')
			    },
			    error: function(){
			    	field.html('auto-save failed.');
			    }
			});

		}
	});

	window.onbeforeunload = function(e) {
		if( !($('#_wpc_mform').length == 0) && $('#_wpc_mform').hasClass('auto-saving') ) {
			return wpc_translate('Your message is not auto-saved..');
		}
		return;
	};

	$(document).on('change', 'form#wpc-u-sort', function() {
		$(this).trigger('submit');
	});

	$(document).on('click', '.wpc-input', function(e) {
		var span = $(".wpc-add-emo,.wpc-add-img");
	    if (!span.is(e.target)) {
			$('textarea', this).trigger('focus');
		}
	});

	window._ptypingVal = $('#_wpc_mform textarea').val();
	window._ptypingValDone = '0';

	window.setInterval(function () {

		if( '1' !== wpc.settings.isTyping_allowed )
			return false;

		if( !($('.single-pm').is(':visible')) )
			return false;

		if( !($('.single-pm').attr('data-pm-id') > '') )
			return false;

		var _pm_id = $('.single-pm').attr('data-pm-id');
		window._typingVal = $('#_wpc_mform textarea').val();

		setTimeout(function() {
			window._ptypingVal = $('#_wpc_mform textarea').val();
		}, 1000);

		if( _typingVal > '' && _typingVal !== _ptypingVal ) {
			setTimeout(function() {
				$.get( wpc.admin.ajax + '?action=wpc_actions&do=isTyping&pm='+_pm_id, function(data){return false;});
			}, 1000);
			window._ptypingValDone = '0';
		} else {
			if( '1' == _ptypingValDone )
				return false;
			setTimeout(function() {
				$.get( wpc.admin.ajax + '?action=wpc_actions&do=isTyping&pm='+_pm_id+'&done=1', function(data){return false;});
			}, 1000);
			window._ptypingValDone = '1';
		}

	}, 1000 );

	$(document).on('skeyup', '#_wpc_mform textarea', function() { // !!! is typing

		var _val = $.trim($(this).val()),
			_pm_id = $('.single-pm').attr('data-pm-id');
		
		if( _val > '' && _pm_id > '' ) {

			$.get( wpc.admin.ajax + '?action=wpc_actions&do=isTyping&pm='+_pm_id, function(data){return false;});

			setTimeout(function() {

				$.get( wpc.admin.ajax + '?action=wpc_actions&do=isTyping&pm='+_pm_id+'&done=1', function(data){return false;});

			}, 4000);

		}

	});

	$(document).on("click", '.wpc-c-actions span.csf', function(e) {
		$('input[type="text"]', this).focus();
	});

	$(document).on("click", ".wpc-modal .wm-close", function(e) {
		e.preventDefault();
		wpcCloseModal();
	});

	$(document).mouseup(function (e){
	    var container = $(".wpc-modal .wm-contents");
	    if (!container.is(e.target) && container.has(e.target).length === 0) {
			wpcCloseModal();
	    }
	});


	jQuery(document).on("click", "a.wpcajx2", function(e) {
		
		wpc.create_event('wpcajx2_click');
		e.preventDefault();
		
		var d = JSON.parse(jQuery(this).attr('data-task'))
		   , h = jQuery(this).attr('href')
		   , preLoad = ! ( d.noPreLoad > '' );

		var arr = d.loadURL.split('&');
		if ( arr[arr.length-1].indexOf('wpc_user=') > -1 && ! ( h.indexOf(wpc.settings.path_to_users) > -1 ) ) {
			window.location.replace(h);
			return;
		}

		if( ! ( d.loadInto > '' ) ) {
			d.loadInto = jQuery('.wpc').is(":visible") ? '.wpc' : false;
		}

		if( ! jQuery(d.loadInto).length ) {
			window.location.replace(h);
			return;
		}

		if( d.confirm > '' ) {

			if( d.confirm > '' ) {
				try { // checking if d.confirm is a callback or no type of string
					if( ! confirm(eval(d.confirm)) ) { return; }
				} catch(e) { // expecting d.confirm as a string
					if( ! confirm(d.confirm) ) { return; }			
				}
			}

			//if( ! confirm(eval(d.confirm)) )
			//	return;
		}

		if( ! d.onSuccess && ! d.onSuccessLoad ) {
			d.onSuccess = 'load';
		}

		if( ! d.success ) {
			d.success = 'html';
		}

		if( preLoad ) wpc_say_loading();

		jQuery.get(d.loadURL, function( html ) {

			var done;
			switch( d.success.toLowerCase() ) {
				case 'html':
					done = html;
					break;
				case 'html=1':
					done = "1" == html;
					break;
			}
			if( done ) {
				
				// init an event
				wpc.create_event('wpcajx2_success');
				
				if( 'load' == d.onSuccess ) {
					jQuery(d.loadInto).html(html);
					wpc_scroll();
					wpc.create_event('a_wpcajx2_loaded');
				}

				else if ( 'remove' == d.onSuccess && d.remove ) {
					jQuery(d.remove).fadeOut(200, function(){jQuery(d.remove).remove()});
				}
				else if ( d.onSuccess && d.onSuccess.indexOf('eval') > -1 ) {
					wpcajx2OnSuccessCall( d.onSuccess );
				}
				else if ( d.onSuccessLoad ) {
					if( preLoad ) wpc_say_loading(); // !# issue
					jQuery.get(d.onSuccessLoad, function(html2) {
						jQuery(d.loadInto).html(html2);

						if( ! d.scrollTo && jQuery('.wpc-messages').length ) {
							d.scrollTo = ".wpc-messages";
							d.scrollCont = d.scrollTo;
						}

						$(d.scrollCont).animate({
						    scrollTop: $(d.scrollTo)[0].scrollHeight
						}, d.scrollSpeed);

						$(d.scrollCont +' img').one('load', function() {
							$(d.scrollCont).animate({
							    scrollTop: $(d.scrollTo)[0].scrollHeight
							}, d.scrollSpeed);
					    }); // for images

					    if( d.focus ) { jQuery(d.focus).trigger('focus').focus(); }

					    wpc.create_event('a_wpcajx2_loaded');
					})
					.fail(function() {
						if( d.failAlert ) alert( eval(d.failAlert) );
						wpc_say_loading(1);
					});
				}

				if( d.successAlert ) alert( eval(d.successAlert) );
				if( preLoad ) wpc_say_loading(1);

				if( d.title > '' )
					document.title = d.title;
				else
					wpc_put_title();
				
				//if( ! d.noHistory )
				//	history.pushState(null, null, h);

				if( d.pushUri || ( ! d.noHistory && h > '' ) ) {
					history.pushState(null, null, ( d.pushUri ? d.pushUri : h ) );
				}

			} else {
				if( d.failAlert ) alert( eval(d.failAlert) );
				else alert( wpc.feedback.err_general );
				if( preLoad ) wpc_say_loading(1);
			}

			wpc_after_ajax_done();

		})
		.fail(function() {
			if( d.failAlert ) alert( eval(d.failAlert) );
			if( preLoad ) wpc_say_loading(1);
		});

	});

	jQuery(document).on("submit", "form.wpcajx2", function(e) {

		wpc.create_event('wpcajx2_submit');

		var f = jQuery(this),
			d = JSON.parse(f.attr('data-task')),
			formData = f.serializeArray();

		if( d.confirm ) {
			if( ! confirm( eval(d.confirm) ) ) {
				e.preventDefault();
				return false;
			}
		}

		if( ! ( d.action > '' ) && f.attr('action') > '' )
			d.action = f.attr('action');

		if( ! ( d.method > '' ) && f.attr('method') > '' )
			d.method = f.attr('method');

		if( ! ( d.loadURL === false ) && ! ( d.loadURL > '' ) )
			d.loadURL = d.action;

		if( ! ( d.loadInto > '' ) ) {
			d.loadInto = jQuery('.wpc').is(":visible") ? '.wpc' : false;
		}

		if( jQuery(d.loadInto).length == 0 ) {
			this.submit();
			return;
		}

		if( d.loadIntoModal && jQuery('.wpc-modal .wm-contents').length ) {
			d.loadInto = '.wpc-modal .wm-contents';
			if( d.onSuccessLoad ) { d.onSuccessLoad += ( d.onSuccessLoad.indexOf('?') > -1 ? '&' : '?' ) + '_wpc_modal' }
		} else { d.loadIntoModal = false }

		if( ! d.animation ) {
			d.animation = 'none';
		}

		if( d.pushUriValues ) {
			d.pushUri = window.location.href;
			d.pushUri = d.pushUri.indexOf('?') > -1 ? d.pushUri.substring(0, d.pushUri.indexOf('?')) : d.pushUri;
		}

		var formAction = f.attr('action') > '' ? f.attr('action') : false;

		switch( d.method.toLowerCase() ) {

			case 'get':

				var postReqForm = '';
				var postReq = '';
				for( i in formData ) {
					postReq += d.loadURL.indexOf('?') > -1 ? '&' : '?';
					postReqForm += formAction ? ( formAction.indexOf('?') > -1 ? '&' : '?' ) : '';
					var postRequest = wpc_parse_search_query( formData[i].name ) + '=' + wpc_parse_search_query( formData[i].value );
					postReq += postRequest;
					formAction += postReqForm + postRequest;

					if( d.pushUriValues && d.pushUri > '' ) {
						d.pushUri += ( d.pushUri.indexOf('?') > -1 ? '&' : '?' ) + postRequest;
					}

				}
				if( d.loadIntoModal ) { postReq += '&_wpc_modal=1' }
				var scss = false;
				wpc_say_loading();

				jQuery.get(d.loadURL+postReq, function(data) {

					if( d.success ) {
						switch(d.success) {
							case 'html':
								if( data ) scss = true;
								break;
							case 'html=1':
								if( "1" == data ) scss = true;
								break;
							case 'html=D':
								if( /^\d+$/.test(data) ) scss = true;
								break;
							default:
								break;
						}
					} else {
						scss = true;
					}

					if( scss ) {
						if( ! d.noLoad ) {
							if( ! d.onSuccessLoad ) {
								var htm = $(data).hide().addClass('_cont_htm');
								jQuery(d.loadInto).html(htm.prop('outerHTML'))
								jQuery('._cont_htm').fadeIn(200).removeClass('_cont_htm');
							} else {
								wpc_say_loading();
								jQuery.get(d.onSuccessLoad, function(data2) {
									var htm = $(data2).hide().addClass('_cont_htm');
									jQuery(d.loadInto).html(htm.prop('outerHTML'))
									jQuery('._cont_htm').fadeIn(200).removeClass('_cont_htm');
									document.body.style.overflowY = '';
								}).fail(function() { wpc_say_loading(1) })
							}
						};
						if(d.successAlert) alert(eval(d.successAlert));

						if( d.setTitle ) {
							if( "boolean" === typeof eval(d.setTitle) )
								wpc_put_title();
							else
								document.title = d.setTitle;
						} else {
							wpc_put_title();
						}
					
						if( d.pushUri || ( ! d.noHistory && f.attr('action') > '' ) ) {
							url = d.pushUri ? d.pushUri : formAction;
							history.pushState(null, null, url);
						}

						if( d.onSuccessCall ) {
							wpcajx2OnSuccessCall( d.onSuccessCall )
						}

					} else {
						if(d.failAlert) alert(eval(d.failAlert));
					}
					wpc_say_loading(1);
					wpc_after_ajax_done();
				})
				.fail(function() {
					if(d.failAlert) alert(eval(d.failAlert));
					wpc_say_loading(1);
				});

				e.preventDefault();
				break;

			case 'post':

				var scss = false;
				wpc_say_loading();

				jQuery.ajax({
		            url: d.action,
		            data: formData,
		            type: 'post',
		            success: function(data){
		            	
		            	if( d.success ) {
							switch(d.success) {
								case 'html':
									if( data ) scss = true;
									break;
								case 'html=1':
									if( "1" == data ) scss = true;
									break;
								case 'html=D':
									if( /^\d+$/.test(data) ) scss = true;
									break;
								default:
									break;
							}
						} else {
							scss = true;
						}

						if( scss ) {
							if( ! d.noLoad ) {
								if( ! d.onSuccessLoad ) {
									var htm = $(data).hide().addClass('_cont_htm');
									jQuery(d.loadInto).html(htm.prop('outerHTML'));
									jQuery('._cont_htm').fadeIn(200).removeClass('_cont_htm');									
								} else {
									d.onSuccessLoad = d.onSuccessLoad.replace(/{{html}}/g, data);
									wpc_say_loading();
									jQuery.get(d.onSuccessLoad, function(data2) {
										var htm = $(data2).hide().addClass('_cont_htm');
										jQuery(d.loadInto).html(htm.prop('outerHTML'))
										jQuery('._cont_htm').fadeIn(200).removeClass('_cont_htm');
										document.body.style.overflowY = '';
									}).fail(function() { wpc_say_loading(1) })
								}
							};
							if(d.successAlert) alert(eval(d.successAlert));

							if( d.setTitle ) {
								if( "boolean" === typeof eval(d.setTitle) )
									wpc_put_title();
								else
									document.title = d.setTitle;
							} else {
								wpc_put_title();
							}
						
							if( d.pushUri ) {
								history.pushState(null, null, d.pushUri);
							}
							if( d.onSuccessCall ) {
								wpcajx2OnSuccessCall( d.onSuccessCall )
							}
						} else {
							if(d.failAlert) alert(eval(d.failAlert));
						}

						if( d.focus ) { jQuery(d.focus).trigger('focus').focus(); }

						wpc_say_loading(1);
						wpc_after_ajax_done();

		            },
		            error: function() {
		            	if(d.failAlert) alert(eval(d.failAlert));
						wpc_say_loading(1);
		            }
		        });

				e.preventDefault();
				break;

			default:
				console.log('Warning', 'form.wpcajx2 handles only get|post requests for the moment.');
				break;					

		}

	});

	var wpc_animate_html = function( d ) {

		if( ! d.target || ! d.html ) {
			return;
		}

		if( ! d.animation ) {
			d.animation = 'none';
		}

		if( ! d.speed ) {
			d.speed = 200;
		}

		var target = jQuery(d.target)
		  , html = jQuery(html);

		switch( d.animation ) {
			case 'slide-left':

				html.css({"position": "absolute", "left": "100%", "top": "0", "width": "100%", "display": "none"});
				html.addClass("wpc_Slide_Left");
				target.after( html );
				var html = jQuery('.wpc_Slide_Left');
				target.css({"position": "relative"});
				target.animate({left:'-110%'},350);

				setTimeout(function() {
					html.show();
					jQuery(html).animate({"left":"0"},350, function() {
						$(this).css({"position": "relative"}).removeClass('wpc_Slide_Left');
						target.remove();
					});
				}, 50);

				break;
		}


	}( { "target": false, "html": false, "animation": false, "speed": false } )

	jQuery(document).on("click", ".wpcfmodal", function(e) {
		
		e.preventDefault();

		var d = jQuery(this).data('task');

		d.key = jQuery(this).data('unique') || false;

		if( ! d.key ) {
			jQuery(this).attr('data-unique', 'md-' + Math.floor(Math.random() * 9999));
			d.key = jQuery(this).data('unique') || false;
		}

		if ( d.content ) {

			if( d.encoded ) {
				d.content = decodeURIComponent(d.content);
			}

			content = d.content;
		} else {
			content = '<div id="loading"><style type="text/css">#loading .wpc-loading{position:relative;padding:0.6em;}.wpc-modal .wm-contents{overflow-x: hidden;}</style>' + wpc.settings.ajax.preloader + '</div>';
		}

		wpcLoadModal(
			content,
			d.onExitHref,
			d.onLoadTitle,
			d.onExitTitle,
			d.onLoadHref,
			d.key
		);

		if( "undefined" === typeof wpc_modal_loaded ) {
			window.wpc_modal_loaded = false;
		}

		window.addEventListener('wpc_modal_loaded', function(e) {
			window.wpc_modal_loaded = true;
		});

		var modal = jQuery('.wpc-modal')
		  , loader = jQuery('#loading', modal);

		if( jQuery('.wpc-modal #loading').length ) {
			wpc_modal_loaded = false;
		} else if ( d.key && ! jQuery('.wpc-modal.'+d.key).length ) {
			wpc_modal_loaded = false;			
		}

		if( ! d.content && ! wpc_modal_loaded ) {

			jQuery.get(d.loadURL+'&_wpc_modal=1', function( html ) {

				loader.fadeOut();
				var html = $(html).hide().addClass('wm-contents-html');
				loader.replaceWith(html.prop('outerHTML'));
				jQuery('.wpc-modal .wm-contents-html').slideDown(200);

				wpc_remove_ajax_duplicates();
				wpc_after_ajax_done();

				if( jQuery('.wpc-modal #wpc-title').attr('data-title') > '' ) {
					document.title = jQuery('.wpc-modal #wpc-title').attr('data-title');
				}

				if(d.focus) {
					jQuery(d.focus, modal).trigger('focus').focus();
				} else {
					if( $('[role="wpcFocus"]', modal).length ) {
						$('[role="wpcFocus"]', modal).trigger('focus').focus();
					}
				}

			})
			.fail(function() {
				loader.fadeOut();
				modal.append('<p>' + wpc.feedback.err_general + '</p>');
			});
			wpc.create_event('wpc_modal_loaded');

		} else {
			return;
		}

	});

	var wpc_remove_ajax_duplicates = function() {
		jQuery('.wpc', '.wpc').each(function(){jQuery(this).removeClass('wpc')})
	}

	jQuery(document).on("click", ".wpc-feedback", function(e) {
	  e.preventDefault();
	  jQuery(this).fadeOut();
	  setTimeout(function(){
	  	jQuery('.wpc-feedback p').remove();
	  	jQuery(this).attr('class', 'wpc-feedback');
	  }, 1000);
	});

	var wpcRemoveEvts = function() {
		jQuery('.wpc-stop-jQ-event').each(function() {
			var evts = ['onclick', 'onsubmit', 'onchange'];
			window.wpcStopjQEventElem = this;
			evts.forEach(function(evt) {
				var elem = wpcStopjQEventElem;
				if( jQuery(elem).attr(evt) > '' ) {
					jQuery(elem).attr('data-'+evt, jQuery(elem).attr(evt));
					jQuery(elem).removeAttr(evt);
				}
			});
			return;
		});
	}
	wpcRemoveEvts();

	jQuery(document).on("change", ".wpc_mths", function(e) {

		var v = jQuery(this).val()
		  , u = wpc.admin.ajax + '?action=wpc&wpc_messages=1'
		  , l = u.length
		  , a = !(jQuery('option[value="archives"]', this).length > 0)
		  , p = wpc.settings.path_to_messages;

		if( "unread" == v ) { u += ( a ? '&wpc_archives=1' : '' ) + '&view=unread'; p += ( a ? 'archives/' : '' ) + '?view=unread'; }
		else if( "archives" == v ) { u += '&wpc_archives=1'; p += 'archives/' }
		else if( "conversations" == v ) { u += '&_lp'; p = wpc.settings.path_to_messages; }
		else if( "users" == v ) { u = wpc.admin.ajax + '?action=wpc&wpc_users=1&load_from_ajax'; p = wpc.settings.path_to_users; }

		if( u.length > l ) {
			var d = '{"action": "'+u+'", "pushUri": "'+p+'"}';
			jQuery(this).closest('form').addClass('wpcajx2').attr('data-task', d);
			jQuery(this).closest('form').trigger('submit');
		}

	});

	var wpc_say_loading = function( done ) {
		var p = jQuery('.wpc-modal').is(':visible') ? jQuery('.wpc-modal .wm-contents > div') : jQuery('.wpc');
		if( ! done ) {
			jQuery('.wpc-loading').remove();
			jQuery('.wpc-modal').addClass('loading');
			p.append( wpc.settings.ajax.preloader );
			jQuery('.wpc input, .wpc textarea, .wpc select').each(function() {
				if( ! $(this).prop("disabled") ) {
					$(this).addClass("wpc_disabled_on_preload").prop("disabled", "disabled");
				}
			});
		} else {
			jQuery('.wpc-loading').remove();
			jQuery('.wpc-modal').removeClass('loading');
			jQuery('.wpc_disabled_on_preload').each(function() {
				$(this).removeClass("wpc_disabled_on_preload").prop("disabled", false);
			});
		}
	}

	function wpc_scroll( d ) {

		if ("object" !== typeof d) { d = {}; }

		// maths: +|- 9999~~~ for top|bottom scrolls 
		if( ! d.scrollSpeed )
			d.scrollSpeed = 50;
		if( ! d.scrollCont )
			d.scrollCont = 'html, body';
		if( ! d.maths )
			d.maths = '+0';

		if( jQuery(d.scrollTo).length ) {
			jQuery(d.scrollCont).animate({
			    scrollTop: eval( jQuery(d.scrollTo).offset().top + d.maths )
			}, d.scrollSpeed);
			jQuery(d.scrollCont +' img').one('load', function() {
				jQuery(d.scrollCont).animate({
				    scrollTop: eval( jQuery(d.scrollTo).offset().top + d.maths )
				}, d.scrollSpeed);
			}); // for images
		} else {
			if( jQuery('.wpc-messages').length ) {
				d.scrollTo = ".wpc-messages";
				d.scrollCont = d.scrollTo;
				jQuery(d.scrollCont).animate({
				    scrollTop: eval( jQuery(d.scrollTo)[0].scrollHeight + d.maths )
				}, d.scrollSpeed);
				jQuery(d.scrollCont +' img').one('load', function() {
					jQuery(d.scrollCont).animate({
				    	scrollTop: eval( jQuery(d.scrollTo)[0].scrollHeight + d.maths )
					}, d.scrollSpeed);
				}); // for images
			}
			if( jQuery('.wpc').length && ! d.nbvps ) {
				jQuery('html,body').animate({
				    scrollTop: jQuery('.wpc').offset().top - 40
				}, 250);
			}
		}
	}({"scrollTo": false, "scrollSpeed": 50, "scrollCont": "html, body", "maths": "+0"});

	var wpc_put_title = function() {
		if( "string" === typeof _wpcTitle && jQuery('#wpc-title').length ) {

			var d = jQuery('.wpc-modal #wpc-title').length && jQuery('.wpc-modal').is(":visible") ? jQuery('.wpc-modal #wpc-title') : jQuery('#wpc-title')
			  , _title = d.attr('data-title');

			jQuery(wpc.settings.ajax.dynamic_title_selector).html( _title );
			_title += _wpcTitle;
			
			if( parseInt( wpc.counter.get_all() ) > 0 ) {
				_title = (wpc.settings.ajax.messages_count_before_title_tab+' '+_title).replace(/%d/g, parseInt( wpc.counter.get_all() ));
			}

			document.title = jQuery('<div/>').html(_title).text().replace(/&quot;/g, '"');
		}
	}

	/* Profile edit cover photo */
	jQuery(document).on("click", ".edit-cover .upload", function(e) {
		var label = $(this)
		  , field = jQuery('.wpc-users.group-cover #img_upload')
		  , file = field.val()
		  , target = jQuery(".wpc-users.group-cover input[name='cover_photo']")
		  , remove = jQuery("label.remove")
		  , cover = jQuery('.wpc-users.group-cover div.wpc-cover');
		if( "disabled" === label.attr("disabled") || ! file ) { return; }
		label.attr("disabled","disabled");
	    var file_data = $(field).prop('files')[0]
	      , form_data = new FormData();
	    form_data.append('file', file_data);
	    cover.addClass("wpc-loading-dots").append('<span class="wpc-loading-dots">.</span>');
	    $.ajax({
            url: wpc.admin.ajax+'?action=wpc_upload&doing=cover-photo',
            dataType: 'text',
            cache: false,
            contentType: false,
            processData: false,
            data: form_data,                         
            type: 'post',
            success: function(img){
            	if( img.indexOf('http') > -1 ) {
					cover.attr("style", "background: url('"+img+"') center center no-repeat;background-size:cover;");
            		target.val(img);
            		remove.removeAttr("disabled");
            		label.attr("disabled","disabled");
            		jQuery('label.select span').text( " "+wpc_translate("select image") );
            	} else {
	               	alert(wpc.feedback.err_upload);	            		
	               	label.removeAttr("disabled");
            	}
            	cover.removeClass("wpc-loading-dots").children('span').remove();
            },
            error: function() {
               	alert(wpc.feedback.err_upload);
               	label.removeAttr("disabled");
               	cover.removeClass("wpc-loading-dots").children('span').remove();
            }
	    });
	});
	jQuery(document).on("click", ".edit-cover .remove", function(e) {
		var label = $(this)
		  , upload = jQuery("label.upload")
		  , field = jQuery('.wpc-users.group-cover #img_upload')
		  , target = jQuery(".wpc-users.group-cover input[name='cover_photo']")
		  , cover = jQuery('.wpc-users.group-cover div.wpc-cover');
		if( "disabled" === label.attr("disabled") ) { return; }
		cover.removeAttr("style");
		label.attr("disabled","disabled");
		upload.attr("disabled","disabled");
		target.val('');
		field.val('');
		alert(wpc.feedback.cover_rem);
		jQuery('label.select span').text( " "+wpc_translate("select image") );
		e.preventDefault();
	});
	jQuery(document).on("change", ".wpc-users.group-cover #img_upload", function(e) {
		var ipt = $(this)
		  , val = ipt.val()
		  , label = jQuery('label.upload')
		  , select = jQuery('label.select span');
		if( val ) {
			label.removeAttr("disabled");
			select.text( " "+val.replace(/^.*[\\\/]/, '') );
		} else {
			label.attr("disabled", "disabled");
			select.text( " "+wpc_translate("select image") );
		}
		e.preventDefault();
	});
	/* Profile edit cover photo - end */

	jQuery(document).on("click", ".wpc-nmtc .wpc-nmt .head > span", function(e){
		$(this).closest(".wpc-nmt").fadeOut(200, function() {
			$(this).remove();
		});
		e.preventDefault();
	});

	jQuery(document).on("click", ".wpc-nmtc a", function(e){
		$(this).closest(".wpc-nmt").fadeOut(200, function() {
			$(this).remove();
		});
		e.preventDefault();
	});

	jQuery(window).bind("load", function() {
		
		jQuery('.wpc_lload').each(function(i, elem){

			var div = $(this)
			  , load = div.attr('data-load');

			if( load ) {

				div.html( div.html() + '<div class="wpc-loading-dots"><p>'+wpc_translate('loading')+' <span class="wpc-loading-dots">..</span></p></div>' );

				$.get( load, function(data){
					div.replaceWith( data );
					wpc_after_ajax_done();
				})
				.fail(function() {
					$('div.wpc-loading-dots', div).replaceWith('<p>'+wpc_translate('Error occured while loading this content')+'.</p>');
				});

			}

		});

	});

	jQuery('.single-message p.attachement-unavailable').each(function(i,elem){
		if ( $(this).closest('.message-content-text').children().length < 2 ) {
			$(this).closest('.single-message').addClass('empty-message');
		}
	});

	

});