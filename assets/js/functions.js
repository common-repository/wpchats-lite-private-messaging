var wpc_append_message = function( rand, text ) {
	var _m = '<div class="single-message mine ajax" id="mt-' + rand + '">';
	_m += '<div class="avatar-container">';
	_m += '<a href="' + wpc.cur_user.link + '" class="wpcajx" data-action="view-profile" data-slug="'+wpc.cur_user.nice_name+'">';
	_m += '<img src="' + wpc.cur_user.avatar + '" class="avatar avatar-35 photo" height="35" width="35">';
	_m += '<span>' + wpc.cur_user.name + '</span>';
	_m += '</a>';
	_m += '</div>';
	_m += '<div class="message-content">';
	_m += '<div class="message-content-text">';
	_m += '<p>' + wpc_htmlencode(text) + '</p>'; //
	_m += '</div>';
	_m += '<div class="message-meta">';
	_m += '<span class="wpc-time-int" data-int="" data-after="ago">'+wpc_translate('just now')+' </span>';
	//_m += '<span class="_rep-lnk" style="display:none"> &nbsp;&bullet; <a href="report/0/" class="wpcajx">report</a></span>';
	//_m += '<span class="_del-lnk" style="display:none"> &nbsp;&bullet; <a href="?do=delete&amp;m=0" class="wpcajx delete-message">delete</a></span>';
	_m += '<div class="wpc-more" style="visibility: hidden;">';
	_m += ' &middot; <span>';
	_m += '<a href="'+wpc.settings.path_to_messages+'{{sender_slug}}/forward/{{message_id}}/" class="wpcfmodal" data-task="{&quot;loadURL&quot;: &quot;'+wpc.admin.ajax+'?action=wpc_actions&amp;do=fwd&amp;wpc_messages=1&amp;wpc_recipient={{sender_slug}}&amp;wpc_forward_message={{message_id}}&quot;, &quot;onExitTitle&quot;: &quot;'+document.title+'&quot;, &quot;onLoadHref&quot;: &quot;'+wpc.settings.path_to_messages+'{{sender_slug}}/forward/{{message_id}}/&quot;, &quot;onExitHref&quot;: &quot;'+wpc.settings.path_to_messages+'{{sender_slug}}/&quot;, &quot;focus&quot;: &quot;input[name=\'q\']&quot;}">';
	_m += 'forward';
	_m += '</a>';
	_m += '</span>';
	_m += ' &middot; <span>';
	_m += '<a href="'+wpc.settings.path_to_messages+'{{sender_slug}}/?do=delete&amp;m={{message_id}}" class="wpcajx2" data-task="{&quot;loadURL&quot;: &quot;'+wpc.admin.ajax+'?action=wpc_actions&amp;do=delete&amp;m={{message_id}}&amp;wpc_messages=1&amp;wpc_recipient={{sender_slug}}&quot;,&quot;confirm&quot;: &quot;wpc.conf.del_m&quot;,&quot;success&quot;: &quot;html=1&quot;,&quot;onSuccess&quot;: &quot;remove&quot;,&quot;remove&quot;: &quot;#message-{{message_id}}&quot;,&quot;failAlert&quot;: &quot;wpc.feedback.err_general&quot;,&quot;noHistory&quot;: &quot;1&quot;,&quot;noPreLoad&quot;: &quot;1&quot;}" data-onclick="return confirm(wpc.conf.del_m)">';
	_m += 'delete';
	_m += '</a>';
	_m += '</span>';
	_m += '</span>';
	_m += '</div>';
	_m += '</div>';
	_m += '</div>';
	_m += '</div>';
	return _m;
}

