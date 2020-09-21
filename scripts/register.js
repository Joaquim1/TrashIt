function checkForm() {
	var emailParam = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;

	var error = 0;

	if($("#firstname").val().length < 2) {
		showError("Please enter a valid first name");
 		markBox("#firstname", 1);
 		error = 1;
	}
 	else
 		markBox("#firstname", 0);

 	if($("#lastname").val().length < 2) {
 		if(error == 0) {
 			error = 1;
	 		showError("Please enter a valid last name");
 		}

 		markBox("#lastname", 1);
 	}
 	else
 		markBox("#lastname", 0);

 	if(!emailParam.test($("#email").val())) {
 		if(error == 0) {
 			error = 1;
	 		showError("Please enter a valid email");
 		}
 		markBox("#email", 1);
 	}
 	else
 		markBox("#email", 0);

 	if($("#password").val().length < 7) {
 		if(error == 0) {
 			error = 1;
	 		showError("Your password must be at least 7 characters with letters and numbers.");
 		}
 		markBox("#password", 1);
 	}
 	else
 		markBox("#password", 0);

 	if($("#confpass").val() != $("#password").val()) {
 		if(error == 0) {
 			error = 1;
	 		showError("Please make sure your passwords match!");
 		}
 		markBox("#confpass", 1);
 	}
 	else
 		markBox("#confpass", 0);

 	if(error == 1)
		return false;
	
	return true;
}

function markBox(id, status = 0) {
	if(status == 1) {
		$(id).css("border", "1px solid #C90000");
	}
	else if(status == 0) {
		$(id).css("border", "1px solid #d7d7d7")
	}
}

function showError(message) {
	$("#error").text(message);
	$("#error").css("visibility", "visible");
}