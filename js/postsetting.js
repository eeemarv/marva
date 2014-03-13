function makePOSTRequest(url, parameters) {
   http_request = false;
   try {
        http_request = window.XMLHttpRequest?new XMLHttpRequest(): new ActiveXObject("Microsoft.XMLHTTP");
        } catch (e) {
                // browser doesn't support ajax. handle however you want
                alert("Browser ondersteunt geen AJAX");
        }

      if (http_request.overrideMimeType) {
      	// set type accordingly to anticipated content type
         //http_request.overrideMimeType('text/xml');
         http_request.overrideMimeType('text/html');
      }
   
   http_request.onreadystatechange = alertContents;
   http_request.open('POST', url, true);
   http_request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
   http_request.setRequestHeader("Content-length", parameters.length);
   http_request.setRequestHeader("Connection", "close");
   http_request.send(parameters);
}

function alertContents() {
   if (http_request.readyState == 4) {
      if (http_request.status == 200) {
         result = http_request.responseText;
         document.getElementById('serveroutput').innerHTML = result; 
	 resultRegExp = /OK/;
	 if(result.match(resultRegExp)){
		document.getElementById('zend').disabled = true;
	 } 
      } else {
         alert('Er was een fout bij het verwerken, probeer opnieuw');
      }
   }
}
   
function get(obj) {
   var poststr = "value=" + encodeURIComponent( document.getElementById("myvalue").value ) +
		"&setting=" + encodeURIComponent( document.getElementById("mysetting").value );
   makePOSTRequest('/preferences/postconfig.php', poststr);
}
