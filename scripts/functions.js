//Loading Spinner
function addLoadingSpinner($element) {
	if ($element.length>0) {
		$element.append("<div class='spinner'></div>");
	}
}
function removeLoadingSpinner($element) {
	if ($element.length>0) {
		$element.find(".spinner").remove();
	}
}

//NOTIFY FUNCTIONS
function notify(message, type) {
	$("#notification").hide();
	$("#notification").attr('class', '').addClass("alert").addClass("alert-"+type).slideDown();
	$("#notification").html('<div class="closedialogx">X</div>'+message);
}
$(document.body).click(function() {
	$("#notification").fadeOut(1200);
});

//Cast String to Integer or 0 if not Integer
//Redfine parseInt() to behave like PHP (int) => e.g. return 0 instead of NaN
function castInt(n) {
	var result = parseInt(n);
	if (isNaN(result)) {
		return 0;
	}
	else {
		return result;
	}
}

//SCHEDULE FUNCTIONS
	

//CHECK IF VARIABLE IS UNDEFINED
function isundefined(input) {
	return (typeof input==='undefined');
}

//CHECK IF VARIABLE IS NULL
function isnull(input) {
	return input===null;
}

//CHECK IF VARIABLE IS NULL OR UNDEFINED
function isnullorundef(input) {
	return (isundefined(input)||isnull(input));
}

//CHECK IF VARIABLE IS A FUNCTION
function isFunction(functionToCheck) {
	var getType = {};
	return functionToCheck && getType.toString.call(functionToCheck) === '[object Function]';
}

//CHECK IF ELEMENT IS VISIBLE
function isVisible($element) {
	return $element.css('display')!=='none';
}



//PREVENT XSS OUTPUT
function preventxss(string) {
	if (isnullorundef(string)) {
		return "";
	}
	else {
		var lt = /</g, 
			gt = />/g, 
			ap = /'/g, 
			ic = /"/g;
		return string.toString().replace(lt, "&lt;").replace(gt, "&gt;").replace(ap, "&#39;").replace(ic, "&#34;");
	}
}

//SUBMIT POST DATA AS IF THE DATA WERE IN A FORM (COMPLETE REDIRECT), NOT AN AJAX REQUEST
function formPOST(path, params, method) {
	method = method || "POST"; // Set method to post by default if not specified.

	// The rest of this code assumes you are not using a library.
	// It can be made less wordy if you use one.
	var form = document.createElement("form");
	form.setAttribute("method", method);
	form.setAttribute("action", path);

	for(var key in params) {
		if(params.hasOwnProperty(key)) {
			var hiddenField = document.createElement("input");
			hiddenField.setAttribute("type", "hidden");
			hiddenField.setAttribute("name", key);
			hiddenField.setAttribute("value", params[key]);

			form.appendChild(hiddenField);
		 }
	}

	document.body.appendChild(form);
	form.submit();
}


//COOKIE FUNCTIONS
function getCookie(c_name) {
	var c_value = document.cookie;
	var c_start = c_value.indexOf(";" + c_name + "=");
	if (c_start == -1) {
		c_start = c_value.indexOf(c_name + "=");
	}
	if (c_start == -1) {
	  c_value = null;
	}
	else {
		c_start = c_value.indexOf("=", c_start) + 1;
		var c_end = c_value.indexOf(";", c_start);
		if (c_end == -1) {
			c_end = c_value.length;
		}
		c_value = unescape(c_value.substring(c_start,c_end));
	}
	return c_value;
}
function setCookie(c_name,value,exdays) {
	var exdate=new Date();
	exdate.setDate(exdate.getDate() + exdays);
	var c_value=escape(value) + ((exdays==null) ? "" : "; expires="+exdate.toUTCString());
	document.cookie=c_name + "=" + c_value+";";
}
function deleteCookie(name) {
	document.cookie = name + '=;expires=Thu, 01 Jan 1970 00:00:01 GMT;';
}


//BUILD HTTP QUERY WITH GET VARIABLES BASED ON INPUT ARRAY
function http_build_query(arraydata, prefix) {
	var urlquery = "";
	for (var i = 0; i<arraydata.length; i++) { //Loop through every array element, and add to URLquery values
		urlquery += prefix+"["+i+"]"+"="+arraydata[i];
		if (i<arraydata.length-1) {
			urlquery += "&";
		}
	}
	return urlquery;
}


//AJAX
function defaultAJAXresultcheck(jsonResponse) { //Default, normal responses to AJAX results
	if (jsonResponse.result=="dbfailure") { //Server/PHP error
		notify("Sorry, there was a database error - Please try again at a later time.", "error");
		return true;
	}
	else if (jsonResponse.result=="invalidinput") {
		notify("Invalid input!","error");
		return true;
	}
	else if (jsonResponse.result=="redirect") { //Server/PHP error
		window.location.replace(param.redirect);
		return true;
	}
	return false;
}