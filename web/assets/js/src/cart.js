(function($) {
    var unitAttributes = function(cartItem, join) {
        var attributes = [];
        cartItem['productUnits'][cartItem.unitId]['combination'].forEach(function(attributeValueId, key) {
            if (typeof cartItem['attributes'][key] != 'undefined') {
                cartItem['attributes'][key]['items'].forEach(function(attributeValue) {
                    if (attributeValue.id == attributeValueId) {
                        attributes.push(attributeValue.name);
                    }
                });
            }
        });
        if (join) {
            var attr = '';
            if (attributes.length > 0) {
                attr = '('+attributes.join()+')';
            }
            attributes = attr;
        }

        return attributes;
    };

    var cartHtml = '';
    var wishlistHtml = '';

    var $body = $('body');
    $body.loadCart = function($cartHeader, $modal, modalTwig) {
        $modal.on('flash-message', function(evt, message) {
            var $flashMsg = $modal.find('[data-flash-message]');
            if ($flashMsg.length) {
                if (!message) {
                    $flashMsg.empty();
                    $flashMsg.hide();
                }
                else {
                    if (!Array.isArray(message)) {
                        message = [message];
                    }
                    message.forEach(function(msg) {
                        $flashMsg.append('<span>'+msg+'</span>');
                    });

                    $flashMsg.toggle(message.length > 0);
                }
            }
        });

        $modal.modal('setting', {
            onHide: function() {
                $modal.trigger('flash-message', '');
            }
        });

        $cartHeader.on('cart-change-message-updated', function() {
            var changeLogs = $cartHeader.data('cart-change-log');
            var messages = [];
            changeLogs['newItems'].forEach(function(cartItem) {
                var attributes = unitAttributes(cartItem);
                var attr = '';
                if (attributes.length > 0) {
                    attr = '('+attributes.join()+')';
                }
                messages.push('You have added '+cartItem['title']+attr+'.');
            });
            changeLogs['changedItems'].forEach(function(cartItem, key) {
                var attributes = unitAttributes(cartItem);
                var attr = '';
                if (attributes.length > 0) {
                    attr = '('+attributes.join()+')';
                }
                var newCartItem = changeLogs['changedNewItems'][key];
                var newAttr = unitAttributes(newCartItem, true);

                messages.push('You have changed '+cartItem['title']+attr+' x'+cartItem['quantity']+' to '+newCartItem['title']+newAttr+' x'+newCartItem['quantity']+'.');
            });
            changeLogs['deletedItems'].forEach(function(cartItem) {
                var attributes = unitAttributes(cartItem);
                var attr = '';
                if (attributes.length > 0) {
                    attr = '('+attributes.join()+')';
                }
                messages.push('You have deleted '+cartItem['title']+attr+'.');
            });

            $modal.trigger('flash-message', messages);
        });

        $cartHeader.on('cart-change-message', function(evt) {
            var previousCart = $cartHeader.data('previousCart');
            var cart = $cartHeader.data('cart');

            var cartItems = {};
            for (var i in cart) {
                cartItems[cart[i]['itemId']] = cart[i];
            }

            var deletedItems = [];
            var changedItems = [];
            var changedNewItems = [];
            for (var i in previousCart) {
                if (cartItems.hasOwnProperty(previousCart[i]['itemId'])) {
                    var isProductEqual = previousCart[i]['id'] == cartItems[previousCart[i]['itemId']]['id'];
                    var isUnitEqual = previousCart[i]['unitId'] == cartItems[previousCart[i]['itemId']]['unitId'];
                    var isQuantityEqual = previousCart[i]['quantity'] == cartItems[previousCart[i]['itemId']]['quantity'];

                    if (!(isProductEqual && isUnitEqual && isQuantityEqual)) {
                        changedItems.push(previousCart[i]);
                        changedNewItems.push(cartItems[previousCart[i]['itemId']]);
                    }
                    delete cartItems[previousCart[i]['itemId']];
                }
                else {
                    deletedItems.push(previousCart[i]);
                }
            }

            var newItems = [];
            for (var i in cartItems) {
                newItems.push(cartItems[i]);
            }
            $cartHeader.data('cart-change-log', {
                newItems: newItems,
                changedItems: changedItems,
                changedNewItems: changedNewItems,
                deletedItems: deletedItems
            });

            $cartHeader.trigger('cart-change-message-updated');
        });

        $cartHeader.on('set-cart', function(evt, data) {
            var $elem = $(this),
                $title = $elem.data('title'),
                $locale = $elem.data('locale'),
                $dataText = $elem.find('[data-text]'),
                total = 0,
                cart = data.cart;

            var $mobileNav = $(".left-wing-mobile");

            for (i in cart) total += parseInt(cart[i].quantity);
            var title = $title.interpolate({items: total + ' ' + (total == 1 ? ($locale==='cn'?'':'item') : ($locale==='cn'?'':'items'))})

            // $elem.attr('title', title);
            $elem.tipso('update', 'content', title).tipso();

            $dataText.each(function() {
                var $text = $(this),
                    text = $text.data('text');

                $text.text(text.interpolate({n: total}));
            });

            $cartHeader.data('previousCart', $cartHeader.data('cart'));
            $cartHeader.data('cart', cart);
            $dataText.toggle(total > 0);

            var $wishlistCount = parseInt($mobileNav.find(".item-counter").text());
            var $messageCount = parseInt($mobileNav.find(".unread-messages-badge").text());

            var $total = $wishlistCount + $messageCount;

            $(".notifications-badge").text($total);

            if($total > 0){
                $(".notifications-badge").show();
            }
            else{
                $(".notifications-badge").hide();
            }

            refreshCartModal();
        });

        $cartHeader.on('add-cart', function(evt, value) {
            var $elem = $(this),
                n = $elem.data('n');

            n = parseInt(n) + parseInt(value);
            $elem.trigger('set-cart', n);
        });

        var refreshCartModal = function() {
            var html = '';
            if (modalTwig == 'cart') {
                html = cartHtml;
           }
            else {
                html = wishlistHtml;
            }

            if (html) {
                var cart = $cartHeader.data('cart');
                var $cart = $($(html).html());
                var $attributeChooser = $cart.find('[data-attribute-chooser]');
                $('body').trigger('loadAttributeChoosers', {attributeChooser: $attributeChooser});
                $cart.find(".single.selection").dropdown();
                $modal.html($cart);
            }
        };
    };

    //wishlist modal show
    $(".wishlist-icon-link").click(function(){
        $(".wishlist-modal").modal("show");
    });

    var $cartHeader = $('.cart-header'),
        $wishlistHeader = $('.wishlist-header'),
        $cartModal = $('#cart-modal'),
        $wishlistModal = $('#wishlist-modal');

    $cartModal.on('click', '[name="cart[]"]', function() {
        var $elem = $(this);
        var itemPrice = $elem.data('price').replace(/[^0-9.]/g, '');
        itemPrice = parseFloat(itemPrice);
        var $totalPrice = $cartModal.find('#modal-cart-total');
        var totalPrice = $totalPrice.text().replace(/[^0-9.]/g, '');
        totalPrice = parseFloat(totalPrice);
        totalPrice = this.checked ? (totalPrice + itemPrice): (totalPrice - itemPrice);
        $totalPrice.text(totalPrice.toFixed(2).replace(/./g, function(c, i, a) {
            return i && c !== "." && !((a.length - i) % 3) ? ',' + c : c;
        }));
    });

    $(document).ready(function(){
        $cartModal.yiLoader();
        $wishlistModal.yiLoader();

        $body.loadCart($cartHeader, $cartModal, 'cart');
        $body.loadCart($wishlistHeader, $wishlistModal, 'wishlist');

        $cartHeader.trigger('set-cart', {cart: $cartHeader.data('cart')});
        $wishlistHeader.trigger('set-cart', {cart: $wishlistHeader.data('cart')});
    });

    var ajaxUpdateCart = function(url, changeData, evt) {
        var $defaultSellerId = $('body').data('sellerId');
        if (!changeData.sellerId && $defaultSellerId) {
            changeData.sellerId = $defaultSellerId;
        }
        $.ajax({
            url: url,
            type: 'POST',
            data: changeData,
            beforeSend: function() {
                if (url.indexOf('wishlist') < 0 && !evt.isTrigger) {
                    $cartModal.modal('show');
                }

                if (url.indexOf('wishlist') < 0) {
                    $cartModal.trigger('loader.start');
                }
                else {
                    $wishlistModal.trigger('loader.start');
                }
            },
            success: function(data) {
                if (data.mode == 'cart') {
                    cartHtml = data.html;
                    $cartHeader.trigger('set-cart', {cart: data.cart});
                    $cartHeader.trigger('cart-change-message');
                    $cartModal.trigger('loader.stop');
                    $cartModal.modal('refresh');
                }
                else {
                    $wishlistModal.trigger('loader.stop');
                    wishlistHtml = data.html;
                    var $buttons = $("[data-href='"+url+"'][data-addtocart*='\"unitId\":\""+changeData.unitId+"\"']");
                    if (changeData.quantity != 0) {
                        $buttons.addClass('active');
                    }
                    else {
                        $buttons.removeClass('active');
                    }
                    $wishlistHeader.trigger('set-cart', {cart: data.cart});
                    $wishlistHeader.trigger('cart-change-message');
                    $wishlistModal.modal('refresh');
                }
            }
        });
    };

    $('body').on('change', '[data-addtocart-change]', function(evt) {
        var $elem = $(this);
        var url = $elem.data('href');
        var data = $elem.data('addtocart-change');
        var change = $elem.data('change');
        var value = $elem.val();
        data[change] = value;
        ajaxUpdateCart(url, data, evt);
    });

    $('body').on('click', '[data-addtocart]', function(evt) {
        var $elem = $(this);
        var url = $elem.data('href');
        var data = $elem.data('addtocart');

        /**
         * Facebook Pixel AddToCart/AddToWishlist tracker
         */
        if(typeof fbq != "undefined"){
            if(url.indexOf("wishlist") == -1){
                fbq('track', 'AddToCart', {
                    content_name: 'Shopping Cart',
                    content_ids: [ data.productId ],
                    content_type: 'product',
                });
            }
            else{
                fbq('track', 'AddToWishlist', {
                    content_name: 'Wishlist',
                    content_ids: [ data.productId ],
                    content_type: 'product',
                });
            }
        }

        ajaxUpdateCart(url, data, evt);
    });

    $('body').on('click', '[data-transfertocart]', function(evt) {
        var $elem = $(this),
            target = $elem.data('transfertocart'),
            $targets = $(target),
            itemIds = [],
            url = $elem.data('href');

        $targets.each(function() {
            itemIds.push($(this).val());
        });

        $.ajax({
            url: url,
            type: 'POST',
            data: {
                itemIds: itemIds
            },
            beforeSend: function() {
                $wishlistModal.trigger('loader.start');
            },
            success: function(data) {
                $wishlistModal.trigger('loader.stop');
                cartHtml = data.cartHtml;
                wishlistHtml = data.wishlistHtml;

                var $wishlistBtns = $("[data-href*='mode=wishlist'][data-addtocart]");
                $wishlistBtns.removeClass('active');
                for (var key in data.wishlist) {
                    var item = data.wishlist[key];
                    var $buttons = $("[data-href*='mode=wishlist'][data-addtocart*='\"unitId\":\""+item.unitId+"\"']");
                    $buttons.addClass('active');
                }

                $wishlistHeader.trigger('set-cart', {cart: data.wishlist});
                $wishlistModal.modal('hide');
                $cartHeader.trigger('set-cart', {cart: data.cart});
                $cartModal.modal('show');
            }
        });
    });

    $body.on('click', '[data-buynow]', function(evt) {
        evt.preventDefault();
        var $elem = $(this),
            url = Routing.generate('checkout_buynow');
            data = $elem.data('buynow'),
            quantity = $body.data('quantity');
        data.quantity = quantity ? quantity: 1;

        /**
         * Facebook Pixel AddToCart tracker
         */
        if(typeof fbq != "undefined"){
            fbq('track', 'AddToCart', {
                content_name: 'Shopping Cart',
                content_ids: [ data.productId ],
                content_type: 'product',
            });
        }

        window.location = url+'?'+$.param(data);
    });

    $('body').on('setUnit', '[data-cartitem]', function(evt, i) {
        var $elem = $(this);
        var cartitem = $elem.data('cartitem');
        cartitem['unitId'] = i;
        $elem.data('cartitem', cartitem);
        $elem.trigger('updatedCartItem');
    });

    $('body').on('updatedCartItem', '[data-cartitem]', function(evt) {
        var $elem = $(this);
        if ($elem.data('trigger') == 'auto') {
            var url = $elem.data('updatecartitem');
            var data = $elem.data('cartitem');
            if (url) {
                ajaxUpdateCart(url, data, evt);
            }
        }
    });

    $('body').on('click', '[data-remove-cartitem]', function(evt) {
        evt.preventDefault();
        var
            $elem = $(this),
            $cartItem = $elem.closest('[data-cartitem]'),
            $deleteConfirm = $cartItem.find('[data-cart-delete-confirm]'),
            $deleteConfirms = $('[data-cart-delete-confirm]')
        ;

        $deleteConfirms.each(function() {
            $(this).dimmer('hide');
        });
        $deleteConfirm.dimmer('show');
    });

    $('body').on('click', '[data-cart-delete-cancel]', function(evt) {
        evt.preventDefault();
        var
            $elem = $(this),
            $deleteConfirm = $elem.closest('[data-cart-delete-confirm]')
        ;

        $deleteConfirm.dimmer('hide');
    });


    // behavior for deleting proceed to checkout when no item is selected
    $('body').on('click', '[name="cart[]"]', function() {
        var $cartModalSubmit = $('[data-cart-modal-submit]');
        var $elem = $(this);
        if ($elem.prop('checked')) {
            $cartModalSubmit.addClass('purple');
            $cartModalSubmit.prop('disabled', false);
        }
        else {
            var $checkedItems = $('[name="cart[]"]:checked');
            if ($checkedItems.length) {
                $cartModalSubmit.addClass('purple');
                $cartModalSubmit.prop('disabled', false);
            }
            else {
                $cartModalSubmit.removeClass('purple');
                $cartModalSubmit.prop('disabled', true);
            }
        }
    });

    $(window).on("load", function(){
        //cart modal show
        $(".cart-icon-link, .new-cart").click(function(){
            $(".cart-modal").modal("show");
        });
    });

})(jQuery);
