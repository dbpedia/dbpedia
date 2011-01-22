    var http_request = false;
	function loadClusterConnection(url) {
		http_requestc = false;
        if (window.XMLHttpRequest) { // Mozilla, Safari,...
            http_requestc = new XMLHttpRequest();
            if (http_requestc.overrideMimeType) {
                http_requestc.overrideMimeType('text/xml');
            }
        } else if (window.ActiveXObject) { // IE
            try {
                http_requestc = new ActiveXObject("Msxml2.XMLHTTP");
            } catch (e) {
                try {
                    http_requestc = new ActiveXObject("Microsoft.XMLHTTP");
                } catch (e) {}
            }
        }
        if (!http_requestc) {
	            alert('Ende :( Kann keine XMLHTTP-Instanz erzeugen');
	            return false;
	    }
	   	http_requestc.onreadystatechange = function(){
	        if (http_requestc.readyState == 4) {
             var answer = http_requestc.responseText;
        	 //var answer = http_request.responseText;
        	 if(document.getElementById("clusterCon").innerHTML != answer){
                document.getElementById("clusterCon").innerHTML = answer;
                //document.getElementById("clusterCon").style.visibility='visible';
                toggle('clusterCon',false);
             }
             else {
                document.getElementById("clusterCon").innerHTML = "";
             }
        	}
		};
	    http_requestc.open('GET', url + "&dummy=" + new Date().getTime(), true);
	    http_requestc.send(null);	    	
	}
	function saveQuery(url,arrayserialized) {
		http_requests = false;
		//alert(arrayserialized);
        if (window.XMLHttpRequest) { // Mozilla, Safari,...
            http_requests = new XMLHttpRequest();
            if (http_requests.overrideMimeType) {
                http_requests.overrideMimeType('text/xml');
            }
        } else if (window.ActiveXObject) { // IE
            try {
                http_requests = new ActiveXObject("Msxml2.XMLHTTP");
            } catch (e) {
                try {
                    http_requests = new ActiveXObject("Microsoft.XMLHTTP");
                } catch (e) {}
            }
        }
        if (!http_requests) {
	            alert('Ende :( Kann keine XMLHTTP-Instanz erzeugen');
	            return false;
	    }
	   	http_requests.onreadystatechange = function(){
	        if (http_requests.readyState == 4) {
             var answer = http_requests.responseText;
        	 //var answer = http_request.responseText;
        	 if(document.getElementById("save").innerHTML != answer){
                document.getElementById("save").innerHTML = answer;
             }
             else {
                document.getElementById("save").innerHTML = "";
             }
        	}
		};
	    http_requests.open('POST', url + "&dummy=" + new Date().getTime(), true);
	    http_requests.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8");
	    http_requests.send("arrayserialized=" + arrayserialized);
	    setTimeout("ladeQueries('ajax.php?f=2&sort=time','q')",300);
	}
	function loadProgress(url) {
		http_requestp = false;

        if (window.XMLHttpRequest) { // Mozilla, Safari,...
            http_requestp = new XMLHttpRequest();
            if (http_requestp.overrideMimeType) {
                http_requestp.overrideMimeType('text/xml');
            }
        } else if (window.ActiveXObject) { // IE
            try {
                http_requestp = new ActiveXObject("Msxml2.XMLHTTP");
            } catch (e) {
                try {
                    http_requestp = new ActiveXObject("Microsoft.XMLHTTP");
                } catch (e) {}
            }
        }
        if (!http_requestp) {
	            alert('Ende :( Kann keine XMLHTTP-Instanz erzeugen');
	            return false;
	        }
	        
	        http_requestp.onreadystatechange = alertProgress;
	        http_requestp.open('GET', url + "&dummy=" + new Date().getTime(), true);
	        http_requestp.send(null);
	}
    
    function ladeQueries(url,label) {

        http_request = false;

        if (window.XMLHttpRequest) { // Mozilla, Safari,...
            http_request = new XMLHttpRequest();
            if (http_request.overrideMimeType) {
                http_request.overrideMimeType('text/xml');
            }
        } else if (window.ActiveXObject) { // IE
            try {
                http_request = new ActiveXObject("Msxml2.XMLHTTP");
            } catch (e) {
                try {
                    http_request = new ActiveXObject("Microsoft.XMLHTTP");
                } catch (e) {}
            }
        }
     
		if (label=='q') {
	        if (!http_request) {
	            alert('Ende :( Kann keine XMLHTTP-Instanz erzeugen');
	            return false;
	        }
	        
	        http_request.onreadystatechange = alertQuerylist;
	        http_request.open('GET', url + "&dummy=" + new Date().getTime(), true);
	        http_request.send(null);
	       
		}
    }

    function alertQuerylist() {
        if (http_request.readyState == 4) {
             var answer = http_request.responseText;
        	   //var answer = http_request.responseText;
        	   if(document.getElementById("queries").innerHTML != answer){
                document.getElementById("queries").innerHTML = answer;
              }
              else{
                document.getElementById("queries").innerHTML = "";
              }
        }

    }
   
      function alertProgress() {
        if (http_requestp.readyState == 4) {
             var answer = http_requestp.responseText;
        	   //var answer = http_request.responseText;
        	   if(document.getElementById("progress").innerHTML != answer){
                document.getElementById("progress").innerHTML = answer;
              	document.getElementById("progress").style.visibility='visible';
              }
              else{
                document.getElementById("progress").innerHTML = "";
              }
        }

    }

	function ladeInfobox(url,id,object) {
		http_requestib = false;

        if (window.XMLHttpRequest) { // Mozilla, Safari,...
            http_requestib = new XMLHttpRequest();
            if (http_requestib.overrideMimeType) {
                http_requestib.overrideMimeType('text/xml');
            }
        } else if (window.ActiveXObject) { // IE
            try {
                http_requestib = new ActiveXObject("Msxml2.XMLHTTP");
            } catch (e) {
                try {
                    http_requestib = new ActiveXObject("Microsoft.XMLHTTP");
                } catch (e) {}
            }
        }
        if (!http_requestib) {
	            alert('Ende :( Kann keine XMLHTTP-Instanz erzeugen');
	            return false;
	        }
	        
	        http_requestib.onreadystatechange = function(){
	        if (http_requestib.readyState == 4) {
             var answer = http_requestib.responseText;
        	   //var answer = http_request.responseText;
        	   if(document.getElementById("ib_" + id).innerHTML != answer){
                document.getElementById("ib_" + id).innerHTML = "<table class=\"closeheader\"><tr><td width=\"100%\">" +object+ "</td><td style=\"background-color:orange;\"><a href=\"#e\" style=\"color:white;text-decoration:none;\" onclick=\"closeInfobox(" +id+ ")\">x</a></td></tr></table>" +answer;
              	document.getElementById("ib_" + id).style.backgroundColor = 'white';
              }
              else{
                document.getElementById("ib_" + id).innerHTML = "";
              }
        }
	        };
	        http_requestib.open('GET', url + "&dummy=" + new Date().getTime(), true);
	        http_requestib.send(null);
        
	}
	function closeInfobox(id) {
	//document.getElementById("ib_" + id).style.visibility = 'hidden';
	document.getElementById("ib_" + id).innerHTML = "";
	document.getElementById("ib_" + id).style.backgroundColor = 'transparent';
	}
	
