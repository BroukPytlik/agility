(function($, undefined) {

$.nette.ext('spinner', {
	init: function () {
		this.spinner = this.createSpinner();
		this.spinnerImage = this.createSpinnerImage();
		this.spinnerImage.appendTo(this.spinner);
		this.spinner.appendTo('#container');
	},
	//start: function () {
	before: function () {
		this.spinner.show(this.speed);
		// set width of div with image
		this.spinnerImage.css({
		    width:this.spinner.width(),
		    top:($(window).scrollTop()+$(window).height()/4)
		});
	},
	complete: function () {
		this.spinner.hide(this.speed);
	}
}, {
	createSpinner: function () {
		return $('<div>', {
			id: 'ajax-spinner',
			css: {
				display: 'none'
			}
		});
	},
	createSpinnerImage: function(){
	    return $('<div>',{
		id:'ajax-spinner-image'
	    });
	},
	spinner: null,
	spinnerImage: null,
	speed: undefined
});

})(jQuery);