(function ($) {
    var $messageModal = $('#modal-message-container');
    var formData = false;

    $(document).ready(function () {

        if (window.FormData) {
            formData = new FormData();
        }

        $(document).on('change', '#input-first-inner-banner', function () {
            processUploadedImage (this.files, $('#container-first-inner-banner'), 'firstInnerBanner');
        });

        $(document).on('change', '#input-second-inner-banner', function () {
            processUploadedImage (this.files, $('#container-second-inner-banner'), 'secondInnerBanner');
        });

        $(document).on('change', '#input-third-inner-banner', function () {
            processUploadedImage (this.files, $('#container-third-inner-banner'), 'thirdInnerBanner');
        });

        $(document).on('click', '#btn-submit', function () {
            var $this = $(this);
            var csrfToken = $("meta[name=csrf-token]").attr("content");
            var firstMessage = $('#input-first-message').val().trim();
            var secondMessage = $('#input-second-message').val().trim();
            var firstBannerUrl = $('#input-first-inner-banner-url').val().trim();
            var secondBannerUrl = $('#input-second-inner-banner-url').val().trim();
            var thirdBannerUrl = $('#input-third-inner-banner-url').val().trim();

            formData.append('_token', csrfToken);
            formData.append('firstMessage', firstMessage);
            formData.append('secondMessage', secondMessage);
            formData.append('firstBannerUrl', firstBannerUrl);
            formData.append('secondBannerUrl', secondBannerUrl);
            formData.append('thirdBannerUrl', thirdBannerUrl);

            $.ajax({
                url: Routing.generate('cms_update_daily_login'),
                method: 'POST',
                dataType: 'json',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function () {
                    //$this.attr('disabled', true);
                },
                success: function (response) {
                    $this.attr('disabled', false);

                    if (response.isSuccessful === true) {
                        $messageModal.find('.header-content').html('Successfully Updated');
                        $messageModal.find('.detail-content').html('');
                        $messageModal.modal('show');
                        location.reload();
                    }
                    else {
                        $messageModal.find('.header-content').html('-' . response.message);
                        $messageModal.find('.detail-content').html('');
                        $messageModal.modal('show');
                    }

                }

            });

        });

    });

    /**
     * Process Uploaded Image
     *
     * @param files
     * @param $imageContainer
     * @param $key
     */
    function processUploadedImage (files, $imageContainer, key)
    {
        var i, len = files.length, img, reader, file;

        for (i = 0; i < len; i++) {
            file = files[i];

            if ( window.FileReader ) {
                // Only process image files.
                if (!file.type.match('image.*')) {
                    continue;
                }

                reader = new FileReader();
                reader.onloadend = function (e) {
                    $imageContainer.find('img').attr('src', e.target.result);
                };
                reader.readAsDataURL(file);
            }

            if (formData !== false) {
                formData.append(key, file);
            }

        }

    }

})(jQuery);
