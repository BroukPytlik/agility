$(function () {
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

// continue only if browser dont know media queries
if(typeof window.matchMedia != "undefined") return;

var fullResWidth = '830';
var semifullResWidth = '700';

function compute(){
    var width = $(window).width();
    // remove set styles
    $("#outer-content").removeAttr('style');
    $("#sidebar").removeAttr('style');
    $("#linkAddNew").removeAttr('style');
    
    
    if(width < fullResWidth){
	$("#outer-content").css({width: '100%'});
	$("#sidebar").css({
	    margin: '0.5em 0.2em',
	    width: '90%',
	    'float': 'none'
	});
    }
    if(width < fullResWidth){
	$("#linkAddNew").css({
	    position: 'relative',
	    top:'auto',
	    left: 'auto'
	});
	
    }
}


$(window).resize(function(){
    compute();
})

});