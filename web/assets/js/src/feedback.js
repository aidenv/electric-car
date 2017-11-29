(function($) {
    var $productFeedbackForm = $('#product-feedback-form');

    if ($productFeedbackForm.length > 0) {
        var formRules = {
            fields: {
                title: {
                    identifier: 'title',
                    rules: [
                        {
                            type: 'empty',
                            prompt: 'Title is required'
                        }
                    ]
                },
                review: {
                    identifier: 'review',
                    rules: [
                        {
                            type: 'empty',
                            prompt: 'Content is required'
                        }
                    ]
                },
                rating: {
                    identifier: 'rating',
                    rules: [
                        {
                            type: 'empty',
                            prompt: 'Rating is required'
                        }
                    ]
                }
            },
            onSuccess: function() {
                $('[data-product-feedback-form-modal-message]').hide();
            },
            onFailure: function(formErrors, fields) {
                var $errors = $('<ul></ul>');
                formErrors.forEach(function(formError) {
                    $errors.append('<li>'+formError+'</li>');
                });
                
                $('[data-product-feedback-form-modal-message]').html($errors).show();
                
                return false;
            }
        };

        var $form = $productFeedbackForm.form(formRules);
    }

    var $sellerFeedbackForm = $('#seller-feedback-form');

    if ($sellerFeedbackForm.length > 0) {
        var formRules = {
            fields: {
                title: {
                    identifier: 'seller_feedback[title]',
                    rules: [
                        {
                            type: 'empty',
                            prompt: 'Title is required'
                        }
                    ]
                },
                review: {
                    identifier: 'seller_feedback[feedback]',
                    rules: [
                        {
                            type: 'empty',
                            prompt: 'Content is required'
                        }
                    ]
                },
                communicationRating: {
                    identifier: 'seller_feedback[rating1]',
                    rules: [
                        {
                            type: 'empty',
                            prompt: 'Communication Rating is required'
                        }
                    ]
                },
                qualityRating: {
                    identifier: 'seller_feedback[rating2]',
                    rules: [
                        {
                            type: 'empty',
                            prompt: 'Quality Rating is required'
                        }
                    ]
                }
            },
            onSuccess: function() {
                $('[data-seller-feedback-form-modal-message]').hide();
            },
            onFailure: function(formErrors, fields) {
                var $errors = $('<ul></ul>');
                formErrors.forEach(function(formError) {
                    $errors.append('<li>'+formError+'</li>');
                });

                $('[data-seller-feedback-form-modal-message]').html($errors).show();
                
                return false;
            }
        };

        var $form = $sellerFeedbackForm.form(formRules);
    }

    $('.coupled, .coupled-feedback-product').modal('setting', 'onHide', function() {
        $('[data-seller-feedback-form-modal-message]').hide();
        $('[data-product-feedback-form-modal-message]').hide();
    });
})(jQuery);