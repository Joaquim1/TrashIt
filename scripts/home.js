$(document).ready(function() {
	navCheck();

	$(".holder").css("width", $(window).width());
	$(".holder").css("height", $(window).height());

	$(window).scroll(function() {
		navCheck();
	});

	$(".navLink").click(function() {
		if($(window).width() <= 736) {
			toggleMenu();
		}
	});

});

function navCheck(active) {
	if($(window).scrollTop() > 4) {
		if($(window).width() <= 736) {
			$(".nav").css("background", "#fff");
		}
		$(".navLink").css("color", "rgb(0,45,71)");
		$("#nav-holder").css("height", "70px");
		$("#nav-holder").css("position", "fixed");
		$("#nav-holder").css("box-shadow", "0px 5px 5px rgba(0,0,0,0.1)");
		$("#logo").css("background", "url(images/home/logo.png) no-repeat center");
		$("#logo").css("background-size", "171px 47px");
		$(".bar1").css("background-color", "#063e6b");
		$(".bar2").css("background-color", "#063e6b");
		$(".bar3").css("background-color", "#063e6b");
	}
	else {
		if($(window).width() <= 736) {
			$(".nav").css("background", "#fff");
			$(".navLink").css("color", "rgb(0,45,71)");
			$("#nav-holder").css("height", "70px");
			$("#nav-holder").css("position", "fixed");
			$("#nav-holder").css("box-shadow", "0px 5px 5px rgba(0,0,0,0.1)");
			$("#logo").css("background", "url(images/home/logo.png) no-repeat center");
			$("#logo").css("background-size", "171px 47px");
			$(".bar1").css("background-color", "#063e6b");
			$(".bar2").css("background-color", "#063e6b");
			$(".bar3").css("background-color", "#063e6b");
		} else {
			$(".navLink").css("color", "rgb(255,255,255)");
			$("#nav-holder").css("height", "0px");
			$("#nav-holder").css("position", "relative");
			$("#nav-holder").css("box-shadow", "none");
			$(".nav").css("display", "block");
			$("#logo").css("background", "url(images/home/logo-white.png) no-repeat center");
			$("#logo").css("background-size", "171px 47px");
			$(".bar1").css("background-color", "#fff");
			$(".bar2").css("background-color", "#fff");
			$(".bar3").css("background-color", "#fff");
		}
	}
}