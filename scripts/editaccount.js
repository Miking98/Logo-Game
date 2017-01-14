$(document).ready(function() {
	$("#editaccount-form-password-show").click(function() {
		if ($(this).val()=="hide") { //Change password from Hidden => Show
			$(this).text("Hide");
			$(this).val("show");
			$("#editaccount-form-password").attr('type', 'text');
		}
		else { //Show => Hide
			$(this).text("Show");
			$(this).val("hide");
			$("#editaccount-form-password").attr('type', 'password');
		}
	});

	$("#editaccount-form-password-container-toggle").click(function() {
		$("#editaccount-form-password").val("");
		if ($("#editaccount-form-password").is(":visible")) {
			$("#editaccount-form-password").prop("required", false);
		}
		else {
			$("#editaccount-form-password").prop("required", true);
		}
	});
});