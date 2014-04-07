var engine = new Bloodhound({
	name: 'active-users-oooo',
	prefetch: 'ajax/typeahead_users.php',
	datumTokenizer: function(d) { 
		  return Bloodhound.tokenizers.whitespace(d.c); 
	  },
	queryTokenizer: Bloodhound.tokenizers.whitespace
});

engine.initialize();

$('document').ready(function(){
	$('.typeahead-users').typeahead({
		highLight: true
	},
	{
		name: 'active-users-oooo',
		displayKey: function(data){ 
			return data.c + ' ' + data.n;
			},
		source: engine.ttAdapter()
	})
});
	
/*	
		{
		name:'active_users',
		prefetch: 
			{ url: 'ajax/typeahead_active_users.php',
			  filter: function(data){
					return data.map(function(user){
						return { 
							value : user.c + ' ' + user.n,
							tokens : [ user.c, user.n ],
							class : (user.e) ? 'warning' : ((user.s) ? 'info' : ((user.le) ? 'error' : ((user.a) ? 'success' : ''))),
							balance : user.b,
							limit : user.l
						};
					});
				},
			  ttl: 100000	
			}
//		template: '<p class="{{ class }}">{{ value }}</p>',
//		engine: Hogan
		});
	});
*/
