define([
    "jquery"
], function($){
    "use strict";

    return function(config, element) {
        var top = config.top;
        var speed = config.speed;
        var timeout = config.timeout;

        function setCookie(name,value,days)
        {
          if (days) {
                var date = new Date();
                date.setTime(date.getTime()+(days*24*60*60*1000));
                var expires = "; expires="+date.toGMTString();
              }
              else var expires = "";
              document.cookie = name+"="+value+expires+"; path=/";
        }

        function getCookie(name)
        {
              var nameEQ = name + "=";
              var ca = document.cookie.split(';');
              for(var i=0;i < ca.length;i++) {
                var c = ca[i];
                while (c.charAt(0)==' ') c = c.substring(1,c.length);
                if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
              }
              return null;
        }

        if(getCookie("shownewsletter") != 1){
            if ($.fn.bPopup) {
                 var pPopup = $(element).bPopup({
                    position: ['auto', top],
                    speed: speed,
                    transition: 'slideDown',
                    onClose: function() { setCookie("shownewsletter",'1', timeout); }
                });
                $('.newletter_popup_close').on('click', function(){
                    pPopup.close();
                });
            } else {
                console.warn("bPopup plugin not found");
            }
        }

        $( "#newsletter_pop_up form" ).submit(function( event ) {
                setCookie("shownewsletter",'1',1);
        });

        $('#newsletter_popup_dont_show_again').on('change', function(){
            if(getCookie("shownewsletter") != 1){
                setCookie("shownewsletter",'1',1)
            }else{
                setCookie("shownewsletter",'0',1)
            }
        });
    }
});
