
/* ================================= */
/* :::::::::: 1. Loading ::::::::::: */
/* ================================= */

 
  if( /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ) {
    $(".yt-player").hide();
    $(".yt-controls").hide();
  } 

$(window).load(function() {

    $(".loader-item").delay(500).fadeOut();
    $("#page-loader").delay(700).fadeOut("slow");
 
    setTimeout(function() {
    $(".title").delay(1000).css({display: 'none'}).fadeIn(1000);
    $(".arrow").delay(1000).css({display: 'none'}).fadeIn(1000);
    });
});


/* ================================= */
/* :::::::: 2. Scroll Reveal ::::::: */
/* ================================= */

     (function($) {

        'use strict';

        window.sr= new scrollReveal({
          reset: true,
          move: '10px',
          mobile: false
        });

      })();

/* ================================= */
/* :::::::: 3. Smooth scroll ::::::: */
/* ================================= */
  smoothScroll.init();

/* ================================= */
/* ::::::::: 4. Backstretch :::::::: */
/* ================================= */

/* Active Single Image Background  */  
  
// $("body").backstretch("images/background2.jpg");

// ==== SLIDESHOW BACKGROUND ====
// Set URLs to background images inside the array
// Each image must be on its own line, inbetween speech marks (" ") and with a comma at the end of the line
// Add / remove images by changing the number of lines below
// Variable fade = transition speed for fade animation, in milliseconds
// Variable duration = time each slide is shown for, in milliseconds
          

 /* ↓ Remove comments if you want to use the slideshow  ↓  */ 

 $("#top").backstretch([
        "/coming_soon/images/background1.jpg", 
        "/coming_soon/images/background2.jpg", 
        "/coming_soon/images/background3.jpg", 
        "/coming_soon/images/background4.jpg"
    ],{duration: 3000, fade: 750});


/* ================================= */
/* :::::::::: 5. Countdown ::::::::: */
/* ================================= */


    // To change date, simply edit: var endDate = "Dec 01, 2015 20:39:00";
    $(function() {
      var endDate = "Dec 12, 2015 00:00:01";
      $('.countdown').countdown({
        date: endDate,
        render: function(data) {
          $(this.el).html('<div><div><span>' + (parseInt(this.leadingZeros(data.years, 2)*365) + parseInt(this.leadingZeros(data.days, 2))) + '</span><span>days</span></div><div class="dash-glob"><span>' + this.leadingZeros(data.hours, 2) + '</span><span>hours</span></div></div><div class="countdown-ms dash-glob"><div><span>' + this.leadingZeros(data.min, 2) + '</span><span>minutes</span></div><div class="dash-glob"><span>' + this.leadingZeros(data.sec, 2) + '</span><span>seconds</span></div></div>');
        }
      });
    });


/* ================================= */
/* :::::::: 6. Contact form :::::::: */
/* ================================= */

    var $contactFormMsg = $('.message-box-coming-soon');
    var $loader = $('<button class="disabled"><img class="loader" src="'+$('#img-loader').attr('src')+'" alt=""></button>');
    $("#ajax-contact-form").submit(function(evt) {
        var $form = $(this);
        var $submitBtn = $form.find('[type="submit"]');
        if ($form.valid()) {
            evt.preventDefault();
            $.ajax({
                type: $form.attr('method'),
                data: $form.serialize(),
                beforeSend: function() {
                    $submitBtn.replaceWith($loader);
                },
                success: function() {
                    $contactFormMsg
                        .removeClass('red')
                        .addClass('green')
                        .text("Your message was successfully sent. We will contact you shortly.")
                        .show()
                    ;
                },
                error: function() {
                    $contactFormMsg
                        .removeClass('green')
                        .addClass('red')
                        .text("There was a problem sending your message.")
                        .show()
                    ;
                },
                complete: function() {
                    $loader.replaceWith($submitBtn);
                }
            });
        }
    });

