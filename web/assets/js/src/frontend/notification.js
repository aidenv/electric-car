// (function($) {

//      var $notifications = $('[data-notifications]');

//      $notifications.each(function() {
//          var $elem = $(this);
//          var settings = $elem.data('notifications');
//          var socket = io(settings.protocol+'://'+settings.host+':'+settings.port, {query: "s="+settings.s});

//          socket.on('notification', function(data) {
//              $notification = $('<li>'+data+'</li>');
//              $notifications.prepend($notification);
//          });
//      });

// })(jQuery);
