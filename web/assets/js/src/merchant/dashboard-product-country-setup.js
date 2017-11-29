(function ($) {
    $(".logistics").on("change", function() {
        var self = $(this),
            handlingFeeElem = self.closest('.panel-body').find('.handling-fee');

        if (self.dropdown('get value') == YILINKER_EXPRESS) {
            handlingFeeElem.hide();
        }
        else {
            handlingFeeElem.show();
        }
    });

    $(".user-warehouse").on("change", function() {
        var self = $(this),
            selectedOption = self.find('option:selected'),
            selectedCountryCode = selectedOption.data('country-code'),
            codCheckboxElemContainer = self.closest('.panel-body').find('.cod-checkbox'),
            codCheckboxElem = codCheckboxElemContainer.find('input[type="checkbox"]');

        if (selectedCountryCode.toLowerCase() == countryCode.toLowerCase()) {
            codCheckboxElemContainer.show();
            codCheckboxElem.prop('checked', true);
        }
        else {
            codCheckboxElemContainer.hide();
            codCheckboxElem.prop('checked', false);
        }
    });

    $(".show-prev-remarks").on("click", function() {
        $(".prev-remarks").slideToggle({direction: "up" }, 400);
        $(this).toggleClass("show-txt-remarks");
    });
})(jQuery);