var wpc_append_snippet = function( args ) {
	var snip = '<div class="message-snippet unread received" id="pm-'+args.pm_id+'" data-pm-id="'+args.pm_id+'">';
	snip += '<div class="avatar-cont">';
	snip += '<img src="'+args.avatar+'" class="avatar avatar-50 photo" height="50" width="50">';
	snip += '<span class="wpc-u-status '+args.online_class+'"><span class="wpc-time-int user-'+args.user_id+'" data-int="'+args.user_status_int+'" data-before="online" data-after="ago">'+args.user_status+'</span></span>';
	snip += '</div>';
	snip += '<div class="content-cont">';
	snip += '<div class="contact-date">';
	snip += '<span>'+args.user_name+' <span class="count">('+parseInt(args.unread_count)+')</span></span>';
	snip += '<span><span class="wpc-time-int" data-int="'+args.sent_int+'" data-before="" data-after="ago">'+args.sent_date+' ago</span></span>';
	snip += '</div>';
	snip += '<div class="content-excerpt">';
	snip += '<span class="wpc-snippet-author">';
	snip += '<span><img alt="" src="'+args.avatar+'" class="avatar avatar-20 photo" height="20" width="20"></span>';
	snip += '<span> '+args.user_name+' </span>';
	snip += '</span>';
	snip += '<span class="content-text"> "'+args.excerpt+'"</span>';
	snip += '</div>';
	snip += '</div>';
	snip += '<a href="'+args.link+'" class="wpcajx read" data-action="load-conversation" data-slug="'+args.user_slug+'"></a>';
	snip += '</div>';
	return snip;
}
var wpc_append_received_message = function( args ) {
	var _m = '<div class="single-message their newm ajax" id="message-' + args.id + '">';
	_m += '<div class="avatar-container">';
	_m += '<a href="' + args.link + '" class="wpcajx" data-action="view-profile" data-slug="'+args.slug+'">';
	_m += '<img src="' + args.avatar + '" class="avatar avatar-35 photo" height="35" width="35">';
	_m += '<span>' + args.name + '</span>';
	_m += '</a>';
	_m += '</div>';
	_m += '<div class="message-content">';
	_m += '<div class="message-content-text">';
	_m += args.message; //
	_m += '</div>';
	_m += '<div class="message-meta">';
	_m += '<span class="wpc-time-int" data-int="'+args.date+'" data-after="ago">'+args.date_diff+' ago</span>';
	_m += '<span> &nbsp;&bullet; <a href="?do=delete&amp;m='+args.id+'" class="wpcajx delete-message" data-action="delete" data-message="'+args.id+'" data-slug="'+args.slug+'">delete</a></span>';
	_m += '</div>';
	_m += '</div>';
	_m += '</div>';
	return _m;
}
var wpc_htmlencode = function(str) {
	// ref http://stackoverflow.com/a/2613591
    str = str.replace(/[&<>"']/g, function($0) {
        return "&" + {"&":"amp", "<":"lt", ">":"gt", '"':"quot", "'":"#39"}[$0] + ";";
    });
    return str.replace(/\n/g, '<br/>');
}
var wpc_jq_output_message = function( string ) {
	var _tt = document.createElement('TEXTAREA');
	_tt.value = string;
	string = _tt.value;
	//_tt.remove();
	string = string.replace(/&_lt;/g, "&lt;");
	string = string.replace(/&_gt;/g, "&gt;");
	return string;
}
var wpc_parse_search_query = function( query ) {
	if( "string" === typeof query ) {
		query = query.replace(/ /g,"+");
		query = query.replace(/&/g,"%26");
	} else {
		query = '';
	}
	return query;
}

var wpc_translate = function(string) {
	for( i in wpc.translate ) { if( string == wpc.translate[i] ) return wpc.translate[i]; }
	return string;
}

var wpcajx2OnSuccessCall = function(callback) {

	switch( callback ) {
		//case:
		//	break

		default:
			eval(callback);
			break;
	}

}

// no jQuery involved
function wpcCheckParentLabel(elem, selector) {

	if( ! elem || ! elem.parentElement ) { return; }

	if( "string" !== typeof selector ) {
		selector = 'label.wpc-label';
	}

	var ev = ''
	  , dataBg = elem.parentElement.getAttribute('data-active-bg') ? elem.parentElement.getAttribute('data-active-bg') : false
	  , dataClr = elem.parentElement.getAttribute('data-active-clr') ? elem.parentElement.getAttribute('data-active-clr') : false
	  , dataC = elem.parentElement.getAttribute('data-active-class') ? elem.parentElement.getAttribute('data-active-class') : false;
	if( ! ( dataC > '' ) )
		dataC = 'active';
	dataC = ' ' + dataC;
	switch( elem.type ) {
		case "radio":
			ev = 'elem.checked';
			break;
		default:
			ev = 'elem.checked';
			break;
	}
	var c = elem.parentElement.getAttribute('class');
	if( eval( ev ) ) {
		switch( elem.type ) {
			case "radio":
				var r = document.querySelectorAll( selector );
				for ( i in r ) {
					if("object" === typeof r[i]) {
						r[i].setAttribute('class', c.replace(dataC, ''));
						r[i].style.background = '';
					}
				}
				break;
			default:
				break;
		}
		elem.parentElement.setAttribute('class', c+dataC);
		elem.parentElement.style.background = dataBg;
		elem.parentElement.style.color = dataClr;
	} else {
		elem.parentElement.setAttribute('class', c.replace(dataC, ''));
		elem.parentElement.style.background = '';
		elem.parentElement.style.color = '';
	}
}(false, 'label.wpc-label');


