window.addEvent('domready', function() {
	var MyContactFormDiv = new Fx.Slide('contactformdiv');
	var MyPwFormDiv = new Fx.Slide('pwformdiv');
	var MyOIDFormDiv = new Fx.Slide('oidformdiv');
	
	MyContactFormDiv.slideIn();
	$('contactformdiv').removeClass('hidden');

	MyPwFormDiv.slideIn();
	$('pwformdiv').removeClass('hidden');

	MyOIDFormDiv.slideIn();
	$('oidformdiv').removeClass('hidden');

	$('contactform').addEvent('submit', function(e) {
		e.stop();
		var log = $('log_res').empty().addClass('ajax-loading');
		this.set('send', {
			onComplete: function(response) {
			log.removeClass('ajax-loading');
			responseRegExp = /OK/;
			if(response.match(responseRegExp)){
				jsnotify(response,0);
				MyContactFormDiv.slideIn();
			} else {
				jsnotify(response,0);
			}
		}});
		//Send the form.
		this.send();
	});

	$('oidform').addEvent('submit', function(e) {
		e.stop();
		var log = $('log_res').empty().addClass('ajax-loading');
		this.set('send', {
			onComplete: function(response) {
			log.removeClass('ajax-loading');
			responseRegExp = /OK/;
			if(response.match(responseRegExp)){
				jsnotify(response,0);
				MyOIDFormDiv.slideIn();
			} else {
				jsnotify(response,1);
			}
		}});
		//Send the form.
		this.send();
	});


	$('pwform').addEvent('submit', function(e) {
		e.stop();
		var log = $('log_res').empty().addClass('ajax-loading');
		this.set('send', {
			onComplete: function(response) {
			log.removeClass('ajax-loading');
			responseRegExp = /OK/;
			if(response.match(responseRegExp)){
				jsnotify(response,0);
				MyPwFormDiv.slideIn();
			} else {
				jsnotify(response,1);
			}
		}});
		//Send the form.
		this.send();
	});


	$('showcontactform').addEvent('click', function(e) {
		e.stop();
		MyContactFormDiv.toggle();
	});

	$('showpwform').addEvent('click', function(e) {
		e.stop();
		MyPwFormDiv.toggle();
	});
	
	$('showoidform').addEvent('click', function(e) {
		e.stop();
		MyOIDFormDiv.toggle();
	});

});
