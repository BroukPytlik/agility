/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


function AgilityCalendar(){
    this.calendar=null;
    
    /**
     * initialization
     */
    this.setDialogs = function(calendar){
	this.calendar = calendar;
	var i=0;
	var self = this;
	// for all events
	calendar.find('.ec-event').each(function(){
	    // test if this event wasn't already initialized
	    if($(this).attr('calendar-initialized')) return;
	    // if not, then find all cells for this event
	    var cells = calendar.find('[event='+$(this).attr('event')+']');
	    $(cells).each(function(){
		// save init attr
		$(this).attr('calendar-initialized',true);
		// for each
		$(this).hover(function(){
			$(cells).addClass('active');
		},function(){
			$(cells).removeClass('active');
		});
		// handle click event for showing details
		$(this).click(function(){
		    // find details
		    var detail = calendar.find('[for-event='+$(this).attr('event')+']');
		    detail.dialog({
			autoOpen: false,
			minWidth:560,
			width:560,
			minHeight:120,
			height: 'auto',
			show: {effect:"scale",duration:150},
			hide: {effect:"scale",duration:150},
			position: {my:'top',at:'bottom+10px', of: $(this),collision:'flipfit'}
		    });
		    if($('[for-event='+$(this).attr('event')+']').dialog('isOpen')){
			$('[for-event='+$(this).attr('event')+']').dialog('close');
		       // $(detail).hide();
		    }
		    else{
			$('[for-event='+$(this).attr('event')+']').dialog('open');
		    }
		});
	    
	    }); // end of each()
	    var item =$('[for-event='+$(this).attr('event')+']');
	  //  self.loadDetails(item,item.attr('data-url'));
	});// end of calendar.find
    };
    
    
    this.destroyDialogs = function(){
	    if(this.calendar == null) return;
	    
	    $('.calendarItem').each(function(){
		if($(this).hasClass('ui-widget-content'))
		$(this).dialog('destroy');
	    });
	    this.calendar = null;
    }
    
}
var calendar = new AgilityCalendar()