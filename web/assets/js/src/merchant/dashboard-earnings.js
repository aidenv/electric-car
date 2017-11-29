(function($) {
    var $daterangepicker = $('.sales-report-daterange');

    $daterangepicker.daterangepicker({
        "autoApply": true,
        "opens": "left"
    });

    $daterangepicker.on('apply.daterangepicker', function(evt, picker) {
        var $form = $('<form action="" method="POST"></form>');
        $form.append('<input type="hidden" name="startdate" value="'+picker.startDate.format('MM/DD/YYYY')+'"/>');
        $form.append('<input type="hidden" name="enddate" value="'+picker.endDate.format('MM/DD/YYYY')+'"/>');
        $('body').append($form);
        $form.submit();
    });

    $(document).ready(function(){
        //For pie graph
        // Get context with jQuery - using jQuery's .get() method.
        var ctxPie = $("#pie-earnings").get(0).getContext("2d");
        // This will get the first returned node in the jQuery collection.
        var myPieChart = new Chart(ctxPie, pieOptions);
        var $earningTypes = $('[data-earning-type-of-group]');
        var pie = [];
        $earningTypes.each(function() {
            var $earningType = $(this);
            var $earningParts = $earningType.find('td');

            pie.push({
                value: $earningParts.eq(2).text().replace('%', ''),
                color: $earningParts.eq(0).css('background-color'),
                label: $earningParts.eq(1).text()
            });
        });

        var pieOptions = {
            scaleFontFamily: "'Panton', Helvetica Neue', 'Helvetica', 'Arial', sans-serif",
            responsive: true
        }

        var myPieChart = new Chart(ctxPie).Pie(pie, pieOptions);
    });
})(jQuery);
