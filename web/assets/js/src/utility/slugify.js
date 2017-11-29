(function($) {

    "use strict";

    var convertToSlug = function (text){
        var slug = text.toString()
        .toLowerCase()
        .replace(/\s+/g, '-')           // Replace spaces with -
        .replace(/[^\w\-]+/g, '-')      // Replace all non-word chars
        .replace(/\-\-+/g, '-')         // Replace multiple - with single -
        .replace(/^-+/, '')             // Trim - from start of text
        .replace(/-+$/, '');            // Trim - from end of text

        return slug;
    };

    $.fn.slugify = function(target) {

        var $elem = this;

        $elem.on("keyup", function(evt) {
            var string = convertToSlug($elem.val());
            target.val(string);
        });
    };

})(jQuery);
