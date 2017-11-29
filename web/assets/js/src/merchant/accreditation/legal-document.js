(function ($) {
    var isDocumentEdited = false;
    var $messageModal = $('#modal-message');
    var pdfExtension = /(\.pdf)$/i;
    var imageExtensions = /(\.jpg|\.jpeg|\.png)$/i;
    var PDF_MAX_SIZE = 10485760; //10MB

    $(document).ready(function ($) {
        var isMerchant = parseInt($('#is-merchant').val());
        var otherFileArray = [];

        if ($('.existing-file').length > 0) {
            $.each($('.existing-file'), function (key, val) {
                var $this = $(this);
                otherFileArray.push({
                    otherLegalDocumentId: $this.attr('data-id'),
                    isRemoved: false
                })
            });
        }

        if ($('input[name=file-mayors-permit]').length == 0) {
            var htmlMayorsDiv = '' +
                '<div class="form">' +
                    '<div class="row">' +
                        '<label>Mayor\'s Permit</label>' +
                        '<label for="" class="light">Select your Mayor\'s Permit file from your computer</label>' +
                        '<input type="file" class="form-ui" name="file-mayors-permit">' +
                        '<span class="form-ui-note">Compatible files: JPEG, PNG and PDF</span>' +
                    '</div>' +
                '</div>';
            $('#input-container').append(htmlMayorsDiv);
        }

        $(document).on('click', '.trigger-delete-existing-file', function () {
            var $this = $(this);
            var legalDocumentId = $this.attr('data-id');

            for (var i = 0; i < otherFileArray.length; i++) {
                if (otherFileArray[i].otherLegalDocumentId == legalDocumentId) {
                    otherFileArray[i].isRemoved = true;
                    break;
                }
            }

            $this.parent().parent().remove();
        });

        $(document).on('click', '#btn-add-more', function () {
            var $otherInputHtml = '' +
                '<div class="input-inner-button">' +
                    '<input type="file" class="form-ui file-other" name="file-other[]">' +
                    '<button class="button gray uppercase trigger-delete-other-file"><i class="icon-trash"></i></button>' +
                '</div>';

            $('#other-file-container').append($otherInputHtml);
        });

        $(document).on('click', '.trigger-delete-other-file', function () {
            $(this).parent().remove();

            if ($('.trigger-delete-other-file').length == 0) {
                $('#btn-add-more').trigger('click');
            }

        });

        $(document).on('click', '#btn-submit-legal-documents', function () {
            var $this = $(this);

            var dtiSecPermitFile = $('input[name=file-dti-sec-permit]');
            var mayorsPermitFile = $('input[name=file-mayors-permit]');
            var birPermitFile = $('input[name=file-bir-permit]');
            var $otherFile = $('.file-other');
            var isUpdate = parseInt($('#is-update').val());
            var isTermsAndConditionChecked = $('.ui.checkbox').checkbox('is checked');

            if (isTermsAndConditionChecked == false) {
                $messageModal.find('.header-content').html('You need to agree with our Terms & Conditions in order to proceed with your registration.');
                $messageModal.find('.sub-header-content').html('');
                $messageModal.modal('show');
                return;
            }

            if (isUpdate === 0 && (
                dtiSecPermitFile.val() === '' ||
                birPermitFile.val() === '')
            ) {
                $messageModal.find('.header-content').html('Supply all required fields to continue');
                $messageModal.find('.sub-header-content').html('');
                $messageModal.modal('show');
                return;
            }

            var formData = new FormData();

            if (dtiSecPermitFile.attr('data-file') != 'pdf' && typeof dtiSecPermitFile.attr('data-file') != 'undefined') {
                formData.append('dtiPermit', new File([dataURItoBlob(dtiSecPermitFile.attr('data-file'))], dtiSecPermitFile.attr('data-name')));
            }
            else {
                formData.append('dtiPermit', dtiSecPermitFile.prop('files')[0]);
            }

            if (mayorsPermitFile.attr('data-file') != 'pdf' && typeof mayorsPermitFile.attr('data-file') != 'undefined') {
                formData.append('mayorsPermit', new File([dataURItoBlob(mayorsPermitFile.attr('data-file'))], mayorsPermitFile.attr('data-name')));
            }
            else {
                formData.append('mayorsPermit', mayorsPermitFile.prop('files')[0]);
            }

            if (birPermitFile.attr('data-file') != 'pdf' && typeof birPermitFile.attr('data-file') != 'undefined') {
                formData.append('birPermit', new File([dataURItoBlob(birPermitFile.attr('data-file'))], birPermitFile.attr('data-name')));
            }
            else {
                formData.append('birPermit', birPermitFile.prop('files')[0]);
            }

            formData.append('isUpdate', isUpdate);
            formData.append('otherFileArray', JSON.stringify(otherFileArray));
            formData.append('isMerchant', isMerchant);

            $.each($otherFile, function (key, input) {
                if ($(this).attr('data-file') != 'pdf' && typeof $(this).attr('data-file') != 'undefined') {
                    formData.append('otherFile[]', new File([dataURItoBlob($(this).attr('data-file'))], $(this).attr('data-name')));
                }
                else {
                    formData.append('otherFile[]', $(this).prop('files')[0]);
                }
            });

            $.ajax({
                url: Routing.generate('merchant_accreditation_submit_legal_documents'),
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

        $(document).on('click', '#btn-go-back', function () {
            confirmBackAction ();
        });

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

    $(".terms-modal-trigger").on("click", function(){
        $("#modal-terms-conditions").modal("show");
    });
})(jQuery);
