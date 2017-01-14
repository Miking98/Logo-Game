$(document).ready(function() {
	$("#login-box-form-password-show").click(function() {
		if ($(this).val()=="hide") { //Change password from Hidden => Show
			$(this).text("Hide");
			$(this).val("show");
			$("#login-box-form-password").attr('type', 'text');
		}
		else { //Show => Hide
			$(this).text("Show");
			$(this).val("hide");
			$("#login-box-form-password").attr('type', 'password');
		}
	})
});