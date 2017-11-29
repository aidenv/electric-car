

    /**
     * Initialize the google map 
     */
    function initMap(mapCanvas, latitude, longitude, heightpx, widthpx) 
    {
        map = new google.maps.Map(mapCanvas, {
            center: {
                lat: parseFloat(latitude), 
                lng: parseFloat(longitude)
            },
            scrollwheel: true,
            zoom: 11
        });

        mapCanvas.style.height = heightpx + 'px';
        mapCanvas.style.width = widthpx + 'px';

        return map;
    }

    /**
     * Create marker on the map
     */
    function createMarker(map, latlng)
    {
        var marker;
        map.setCenter(latlng);
        marker = new google.maps.Marker({
            map: map, 
            position: latlng,
            title:"I'm here!",
            draggable: true
        });

        return marker;
    }

    /**
     * Geo code the the address string and set the location marker
     */
    function geocodeAddress(map, address, type) 
    {
        geocoder = new google.maps.Geocoder();
        geocoder.geocode( { 'address': address }, function(results, status) {
            if (status == google.maps.GeocoderStatus.OK) {
                google.maps.event.addDomListener(window, 'load', initialize(results[0].geometry.location, type));
            }
        });
    }  
    