//var newObjectList = new Array();

function addNewObject(userInput,variable) {
	for(i=0;i<=variable.length;i++) {
		if (userInput==variable[i]) {
			alert(userInput + " already in List");
			var dontadd = false;
		}
	}
	
	if((variable.length<5) && dontadd!=false) {
		variable.push(userInput);
		printIgnored();
		
	}
	else {
		if (dontadd!=false)
			alert("Maximum of 5 ignored Objects reached");
	}
}
function deleteObject(n,variable) {
	var x = -1;
	while((++x)<variable.length) {
		if(x>=n) 
			variable[x] = variable[x+1];	
	}
	variable.pop();
	printIgnored();
}

function printIgnored() {
	if ( document.getElementById("ignoreObjectList").hasChildNodes() ) {
	    while ( document.getElementById("ignoreObjectList").childNodes.length >= 1 ) {
	        document.getElementById("ignoreObjectList").removeChild( document.getElementById("ignoreObjectList").firstChild );       
	    } 
	}
	if ( document.getElementById("ignorePredicateList").hasChildNodes() ) {
	    while ( document.getElementById("ignorePredicateList").childNodes.length >= 1 ) {
	        document.getElementById("ignorePredicateList").removeChild( document.getElementById("ignorePredicateList").firstChild );       
	    } 
	}
/*
	if (isNaN(document.getElementById("continueform"))) {
		if ( document.getElementById("ignoreHelp").hasChildNodes() ) {
	    while ( document.getElementById("ignoreHelp").childNodes.length >= 1 ) {
	        document.getElementById("ignoreHelp").removeChild( document.getElementById("ignoreHelp").firstChild );       
	    	} 
		}	
	}
*/
	for (i=0;i<newObjectList.length;i++) {
		var myInput = document.createElement("input");
		myInput.setAttribute("type","hidden");
		myInput.setAttribute("name","ignoreObject_" + i);
		myInput.setAttribute("value",newObjectList[i]);
		var myInputText=document.createElement("span");
		//myInputText.innerHTML=' <a href="javascript:deleteObject('+ i +',newObjectList)">[-]</a>'+(newObjectList[i].length>10)?newObjectList[i].substring(0,10)+'...':newObjectList[i];
		if (newObjectList[i].length>20)
			myInputText.innerHTML=' <a href="javascript:deleteObject('+ i +',newObjectList)">[-]</a>'+newObjectList[i].substring(0,20)+'...';
		else
			myInputText.innerHTML=' <a href="javascript:deleteObject('+ i +',newObjectList)">[-]</a>'+newObjectList[i];
		var oNewNode = document.createElement("LI");
		
		document.getElementById("ignoreObjectList").appendChild(oNewNode);
		oNewNode.appendChild(myInput);
		oNewNode.appendChild(myInputText);		
/*
		if (isNaN(document.getElementById("continueform"))) {
			document.getElementById("ignoreHelp").appendChild(myInput);
		}
*/
	}
	for (i=0;i<newPredicateList.length;i++) {
		var myInputPred = document.createElement("input");
		myInputPred.setAttribute("type","hidden");
		myInputPred.setAttribute("name","ignorePredicate_" + i);
		myInputPred.setAttribute("value",newPredicateList[i]);
		var myInputPredText=document.createElement("span");
		//myInputPredText.innerHTML=' <a href="javascript:deleteObject('+ i +',newPredicateList)">[-]</a>'+(newPredicateList[i].length>10)?newPredicateList[i].substring(0,10)+'...':newPredicateList[i];
		if (newPredicateList[i].length>20)
			myInputPredText.innerHTML=' <a href="javascript:deleteObject('+ i +',newPredicateList)">[-]</a>'+newPredicateList[i].substring(0,20)+'...';
		else
			myInputPredText.innerHTML=' <a href="javascript:deleteObject('+ i +',newPredicateList)">[-]</a>'+newPredicateList[i];
		var oNewNodePred = document.createElement("LI");
		
		document.getElementById("ignorePredicateList").appendChild(oNewNodePred);
		oNewNodePred.appendChild(myInputPred);
		oNewNodePred.appendChild(myInputPredText);		
/*
		if (isNaN(document.getElementById("continueform"))) {
			document.getElementById("ignoreHelp").appendChild(myInputPred);
		}
*/
	}
	
}