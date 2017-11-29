var $loader = "<div class='ui active centered small inline inverted loader'>&nbsp;</div>";
var validImageExtensions = /(\.jpg|\.jpeg|\.png)$/i;
var $messageModal = $('#modal-message');
var $errorMessageContainer = $('.error-message-container');

(function ($) {

    $(document).ready(function () {

        $(document).on('click', '#btn-save-user-info', function () {
            var $this = $(this);
            var $firstName = $('input[name=firstName]');
            var $lastName = $('input[name=lastName]');
            var $storeName = $('input[name=storeName]');
            var $storeSlug = $('input[name=storeSlug]');
            var $storeDesc = $('#storeDescription');
            var $hasValidId = $('#hasValidId');
            var $data = new FormData();
            $errorMessageContainer.html('').hide();

            if (
                $storeName.val().trim() == '' ||
                $storeSlug.val().trim() == '' ||
                $firstName.val().trim() == '' ||
                $lastName.val().trim() == ''
        ) {
                $errorMessageContainer.html('<div>Please fill up all the required fields.</div><br>').show();
                $('html,body').animate({scrollTop: $errorMessageContainer.offset().top - 60}, 800);
                return false;
            }

            $data.append('firstName', $firstName.val().trim());
            $data.append('lastName', $lastName.val().trim());
            $data.append('storeName', $storeName.val().trim());
            $data.append('storeSlug', $storeSlug.val().trim());
            $data.append('storeDesc', $storeDesc.val().trim());

            $.ajax({
                url         : Routing.generate('user_affiliate_proccess_accreditation_information'),
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
