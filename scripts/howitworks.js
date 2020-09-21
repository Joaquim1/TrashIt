$(document).ready(function() {
	setTimeout(function() { loadIcon("#user-laptop") }, 100);
	setTimeout(function() { loadIcon("#home-garbage") }, 300);
	setTimeout(function() { loadIcon("#truck-pickup") }, 600);
});

function loadIcon(div)
{
	$(div).css('opacity', 0).slideDown('slow').animate(
    	{ opacity: 1 },
    	{ queue: false, duration: 'slow' }
  	);
}