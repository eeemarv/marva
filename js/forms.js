function recalc_table_sum(el_input){
	var tbody = el_input.parentNode.parentNode.parentNode;
	var inputs = tbody.getElementsByTagName('input');
	var sum = 0;
	for (var i = 0; i < inputs.length; i++){
		sum += (inputs[i].value) ? parseInt(inputs[i].value) : 0;
	}
	document.getElementById('table_total').innerHTML = '<strong>' + sum + '</strong>';
}

$(document)
    .on('change', '.btn-file :file', function() {
        var input = $(this),
            label = input.val().replace(/\\/g, '/').replace(/.*\//, '');
        input.trigger('fileselect', [label]);
});

$(document).ready( function() {
    $('.btn-file :file').on('fileselect', function(event, label) {
        console.log(label);
        var input = $(this).parents('.input-group').find(':text');
		input.val(label);
    });
});
