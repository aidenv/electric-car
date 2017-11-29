(function($) {
    'use strict';

    var dateRangeEarningsURL = Routing.generate('core_daterange_earnings');
    var $dateRange = $('.sales-report-daterange'),
        $copyReferralCodeLinkTrigger = $(".copy-referral-code-link"),
        client = new ZeroClipboard($copyReferralCodeLinkTrigger, {
            moviePath: "/assets/js/bower/ZeroClipboard.swf"
        });

    $dateRange.daterangepicker({
        "autoApply": true,
        "opens": "left"
    });

    var refreshChart1 = function() {
        // Get context with jQuery - using jQuery's .get() method.
        var ctx = $("#sales").get(0).getContext("2d");
        // This will get the first returned node in the jQuery collection.
        var myNewChart = new Chart(ctx);
        var graphData = $('.sales-chart-container').data('transaction-graph');

        var dates = dateArray();
        var labels = formatDates(dates);

        var soldDataPoints = [];
        var datepicker = $dateRange.data('daterangepicker');

        var $earningFilterForm = $('[name="earning_filter"]');
        var formdata = {};
        $earningFilterForm.serializeArray().forEach(function(param) {
            formdata[param.name] = param.value;
        });

        $.ajax({
            url: dateRangeEarningsURL,
            data: formdata,
            success: function(earnings) {
                var earningObj = {};
                earnings.forEach(function(earning) {
                    earningObj[earning.dayEarned] = earning.amountEarned;
                });

                var yData = [];
                dates.forEach(function(date) {
                    var formattedDate = date.format('MM/DD/YYYY');
                    if (earningObj.hasOwnProperty(formattedDate)) {
                        yData.push(earningObj[formattedDate]);
                    }
                    else {
                        yData.push(0);
                    }
                });

                var data = {
                    labels: labels,
                    datasets: [
                        {
                            label: "Sold",
                            fillColor: "rgba(84,182,167,0)",
                            strokeColor: "rgba(84,182,167,1)",
                            pointColor: "rgba(84,182,167,1)",
                            pointStrokeColor: "#fff",
                            pointHighlightFill: "#fff",
                            pointHighlightStroke: "rgba(84,182,167,1)",
                            data: yData
                        }
                    ]
                };

                var options = {
                    scaleFontFamily: "'Panton', Helvetica Neue', 'Helvetica', 'Arial', sans-serif",
                    responsive: true,
                    bezierCurve : false,
                    datasetStrokeWidth : 3
                };

                var myLineChart = new Chart(ctx).Line(data, options);
            }
        });

    };



    function getSelectedDate ()
    {
        var selectedDateRange = $('.filter-day-range').find('.active').attr('data-value');
        var date = new Date();
        var currentDate = new Date();
        var startingDate = new Date(date.setMonth(date.getMonth() - 1));

        if (selectedDateRange === 'weekly') {
            date = new Date();
            startingDate = new Date(date.setDate(date.getDate() - 7));
        }

        $('#dateFrom').val(moment(startingDate).format('MM/D/YYYY'));
        $('#dateTo').val(moment(currentDate).format('MM/D/YYYY'));

        return getArrayOfDates (startingDate, currentDate);
    }

    Date.prototype.addDays = function(days) {
        var dat = new Date(this.valueOf());
        dat.setDate(dat.getDate() + days);

        return dat;
    };

    function getArrayOfDates (startDate, stopDate) {
        var dateArray = [];
        var currentDate = startDate;

        while (currentDate <= stopDate) {
            var date = new Date (currentDate);
            //var dateFormated = moment(date).format('D MMM');
            dateArray.push( date );
            currentDate = currentDate.addDays(1);
        }

        return dateArray;
    }

    function formatArrayOfDates (arrayOfDate)
    {
        var dateArray = [];

        arrayOfDate.forEach(function(date) {
            var dateFormatted = moment(date).format('D MMM');
            dateArray.push( dateFormatted );
        });

        return dateArray;
    }

    function refreshChart ()
    {
        var dates = getSelectedDate ();
        var labels = formatArrayOfDates (dates);

        // Get context with jQuery - using jQuery's .get() method.
        var ctx = $("#sales").get(0).getContext("2d");
        // This will get the first returned node in the jQuery collection.
        var myNewChart = new Chart(ctx);
        var graphData = $('.sales-chart-container').data('transaction-graph');

        var soldDataPoints = [];
        var formData = {
            earning_filter: {
                'daterange': $('#dateFrom').val() + ' - ' + $('#dateTo').val()
            }
        };

        $.ajax({
            url: Routing.generate('core_daterange_earnings'),
            data: formData,
            success: function(earnings) {
                var earningObj = {};

                earnings.forEach(function(earning) {
                    earningObj[earning.dayEarned] = earning.amountEarned;
                });

                var yData = [];
                dates.forEach(function(date) {
                    var formattedDate = moment(date).format('MM/DD/YYYY');
                    if (earningObj.hasOwnProperty(formattedDate)) {
                        yData.push(earningObj[formattedDate]);
                    }
                    else {
                        yData.push(0);
                    }
                });

                var data = {
                    labels: labels,
                    datasets: [
                        {
                            label: "Sold",
                            fillColor: "rgba(84,182,167,0)",
                            strokeColor: "rgba(84,182,167,1)",
                            pointColor: "rgba(84,182,167,1)",
                            pointStrokeColor: "#fff",
                            pointHighlightFill: "#fff",
                            pointHighlightStroke: "rgba(84,182,167,1)",
                            data: yData
                        }
                    ]
                };

                var options = {
                    scaleFontFamily: "'Panton', Helvetica Neue', 'Helvetica', 'Arial', sans-serif",
                    responsive: true,
                    bezierCurve : false,
                    datasetStrokeWidth : 3
                };

                var myLineChart = new Chart(ctx).Line(data, options);
            }
        });
    }

    $(document).ready(function(){
        refreshChart();

        client.on( "load", function(client) {
          client.on( "complete", function(client, args) {
            // `this` is the element that was clicked
            this.style.display = "none";
          });
        });


        $copyReferralCodeLinkTrigger.click(function(){
            $(".copied-referral-success").show();
        });
    });

    $(document).on('click', '.filter-graph', function () {
        var $this = $(this);
        var isSelected = $this.hasClass('active');

        if (isSelected === false) {
            $('.filter-graph').attr('class', 'filter-graph');
            $this.addClass('active');
            refreshChart();
        }

    });

    $(window).on("load", function(){
        $(".sales-datepicker-container").show();
    });

    $('.sales-daterange').daterangepicker({
        "autoApply": true,
        "opens": "left"
    }).on("hide.daterangepicker", function(event, datePicker){
        var dateFrom = datePicker.startDate.format('YYYY-MM-DD');
        var dateTo = datePicker.endDate.format('YYYY-MM-DD');
        updateDateFilters(dateFrom, dateTo);
    });

    $('.date-filter.dropdown').on('change', function(){
        var $this = $(this);
        var value = $this.dropdown('get value');
        if(value > 0){
            var $selectedOption = $this.find('select option[value="'+value+'"]');

            var dateFrom = $selectedOption.attr('data-dateFrom');
            var dateTo = $selectedOption.attr('data-dateTo');
            updateDateFilters(dateFrom, dateTo);
        }
    });

    function updateDateFilters(dateFrom, dateTo)
    {
        /**
         * Reload page with query parameters
         */
        var reloadUrl = window.location.origin + window.location.pathname + "?dateFrom="+dateFrom+"&dateTo="+dateTo;
        window.location.href = reloadUrl;
    }

    function createCanvas($path){
        var $deferred = $.Deferred();
        var $img = new Image();
        var $canvas = new fabric.Canvas('qrCode');
        var $context = $canvas.getContext('2d');

        $img.src = $path;
        $img.onload = function() {
            $canvas.setHeight(this.naturalHeight);
            $canvas.setWidth(this.naturalWidth);

            var imgInstance = new fabric.Image($img, {
                left: 0,
                top: 0,
                width: this.naturalWidth,
                height: this.naturalHeight
            });

            $canvas.add(imgInstance);

            $deferred.resolve($canvas);
        };

        return $deferred.promise();
    }

    function dataURItoBlob(dataURI) {
        // convert base64/URLEncoded data component to raw binary data held in a string
        var byteString;
        if (dataURI.split(',')[0].indexOf('base64') >= 0)
            byteString = atob(dataURI.split(',')[1]);
        else
            byteString = unescape(dataURI.split(',')[1]);

        // separate out the mime component
        var mimeString = dataURI.split(',')[0].split(':')[1].split(';')[0];

        // write the bytes of the string to a typed array
        var ia = new Uint8Array(byteString.length);
        for (var i = 0; i < byteString.length; i++) {
            ia[i] = byteString.charCodeAt(i);
        }

        return new Blob([ia], {type:mimeString});
    }

    $(document).ready(function(){
        //For pie graph
        // Get context with jQuery - using jQuery's .get() method.
        var pieContainer = $("#pie-store-space").get(0);
        if (pieContainer) {
            var ctxPie = pieContainer.getContext("2d");

            var pieData = [
                {
                    value : parseFloat($("#pie-store-space").attr('data-store-space')) - parseFloat($("#pie-store-space").attr('data-product-count')),
                    color : "#D02327",
                    label : 'Free Space',
                    labelColor : 'white',
                    labelFontSize : '16'
                },
                {
                    value : $("#pie-store-space").attr('data-product-count'),
                    color : "#F7464A",
                    label : 'Selected',
                    labelColor : 'white',
                    labelFontSize : '16'
                }
            ];

            var myPieChart = new Chart(ctxPie).Pie(pieData);
        }
    });

    $(".qr-code-modal-trigger").on("click", function(){
        var $this = $(this);
        var $qrCodeModal = $(".qr-code-modal");

        $(".img-qr-code-holder>img").attr("src", $this.find("img").data("medium"));
        $(".download-qr.thumb").find("a").attr("data-href", $this.find("img").data("thumb"));
        $(".download-qr.small").find("a").attr("data-href", $this.find("img").data("small"));
        $(".download-qr.medium").find("a").attr("data-href", $this.find("img").data("medium"));
        $(".download-qr.large").find("a").attr("data-href", $this.find("img").data("large"));
        $(".download-qr.svg").find("a").attr("data-href", $this.find("img").data("thumb"));

        $qrCodeModal.modal("show");
    });

    $(".total-comment-modal-trigger").on("click", function(){
        $(".total-comment-modal").modal("show");
    });

    $(".total-followers-modal-trigger").on("click", function(){
        $(".total-followers-modal").modal("show");
    });

    $(".total-buyer-network-modal-trigger").on("click", function(){
        $(".copied-referral-success").hide();
        $(".total-buyer-network-modal").modal("show");
    });

    $(".total-affiliate-network-modal-trigger").on("click", function(){
        $(".copied-referral-success").hide();
        $(".total-affiliate-network-modal").modal("show");
    });

    $(".affiliate-level-modal-trigger").on("click", function(){
        $(".affiliate-level-modal").modal("show");
    });

    $(".download-qr").on("click", function(){
        var $this = $(this);
        var $name = $this.find("a").attr("data-download");
        var $path = $this.find("a").attr("data-href");
        var $promise = createCanvas($path);

        $promise.done(function($canvas){

            if($this.hasClass("svg")){
                var $svg = $canvas.toSVG();
                saveAs(new Blob([$svg], {type:"application/svg+xml"}), $name + ".svg");
            }else{
                saveAs(dataURItoBlob($canvas.toDataURL()), $name + ".png");
            }
        });
    });

})(jQuery);
