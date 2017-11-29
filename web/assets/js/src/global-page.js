(function ($) {

    $('#focus-multiple').click(function(){
        $('#vmap').vectorMap('set', 'focus', {regions: ['GB', 'AU'], animate: true});
    });

    var regionHover = function(code, hover) {

        if (typeof hover == 'undefined') {
            hover = true;
        }

        if (typeof countries[code] != 'undefined') {
            if (hover) {
                $("[data-code='"+code+"']").css('fill', 'rgb(145, 94, 156)');
            }
            else {
                $("[data-code='"+code+"']").css('fill', 'rgb(187, 172, 191)');
            }
        }
    }

    $('#vmap').vectorMap({
        map: 'world_mill_en',
        backgroundColor: 'rgba(239, 239, 239, 0.5)',
        panOnDrag: true,
        regionStyle: {
            initial: {
                fill: "#DED9CF"
            },
            hover: {
                "fill-opacity": "1",
                cursor: 'pointer'
            }
        },
        onRegionClick: function(e, code) {
            if (typeof countries[code] != 'undefined') {
                window.location = countries[code].weburl;
            }
        },
        onRegionOver: function(e, code) {
            regionHover(code);
        },
        onRegionOut: function(e, code) {
            regionHover(code, false);
        },
        onRegionTipShow: function (e, el, code) {
            if (typeof countries[code] != 'undefined') {
                el.html(countries[code].name);
            }
            else {
                e.preventDefault();
            }
        },
        markers: domain.map(function(h){ return {name: h.name, latLng: h.coords} }),
        labels: {
            markers: {
                render: function(index){
                    return domain[index].name;
                },
                offsets: function(index){
                    var offset = domain[index]['offsets'] || [0, 0];

                    return [offset[0] - 7, offset[1] + 3];
                }
            }
        },
        onMarkerClick: function(events, index) {
            window.location = domain[index].weburl;
        },
        onMarkerOver: function(events, index) {
            regionHover(domain[index]['code']);
        },
        onMarkerOut: function(events, index) {
            console.log($(this));
            regionHover(domain[index]['code'], false);
        },
        series: {
            markers: [{
                attribute: 'image',
                scale: {
                  'available': pinImageSrc
                },
                values: domain.reduce(function(p, c, i){ p[i] = c.status; return p }, {})
            }],
            regions: [{
                values: regions,
                attribute: 'fill',
            }]
        }
    });

    $(window).on("ready load", function(){
        $('#focus-multiple').trigger("click");
    })
}(jQuery));
