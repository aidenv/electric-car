(function($) {
    'use strict';

    var options = {
        changeDiscountedPrice: function() {
            var price = this.$price.val();
            var discount = this.$discount.val();
            var discountedPrice = price - (price * (discount / 100));

            this.$discountedPrice.val(discountedPrice.toFixed(2));
        },
        changeDiscount: function() {
            var price = this.$price.val();
            var discountedPrice = this.$discountedPrice.val();
            var discount = (discountedPrice / price) * 100;

            if (price == discountedPrice || isNaN(discount)) {
                this.$discount.val(0);
            }
            else {
                this.$discount.val(discount.toFixed(2));
            }

        }
    };

    $.fn.productUnit = function(settings) {
        settings = $.extend({}, options, settings);
        var $elem = this;

        settings.$price = $elem.find('[data-product-unit-price]');
        settings.$discount = $elem.find('[data-product-unit-discount]');
        settings.$discountedPrice = $elem.find('[data-product-unit-discountedPrice]');

        settings.$price.on('change', function() {
            settings.changeDiscountedPrice();
        });

        settings.$discount.on('change', function() {
            settings.changeDiscountedPrice();
        });

        settings.$discountedPrice.on('change', function() {
            settings.changeDiscount();
        });

        settings.changeDiscount();
    };

    var $productUnit = $('[data-product-unit]');
    $productUnit.each(function() {
        var $elem = $(this);
        $elem.productUnit({});
    });

})(jQuery);