var wpc_nf_toggle_check_all = function( elem ) {
	var elements = document.querySelectorAll('input[name="_nf_c"]')
	  , vals = []
	  , t = document.querySelector('input._task');
	for ( i in elements ) {
		if(elem.checked) {
			vals.push( elements[i].value );
			t.value = vals.toString();
			if( undefined !== elements[i].parentNode ) {
				elements[i].parentNode.className = elements[i].parentNode.className + ( elements[i].parentNode.className > '' ? ' ' : '' ) + 'chkd'; 
			}
		} else {
			t.value = '';
			if( undefined !== elements[i].parentNode) {
				elements[i].parentNode.className = elements[i].parentNode.className.replace(/ chkd/g,'').replace(/chkd/g,'');
			}
		}
		elements[i].checked = elem.checked;
	}
}

var wpc_nf_toggle_check = function( elem ) {
	var t = document.querySelector('input._task');
	if(elem.checked) {
		t.value += ',' + elem.value;
		elem.parentNode.className = elem.parentNode.className + ( elem.parentNode.className > '' ? ' ' : '' ) + 'chkd'; 
	} else {
		t.value = t.value.replace(elem.value, '');
		elem.parentNode.className = elem.parentNode.className.replace(/ chkd/g,'').replace(/chkd/g,''); 
	}
}

function wpc_create_event(name, data) {
	if( "object" !== typeof data ) { data = []; }
	var e = document.createEvent('Event');
	e.initEvent(name, true, true);
	e.data = data;
	document.dispatchEvent(e);
}

window.addEventListener('a_wpcajx2_loaded', function(e) {
	if( window.jQuery ) { jQuery('.wpc-sdnwm').remove(); }
	else {
		var el = document.querySelectorAll('.wpc-sdnwm');
		for ( i in el ) { if( "object" == typeof el[i] ) el[i].remove(); }
	}
	return;
});

function wpc_sound_notif( soruce ) { // todo = append audios onload to load them before use
	if( "1" == wpc.preferences.audio_notifications ) {
		var a = document.createElement('audio');
		a.src = soruce;
		a.autoplay = true;
		document.body.appendChild(a);
	}
}

function wpc_tiny_device_remove_sidebar(w) {
	if( "number" !== typeof w ) { w = 481 }
	if( ! document.getElementsByClassName('wpcs-sidebar').length ) {
		return;
	}
	var width = window.innerWidth || screen.width;
	if( width <= w ) {
	    if( window.jQuery ) {
	    	jQuery('.wpcs-sidebar').remove();
	    } else {
	    	var s = document.getElementsByClassName('wpcs-sidebar');
	    	s[0].remove();
	    }
	}
	return;
}(481)
if( document.getElementsByClassName('wpcs-sidebar').length ){
	window.addEventListener('load', function(){
		return wpc_tiny_device_remove_sidebar()}, false
	);
}
window.addEventListener('a_a_wpcajx2_loaded', function(){
	return wpc_tiny_device_remove_sidebar()
}, false);
window.addEventListener('wpc_after_ajax_done', function(){
	return wpc_tiny_device_remove_sidebar()
}, false);

// jQuery

var wpc_append_feedback = function( message, Class ) {

	var div = document.createElement("div");
	div.innerHTML = message;
	var messageInner = div.textContent || div.innerText || "";

	if( ! ( message.indexOf('<p>') > -1 ) ) {
		message = '<p>' + wpc_translate(messageInner) + '</p>';
	}

	var p = jQuery('.wpc-feedback');

	if( ! ( p.length ) && jQuery('.wpc').length ) {
		jQuery(jQuery('.wpc').children()[0]).html( '<div class="wpc-feedback" style="display:none"><span>&times;</span></div>' + jQuery(jQuery('.wpc').children()[0]).html() )
		var p = jQuery('.wpc-feedback');
	}

	if( ! ( p.length ) )
		return; // could not append

	p.hide();
	jQuery('p', p).remove();
  	p.attr('class', 'wpc-feedback');

	p.addClass(Class);
	p.append(message);

	p.fadeIn();

	jQuery("body").animate({
	    scrollTop: Math.floor(p.offset().top-135)
	}, 0);


}


