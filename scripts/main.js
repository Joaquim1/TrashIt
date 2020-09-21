$(document).ready(function() {
	$(".group input:text").focus(function() {
		var label = $(this).parent().find("label").first();
		focusText(label);
	});

	$(".group input:text").focusout(function() {
		var label = $(this).parent().find("label").first();

		if($(this).val().length < 1) {
			focusOutText(label);
		}
	});

	$(".group").each(function() {
		var input = $(this).find("input");

		if(input.val().length >= 1)
		{
			$(this).find("label").css("top", "-17px");
		}
	});

	$(".mask").click(function() {
		closePopup(".popup");
	});

	$(".close").click(function() {
		closePopup(".popup");
	});
});

function focusText(element) {
	element.animate({
		top: "-17px",
		color: "#002d47",
	}, 200);
}

function focusOutText(element) {
	element.animate({
		top: "0px",
		color: "#002d47",
	}, 200);
}

function showMask() {
	$(".mask").show();
}

function hideMask() {
	$(".mask").hide();
}

function openPopup(id)
{
	$(id).css("max-height", $(window).height() - 100);
	$(id).show();
	showMask();
}

function closePopup(id)
{
	hideMask();
	$(id).hide();
}

function toggleMenu() {
    $("#mobile-nav").toggleClass("change");
    $(".nav").slideToggle();
}

function scrollTo(element, time = 400)
{
	$('html, body').animate({
		scrollTop: $(element).offset().top - 70
	}, time);
}