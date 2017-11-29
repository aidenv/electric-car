function handleClientResize($file, $img){

    var browserWindow = window.URL || window.webkitURL;
    var filename =  $file.name;
    var extension = filename.substring(filename.lastIndexOf('.') + 1).toLowerCase();
    var objectUrl = browserWindow.createObjectURL($file);

    return createImage(objectUrl);
}

function createImage (src) {
    var deferred = $.Deferred();
    var img = new Image();
    
    img.onload = function() {
        $(img).attr("naturalWidth", this.naturalWidth);
        $(img).attr("naturalHeight", this.naturalHeight);

        deferred.resolve(img);
    };

    img.src = src;
    
    return deferred.promise();
};

function resize (image) {

    var $height = parseInt($(image).attr("naturalheight"));
    var $width = parseInt($(image).attr("naturalwidth"));

    mainCanvas = document.createElement("canvas");

    if($width > $height){
        var $multiplier = 1 - (($width/$height)%1);
        mainCanvas.width = 1280;
        mainCanvas.height = 1280 * $multiplier;
    }
    else{
        var $multiplier = 1 - (($height/$width)%1);
        mainCanvas.height = 1280;
        mainCanvas.width = 1280 * $multiplier;
    }

    var ctx = mainCanvas.getContext("2d");

    ctx.drawImage(image, 0, 0, mainCanvas.width, mainCanvas.height);

    return mainCanvas.toDataURL("image/jpeg");
};

function dataURItoBlob(dataURI) {
    // convert base64/URLEncoded data component to raw binary data held in a string
    var byteString;
    if (dataURI.split(',')[0].indexOf('base64') >= 0)
        byteString = atob(dataURI.split(',')[1]);
    else
        byteString = unescape(dataURI.split(',')[1]);

    // separate out the mime component
    var mimeString = dataURI.split(',')[0].split(':')[1].split(';')[0];

    // write the bytes of the string to a typed array
    var ia = new Uint8Array(byteString.length);
    for (var i = 0; i < byteString.length; i++) {
        ia[i] = byteString.charCodeAt(i);
    }

    return new Blob([ia], {type:mimeString});
}
