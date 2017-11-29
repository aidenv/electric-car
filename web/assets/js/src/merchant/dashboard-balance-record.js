(function($) {
    'use strict';

    var dateRangeEarningsURL = Routing.generate('core_daterange_earnings');
    var $dateRange = $('.sales-report-daterange');

    $dateRange.daterangepicker({
        "autoApply": true,
        "opens": "left"
    });

    var dateArray = function() {
        var datepicker = $dateRange.data('daterangepicker');
        var currentDate = datepicker.startDate; 
        var endDate = datepicker.endDate;
        var dates = [currentDate];
        while (endDate.diff(currentDate, 'days') > 0) {
            currentDate = currentDate.clone().add(1, 'day');
            dates.push(currentDate);
        }

        return dates;
    };

    var formatDates = function(dates) {
        var formattedDates = [];
        dates.forEach(function(date) {
            formattedDates.push(date.format('D MMM'));
        });

        return formattedDates;
    };

    var refreshChart = function() {
        // Get context with jQuery - using jQuery's .get() method.
        var ctx = $("#balance-record").get(0).getContext("2d");
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
                }

                var myLineChart = new Chart(ctx).Line(data, options);
            }
        });

        
    };

    $(document).ready(function(){
        refreshChart();
    });

})(jQuery);
