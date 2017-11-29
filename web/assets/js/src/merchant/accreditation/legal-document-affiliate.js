(function ($) {
    var isDocumentEdited = false;
    var pdfExtension = /(\.pdf)$/i;
    var imageExtensions = /(\.jpg|\.jpeg|\.png)$/i;
    var $messageModal = $('#modal-message');
    var PDF_MAX_SIZE = 10485760; //10MB

    $(document).ready(function ($) {
        var isAffiliate = parseInt($('#is-affiliate').val());

        $(document).on('change', ':file', function () {
            var $this = $(this);
            var file = $this.prop('files')[0];

            if (imageExtensions.test(file['name'].toLowerCase())) {
                var browserWindow = window.URL || window.webkitURL;
                var objectUrl = browserWindow.createObjectURL(file);

                var $promise = createImage(objectUrl);

                $promise.done(function($object) {
                    resize($object, $this);
                });
                isDocumentEdited = true;
            }
            else if (pdfExtension.test(file['name'].toLowerCase()) && file['size'] < PDF_MAX_SIZE) {
                isDocumentEdited = true;
                $this.attr('data-file', 'pdf');
            }
            else {
                $messageModal.find('.header-content').html('Please upload a valid Image file.');
                $messageModal.find('.sub-header-content').html('Allowed file extensions are jpeg, png and pdf (max size 10MB).');
                $messageModal.modal('show');
                $this.val('');
            }

        });

        $(document).on('click', '#btn-submit-legal-documents', function () {
            var $this = $(this);

            var tinFile = $('input[name=file-tin]');
            var validIdFile = $('input[name=file-valid-id]');
            var legalDocumentTypeId = $('#drop-down-valid-id').val();
            var isUpdate = parseInt($('#is-update').val());
            var oldLegalDocumentTypeId = $('#legal-doc-type-id').length > 0 ? $('#legal-doc-type-id').val() : 0;
            var isTermsAndConditionChecked = $('.ui.checkbox').checkbox('is checked');

            if (isTermsAndConditionChecked == false) {
                $messageModal.find('.header-content').html('You need to agree with our Terms & Conditions in order to proceed with your registration.');
                $messageModal.find('.sub-header-content').html('');
                $messageModal.modal('show');
                return;
            }

            if (isUpdate === 0 && (
                tinFile.val() === '' ||
                validIdFile.val() === '' ||
                legalDocumentTypeId === '' ||
                legalDocumentTypeId === 0 )
            ) {
                $messageModal.find('.header-content').html('Supply all required fields to continue');
                $messageModal.find('.sub-header-content').html('');
                $messageModal.modal('show');
                return;
            }

            var formData = new FormData();

            if (tinFile.attr('data-file') != 'pdf' && typeof tinFile.attr('data-file') != 'undefined') {
                formData.append('tinFile', new File([dataURItoBlob(tinFile.attr('data-file'))], tinFile.attr('data-name')));
            }
            else {
                formData.append('tinFile', tinFile.prop('files')[0]);
            }

            if (validIdFile.attr('data-file') != 'pdf' && typeof validIdFile.attr('data-file') != 'undefined') {
                formData.append('validIdFile', new File([dataURItoBlob(validIdFile.attr('data-file'))], validIdFile.attr('data-name')));
            }
            else {
                formData.append('validIdFile', validIdFile.prop('files')[0]);
            }

            formData.append('legalDocumentTypeId', legalDocumentTypeId);
            formData.append('isUpdate', isUpdate);
            formData.append('isAffiliate', isAffiliate);
            formData.append('oldLegalDocumentTypeId', oldLegalDocumentTypeId);

            $.ajax({
                url: Routing.generate('merchant_accreditation_submit_affiliate_legal_documents'),
                type: 'post',
                cache: false,
                dataType: 'json',
                processData: false,
                contentType: false,
                data: formData,
                beforeSend: function () {
                    $this.find('.loader').removeClass('hidden');
                    $this.find('.text').addClass('hidden');
                    $this.attr('disabled', true);
                },
                success: function (response) {
                    $this.find('.loader').addClass('hidden');
                    $this.find('.text').removeClass('hidden');
                    $this.attr('disabled', false);

                    if (response.isSuccessful) {
                        $messageModal.find('.header-content').html('Successfully Uploaded');
                        $messageModal.find('.sub-header-content').html('');
                        $messageModal.modal('show');
                        window.location.replace(Routing.generate('merchant_accreditation'));
                    }
                    else {
                        $messageModal.find('.header-content').html(response.message);
                        $messageModal.find('.sub-header-content').html('');
                        $messageModal.modal('show');
                    }

                }
            });

        });

        $(document).on("click", ".terms-modal-trigger", function() {
            $("#modal-terms-conditions").modal("show");
        });

    });

    $(document).on('click', '#btn-go-back', function () {
        confirmBackAction ();
    });

    /**
     * Back action confirmation
     */
    function confirmBackAction ()
    {

        if (isDocumentEdited) {
            $('#modal-confirm-back')
                .modal({
                    onApprove: function () {
                        window.location.replace(Routing.generate('merchant_accreditation'));
                    }
                })
                .modal('show');
        }
        else {
            window.location.replace(Routing.generate('merchant_accreditation'));
        }

    }

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

    function dataURItoBlob(dataURI)
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
