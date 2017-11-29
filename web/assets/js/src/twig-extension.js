(function($) {
    var assetPackages = {
        product: '/assets/images/uploads/products',
        chat: '/assets/images/uploads/chats',
        cms: '/assets/images/uploads/cms',
        user: '/assets/images/uploads/users'
    };

    $twigExtension = $('#twig-extension');
    var routes = $twigExtension.data('routes');

    Twig.extendFunction("asset", function(value, packageName) {
        var prefix = '/assets';
        if (assetPackages.hasOwnProperty(packageName)) {
            prefix = assetPackages[packageName];
        }

        return prefix+'/'+value;
    });

    Twig.extendFunction("path", function(value, data) {
        var route = '';
        if (routes.hasOwnProperty(value)) {
            route = routes[value];
            if (data) {
                route = decodeURI(route).interpolate(data);
            }
        }

        return route;
    });

    Twig.extendFilter("json_escape", function(value) {
        return value.replace(/&/g, "&amp;")
                    .replace(/>/g, "&gt;")
                    .replace(/</g, "&lt;")
                    .replace(/"/g, "&quot;");
    });
})(jQuery);
