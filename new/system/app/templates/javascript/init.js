/**
 * This contain some initializations and configurations
 */

	    loaded = false;
	    /*$.nette.ext('cacheDisabling',{
		complete: function(){
		    $.nette.ext('history').cache = false;
		}
	    });*/

	$.nette.ext('my-hooks',{
		load: function () {
		    // datepicker
		    // because calendar prefilled format suck
		    // timeout is because some bug.... :(
		    setTimeout(function(){init();},1);
		    // set links in items to be target=_blank
		    agilityUI.linksTargetBlank();
		    // live validation
		    for (var i = 0; i < document.forms.length; i++) {
			Nette.initForm(document.forms[i]);
		    }
		} ,
		complete: function(){
		    for (var i = 0; i < document.forms.length; i++) {
			    Nette.initForm(document.forms[i]);
		    }
		    // because on page loading there is no reason to call some scripts
		    loaded = true;
		},
		success: function(payload){
		     agilityUI.pageStyles(payload,null);
		    // set links in items to be target=_blank
		     agilityUI.linksTargetBlank();
		},
		error: function(){
		    alert('Na stránce došlo k chybě! Zkuste to prosím znova. Pokud to nepomůže, dejte nám prosím vědět.');
		}
	});
            /** setup other things */
            /* $.nette.ext('datepicker', {
                    load: function () {
			// because calendar prefilled format suck
                        setTimeout(function(){init();},1);
                        
                    } // end ajax load
                });
		*/
	   /* $.nette.ext('onLiveValidation',{
		load: function(){
		    for (var i = 0; i < document.forms.length; i++) {
			    Nette.initForm(document.forms[i]);
		    }
		},
		complete: function(){
		    for (var i = 0; i < document.forms.length; i++) {
			    Nette.initForm(document.forms[i]);
		    }
		}
	    }); */
	    // change colors: 
	    // on url change
	    $(window).on('popstate.nette', $.proxy(function (e){
		 agilityUI.pageStyles(null,e);
	    }));
	    // or on change page colors on ajax
	    /*$.nette.ext('page-colors',{
		success: function(payload){
		     agilityUI.pageStyles(payload,null);
		}
	    });*/
            /*$.nette.ext('test',{
		
		load: function(){
		 /// console.log('load');
		},
		before: function(){
		  //console.log('before------');
		},
		start: function(){
		  //console.log('start');
		},
		error: function(){
		 // console.log('error');
		},
		success: function(payload){
		  console.log('success');
		  console.log(payload);
		},
		complete: function(){
		//  console.log('complete');
		}
	    }); /* */
            
            /** setup onLoad things */
            $(function () {
		
		unsetAjaxOnBadBrowsers();
		
		
                $.nette.init();
            /* Czech initialisation for the jQuery UI date picker plugin. */
            /* Written by Tomas Muller (tomas@tomas-muller.net). */
                $.datepicker.regional['cs'] = {
                    closeText: 'Zavřít',
                    prevText: '&#x3c;Dříve',
                    nextText: 'Později&#x3e;',
                    currentText: 'Nyní',
                    monthNames: ['leden', 'únor', 'březen', 'duben', 'květen', 'červen', 'červenec', 'srpen',
                        'září', 'říjen', 'listopad', 'prosinec'],
                    monthNamesShort: ['led', 'úno', 'bře', 'dub', 'kvě', 'čer', 'čvc', 'srp', 'zář', 'říj', 'lis', 'pro'],
                    dayNames: ['neděle', 'pondělí', 'úterý', 'středa', 'čtvrtek', 'pátek', 'sobota'],
                    dayNamesShort: ['ne', 'po', 'út', 'st', 'čt', 'pá', 'so'],
                    dayNamesMin: ['ne', 'po', 'út', 'st', 'čt', 'pá', 'so'],
                    weekHeader: 'Týd',
                    dateFormat: 'dd. mm. yy',
                    firstDay: 1,
                    isRTL: false,
                    showMonthAfterYear: false,
                    yearSuffix: ''
                };
                $.datepicker.setDefaults($.datepicker.regional['cs']);
                
                
                
		/** autoload for filter selecting */
		$('#stateSelector').change(function(){
		    $('#stateSelectorButton').click();
		});
		
		
                
            }); // end of onLoad


	    function init(){
		agilityUI.jqueryuiInit();
		agilityUI.calendarInit()
		//agilityUI.fixedBars(); 

		agilityUI.createAddress("#content");

	    }
	    

	    function unsetAjaxOnBadBrowsers(){
		if (!(window.history && history.pushState && window.history.replaceState && !navigator.userAgent.match(/((iPod|iPhone|iPad).+\bOS\s+[1-4]|WebApps\/.+CFNetwork)/))){
		    $('.ajax').removeClass('ajax');
		    // dont forget for editing!
		    $('[role=editOpen]').off('click');
		    $('[role=editDelete]').off('click');
		    $('[role=editOpen]').removeAttr('role');
		    $('[role=editDelete]').removeAttr('role');
		}
	    }