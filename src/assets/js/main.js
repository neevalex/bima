const header = $('.header');
const mockup = $('#mockup');
let mockupPosition = 100;
mockup.length ? mockupPosition = mockup.position().top + mockup.height() : mockupPosition = 100;

$(window).on('load', function () {
	$(window).scrollTop(0);
	
	AOS.init();

	$(window).resize(function () {
		AOS.refresh();
	});

	header.addClass('loaded');
	tippy('.tippy');
	
	$(".p_filter").click(function(){
		$(this).hide();
		$('.woof_sid').show();
		$(".p_filter_off").show()
	});
	
	$(".p_filter_off").click(function(){
		$(this).hide();
		$('.woof_sid').hide();
		$(".p_filter").show()
    });
	
});

$(window).resize(function () {
	AOS.refresh;
});


