// Vlastní NETTE validace formu
$("form :input").focusout(function(){
    var elmName = $(this).attr("name");
    $("#inline-error-" + elmName).remove();
    var b = document.getElementById($(this).closest("form").attr("id"));
    if (b) {
      var c = nette.getFormValidators(b);
      if (c[elmName]) {
        var q = c[elmName](b);
        if (q) {
          $(this).parent().append("<p id='inline-error-" + elmName + "' class='inline-error'>" + q + "</p>");
        }
      }
    }
});