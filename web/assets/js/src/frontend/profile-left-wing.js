(function ($) {

    $(document).ready(function () {
        var IMAGE_TYPE_AVATAR = 0;
        var IMAGE_TYPE_BANNER = 1;
        var $imageCropperData = {};
        var $image = $("#profile-photo-modal .cropper-profile-photo img"), profileCropBoxData, profileCanvasData;

        $(document).on("change", ".profile-file-input:not(.mobile-change-profile-photo-input)", function (e){
            var cropImage = $('.information-container .cropper-profile-photo > img');

            var imageFile = this.files[0];
            var browserWindow = window.URL || window.webkitURL;
            var filename =  imageFile.name;
            var extension = filename.substring(filename.lastIndexOf('.') + 1).toLowerCase();
            var objectUrl = browserWindow.createObjectURL(imageFile);

            var $promise = createImage(objectUrl);

            $promise.done(function($object){
            
                resize($object);

                $image.cropper({
                    aspectRatio: 1 / 1,
                    autoCropArea: 1,
                    strict: true,
                    minContainerHeight: 350,
                    maxContainerHeight: 350,
                    minContainerWidth: 350,
                    maxContainerWidth: 350,
                    minCropBoxWidth: 300,
                    maxCropBoxWidth: 300,
                    minCropBoxHeight: 300,
                    maxCropBoxHeight: 300,
                    guides: false,
                    highlight: false,
                    dragCrop: false,
                    cropBoxMovable: false,
                    cropBoxResizable: false,
                    responsive: false,
                    built: function () {
                        $image.cropper('crop');
                        $image.cropper("destroy");
                        $("#profile-photo-modal").modal('show');
                    }
                });
            })
        });

        $('#profile-photo-modal').modal({
            observeChanges: true,
            onShow : function(){
                $(".change-profile-photo-errors").addClass("hidden").html("");
                $image.cropper({
                    aspectRatio: 1 / 1,
                    autoCropArea: 1,
                    strict: true,
                    minContainerHeight: 350,
                    maxContainerHeight: 350,
                    minContainerWidth: 350,
                    maxContainerWidth: 350,
                    minCropBoxWidth: 300,
                    maxCropBoxWidth: 300,
                    minCropBoxHeight: 300,
                    maxCropBoxHeight: 300,
                    guides: false,
                    highlight: false,
                    dragCrop: false,
                    cropBoxMovable: false,
                    cropBoxResizable: false,
                    responsive: false,
                    built: function () {
                        // Strict mode: set crop box data first
                        $image.cropper('setCropBoxData', profileCropBoxData);
                        $image.cropper('setCanvasData', profileCanvasData);
                    },
                    crop: function(e) {
                        $imageCropperData = {
                            x : e.x,
                            y : e.y,
                            width : e.width,
                            height : e.height,
                            rotate : e.rotate,
                            scaleX : e.scaleX,
                            scaleY : e.scaleY
                        };
                    }
                });
            },
            onHidden : function(){
                $image.cropper("destroy");
                $(".profile-file-input").val("");
            }
        });

        $('#crop-profile-image-btn').on('click', function () {
            var $formData = new FormData();
            var $responseErrorBox = $(".change-profile-photo-errors");
            var $button = $(this);

            var $dataUri = $image.attr("src");

            $formData.append("image", dataURItoBlob($dataUri), $("input[name='profile-photo']:not(.mobile-change-profile-photo-input)")[0].files[0].name);
            $formData.append("imageType", IMAGE_TYPE_AVATAR);
            $formData.append("x", $imageCropperData.x);
            $formData.append("y", $imageCropperData.y);
            $formData.append("width", $imageCropperData.width);
            $formData.append("height", $imageCropperData.height);
            $formData.append("resizeWidth", 350);
            $formData.append("resizeHeight", 350);

            $.ajax({
                url: Routing.generate('core_upload_user_photo'),
                type: 'POST',
                data: $formData,
                processData: false,
                contentType: false,
                beforeSend: function() {
                    $button.html("<div class='ui active centered small inline inverted loader'>&nbsp;</div>").addClass("disabled");
                },
                success: function(response) {
                    if(response.isSuccessful){
                        $image.cropper("destroy");
                        $(".photo-placeholder").text("Edit Photo");
                        $(".user-image-profile > img").attr("src", response.data.mediumUrl);
                        $("#user-avatar > img").attr("src", response.data.thumbnailUrl);
                        $("#profile-photo-modal").modal('hide');
                    }
                },
                error: function(response){
                    var $responseJson = response.responseJSON;
                    var $errors = $responseJson.data.errors;
                    var $errorList = "<ul>";

                    $errors.forEach(function(value){
                        $errorList += "<li>" + value + "</li>"
                    });

                    $errorList += "</ul>";

                    $responseErrorBox.html($errorList);
                    $responseErrorBox.removeClass("hidden");
                },
                complete: function(){
                    $button.html("Submit").removeClass('disabled');
                }
            });
        });

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

            $image.attr('src', mainCanvas.toDataURL("image/jpeg"));
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
    });
})(jQuery);
