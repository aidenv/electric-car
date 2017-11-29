var $loader = "<div class='ui active centered small inline inverted loader'>&nbsp;</div>";
var validImageExtensions = /(\.jpg|\.jpeg|\.png)$/i;
var $messageModal = $('#modal-message');
var $errorMessageContainer = $('.error-message-container');

(function ($) {

    $(document).ready(function () {

        $(document).on('click', '#btn-save-user-info', function () {
            var $this = $(this);
            var $tinId = $('input[name=tinId]');
            var $tinImage = $('input[name=tinImage]');
            var $hasValidId = $('#hasValidId');
            var $data = new FormData();
            $errorMessageContainer.html('').hide();

            $data.append('tinId', $tinId.val().trim());

            if ($hasValidId != 0 && typeof $tinImage.attr('data-file') != 'undefined') {
                $data.append('tinImage', new File([dataURItoBlob($tinImage.attr('data-file'))], $tinImage.attr('data-name')));
            }

            $.ajax({
                url         : Routing.generate('user_affiliate_proccess_legal_document'),
                type        : 'post',
                cache       : false,
                dataType    : 'json',
                processData : false,
                contentType : false,
                data        : $data,
                beforeSend  : function () {
                    $this.html($loader).attr('disabled', true);
                },
                success     : function (response) {
                    $this.html("Save Changes").attr('disabled', false);

                    if (response.isSuccessful == true) {
                        $messageModal.find('.header-content').html('Successfully update!');
                        $messageModal.find('.sub-header-content').html('');
                        $messageModal
                            .modal({
                                closable: false,
                                onApprove : function () {
                                    location.reload();
                                }
                            })
                            .modal('show');
                    }
                    else {
                        var message = '';
                        $.each(response.message, function (key, value) {
                            message += '<div>' + value + '</div>';
                        });

                        $errorMessageContainer.html(message).show();
                        $('html,body').animate({scrollTop: $errorMessageContainer.offset().top - 60}, 800);
                    }

                }
            });

        });

        $(document).on('change', '#tinImage', function () {
            var $this = $(this);
            var file = $this.prop('files')[0];

            if (validImageExtensions.test(file['name'].toLowerCase())) {
                var browserWindow = window.URL || window.webkitURL;
                var objectUrl = browserWindow.createObjectURL(file);

                var $promise = createImage(objectUrl);

                $promise.done(function($object) {
                    resize($object, $this);
                });
                $('#hasValidId').val(1);

            }
            else {
                $messageModal.find('.header-content').html('Please upload a valid Image file.');
                $messageModal.find('.sub-header-content').html('Allowed file extensions are jpeg and png (max size 10MB).');
                $messageModal.modal('show');
                $this.val('');
            }

        });

    });

    function createImage (src)
    {
        var deferred = $.Deferred();
        var img = new Image();

        img.onload = function() {
            $(img).attr("naturalWidth", this.naturalWidth);
            $(img).attr("naturalHeight", this.naturalHeight);

            deferred.resolve(img);
        };

        img.src = src;
        return deferred.promise();
    }

    function resize (image, $this)
    {
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

        $this.attr('data-file', mainCanvas.toDataURL("image/jpeg"));
        $this.attr('data-name', $this.prop('files')[0]['name']);
    }

    function dataURItoBlob (dataURI)
    {
        // convert base64/URLEncoded data component to raw binary data held in a string
        if (dataURI == 'pdf') {
            return;
        }

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

})(jQuery);
