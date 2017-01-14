$(document).ready(function() {
	//Bootstrap File Upload Buttons and feedback
	$("[type=file]").on("change", function() {
		var files = $(this)[0].files;
		var fileNames = "";
		for (var i = 0; i<files.length; i++) {
			if (i!=0) {
				fileNames += ", ";
			}
			fileNames += files[i].name;
			if (i==10) { //Limit to 10
				fileNames += "...";
				break;
			}
		}
		$(this).closest(".form-group").children(".label-info").html(fileNames);
	});
	
	//Bootstrap Tooltips
	$('[data-toggle="tooltip"]').tooltip();

	//Text toggle
	$(document.body).on('click', '.text-toggle', function() {
		var newText = $(this).attr('data-alt-text');
		$(this).attr('data-alt-text', $(this).text());
		$(this).text(newText);
	});

	//Radio button toggle
	$('input[type=radio][data-toggle=radio-collapse]').each(function(index, item) {
		var $item = $(item);
		var $target = $($item.attr('target'));

		$('input[type=radio][name="' + item.name + '"]').on('change', function() {
			if($item.is(':checked')) {
			  $target.show();
			}
			else {
			  $target.hide();
			}
		});
	});

});