(function($) {

    var treeNodes = [];
    var changes = [];
    var hasCustom = $("#category-list-container").data("has-custom-category");
    var loadedTree = createTree($("div#category-list-container > ul > li.category-item"));
    var loadedTreeInstance = [];

    var $currentCategory = null;
    var $userProducts = [];
    var $customCategoryProducts = [];
    var $customProductsBak = []; //back up sort order if searching
    var $parentCategoriesSelect = $("select[name='parent-categories']");  
    var $customizedModal = $("div.customized-modal");  
    var $form = $("form[name='customized-category-form']"); 

    var $categoryTree = $('.category-tree');
    var $deleteCategoryTree = $('.delete-category-tree');
    var $editCategoryTree = $('.edit-category-tree');
    var $responseErrorBox = $(".custom-category-errors");
    var $isSearching = false;

    $categoryTree.jstree({
        "core" : {
            "data" : loadedTree,
            "check_callback" : true,
            "themes":{
                "icons":false
            }
        },
        "types" : {
            "#" : {
                "max_depth" : 2
            }
        },
        "plugins" : [
            "types",
            "wholerow"
        ]
    });


    $deleteCategoryTree.jstree({
        "checkbox": { "three_state" : false },
        "core" : {
            "data" : loadedTree,
            "check_callback" : true,
            "themes":{
                "icons":false
            }
        },
        "types" : {
            "#" : {
                "max_depth" : 2
            }
        },
        "plugins" : [
            "types",
            "wholerow",
            "checkbox"
        ]
    });

    $editCategoryTree.jstree({
        "core" : {
            "data" : loadedTree,
            "check_callback" : true,
            "themes":{
                "icons":false
            }
        },
        "types" : {
            "#" : {
                "max_depth" : 2
            }
        },
        "plugins" : [
            "sortjs",
            "dnd",
            "types",
            "wholerow"
        ]
    });

    $categoryTree.jstree("open_all");
    $editCategoryTree.jstree("open_all");
    $deleteCategoryTree.jstree("open_all");

    $editCategoryTree.bind("loaded.jstree", function (e, data){
        $(treeNodes).each(function(key, value){
            if(typeof value !== 'undefined'){
                var categoryItem = $('.edit-category-tree ul > li.jstree-node#' + value.id + ' > a.jstree-anchor');
                var htmlVal = $(categoryItem).html();
                var customProductSpan = '<span class="icon icon-edit icon-lg pull-right customized-modal-trigger" data-id="'+ value.id +'"></span>';

                $(categoryItem).html(htmlVal + customProductSpan);
            }
        });

        var treeInstance = $(loadedTree).jstree(true);

        if(treeInstance != false && treeInstance != null){
            loadedTreeInstance = treeInstance.get_json('#');
        }
    });

    $editCategoryTree.bind("refresh.jstree", function(e, data) {
        $(treeNodes).each(function(key, value){
            if(typeof value !== 'undefined'){
                var categoryItem = $('.edit-category-tree ul > li.jstree-node#' + value.id + ' > a.jstree-anchor');
                var htmlVal = $(categoryItem).html();
                var customProductSpan = '<span class="icon icon-edit icon-lg pull-right customized-modal-trigger" data-id="'+ value.id +'"></span>';

                $(categoryItem).html(htmlVal + customProductSpan);
            }
        });

        hasCustom = true;

        var treeInstance = $(loadedTree).jstree(true);

        if(treeInstance != false && treeInstance != null){
            loadedTreeInstance = treeInstance.get_json('#');
        }
    });

    $editCategoryTree.bind("move_node.jstree", function (e, data) {

        var categoryInstance = data.instance;
        var currentTreeInstance = categoryInstance.get_json('#');

        var diff = [];
        $(currentTreeInstance).each(function(key, value){
           if(JSON.stringify(value) != JSON.stringify(loadedTreeInstance[key])){
                var subcategories = value.children;

                if(subcategories.length > 0){
                    subcategories.forEach(function(subcategory, index){
                        value.children[index].sortOrder = index;
                    });
                }

                value.sortOrder = key;
                diff.push(value);
           }
        });

        changes = diff;

        $(treeNodes).each(function(key, value){
            if(typeof value !== 'undefined'){
                var categoryItem = $('.edit-category-tree ul > li.jstree-node#' + value.id + ' > a.jstree-anchor');
                var htmlVal = $(categoryItem).html();
                var customProductSpan = '<span class="icon icon-edit icon-lg pull-right customized-modal-trigger" data-id="'+ value.id +'"></span>';

                var $modalTrigger = $(categoryItem).children(".customized-modal-trigger");

                if($modalTrigger.length == 0){
                    $(categoryItem).html(htmlVal + customProductSpan);
                }
            }
        });

        $(this).jstree("open_all");
    });

    $(".save-edit-trigger").click(function(){

        var $postData = [];

        changes.forEach(function(category){
            var $parentCategory = {
                    categoryId : category.id,
                    categoryName : category.text,
                    parentId : 0,
                    sortOrder : category.sortOrder,
                    subcategories : []
            };

            if(category.children.length > 0){
                category.children.forEach(function(subcategory){
                    var $subcategory = {
                        categoryId : subcategory.id,
                        categoryName : subcategory.text,
                        parentId : category.id,
                        sortOrder : subcategory.sortOrder
                    };

                    $parentCategory.subcategories.push($subcategory);
                })
            }

            $postData.push($parentCategory);
        });

        $.ajax({
            url: Routing.generate('merchant_sort_custom_categories'),
            type: 'POST',
            data: { categories : JSON.stringify($postData) },
            success: function(response) {
                if(response.isSuccessful){
                    $(".edit-category-tree-container").transition({
                        animation: "scale",
                        onComplete : function() {
                          $(".category-tree-container").transition({
                            animation: "scale",
                            interval:   500});
                        }
                    });

                    recreateTree();
                }
            }
        });
    });

    $(".edit-category-trigger").click(function(){
        $(".category-tree-container").transition({
            animation: "scale",
            onComplete : function() {
              $(".edit-category-tree-container").transition({
                animation: "scale",
                interval:   1300});
            }
        });
    });

    //Animation transition for edit category form hide
    $(".cancel-edit-trigger").click(function(){
        $(".edit-category-tree-container").transition({
            animation: "scale",
            onComplete : function() {
                $(".category-tree-container").transition({animation : "scale", interval : 500});
                $editCategoryTree.jstree(true).refresh();
            }
        });
    });

    $(document).on("click", ".add-new-category", function(){
        var $li = "";
        var $categories = [];

        resetCustomCategoryProductsInstance()
        
        $form.form("clear");
        $form.find(".parent-chooser").dropdown('restore defaults');
        $responseErrorBox.addClass("hidden");
        $responseErrorBox.html("");
        $currentCategory = null;
        $responseErrorBox.addClass("hidden").html("");
        $(".customized-modal").modal("show");

        $(".customized-modal").attr("data-action", "add");
        $("#user-products").html("");
        $("#custom-category-products").html("");

        $.ajax({
            url: Routing.generate('merchant_get_parent_categories'),
            type: 'GET',
            success: function(response) {
                if(response.isSuccessful){
                    var $li = "";
                    $parentCategoriesSelect.html("");

                    $li += "<option value='0'>Select a parent category</option>"  
                    response.data.forEach(function(value){
                        $li += "<option value='" + value.categoryId + "'>" + value.name + "</option>";
                    });

                    $parentCategoriesSelect.html($li);
                    $parentCategoriesSelect.dropdown();
                }
            }
        });

        $li = getAllProducts();
        $("#user-products").append($li).sortable();
    });

    $(document).on("click", "span.icon-edit.customized-modal-trigger", function(){
        $currentCategory = $(this).attr("data-id");
        var $li = "";
        
        $form.form("clear");
        $form.find(".parent-chooser").dropdown('restore defaults');
        $responseErrorBox.addClass("hidden").html("");
        $(".customized-modal").modal("show");

        var $categories = [];
        $(".customized-modal").attr("data-action", "update");
        $("#user-products").html("");
        $("#custom-category-products").html("");

        $.ajax({
            url: Routing.generate('merchant_get_category_details'),
            type: 'POST',
            data: {categoryId:$currentCategory},
            success: function(response) {
                if(response.isSuccessful){
                    var $li = "";
                    $parentCategoriesSelect.html("");

                    $li += "<option value='0'>Select a parent category</option>"  
                    response.data.parentCategories.forEach(function(value){
                        if($currentCategory != value.categoryId){
                            $li += "<option value='" + value.categoryId + "'>" + value.name + "</option>";
                        }
                    });

                    $parentCategoriesSelect.html($li);
                    $parentCategoriesSelect.dropdown();

                    //set form values
                    if(response.data.categoryDetails.parentId !== null){
                        setTimeout(function(){
                            $form.find(".parent-chooser").dropdown('set selected', response.data.categoryDetails.parentId);
                        }, 500);
                    }

                    $form.find("input[name='name']").val(response.data.categoryDetails.categoryName);

                    var $loadedUserProducts = response.data.categoryDetails.products;
                    var $container = document.createElement("div");

                    $("#custom-category-products").html("");
                    $customCategoryProducts = [];

                    $($loadedUserProducts).each(function(index, details){
                        $loadedUserProducts[index].id = parseInt(details.productId);
                        $loadedUserProducts[index].name = details.productName;
                    });

                    $loadedUserProducts.forEach(function(details){
                        var $productPosition = -1;
                        var $ctr = 0;

                        $.map($userProducts, function(value){
                            if(value.id == details.id){
                                $productPosition = $ctr;
                            }

                            $ctr++;
                        });

                        if($productPosition > -1){
                            $userProducts.splice($ctr, 1);
                            $customCategoryProducts.push(details); 

                            $li = createProductLi(details, false);
                            $($li).find(".icon").removeClass("add-to-custom").removeClass("icon-plus").addClass("remove-from-custom").addClass("icon-times");
                            $($container).append($li);
                        }
                    });

                    $("#custom-category-products").append($($container).children()).sortable();

                    //trigger list change
                    $("input[name='userProducts']").trigger("keyup");
                }
            }
        });

        $li = getAllProducts();
        $("#user-products").append($li).sortable();
    });

    $(document).on("keyup", "input[name='customCategoryProducts']", function(){
        
        var $container = document.createElement("div");
        var $products = $("#custom-category-products-container").data("user-products");
        var $keyword = $(this).val().trim();

        if($keyword != ""){
            $isSearching = true;
            if($customProductsBak.length === 0){
                $customProductsBak = $customCategoryProducts;
            }

            $("#custom-category-products").html("");
            $customCategoryProducts = [];

            $products.forEach(function(details){
                var $productPosition = -1;
                var $ctr = 0;

                $.map($userProducts, function(value){
                    if(value.id == details.id){
                        $productPosition = $ctr;
                    }

                    $ctr++;
                });

                if($productPosition == -1){
                    $customCategoryProducts.push(details); 
                    if(details.name.toLowerCase().indexOf($keyword) != -1){
                        $($container).append(createProductLi(details, true));
                    }
                }
            });

            $("#custom-category-products").append($($container).children()).sortable();
        }
        else if($keyword === "" && $isSearching === true){
            //searching is done 
            $customCategoryProducts = [];
            $customProductsBak.forEach(function(details){
                var $contains = false;

                $userProducts.forEach(function($product){
                    if($product.id == details.id){
                        $contains = true;
                    }
                });

                if(!$contains){
                    $customCategoryProducts.push(details); 
                    $($container).append(createProductLi(details, true));
                }
            });

            $customProductsBak = [];
            $isSearching = false;
            $list = $($container).children();
            $("#custom-category-products").html($list).sortable();
        }
    });

    $(document).on("keyup", "input[name='userProducts']", function(){
        var $container = document.createElement("div");

        var $products = $("#custom-category-products-container").data("user-products");
        var $keyword = $(this).val();
        $("#user-products").html("");

        $userProducts = [];

        $products.forEach(function(details){
            var $productPosition = -1;
            var $ctr = 0;

            $.map($customCategoryProducts, function(value){
                if(value.id == details.id){
                    $productPosition = $ctr;
                }

                $ctr++;
            });

            if($productPosition == -1){
                $userProducts.push(details); 

                $customCategoryProducts.forEach(function($value, $index){
                    if($value.id == details.id){
                        $customCategoryProducts.splice($index, 1);
                    }
                });

                $customProductsBak.forEach(function($value, $index){
                    if($value.id == details.id){
                        $customProductsBak.splice($index, 1);
                    }
                });

                if(details.name.toLowerCase().indexOf($keyword) != -1){
                    $($container).append(createProductLi(details, false));
                }
            }
        });

        $("#user-products").append($($container).children()).sortable();
    });

    $(".delete-category-modal-trigger").click(function(){
        $(".delete-category-modal").modal("show");

        $('.coupled').modal({
            allowMultiple: false
        });

        // Open modal when click the "Okay" button from the edit mobile number modal
        $('.success-delete-category-modal').modal('attach events', '.delete-category-modal .submit-to-success');
     });

    $(document).on("click", ".delete-category-tree .jstree-anchor", function(){
        
        var deleteCategoryTreeInstance = $('.delete-category-tree').jstree(true);
        var checkedIds = deleteCategoryTreeInstance.get_selected();

        if(checkedIds.length > 0){
            $(".delete-category-modal-trigger").removeAttr("disabled");
        }
        else{
            $(".delete-category-modal-trigger").attr("disabled", "disabled");
        }
    });

    $(".delete-category-modal .submit-to-success").on("click", function(){
        var deleteCategoryTreeInstance = $('.delete-category-tree').jstree(true);
        var checkedIds = deleteCategoryTreeInstance.get_selected();
        var $button = $(".delete-custom-category-button");

        if(checkedIds.length > 0){
            $.ajax({
                url: Routing.generate('merchant_delete_custom_categories'),
                type: 'POST',
                data: { categoryIds : JSON.stringify(checkedIds) },
                beforeSend: function(){
                    $button.html("<div class='ui active centered small inline inverted loader'>&nbsp;</div>").addClass("disabled");
                },
                success: function(response) {
                    if(response.isSuccessful){
                        recreateTree();
                    }
                },
                complete: function(){
                    $button.html("Submit").removeClass('disabled');
                }
            });
        }
        // deleteCategoryTreeInstance.delete_node(deleteCategoryTreeInstance.get_selected());
    });

    $(".ui.dropdown").dropdown();

    $("#custom-category-products").sortable({
        connectWith: ".connected-droppable",
        update: function(event, ui){
            var $customCategoryContainer = $("#custom-category-products");

            $customCategoryProducts = [];
            $customCategoryContainer.children("li").each(function($index, $li){
                var $product = $.parseJSON($($li).attr("data-product"));
                var $productPosition = -1;
                var $ctr = 0;

                $.map($userProducts, function(value){
                    if(value.id == $product.id){
                        $productPosition = $ctr;
                    }

                    $ctr++;
                });

                $customCategoryProducts.push($product);

                if($productPosition > -1){
                    $userProducts.splice($productPosition, 1);
                }

                $($li).find(".icon").removeClass("add-to-custom").removeClass("icon-plus").addClass("remove-from-custom").addClass("icon-times");
            });
        }
    }).disableSelection();

    $("#user-products").sortable({
        connectWith: ".connected-droppable",
        update: function(event, ui){
            var $userProductsContainer = $("#user-products");

            $userProductsContainer.children("li").each(function($index, $li){
                $($li).find(".icon").removeClass("remove-from-custom").removeClass("icon-times").addClass("add-to-custom").addClass("icon-plus");
            });
        }
    }).disableSelection();

    $("#custom-category-products").droppable({
        accept: ".connected-droppable#user-products li",
        drop: function(event, ui){
            $li = ui.helper;
            var $product = $.parseJSON($li.attr("data-product"));
            var $keyword = $("input[name='customCategoryProducts']").val();

            $userProducts.forEach(function($value, $index){
                if($value.id == $product.id){
                    $userProducts.splice($index, 1);
                }
            });

            $customCategoryProducts.push($product);

            if($keyword.trim() != ""){
                $customProductsBak.push($product);
            }

            $customCategoryProducts = [];
            $("#custom-category-products").children("li").each(function(index, product){
                var $dataProduct = $(product).attr("data-product");
                if(typeof $dataProduct == 'undefined'){
                    $customCategoryProducts.push($product);
                }
                else{
                    $dataProduct = $.parseJSON($dataProduct);
                    $customCategoryProducts.push($dataProduct);
                }
            });
        }
    });

    $("#user-products").droppable({
        accept: ".connected-droppable#custom-category-products li",
        drop: function(event, ui){
            $li = ui.helper;
            var $product = $.parseJSON($li.attr("data-product"));

            $customCategoryProducts.forEach(function($value, $index){
                if($value.id == $product.id){
                    $customCategoryProducts.splice($index, 1);
                }
            });

            $customProductsBak.forEach(function($value, $index){
                if($value.id == $product.id){
                    $customCategoryProducts.splice($index, 1);
                }
            });

            $userProducts.push($product);
        }
    });

    $(document).ready(function(){

        var $customCategoryFormRules = {
            fields: {
                name: {
                    identifier  : 'name',
                    rules: [
                      {
                        type   : 'empty',
                        prompt : 'Category name is required.'
                      }
                    ]
                }
            },
            onSuccess: function(){
                var $dataAction = $customizedModal.attr("data-action");

                switch($dataAction){
                    case "add":
                        addCustomCategory();
                        break;
                    case "update":
                        updateCustomCategory();
                        break;
                }

                return false;
            }
        };

        $form.form($customCategoryFormRules);
    });

    $(document).on("click", ".remove-from-custom",function(event){
        var $icon = $(this);
        removeFromCustom($icon);
    });

    $(document).on("click", ".add-to-custom", function(){
        var $icon = $(this);
        addToCustom($icon);
    });

    function updateCustomCategory(){

        var $categoryName = $form.form("get value", "name");
        var $parentId = $form.form("get value", "parent-categories");
        var $customCategoryContainer = $("#custom-category-products");
        var $products = [];

        $responseErrorBox.addClass("hidden");
        $responseErrorBox.html("");

        $productCollection = []; 
        if($isSearching){
            $productCollection = $customProductsBak;
        }
        else{
            $productCollection = $customCategoryProducts;
        }

        $productCollection.forEach(function(product){
            $products.push(product.id);
        });

        $products = JSON.stringify($products);

        var $postData = {
            categoryId : $currentCategory,
            categoryName : $categoryName,
            parentId : $parentId,
            products : $products
        };

        $.ajax({
            url: Routing.generate('merchant_update_custom_categories'),
            type: 'POST',
            data: $postData,
            beforeSend: function(){
                applyLoading($form);
            },
            success: function(response) {
                if(response.isSuccessful){
                    recreateTree();

                    $form.form("clear");
                    $form.find(".parent-chooser").dropdown('restore defaults');
                    $responseErrorBox.addClass("hidden");
                    $responseErrorBox.html("");
                    $(".success-customized-category-updated-message").modal("show");
                }
            },
            error: function(response) {
                var $responseJson = response.responseJSON;
                var $errors = $responseJson.data.errors;
                var $errorList = "<ul>";
                $errorList += "<li>" + $responseJson.message + "</li>"
                $errorList += "</ul>";

                $responseErrorBox.html($errorList);
                $responseErrorBox.removeClass("hidden");

                unloadButton($form);
            },
            complete: function(){
                unloadButton($form);
            }
        });
    }

    function addCustomCategory(){

        var $categoryName = $form.form("get value", "name");
        var $parentId = $form.form("get value", "parent-categories");
        var $customCategoryContainer = $("#custom-category-products");

        var $postData = {
            categoryName : $categoryName,
            parentId : $parentId,
            products : []
        };

        $responseErrorBox.addClass("hidden");
        $responseErrorBox.html("");

        $productCollection = []; 
        if($isSearching){
            $productCollection = $customProductsBak;
        }
        else{
            $productCollection = $customCategoryProducts;
        }

        $productCollection.forEach(function(product){
            $postData.products.push(product.id);
        });

        $postData.products = JSON.stringify($postData.products);

        $.ajax({
            url: Routing.generate('merchant_add_custom_categories'),
            type: 'POST',
            data: $postData,
            beforeSend: function(){
                applyLoading($form);
            },
            success: function(response) {
                if(response.isSuccessful){
                    recreateTree();

                    hasCustom = true;
                    $form.form("clear");
                    $form.find(".parent-chooser").dropdown('restore defaults');
                    $responseErrorBox.addClass("hidden");
                    $responseErrorBox.html("");
                    $(".success-new-customized-category-message").modal("show");
                }
            },
            error: function(response) {
                var $responseJson = response.responseJSON;
                var $errors = $responseJson.data.errors;
                var $errorList = "<ul>";
                $errorList += "<li>" + $responseJson.message + "</li>"
                $errorList += "</ul>";

                $responseErrorBox.html($errorList);
                $responseErrorBox.removeClass("hidden");
            },
            complete: function(){
                unloadButton($form);
            }
        });
    }

    function getAllProducts(){
        var $container = document.createElement("div");
        var $products = $("#custom-category-products-container").data("user-products");

        $userProducts = [];

        $products.forEach(function(details){
            $($container).append(createProductLi(details, false));
            $userProducts.push(details);
        });

        return $($container).children("li");
    }

    function resetCustomCategoryProductsInstance(){
        $customCategoryProducts = [];
    }

    function createProductLi($details, $isRemove){

        var $li = document.createElement("li");
        var $options = document.createElement("div");
        var $imageHolder = document.createElement("div");
        var $ellipsisOverflow = document.createElement("p");
        var $icon = document.createElement("i");
        var $image = document.createElement("img");

        $($li).addClass("draggable-product").attr("data-product", JSON.stringify($details)).attr("data-product-id", $details.id).attr("data-name", $details.name);
        
        $($options).addClass("options");
        $($imageHolder).addClass("image-holder image-product");
        $($ellipsisOverflow).addClass("ellipsis-overflow");

        if(!$isRemove){
            $($icon).addClass("icon icon-plus pull-left bold add-to-custom");
        }
        else{
            $($icon).addClass("icon icon-times pull-left bold remove-from-custom");
        }

        $($image).attr("src", $details.image).addClass("img-auto-place");

        $($ellipsisOverflow).append($details.name);

        $($imageHolder).append($image);
        $($options).append($icon);

        $($li).append($options).append($imageHolder).append($ellipsisOverflow);

        return $li;
    }

    function addToCustom($icon){
        var $li = $icon.parents("li").clone();
        var $product = $.parseJSON($li.attr("data-product"));
        var $keyword = $("input[name='customCategoryProducts']").val();

        $userProducts.forEach(function($value, $index){
            if($value.id == $product.id){
                $userProducts.splice($index, 1);
            }
        });

        $customCategoryProducts.push($product);

        if($keyword.trim() != ""){
            $customProductsBak.push($product);
        }

        $icon.parents("li").fadeOut(function(){
            $("#custom-category-products").append($li).fadeIn().sortable();
            $($li).find(".icon").removeClass("add-to-custom").removeClass("icon-plus").addClass("remove-from-custom").addClass("icon-times");
        });
    }

    function removeFromCustom($icon){
        var $li = $icon.parents("li").clone();
        var $product = $.parseJSON($li.attr("data-product"));

        var $productPosition = -1;

        var $ctr = 0;

        $customCategoryProducts.forEach(function($value, $index){
            if($value.id == $product.id){
                $customCategoryProducts.splice($index, 1);
            }
        });

        $customProductsBak.forEach(function($value, $index){
            if($value.id == $product.id){
                $customCategoryProducts.splice($index, 1);
            }
        });

        $userProducts.push($product);

        $icon.parents("li").fadeOut(function(){
            $($li).find(".icon").removeClass("remove-from-custom").removeClass("icon-times").addClass("add-to-custom").addClass("icon-plus");
            $("#user-products").append($li).fadeIn().sortable();
        });
    }

    function containsObject(obj, list) {
        var i;
        for (i = 0; i < list.length; i++) {
            if (list[i] === obj) {
                return true;
            }
        }

        return false;
    }

    function recreateTree(listing){
        var tree = [];

        $.ajax({
            url: Routing.generate('merchant_get_custom_categories_hierarchy'),
            type: 'GET',
            success: function(response) {
                if(response.isSuccessful){
                    var tree = [];

                    treeNodes = [];
                    response.data.forEach(function(category){
                        var categoryDetails = {
                            "id"        : category.categoryId,
                            "text"      : category.name,
                            "state"     : { "opened" : true },
                            "children"  : []
                        };

                        treeNodes[categoryDetails.id] = categoryDetails;
                        if(category.subcategories.length > 0){
                            category.subcategories.forEach(function(subcategory){
                                var subcategoryDetails = {
                                    "id"        : subcategory.categoryId,
                                    "text"      : subcategory.name,
                                    "state"     : { "opened" : true },
                                    "children"  : []
                                };

                                treeNodes[subcategoryDetails.id] = subcategoryDetails;
                                categoryDetails.children.push(subcategoryDetails);
                            });
                        }

                        tree.push(categoryDetails);
                    });

                    $categoryTree.jstree(true).settings.core.data = tree;
                    $editCategoryTree.jstree(true).settings.core.data = tree;
                    $deleteCategoryTree.jstree(true).settings.core.data = tree;
                    $deleteCategoryTree.jstree(true).settings.checkbox.three_state = false;

                    $categoryTree.jstree(true).refresh();
                    $editCategoryTree.jstree(true).refresh();
                    $deleteCategoryTree.jstree(true).refresh();
                }
            }
        });
    }

    function createTree(listing){
        var tree = [];

        $(listing).each(function(categoryKey, category){
            var $category = $(category);
            var categoryDetails = {
                "id"        : $category.attr("data-id"),
                "text"      : $category.attr("data-text"),
                "state"     : { "opened" : true },
                "children"  : []
            };

            var subcategories = $(category).find("ul > li.subcategory-item");

            treeNodes[categoryDetails.id] = categoryDetails;
            $(subcategories).each(function(subcategoryKey, subcategory){
                var $subcategory = $(subcategory);
                var subcategoryDetails = {
                    "id"        : $subcategory.attr("data-id"),
                    "text"      : $subcategory.attr("data-text"),
                    "state"     : { "opened" : true },
                    "children"  : []
                };

                treeNodes[subcategoryDetails.id] = subcategoryDetails;
                categoryDetails.children.push(subcategoryDetails);
            });

            tree.push(categoryDetails);
        });

        return tree;
    };
    
})(jQuery);