// One-time init — Journal IAS may re-execute this script when loading next pages.
if (window.apbctJsInitialized) {
	// no-op
} else {
window.apbctJsInitialized = true;

var ct_date = new Date(),
	ctTimeMs = new Date().getTime(),
	ctMouseEventTimerFlag = true, //Reading interval flag
	ctMouseData = [],
	ctMouseDataCounter = 0;

function ctSetCookieSec(c_name, value) {
	document.cookie = c_name + "=" + encodeURIComponent(value) + "; path=/; samesite=lax";
}

function apbct_attach_event_handler(elem, event, callback){
	if(typeof window.addEventListener == "function") elem.addEventListener(event, callback);
	else                                             elem.attachEvent(event, callback);
}

function apbct_remove_event_handler(elem, event, callback){
	if(typeof window.removeEventListener == "function") elem.removeEventListener(event, callback);
	else                                                elem.detachEvent(event, callback);
}

ctSetCookieSec("apbct_ps_timestamp", Math.floor(new Date().getTime()/1000));
ctSetCookieSec("apbct_fkp_timestamp", "0");
ctSetCookieSec("apbct_pointer_data", "0");
ctSetCookieSec("apbct_timezone", "0");

setTimeout(function(){
	ctSetCookieSec("apbct_timezone", ct_date.getTimezoneOffset()/60*(-1));
},1000);

//Writing first key press timestamp
var ctFunctionFirstKey = function output(event){
	var KeyTimestamp = Math.floor(new Date().getTime()/1000);
	ctSetCookieSec("apbct_fkp_timestamp", KeyTimestamp);
	ctKeyStopStopListening();
}

//Reading interval
var ctMouseReadInterval = setInterval(function(){
	ctMouseEventTimerFlag = true;
}, 150);

//Writting interval
var ctMouseWriteDataInterval = setInterval(function(){
	ctSetCookieSec("apbct_pointer_data", JSON.stringify(ctMouseData));
}, 1200);

//Logging mouse position each 150 ms
var ctFunctionMouseMove = function output(event){
	if(ctMouseEventTimerFlag == true){

		ctMouseData.push([
			Math.round(event.pageY),
			Math.round(event.pageX),
			Math.round(new Date().getTime() - ctTimeMs)
		]);

		ctMouseDataCounter++;
		ctMouseEventTimerFlag = false;
		if(ctMouseDataCounter >= 50){
			ctMouseStopData();
		}
	}
}

//Stop mouse observing function
function ctMouseStopData(){
	apbct_remove_event_handler(window, "mousemove", ctFunctionMouseMove);
	clearInterval(ctMouseReadInterval);
	clearInterval(ctMouseWriteDataInterval);
}

//Stop key listening function
function ctKeyStopStopListening(){
	apbct_remove_event_handler(window, "mousedown", ctFunctionFirstKey);
	apbct_remove_event_handler(window, "keydown", ctFunctionFirstKey);
}

apbct_attach_event_handler(window, "mousemove", ctFunctionMouseMove);
apbct_attach_event_handler(window, "mousedown", ctFunctionFirstKey);
apbct_attach_event_handler(window, "keydown", ctFunctionFirstKey);

function apbct_collect_visible_fields_and_set_cookie(form ) {

	// Get only fields
	var inputs = [],
		inputs_visible = '',
		inputs_visible_count = 0,
		inputs_with_duplicate_names = [];

	for(var key in form.elements){
		if(!isNaN(+key))
			inputs[key] = form.elements[key];
	}

	// Filter fields
	inputs = inputs.filter(function(elem){

		// Filter fields
		if( getComputedStyle(elem).display    === "none" ||   // hidden
			getComputedStyle(elem).visibility === "hidden" || // hidden
			getComputedStyle(elem).opacity    === "0" ||      // hidden
			elem.getAttribute("type")         === "hidden" || // type == hidden
			elem.getAttribute("type")         === "submit" || // type == submit
			elem.value                        === ""       || // empty value
			elem.getAttribute('name')         === null ||
			inputs_with_duplicate_names.indexOf( elem.getAttribute('name') ) !== -1 // name already added
		){
			return false;
		}

		// Visible fields count
		inputs_visible_count++;

		// Filter inputs with same names for type == radio
		if( -1 !== ['radio', 'checkbox'].indexOf( elem.getAttribute("type") )){
			inputs_with_duplicate_names.push( elem.getAttribute('name') );
			return false;
		}

		return true;
	});

	// Visible fields
	inputs.forEach(function(elem, i, elements){
		inputs_visible += " " + elem.getAttribute("name");
	});
	inputs_visible = inputs_visible.trim();

    ctSetCookieSec("apbct_visible_fields", inputs_visible);
    ctSetCookieSec("apbct_visible_fields_count", inputs_visible_count);

}

// Ready function
function apbct_ready(){
	ctSetCookieSec("apbct_visible_fields", 0);
	ctSetCookieSec("apbct_visible_fields_count", 0);
	if (document.getElementById("ct_checkjs"))
		document.getElementById("ct_checkjs").value = ct_date.getFullYear();

	for(var i = 0; i < document.getElementsByClassName('ct_checkjs').length; i++){
		document.getElementsByClassName('ct_checkjs')[i].value = ct_date.getFullYear();
	}

	setTimeout(function(){

		for(var i = 0; i < document.forms.length; i++){
			var form = document.forms[i];
			var formAction = (form.getAttribute('action') || form.action || '').toString();

			//Exclusion for forms
			if (
				form.classList.contains('slp_search_form') || //StoreLocatorPlus form
				(form.parentElement && form.parentElement.classList.contains('mec-booking')) ||
				formAction.indexOf('activehosted.com') !== -1 || // Active Campaign
				(form.id && form.id === 'caspioform') || //Caspio Form
				typeof form.elements.ct_checkjs === 'undefined' // The form does not contain the ct_ field, skip this
			)
				continue;

			if (form.getAttribute('data-apbct-submit-bound')) {
				continue;
			}
			form.setAttribute('data-apbct-submit-bound', '1');

			// Collect visible fields without blocking native / previous submit handlers
			apbct_attach_event_handler(form, 'submit', function(event){
				var target = event.target || event.srcElement;
				apbct_collect_visible_fields_and_set_cookie(target);
			});
		}
	}, 1000);
}
apbct_attach_event_handler(window, "DOMContentLoaded", apbct_ready);

function apbct_get_checkjs_value() {
	var checkjs = jQuery('#ct_checkjs').val() || jQuery('.ct_checkjs').first().val() || '';
	if (!checkjs || checkjs === '0') {
		checkjs = new Date().getFullYear();
	}
	return checkjs;
}

function apbct_get_xform_form_id(url, settings) {
	var match = url.match(/formId=(\d+)/);
	if (match) {
		return match[1];
	}

	if (typeof FormData !== 'undefined' && settings.data instanceof FormData && settings.data.has && settings.data.has('formId')) {
		return settings.data.get('formId');
	}

	var hiddenFormId = jQuery('input[name="formId"]').val();
	if (hiddenFormId) {
		return hiddenFormId;
	}

	var containerMatch = jQuery('[id^="xform-"]').first().attr('id');
	if (containerMatch) {
		match = containerMatch.match(/^xform-(\d+)$/);
		if (match) {
			return match[1];
		}
	}

	return null;
}

// Include JS checking code to ajax requests.
// Guard: Journal/OpenCart AJAX often passes data as null or an object.
if (typeof jQuery !== 'undefined') {
	jQuery(document).ajaxSend(function(event, xhr, settings) {
		var url = settings.url || '';

		if (typeof settings.data === 'string' && settings.data.indexOf('account=register') !== -1) {
			settings.data += '&ct_checkjs=' + apbct_get_checkjs_value();
		}

		// X-Form Pro sends FormData via AJAX
		if (
			typeof FormData !== 'undefined' &&
			url.indexOf('extension/module/xform') !== -1 &&
			settings.data instanceof FormData
		) {
			if (!settings.data.has || !settings.data.has('ct_checkjs')) {
				settings.data.append('ct_checkjs', apbct_get_checkjs_value());
			}
		}
	});

	jQuery(document).ajaxSuccess(function(event, xhr, settings) {
		var url = settings.url || '';
		if (url.indexOf('extension/module/xform') === -1) {
			return;
		}

		try {
			var json = JSON.parse(xhr.responseText);
			if (json.ct_spam && json.message) {
				var formId = apbct_get_xform_form_id(url, settings);
				if (formId) {
					jQuery('#xform-' + formId + ' div.xform-success')
						.text(json.message)
						.css({
							color: '#a94442',
							background: '#f2dede',
							border: '1px solid #ebccd1',
							padding: '10px',
							marginBottom: '10px'
						})
						.show();
				}
			}
		} catch (e) {}
	});
}

} // end window.apbctJsInitialized
