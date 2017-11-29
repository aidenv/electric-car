(function($) {

    var dateFrom;
    var dateTo;
    var paymentMethod;

    var defaultDateFrom = getUrlParameter('dateFrom');
    var defaultDateTo = getUrlParameter('dateTo');
    var defaultPaymentMethod = getUrlParameter('paymentMethod');
    var tab = getUrlParameter('tab') 
    defaultPaymentMethod = typeof defaultPaymentMethod == 'undefined' ? "0" : defaultPaymentMethod;

    $(".payment-filter option[value='"+defaultPaymentMethod+"']").prop('selected', true);
    var isDatefilterFound = false;
    $(".date-filter option").each(function(index,value){
        var $this = $(this);
        if($this.data('from') == defaultDateFrom && $this.data('to') == defaultDateTo){
            $this.prop('selected', true);
            isDatefilterFound = true;
            return false;
        }
    });

    if(!isDatefilterFound && !(typeof defaultDateFrom === "undefined" && typeof defaultDateTo === "undefined") ) {
        $(".date-filter select").append('<option value="custom" data-from="'+defaultDateFrom+'" data-to="'+defaultDateTo+'">Custom</option>');
        $(".date-filter option[value='custom']").prop('selected', true);
    }
    
    $(document).ready(function(){

        dateFrom = $(".date-filter option:selected").data('from');
        dateTo = $(".date-filter option:selected").data('to');;
        paymentMethod = $(".payment-filter option:selected").val();

        $(".date-filter").dropdown({
            onChange: function(value, text, $selectedItem){                
                var $selectedOption = $(this).find('select option[value="' + value +'"]');
                dateFrom = $selectedOption.data('from');
                dateTo = $selectedOption.data('to');
                filterUpdate();
            }
        });

        $(".payment-filter").dropdown({
            onChange: function(value, text, $selectedItem){
                paymentMethod = value;
                filterUpdate();
            }
        });


        $dateRange = $('.transaction-daterange');
        $dateRange.daterangepicker({
            "autoApply": true,
            "opens": "left",
            locale: {
                format: 'YYYY-MM-DD'
            },
            startDate: $dateRange.data('from'),
            endDate: $dateRange.data('to'),
            parentEl: ".form-daterange-container"
        }, 
        function(start, end, label){            
            dateFrom = start.format('YYYY-MM-DD');
            dateTo = end.format('YYYY-MM-DD')
            filterUpdate();
        });

        $dateRange.val(dateFrom + ' - ' + dateTo);

    });

    function filterUpdate()
    {        
        var tabQuery = "tab=" + getUrlParameter('tab');
        var dateQuery = "dateFrom="+dateFrom+"&dateTo="+dateTo;
        var paymentQuery = "paymentMethod=" + (parseInt(paymentMethod, 10) !== 0 ? paymentMethod : "");
        var filterString = dateQuery + "&" + paymentQuery + "&" + tabQuery;

        /**
         * Reload page with ne 
         */
        var reloadUrl = window.location.origin + window.location.pathname + "?" + filterString;
        window.location.href = reloadUrl;
    }
   
})(jQuery);
