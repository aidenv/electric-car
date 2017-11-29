'use strict';

function Timer(){
    return {
        element : null,
        interval : null,
        expiration : null,
        onStartCallback : null,
        onStopCallback : null,
        instance : null,
        hoursEnabled : false,
        isTableView : false,
        isDivView : false,
        init : function($element){
            this.element = $element;
            this.expiration = $element.attr("data-expiration");
            this.instance = this;

            if($element.hasClass("hours-enabled")){
                this.hoursEnabled = true;
            }

            if($element.hasClass("table-view")){
                this.isTableView = true;
            }

            if($element.hasClass("div-view")){
                this.isDivView = true;
            }

            return this;
        },
        start : function($onStartCallback, $onStopCallback){
            this.interval = setInterval((function(self) {
                return function() {
                    self.tick(self);
                }
            })(this), 1000);

            this.onStartCallback = $onStartCallback;
            this.onStopCallback = $onStopCallback;

            if(this.onStartCallback != null){
                this.onStartCallback();
            }
        },
        stop : function(){
            clearInterval(this.interval);
        },
        tick : function($obj){
            var $date = new Date();
            var $timeNow = Math.floor($date.getTime() / 1000);
            var $expiration = $obj.expiration;
            var $unixTimeDiff = $expiration - $timeNow;
            var $minutes = Math.floor($unixTimeDiff / 60);
            var $seconds = $unixTimeDiff - $minutes * 60;
            var $timeDiff;
            var $hours = 0;
            var $content = "";

            if($seconds < 10){
                $seconds = "0" + $seconds;
            }

            if($obj.hoursEnabled){
                $hours = Math.floor($minutes / 60);
                $minutes = $minutes - ($hours * 60);
                if($hours < 10){
                    $hours = "0" + $hours;
                }
            }

            if(parseInt($minutes) < 10){
                $minutes = "0" + $minutes;
            }

            if($obj.hoursEnabled){
                $timeDiff = $hours + ":" + $minutes + ":" + $seconds;
            }
            else{
                $timeDiff = $minutes + ":" + $seconds;
            }

            if($expiration > $timeNow){
                if($obj.isDivView){
                    if($obj.hoursEnabled){ 

                      $content = "<h1 class='no-margin'>" +
                                    "<div class='time'>" +
                                        "<div class='value' id='hours-left'>" + $hours + "</div>" +
                                        "<span class='time-label'>Hours</span>" +
                                    "</div>" +
                                    "<div class='time'>" +
                                        "<div class='value' id='minutes-left'>" + $minutes + "</div>" +
                                        "<span class='time-label'>Minutes</span>" +
                                    "</div>" +
                                    "<div class='time'>" +
                                        "<div class='value' id='seconds-left'>" + $seconds + "</div>" + 
                                        "<span class='time-label'>Seconds</span>" +
                                    "</div>" +
                                "</h1>";
                    }
                    else{

                      $content = "<h1 class='no-margin'>" +
                                    "<div class='time'>" +
                                        "<div class='value' id='minutes-left'>" + $minutes + "</div>" +
                                        "<span class='time-label'>Minutes</span>" +
                                    "</div>" +
                                    "<div class='time'>" +
                                        "<div class='value' id='seconds-left'>" + $seconds + "</div>" + 
                                        "<span class='time-label'>Seconds</span>" +
                                    "</div>" +
                                "</h1>";
                    }

                    $obj.element.html($content);
                }
                else if($obj.isTableView){
                    if($obj.hoursEnabled){ 
                        $content =  "<td>" + $hours + "</td>" + 
                                    "<td>:</td>" +
                                    "<td>" + $minutes + "</td>" + 
                                    "<td>:</td>" +
                                    "<td>" + $seconds + "</td>";
                    }
                    else{
                        $content =  "<td>" + $minutes + "</td>" + 
                                    "<td>:</td>" +
                                    "<td>" + $seconds + "</td>";
                    }

                    $obj.element.html($content);
                }
                else{
                    $obj.element.text($timeDiff);
                }
            }
            else{

                if($obj.hoursEnabled){
                    if($obj.isDivView){

                      $obj.element.html("<h1 class='no-margin'>" +
                                    "<div class='time'>" +
                                        "<div class='value' id='hours-left'>00</div>" +
                                        "<span class='time-label'>Hours</span>" +
                                    "</div>" +
                                    "<div class='time'>" +
                                        "<div class='value' id='minutes-left'>00</div>" +
                                        "<span class='time-label'>Minutes</span>" +
                                    "</div>" +
                                    "<div class='time'>" +
                                        "<div class='value' id='seconds-left'>00</div>" + 
                                        "<span class='time-label'>Seconds</span>" +
                                    "</div>" +
                                "</h1>");
                    }
                    else if($obj.isTableView){
                        $obj.element.html(
                            "<td>00</td>" + 
                            "<td>:</td>" +
                            "<td>00</td>" + 
                            "<td>:</td>" +
                            "<td>00</td>"
                        );
                    }
                    else{
                        $obj.element.text("00:00:00");
                    }
                }
                else{
                    if($obj.isDivView){

                      $obj.element.html("<h1 class='no-margin'>" +
                                    "<div class='time'>" +
                                        "<div class='value' id='minutes-left'>00</div>" +
                                        "<span class='time-label'>Minutes</span>" +
                                    "</div>" +
                                    "<div class='time'>" +
                                        "<div class='value' id='seconds-left'>00</div>" + 
                                        "<span class='time-label'>Seconds</span>" +
                                    "</div>" +
                                "</h1>");
                    }
                    else if($obj.isTableView){
                        $obj.element.html(
                            "<td>00</td>" + 
                            "<td>:</td>" +
                            "<td>00</td>"
                        );
                    }
                    else{
                        $obj.element.text("00:00");
                    }
                }

                $obj.stop();

                if($obj.onStartCallback != null){
                    $obj.onStopCallback();
                }
            }
        }
    }
};
