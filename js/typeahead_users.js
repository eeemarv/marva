var users = new Bloodhound({
	prefetch: {
		url: 'ajax/typeahead_users.php',
//		ttl: 0,
		filter: function(users){
			return $.map(users, function(user){
				return { 
					value : user.c + ' ' + user.n,
					tokens : [ user.c, user.n ],
					class : (user.e) ? 'warning' : ((user.s) ? 'info' : ((user.le) ? 'danger' : ((user.a) ? 'success' : ''))),
					balance : user.b,
					limit : user.l
				};
			});
		}
	},

	datumTokenizer: function(d) { 
		return Bloodhound.tokenizers.whitespace(d.value); 
	},
	queryTokenizer: Bloodhound.tokenizers.whitespace
});

users.initialize();

$('document').ready(function(){
	$('.typeahead-users').typeahead({
		highLight: true
	},
	{
		displayKey: function(user){ 
			return user.value;
			},
		source: users.ttAdapter(),
		templates: {
			suggestion: Handlebars.compile('<p class="{{class}}">{{ value }}</p>')
		}
	}) 
	users.clearPrefetchCache();
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
