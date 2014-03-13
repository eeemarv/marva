
function onLoadMenuContext()
{
	if (!document.getElementById('menu'))
	{
		return;
	}

	var hrefString = document.location.href ? document.location.href : document.location;
	var hrefAry = hrefString.split('/');

	var filename = hrefAry[hrefAry.length-1].toLowerCase();
	var dir = hrefAry[hrefAry.length-2].toLowerCase();

	var aAry = document.getElementById('menu').getElementsByTagName('a');

	for (var i = 0; i < aAry.length; i++)
	{
		var aHrefString = aAry[i].href;
		var aHrefAry = aHrefString.split('/');

		var aFilename = aHrefAry[aHrefAry.length-1].toLowerCase(); 

		if (aFilename == filename)
		{
			if (aHrefAry.length > 2)
			{
				var aDir = aHrefAry[hrefAry.length-2].toLowerCase();
				
				if (aDir != dir)
				{
					continue;
				}	
			}
			
			aAry[i].className = aAry[i].className + ' current';
			break;
		}

	}
}

window.onload = onLoadMenuContext;


