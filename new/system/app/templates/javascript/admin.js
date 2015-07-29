/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

function Agility(){
    this.agilityUI = null;
    this.window = null; // jqueryui modal window
    this.loader = null; // loader element showed in the modal window
    this.data = null;	// iframe with data
    this.ajaxData = null;	// div with data
    this.confirm = null;    // confirmation dialog
    this.editUrl = null;
    this.itemId = null;
    this.callback = null;
    
    // set to false after opening window
    // set to true after server reply 
    this.alreadyEditing = false;
    
    this.handleEdit = function (parent){
	    /* Delete handling:
	     * When user click on it, a dialog pop-up.
	     * When user click on cancel, nothing change.
	     * When user click on delete, ajax request is done
	     */
	    $(parent).find('[role=editDelete]').click(function(){
		    agility.confirmDelete($(this));
		    return false;
		});
	    /* Delete handling:
	     * When user click on it, a dialog pop-up.
	     * When user click on cancel, nothing change.
	     * When user click on delete, ajax request is done
	     */
	    $(parent).find('[role=editOpen]').click(function(){
		//console.log('open edit')
		    agility.openEdit($(this));
		    return false;
		});
	}
	
    /** set up the jqueryui dialog 
     */
    this.run = function(){
	//var id=this.itemId;
	var modalWindow=this.window
	//var loader = this.loader;
	var confirm = this.confirm;
	var data = this.ajaxData;		
	
	var t = this;
	/** 
	 * create edit form 
	 */
	this.window.dialog({
            modal: true,
	    autoOpen: false,
            buttons: [
		{
		    id: "editForm-button-save",
		    text: 'Uložit',
		    click: function() {
			// sent save request
			data.find('input[name=save]').click();
			
		    }
		},
		{
		    id: "editForm-button-close",
		    text: 'Zavřít',
		    click: function() {
			$(this).dialog("close");
		    }
		}
	    ]
            
        });
	/**
	 * create confirm form 
	 * 
	 */
	this.confirm.dialog({
            resizable: false,
	    autoOpen: false,
            height:200,
            modal: true,
            buttons: {
                "Smazat": function() {
                    $( this ).dialog( "close" );
		    t.deleteItem(function(){
			t.confirm.dialog( "close" );
		    })
                },
                Storno: function() {
                    $( this ).dialog( "close" );
                }
            }
        });
	
	/** hook to ajax loads 
	 *
	 */
	$.nette.ext('agility-admin',{
	    /*before: function(jqXHR, settigns){
		console.log(jqXHR);
		console.log(settigns)
	    }*/
	    load:function(){
		t.handleEdit($('body'))
		$('.no-ajax').off('click.nette');
	    },
	    success:function(payload){
		if(payload.editSaved){
		    t.updateItem(payload.editSaved,function(){
			t.agilityUI.jqueryuiInit();
		    });
		}
	    }
	});
    }
    
    /** open window with editing 
     */
    this.openEdit = function(button){
	// check correct width of window
	var width = 520;
	if($(window).width()<width){
	    width=$(window).width();
	}
	$("#editForm-button-save").button("enable");
	$("#editForm-button-close").button("enable");
	this.itemId = $(button).attr('for');
	this.alreadyEditing = false;
	$(this.window).dialog({
		width: width,
		position:{
		    my: 'top+20',
		    at: 'top',
		    of: $(window)
		}
	});
	
	//$(this.loader).show();
	//$(this.data).hide();
	//$(this.loader).hide();
	//$(this.ajaxData).show();
	
	
	//$("#adminEditForm").attr('src',url);
	$(this.window).dialog('open');
	
    };
    
    
     /** open window with editing 
     */
    this.confirmDelete = function(button){
	this.itemId = $(button).attr('for');
	this.editUrl = $(button).attr('href');
	var t = this;
	this.confirm.dialog('open');
	
    };
    
    /**
     * Delete item
     */
    this.deleteItem = function(cb){
	var id = this.itemId;
	var url = this.editUrl;
	var edited = $("#itemsList").find("div[name="+this.itemId+"]");
	$.ajax({
		url: url,
		type: "GET",
		dataType: "text html",
		success: function(html, textStatus, xhr){
		    if(typeof cb == "function"){
			cb();
		    }
		    edited.animate({'opacity' : 0.0},{
			duration:200, 
			complete: function(){
			    $(this).height( $(this).height() );
			    $(this).empty().slideToggle(300,function(){
				edited.remove();
				
			    })
			} 
		    });
		},
		complete: function(xhr, textStatus){
		    if(xhr.status != 200){
			alert("Chyba! Kód serveru: "+xhr.status+" ("+textStatus+"), objekt je vložen do konzole.");
			console.log(textStatus);
			console.log(xhr);
		    }
		}
	    });
    };
    
    /** update one item in list and on succes call callback
     * @param integer id
     * @param function callback
     */
    this.updateItem = function(id,callback){
	var t=this;
	$.ajax({
		beforeSend: function(xhr){
                    xhr.setRequestHeader('X-Requested-With', {toString: function(){ return ''; }});
                },
		url: $('[name='+id+']').attr('agility-reload'),
		type: "GET",
		dataType: "text html",
		success: function(html, textStatus, xhr){
		    var newElem = $(html);
		    $("[name="+id+"]").replaceWith(newElem);
		    $(newElem).effect('shake',{distance:40});
		    t.handleEdit(newElem);
		    if(typeof callback == "function"){
                        callback();
                    }
		    
		},
		complete: function(xhr, textStatus){
		    if(xhr.status != 200){
			alert("Chyba! Kód serveru: "+xhr.status+" ("+textStatus+"), objekt je vložen do konzole.");
			console.log(textStatus);
			console.log(xhr);
		    }
		}
	    });
    }
    
};
agility = new Agility();
