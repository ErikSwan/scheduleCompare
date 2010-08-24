var httpobject;
var lastName;

function getOffset( el ) {
    var _x = 0;
    var _y = 0;
    
    if(obj.offsetParent) {
	var obj = el;
	while(1)
	{
	    _x += obj.offsetLeft;
	    if(!obj.offsetParent)
		break;
	    obj = obj.offsetParent;
	}
	obj = el;
	while(1)
	{
	    _y += obj.offsetTop;
	    if(!obj.offsetParent)
		break;
	    obj = obj.offsetParent;
	}
    }
    else if(obj.x) {
	_x += obj.x;
	_y += obj.y;
    }
    return { top: _y, left: _x };
}

function getInfo(str,length,myName)
{
    if(str.length != 0)
    {
	httpobject=GetHttpObject();
	if (httpobject != null)
	{
	    lastName = myName;
	    var url="/includes/ajax.php";
	    url=url+"?str="+str;
	    url=url+"&length="+length;
	    url=url+"&myName="+myName;
	    httpobject.onreadystatechange=stateChanged;
	    httpobject.open("get",url);
	    httpobject.send(null);
	}
    } else {
	document.getElementById("autoSuggestionsList").innerHTML="";	
    }
}

function stateChanged()
{
    if (httpobject.readyState==4)
    {
	document.getElementById("autoSuggestionsList").innerHTML=httpobject.responseText;
	document.getElementById("autoSuggestionsList").style.position = "absolute";
	document.getElementById("autoSuggestionsList").style.top = ($("#" + lastName).offset().top + 10) + "px";
	document.getElementById("autoSuggestionsList").style.left = $("#" + lastName).offset().left + "px";
    }
}

function GetHttpObject()
{
    if (window.ActiveXObject) 
	return new ActiveXObject("Microsoft.XMLHTTP");
    else if (window.XMLHttpRequest) 
	return new XMLHttpRequest();
    else 
    {
	alert("Your browser does not support AJAX.");
	return null;
    }
}