/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


function AgilityUI (){


    /** add link to gmaps to all address tag
     */
    this.createAddress = function(parent){
	$(parent).find("address").each(function(){
	    if($(this).attr("has-maps-link")) return;
	    
	    var link='<a href="http://maps.google.com?q='+$(this).html().replace(/<[^>]*>[^<]*<\/[^>]>/, '')+'">Mapa</a>';
	})
    }

    /** create jqueryui-like selectboxes from button and <ul>
     */
    this.createSelect = function(){
	
	$(".agility-select").css({
		position: 'relative','z-index': 2}).each(function(){
	    var root = $(this);
	    // do not reinit
	    if(root.attr('has-agility-select')) return;
	    
	    var showText = root.attr('show-text')
	    var buttonSettings = {
		icons: {
		    primary: (typeof root.attr('icon-primary') != "undefined")? root.attr('icon-primary') :undefined,
		    secondary: (typeof root.attr('icon-secondary') != "undefined")? root.attr('icon-secondary') :undefined
		},
		text: (typeof showText == "undefined" || showText == "true" || showText === 1) ? true : false
	    };
	    
	    root.attr('has-agility-select','true').css({
		'text-align':'left',
		position: 'relative'
	    }).children(':first').click(function(){
		var button = $(this);
		button.next().slideToggle(50);
		var handler =function (e){
		    var container = $(root);
		    //if (container.has(e.target).length === 0){
		    if(button.has(e.target).length === 0){
			button.next().slideUp(50);
		    }
		    $(document).unbind('mouseup',handler);
		};
		$(document).bind('mouseup',handler);
	    }).button(buttonSettings).next().css({
		position: 'absolute',
		'z-index': 3
	    }).slideUp(0).menu();
	});
    };
    
    /**
     * Find all elements of type in parent and change them to radiobuttons in
     * namespace (this is also used as prefix for id's
     * @param {string} parent
     * @param {string} type
     * @param {string} namespace
     * 
     * @return this - provide fluent interface
     */
    this.linksToRadiobuttons = function(parent,type,namespace){
	// create div into which we will move links
	var hider = $(document.createElement('div')).css('display','none');
	$(parent).parent().append(hider);
	//find all links, create radio button on its place and link move to hider.
	$(parent).each(function(){
	    var form = $('<form>',{'style':'display:inline;'});
	    $(this).append(form);
	    $(this).find(type).each(function(){
		var url = $(this).attr('href');
		$(this).addClass('hidden');
		var t = $(this);
		var newElem = $(document.createElement('input'));
		newElem.attr({
		    type:'radio',
		    name: namespace
		}).click(function(){
		    // because on ajax we want to run ajax, but else we need to redirect
		    if($(t).hasClass('ajax')){
			$(t).click();
		    }
		    else if($(t).attr('href')){
			window.location = $(t).attr('href')
		    }
		});
		// set ID
		var id=Math.random();
		if($(this).attr('id')){
		    id = $(this).attr('id');
		}
		newElem.attr('id',namespace+'-'+id);
		// set checked
		if($(this).hasClass('current')){
		    newElem.attr('checked','checked');
		}
		$(form).append(newElem);

		$(form).append('<label for="'+namespace+'-'+id+'"  >'+$(this).html()+'\n\
		    </label>');
	    });
	
	    $(hider).prepend($(this).find('a'));
	});
	return this;
    }
    
    /** init jqueryui settings
     */
    this.jqueryuiInit = function (){
	this.createSelect();
	
	/** change page numbers to radio buttons for jqueryui */
	this.linksToRadiobuttons('.paginator .pages','a','page');
	this.linksToRadiobuttons('#userMenu','a','menu');
	this.linksToRadiobuttons('#viewMode','a','viewMode');
	
	this.placeholderEmulation();

	$( "input[type=submit], button" ).button();
	// calendar link button
	if($( "#calendarLink" ).attr('disabled'))
	   $( "#viewMode-calendarLink" ).button({
		disabled:true,
		text: false,
		icons: {
		    primary: "calendar-icon"   // Custom icon
		}
	    });
	else
	    $( "#viewMode-calendarLink" ).button({
		text: false,
		icons: {
		    primary: "calendar-icon"   // Custom icon
		}
	    });
	// list link button
	if($( "#listLink" ).attr('disabled'))
	    $( "#viewMode-listLink" ).button({
		disabled:true,
		text: false,
		icons: {
		    primary: "list-icon"   // Custom icon
		}
	    });
	else
	    $( "#viewMode-listLink" ).button({
		text: false,
		icons: {
		    primary: "list-icon"   // Custom icon
		}
	    });
	$("#viewMode").buttonset();
	$( "#linkAddNew" ).button({
	    icons: {
		primary: "ui-icon-plus"
	    }
	});
	$( ".paginator .links" ).hide();
	$( ".paginator .previous" ).buttonset();
	$( ".paginator .pages" ).buttonset();
	$( ".paginator .next" ).buttonset();
	$(".paginator a").each(function(){
	    if(this.getAttribute('disabled'))$(this).button('disable');
	});
	$( "#userMenu" ).buttonset();
	
	// set roll down for google maps link
	$(".google-maps").each(function(){
	    $(this).attr('original-height', $(this).height());
	}).hover(function(){
	    if($(this).attr('slidingUp') || $(this).attr('slidingDown')) return;
	    $(this).attr('slidingUp', true);
	    $(this).animate({
		'margin-top':15-($(this).children('img').height()-$(this).height()),
		'height':($(this).children('img').height()-15)
	    },200,function(){$(this).removeAttr('slidingUp');});
	}, function(){
	    if($(this).attr('slidingDown')) return;
	    $(this).attr('slidingDown', true);
	    $(this).animate({
		'margin-top':0,
		'height':($(this).attr('original-height'))
	    },200,function(){$(this).removeAttr('slidingDown', true);});
	    
	});
	
	
       
	this.tooltipInit();
    };
    /** init tooltips 
     */
    this.tooltipInit=function(){
	/** initialize tooltips */
	
	$("form").each(function(){
	    if($(this).closest('#viewControl').length) return;
	    if($(this).closest('#userContent').length) return;
	    $(this).tooltip({
		    position: { my: "left+15 center", at: "right center" } ,
		    close: function(){$(this).removeAttr('title');}
		});
	    $(this).removeAttr('title');
	    $(this).find('small').hide();
	});
    };
    
    /** init calendar picker 
     * 
     */
    this.calendarInit = function(){
	$("input.date").each(function () { // input[type=date] does not work in IE

	    var el = $(this);
	    var value = el.val();
	    var date,minDate,maxDate;
	    
	    // because date could be already formated
	    // default format could fail, so in that case try it in catch again with another
	    try{
		    date = (value ? $.datepicker.parseDate($.datepicker.W3C, value) : null);
		    minDate = el.attr("min") || null;
		    if (minDate)
			minDate = $.datepicker.parseDate($.datepicker.W3C, minDate);
		    maxDate = el.attr("max") || null;
		    if (maxDate)
			maxDate = $.datepicker.parseDate($.datepicker.W3C, maxDate);
		
	    }
	    catch(e){
		date = (value ? $.datepicker.parseDate(el.datepicker("option", "dateFormat"), value) : null);
		minDate = el.attr("min") || null;
		if (minDate)
		    minDate = $.datepicker.parseDate(el.datepicker("option", "dateFormat"), minDate);
		maxDate = el.attr("max") || null;
		if (maxDate)
		    maxDate = $.datepicker.parseDate(el.datepicker("option", "dateFormat"), maxDate);
	    }
	    // input.attr("type", "text") throws exception
	    if (el.attr("type") == "date") {
		var tmp = $("<input/>");
		$.each("class,disabled,id,maxlength,name,readonly,required,size,style,tabindex,title,value,data-nette-rules".split(","), function(i, attr)  {
		    tmp.attr(attr, el.attr(attr));
		});

		tmp.data(el.data());
		el.replaceWith(tmp);
		el = tmp;
	    }
	    el.datepicker({
		minDate: minDate,
		maxDate: maxDate
	    });
	    el.val($.datepicker.formatDate(el.datepicker("option", "dateFormat"), date));
	});
    };
    
	/** keep the paginator all time visible */
    this.fixedBars = function(){
	var t = this;
	// at first run initial check
	t.fixedBarsComputeTop();
	t.fixedBarsComputeBottom();
	
	// then create hook
	$(window).scroll(function () { 
	    t.fixedBarsComputeTop();
	    t.fixedBarsComputeBottom();
	});    
	
    }
    this.fixedBarsComputeTop = function(){
	var offset = $('#viewControlPosition').position().top - $(window).scrollTop();
	if(offset < 0){
	    $('#viewControl').css('position', 'fixed');
	}else{
	    $('#viewControl').css('position', 'relative');
	}
    }
    this.fixedBarsComputeBottom = function(){
	var offset = $('#bottomFixedPosition').position().top - ($(window).scrollTop()+$(window).height());
	if(offset > 0){
	    $('#bottomFixed').css('position', 'fixed');
	}else{
	    $('#bottomFixed').css('position', 'relative');
	}
    }
    
    
    /** create placeholder for browsers that cant it */
    this.placeholderEmulation = function(){
	 if ( !("placeholder" in document.createElement("input")) ) {
	    $("input[placeholder], textarea[placeholder]").each(function() {
		var self = this;
		var val = $(this).attr("placeholder");
		
		var computePos = function(elem,parent){
		    $(elem).css({
			position:'absolute',
			top:3,
			left:10,
			padding:$(parent).css('padding'),
			'padding-right':20,
			// 15 px is because of scrollbar
			width: $(parent).children('textarea,input').innerWidth()-15 
		    })
		};
		
		// set parent position
		var parent=$(this).parent();
		parent.css('position','relative');
		// create placeholder and put content into it
		var placeholder = $('<div>',{
		    id:'placeholder-'+Math.random()
		}).addClass('placeholder')
		.html(val);
		
		computePos(placeholder,parent);
		//console.log(parent[0].tagName)
		placeholder.appendTo(parent)
		.hide();
		if ( this.value == "" ) {
		    //this.value = val;
		    //$(this).addClass("placeholder");
		    placeholder.css({
			width: $(parent).children('textarea,input').innerWidth()-15
		    }).show();
		}
		
		// set resize handler
		$(window).resize(function() {
		    computePos(placeholder,parent);
		});
		
		// set event for the placeholder
		placeholder.click(function() {
			$(self).focus();
		});
		placeholder.hover(function() {
			$(self).addClass('active');
		},function(){
			$(self).removeClass('active');
		})
		// set event for the form
		$(this).keydown(function(e) {
		    // because we want to know the content AFTER it is written, not before
		    setTimeout(function(){
			// if now it is empty, show placeholder
			if($(self).val() ==""){
			    placeholder.show();
			}else{
			    // else hide it if it isn't yet
			    if ( placeholder.css('display')!="none" ) {
				placeholder.hide();
			    }
			}
		    },1);
		   
		    
		});
	    });


	}
	
    }
    
    this.linksTargetBlank = function(){
	$(".item .left a, .item .right a").attr('target', '_blank');
    }
    
    /**
     * change page colors etc
     * @param payload - payload object
     * @param popstate - object from pop history
     */
    this.pageStyles = function(payload,popstate){
	//console.log(newPage);
	if(popstate){
	    var state = popstate.originalEvent.state || this.initialState;
	  //  console.log(state);
	    if(state){
		newPage = state.pageUrl;
	    }
	}
	else if(payload){
	    newPage = payload.page
	}
//console.log(PAGE+' => '+newPage)
	// if page changed and change should be noticed
	     if(PAGE != newPage && (!payload ||!payload.doNotSave) ){

		 listOfStyles=new Array(
		    '-body-page','-wrapper-page', '-h1a-page',
		    '-header-nav-page','-header-nav-a-page',
		    '-container-page', '-footer-page'
		 );
		 for(var i in listOfStyles ){
		     $('.'+PAGE+listOfStyles[i]).switchClass(PAGE+listOfStyles[i], newPage+listOfStyles[i],300)
		 }
		 // save new value
		 PAGE = newPage;

		 //console.log(PAGE);
	     }
	     if(payload){
		 // setup for calendar styling
		 if(payload.isCalendar){
		     //console.log("is cal");
		     $('body').addClass('calendar-page');
		 }
		 else{
		     //console.log("is NOT cal");
		     $('body').removeClass('calendar-page');
		 }
	     }
    }
    
};
var agilityUI = new AgilityUI();