var wpc_user_snippet_markup = function( user, page ) {
	if( "number" !== typeof page ) { page = 0; }
	html = '<li class="page-'+page+'">';
	html += '<div class="avatar-ct">';
	html += '<a href="'+user.link+'" class="wpcajx2" data-task="{&quot;loadURL&quot;: &quot;'+wpc.admin.ajax+'?action=wpc&amp;wpc_users=1&amp;wpc_user='+user.nicename+'&quot;}">';
	html += '<img alt="" src="'+user.avatar+'" class="avatar avatar-55 photo" height="55" width="55" />';
	html += '</a>';
	html += '</div>';
	html += '<div class="info-ct">';
	html += '<span class="user-link"><a href="'+user.link+'" class="wpcajx2" data-task="{&quot;loadURL&quot;: &quot;'+wpc.admin.ajax+'?action=wpc&amp;wpc_users=1&amp;wpc_user='+user.nicename+'&quot;}">';
	html += '<strong>'+user.wpc_name.full+'</strong>';
	html += '</a></span>';
	html += '<span class="wpc-u-status o'+(user.is_online?'n':'ff')+'line">';
	html += jQuery('<div/>').html(user.online_status).text().replace(/&quot;/g, '"');
	html +='</span>';
	if( parseInt(wpc.cur_user.id) > 0 && ( parseInt(wpc.cur_user.id) !== user.ID ) ) {
		html += '<span class="contact-modal">';										
		html += '<a href="'+user.link+'" class="wpcfmodal wpc-btn" data-task="{&quot;content&quot;: &quot;'+encodeURIComponent('<p class="wpc_quick_m"><a href="'+wpc.settings.path_to_messages+'new/" class="wpcfmodal wpc-btn" data-task="{&quot;loadURL&quot;: &quot;'+wpc.admin.ajax+'?action=wpc&amp;wpc_messages=1&amp;wpc_new_message=1&amp;recipient='+user.ID+'&amp;_no_back&quot;, &quot;onExitTitle&quot;: &quot;'+document.title+'&quot;, &quot;onExitHref&quot;: &quot;'+window.location.href+'&quot;, &quot;onLoadHref&quot;: &quot;'+wpc.settings.path_to_messages+'new/&quot;}">'+wpc_translate('compose')+'</a> '+wpc_translate('or')+' <a href="'+wpc.settings.path_to_messages+user.nicename+'/" class="wpc-btn wpcajx2" data-task="{&quot;loadURL&quot;: &quot;'+wpc.admin.ajax+'?action=wpc&amp;wpc_messages=1&amp;wpc_new_message=1&amp;wpc_recipient='+user.nicename+'&quot;}" onclick="jQuery(\'.wpc-modal\').hide()">'+wpc_translate('view conversation')+'</a></p>')+'&quot;, &quot;encoded&quot;: &quot;1&quot;}">'+wpc_translate('Send message')+'</a>';
		html += '</span>';
	}
	html += '</div>';
	html += '</li>';
	return html;
}

function wpc_range( lowEnd, highEnd ) {
	var list = [];
	for (var i = lowEnd; i <= highEnd; i++) {
	    list.push(i);
	}
	return list;
}

var wpc_newm_lighbox_html = function(message) {
	var html = '<div class="wpc-nmt user-'+message.sender_id+'" id="'+message.message_id+'">';
	html += '<div class="head">';
	html += '<h3><a title="'+message.sender_name+'" href='+wpc.settings.path_to_users+message.sender_slug+'/ class="wpcajx2" data-task="{&quot;loadURL&quot;: &quot;'+wpc.admin.ajax+'?action=wpc&amp;wpc_users=1&amp;wpc_user='+message.sender_slug+'&quot;}"><img alt="avatar" src="'+message.sender_avatar+'" class="avatar avatar-33 photo" height="33" width="33" /></a>';
	html += wpc_translate('New message from %s').replace(/%s/g, '<a title="'+message.sender_name+'" href='+wpc.settings.path_to_users+message.sender_slug+'/ class="wpcajx2" data-task="{&quot;loadURL&quot;: &quot;'+wpc.admin.ajax+'?action=wpc&amp;wpc_users=1&amp;wpc_user='+message.sender_slug+'&quot;}">'+message.sender_short_name+'</a>');
	html += '</h3><span>&times;</span></div>';
	html += '<div class="body"><p>'+message.excerpt+'</p>';
	html += '<span class="wpc-time-int" data-int="'+message.date+'" data-before="" data-after="">';
	html += wpc_translate('%s ago').replace(/%s/g, message.date_diff);
	html += '</span>';
	html += '<span class="count">+'+message.unread_count+'</span>';
	html += '<a href="'+wpc.settings.path_to_messages+message.sender_slug+'/" title="'+wpc_translate('view')+'" class="wpcajx2" data-task="{&quot;loadURL&quot;: &quot;'+wpc.admin.ajax+'?action=wpc&amp;wpc_messages=1&amp;wpc_recipient='+message.sender_slug+'&quot;}"></a></div></div>';
	return html;
}

window.addEventListener('wpc_new_message_added', function(){
	var main = document.querySelector( '.wpc-messages .wpc_contents' );
	[].map.call( main.children, Object ).sort( function ( b, a ) {
	    return +b.id.match( /\d+/ ) - +a.id.match( /\d+/ );
	}).forEach( function ( elem ) {
	    main.appendChild( elem );
	});
}, false);

window.wpc_functions_js_loaded = true;