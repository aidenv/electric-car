(function ($) {

    $(document).ready(function () {
        var $rowContainer = $('#banner-row-container');
        var $messageModal = $('#modal-message');
        var $loader = "<div class='ui active centered small inline inverted loader'>&nbsp;</div>";
        var validImageExtensions = /(\.jpg|\.jpeg|\.png)$/i;

        $(document).on('click', '.remove-banner', function () {
            var $this = $(this);
            $this.closest('.banner-row').remove();
        });

        $(document).on('click', '#add-row', function () {
            var $rowDiv = $('#clone-row').find('.banner-row').clone();
            var order = $rowContainer.find('.banner-row').length + 1;
            var $file = $('.new-file');
            var $cloneFile = $file.clone();

            if ($file.val() == '') {
                $messageModal.find('.header-content').html('Banner file is required');
                $messageModal.find('.sub-header-content').html('');
                $messageModal.modal('show');
                return;
            }

            $cloneFile.attr('class', 'form-ui banner-file')
                      .attr('data-is-new', 'true');
            $rowDiv.find('.banner-link').val($('.new-link').val());
            $rowDiv.find('.banner-order').val(order);
            $rowDiv.find('.banner-file-cont').html($cloneFile);
            $rowContainer.append($rowDiv);
            $file.val('');
            $('.new-link').val('');
        });

        $(document).on('change', '.banner-order', function () {
            var $this = $(this);
            var isUsed = false;
            $('.banner-order').not(this).each(function (key, val) {
                var $order = $(this);
                if ($order.val() == $this.val()) {
                    isUsed = true;
                }
            });

            if (isUsed == true) {
                $this.addClass('error');
                $this.val('');
            }
            else {
                $this.removeClass('error')
            }

        });

        $(document).on('change', '.new-file, .banner-file', function () {
            var $this = $(this);
            var className = $this.hasClass('new-file');
            var file = $this.prop('files')[0];

            if (!validImageExtensions.test(file['name'].toLowerCase())) {
                $messageModal.find('.header-content').html('Please upload a valid Image file.');
                $messageModal.find('.sub-header-content').html('Allowed file extensions are jpeg and png (max size 10MB).');
                $messageModal.modal('show');
                $(this).val('');
            }
            else {
                if (className) {
                    var $imagePreview = $('#clone-row').find('.image-preview');
                }
                else {
                    var $imagePreview = $this.parent().parent().parent().parent().parent().find('.image-preview');
                    $this.attr('data-is-new', 'true');
                }
                readURL(this, $imagePreview);
            }

        });

        $(document).on('click', '#save-banners', function () {
            var $this = $(this);
            var $rowContainer = $('#banner-row-container');
            var hasError = false;
            var $data = new FormData();
            var ctr = 0;

            $rowContainer.find('.banner-row').each(function () {
                var $thisRow = $(this);
                var $file = $thisRow.find('.banner-file');
                var isNew = $file.attr('data-is-new');
                var fileName = isNew == 'false' ? $file.attr('data-file-name') : 'new';

                if (isNew == false && typeof $file.prop('files')[0] == 'undefined') {
                    hasError = true;
                }

                $data.append('bannerFile['+ctr+']', isNew == 'true' ? $file.prop('files')[0] : null);
                $data.append('fileName['+ctr+']', fileName);
                $data.append('isNew['+ctr+']', isNew);
                $data.append('link['+ctr+']', $thisRow.find('.banner-link').val());
                $data.append('order['+ctr+']', $thisRow.find('.banner-order').val());
                ctr++;
            });
            $data.append('applyImmediate', $('#apply-immediate').is(':checked'));

            if (hasError) {
                $messageModal.find('.header-content').html('Invalid banners');
                $messageModal.find('.sub-header-content').html('Allowed file extensions are jpeg and png.');
                $messageModal.modal('show');
                $(this).val('');
            }

            $.ajax({
                url         : Routing.generate('cms_update_main_banner'),
                type        : 'post',
                cache       : false,
                dataType    : 'json',
                processData : false,
                contentType : false,
                data        : $data,
                beforeSend : function () {
                    $this.html($loader).attr('disabled', true);
                },
                success: function (response) {
                    $this.html('Save').attr('disabled', false);

                    if (response.isSuccessful) {
                        $messageModal.find('.header-content').html('Successfully update!');
                        $messageModal.find('.sub-header-content').html('');
                        $messageModal
                            .modal({
                                closable : false,
                                onApprove: function () {
                                    location.reload();
                                }
                            })
                            .modal('show');
                    }
                    else {
                        $messageModal.find('.header-content').html(response.message);
                        $messageModal.find('.sub-header-content').html('');
                        $messageModal.modal('show');
                    }
                }
            });

        });

        function readURL($this, $image) {
            var $input = $($this)[0];
            if ($input.files && $input.files[0]) {
                var reader = new FileReader();

                reader.onload = function (e) {
                    $image.attr('src', e.target.result);
                };

                reader.readAsDataURL($input.files[0]);
            }
        }

    });

})(jQuery);