(function($) {

   $('input.sales-report-daterange').daterangepicker({
        "autoApply": true,
        "opens": "left",
        "startDate" : moment($("#dateFrom").val()).format('MM/DD/YYYY'),
        "endDate" : moment($("#dateTo").val()).format('MM/DD/YYYY'),
    });

   $('input.sales-report-daterange').on('apply.daterangepicker', function(ev, picker) {
        $("#dateFrom").val(picker.startDate.format('YYYY-MM-DD'));
        $("#dateTo").val(picker.endDate.format('YYYY-MM-DD'));
   });

    $(document).ready(function(){
        var ctx = $("#sales-report").get(0).getContext("2d"),
            myNewChart = new Chart(ctx, options),
            labels = [],
            salesDataSet = [],
            invalidDataSet = [],
            datasets = [{
                label: "Hide",
                fillColor: "rgba(255,255,255,0)",
                strokeColor: "rgba(255,255,255,1)",
                pointColor: "rgba(255,255,255,1)",
                pointStrokeColor: "#fff",
                pointHighlightFill: "#fff",
                pointHighlightStroke: "rgba(255,255,255,1)",
                data: []
            }];

        $.each(JSON.parse(salesDataCount), function(key, value) {
            labels.push(moment(key).format('DD MMM'));
            salesDataSet.push(value);
        });

        if (filterSales == 1) {
            datasets.push({
                label: "Sales",
                fillColor: "rgba(84,182,167,0)",
                strokeColor: "rgba(84,182,167,1)",
                pointColor: "rgba(84,182,167,1)",
                pointStrokeColor: "#fff",
                pointHighlightFill: "#fff",
                pointHighlightStroke: "rgba(84,182,167,1)",
                data: salesDataSet
            });
        }

        if (filterInvalid == 1) {
            $.each(JSON.parse(invalidDataCount), function(key, value) {
                invalidDataSet.push(value);
            });

            datasets.push({
                label: "Invalid",
                fillColor: "rgba(228,73,84,0)",
                strokeColor: "rgba(228,73,84,1)",
                pointColor: "rgba(228,73,84,1)",
                pointStrokeColor: "#fff",
                pointHighlightFill: "#fff",
                pointHighlightStroke: "rgba(228,73,84,1)",
                data: invalidDataSet
            });
        }

        var data = {
            labels: labels,
            datasets: datasets
        };

        var options = {
            scaleFontFamily: "'Panton', Helvetica Neue', 'Helvetica', 'Arial', sans-serif",
            responsive: true,
            bezierCurve : false,
            datasetStrokeWidth : 3
        }

        var myLineChart = new Chart(ctx).Line(data, options);
    });

})(jQuery);