/* ================================= */
/* :: 7. Validation contact form  :: */
/* ================================= */
    var ajaxContactForms = {};
    $("#ajax-contact-form").validate({
        rules:{
            'form[email]':{
                required: true,
                email: true,
            },
            'form[subject]':{
                required: true,
            },
            'form[body]':{
                required: true,
            },
        },
        errorClass: 'contact-error',
        messages:{
            'form[email]':{
                email: "<p style='color: #A7170D; margin: 0'><i class='fa fa-exclamation-triangle'></i> Email is invalid</p>",
                required: "<p style='color: #A7170D; margin: 0'><i class='fa fa-exclamation-triangle'></i> Email is required</p>",
            },
            'form[subject]':{
                required: "<p style='color: #A7170D; margin: 0'><i class='fa fa-exclamation-triangle'></i> Subject is required</p>",
            },
            'form[body]':{
                required: "<p style='color: #A7170D; margin: 0'><i class='fa fa-exclamation-triangle'></i> Message is required</p>",
            },
        }
    });

/* ================================= */
/* :::::::: 8. Ajax mailchimp :::::: */
/* ================================= */

    // Example MailChimp url: http://xxx.xxx.list-manage.com/subscribe/post?u=xxx&id=xxx
    // $('#subscribe').ajaxChimp({
    //   language: 'eng',
    //   url: 'http://stevedogs.us9.list-manage.com/subscribe/post?u=df0aa2ea10f32337b29b342d4&id=41ddc569b4'
    // });

    // Mailchimp translation
    //
    // Defaults:
    //'submit': 'Submitting...',
    //  0: 'We have sent you a confirmation email',
    //  1: 'Please enter a value',
    //  2: 'An email address must contain a single @',
    //  3: 'The domain portion of the email address is invalid (the portion after the @: )',
    //  4: 'The username portion of the email address is invalid (the portion before the @: )',
    //  5: 'This email address looks fake or invalid. Please enter a real email address'

    // $.ajaxChimp.translations.eng = {
    //   'submit': 'Submitting...',
    //   0: '<i class="fa fa-check"></i> Tnx for subscribing, we will be in touch soon!',
    //   1: '<i class="fa fa-warning"></i> You must enter a valid e-mail address.',
    //   2: '<i class="fa fa-warning"></i> E-mail address is not valid.',
    //   3: '<i class="fa fa-warning"></i> E-mail address is not valid.',
    //   4: '<i class="fa fa-warning"></i> E-mail address is not valid.',
    //   5: '<i class="fa fa-warning"></i> E-mail address is not valid.'
    // }

var $subscribeMsg = $('.subscribe-message');
$('#subscribe').on('submit', function(evt) {
    evt.preventDefault();
    var $elem = $(this);
    $.ajax({
        type: 'POST',
        url: $elem.attr('action'),
        data: $elem.serialize(),
        beforeSend: function() {
            $subscribeMsg.text('Submitting...');
        },
        success: function(data) {
            if (data.isSuccessful) {
                window.location = Routing.generate('subscribe_success');
                $subscribeMsg.html('<span>'+data.message+'</span>');
            }
            else {
                $subscribeMsg.html('<i class="fa fa-warning" style="color: #da202d"></i> '+data.message);
            }
        },
        error: function() {
            $subscribeMsg.text('Internal Server Error');
        }
    });
});

/* ================================= */
/* :: 9. Player Youtube Controls ::  */
/* ================================= */
    
    $(function(){
      $(".yt-player").mb_YTPlayer();
    });

    // yt controls
    $('#yt-play').click(function(event){
      event.preventDefault();
      if ($(this).hasClass("fa-play") ) {
          $('.yt-player').playYTP();
      } else {
          $('.yt-player').pauseYTP(); 
      }
      $(this).toggleClass("fa-play fa-pause");
      return false;
    });
    $('#yt-volume').click(function(event){
      event.preventDefault();
      $('.yt-player').toggleVolume();
      $(this).toggleClass("fa-volume-off fa-volume-up");
      return false;
    });