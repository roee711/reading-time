(function($) {
 $("#clear-calculations").click(function (){

     $.ajax({
         url:readingtime_object.ajax_url,
         type:"POST",
         data: {
             action:'set_calculations',
         },   success: function(response){
             $("#msg-calculations").html(response)
                 .fadeIn( function()
                 {
                     setTimeout( function()
                     {
                         $("#msg-calculations").fadeOut("fast");
                     }, 2000);
                 });

         }, error: function(response){
             $("#msg-calculations").html(response);
         }
     });
 });
})(jQuery);