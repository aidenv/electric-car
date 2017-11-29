var $nestedCategories = $("[data-nested-categories]").data("nested-categories");
var $flattenedHeirarchy;
var $keyword = "", $categoryTimeOut, $searchCollectionTimeOut, $renderTimeOut, $breadCrumbList = "";
var $columnCount = null, $breadcrumbCollection = null, $previousKeyword = null;
var $breadcrumb = $(document.createElement("ul"));
var $searchedCategoriesCollection = [], $searchResultsPath = [];
var $isLoaded = false, $ajaxLoading = false;
var $emptySearchDiv = $(".empty-category-search-results");
var $currentPage = 1;
var $modal = $("#modal-category-selection");

(function ($) {

	$(document).ready(function(){

        $modal.yiLoader();

        $(document).on('click', '#btn-select-category', function() {
            var $this = $(this);

            if(!$isLoaded){
                $("input[name='keyword']").val("");
                $("#list-breadcrumb").html("<li><a>All Categories</a></li>");

                if ($this.data('readonly')) {
                    return false;
                }

                $modal.find('#category-container').html('');
                addCategoryColumnAndRow(null, null, false);


                $("#submit-selected-category").attr("data-product-category", "");
                $isLoaded = true;
            }
            
            $modal.modal('show');

            instantiateCustomScroll();
        });

        $(document).on('click', '#category-container .child-category-row', function () {

            
            var $this = $(this);
            var $categoryId = parseInt($this.data('id'));
            var $value = $this.data('value');

            var $currentColumn = parseInt($this.parents(".category-column").data("col"));
            var $columns = $(".category-column");

            var $subcategories = [];

            $breadcrumb = $(document.createElement("ul"));

            $.each($columns, function($key, $val){
                var $column = $($val);
                var $colData = parseInt($column.data("col"));

                if($colData > $currentColumn){
                    $($column).remove();
                }
            });

            if($this.hasClass('has-children')){

                $.ajax({
                    url: Routing.generate('product_upload_get_child_category'),
                    type: 'POST',
                    data: {
                        categoryId : $categoryId
                    },
                    beforeSend: function(){
                        $modal.trigger('loader.start');
                    },
                    success: function(response) {
                        if(response.isSuccessful){
                            addCategoryColumnAndRow($categoryId, $value, false, response.data.categories);
                        }
                    },
                    complete: function(){
                        $modal.trigger('loader.stop');
                        instantiateCustomScroll();          
                    }
                });
            }

            $this.parents(".list-category-selection").find("a").removeClass("active");
            $this.addClass("active");

            $.map($(".list-category-selection") ,function($column, $index){
                var $category = $($column).find(".child-category-row.active").data("value");
                $breadcrumb.append(constructBreadcrumbItem($category.name, $category));
            });

            $breadcrumb.prepend(constructBreadcrumbItem("All Categories", null));

            $("#list-breadcrumb").html($breadcrumb.children());

            if(!$this.hasClass("has-children")){
                $("#submit-selected-category").attr("data-product-category", $value.id);
            }
            else{
                $("#submit-selected-category").attr("data-product-category", "");
            }

            instantiateCustomScroll();
        });

        $(document).on("click", ".category-item", function(){

            var $this = $(this);
            var $list = $this.find(".category-breadcrumb .breadcrumb");
            
            var $promise = repopulateModal($list);

            $modal.trigger("loader.start");

            $promise.done(function(){
                $modal.trigger("loader.stop");
            });
        });

		$(document).on("click", ".breadcrumb", function(){
            var $this = $(this);
            var $productCategory = $this.data("value");

            if($productCategory.hasOwnProperty("id")){
                refreshModalData($productCategory);
            }
            else{
                var $this = $(this);

                $("#list-breadcrumb").html("<li><a>All Categories</a></li>");

                if ($this.data('readonly')) {
                    return false;
                }

                $modal.find('#category-container').html('');
                addCategoryColumnAndRow(null, null, false);

                instantiateCustomScroll();
            }
		});

        $("#submit-selected-category").click(function(){
            var $breadcrumbs = $("#list-breadcrumb").children().clone();
            var $productCategory = $(this).attr("data-product-category");

            if($productCategory != "" && $productCategory != null){
                $("#inputCategory").val($productCategory);
                $("#main-breadcrumbs").html($breadcrumbs);
            }
            else{
                $("#inputCategory").val("");
                $("#main-breadcrumbs").html("");
            }
        });

        $(window).on("load", function() {
            $(".category-list, .category-finder-wrapper .search-result-container").mCustomScrollbar();
        });

        $(document).on("focusin", ".category-selector-search", function() {
            $(".category-finder-wrapper .dimmer").dimmer("show");
        });

        $(document).on("focusout", ".category-selector-search", function() {
            $previousKeyword = "";
            $(".category-finder-wrapper .dimmer").dimmer("hide");
        });

        $(document).on('click', '.list-category-selection > li > a', function() {
            $(this).parents(".category-group").find("a.active").removeClass("active");
            $(this).addClass("active");
        });

        $(document).on("keyup", "input[name='keyword']", function(){
            $searchedCategoriesCollection = [];
            $(".search-result-container").html("");
            $breadCrumbList = "";
            generateSearchResults();
        });

        $(document).on("keydown", "input[name='keyword']", function(){
            clearSearchedCategories();
        }); 

        $(document).on("focusin", "input[name='keyword']", function(){
            $searchedCategoriesCollection = [];
            $(".search-result-container").html("");
            $breadCrumbList = "";
            generateSearchResults();
        }); 

        $(".search-result-container").scroll(function() {
            var $resultHeight = $(".category-search-results").height();
            var $current = $(".search-result-container").scrollTop() + $(".search-result-container").height();
            
            if($current >= $resultHeight && $(".search-result-container").hasClass("scrollable")){
                generateSearchResults();
            }
        });
    });

    function repopulateModal($list){

        var $deferred = $.Deferred();
        var $promise = constructData($list);

        $breadcrumb = $(document.createElement("ul"));

        $promise.done(function($flattenedChildren){
            addCategoryColumnAndRow(null, null, false);
            $.each($list, function($index, $item){
                var $category = $($item).data("value");
                if($index > 0){
                    addCategoryColumnAndRow($category.id, $category, false, $flattenedChildren[$category.id]);
                    $(".child-category-row[data-id='"+$category.id+"']").addClass("active");
                    $breadcrumb.append(constructBreadcrumbItem($category.name, $category));
                    instantiateCustomScroll();  
                }
                else{
                    $breadcrumb.prepend(constructBreadcrumbItem("All Categories", null));
                }

                if(($index+1) == $list.length){
                    $("#list-breadcrumb").html($breadcrumb.children());

                    if(_.isObject($category) && $category.hasChildren == false){
                        $("#submit-selected-category").attr("data-product-category", $category.id);
                    }
                    else{
                        $("#submit-selected-category").attr("data-product-category", "");
                    }

                    $deferred.resolve();
                }
            });
        });

        return $deferred.promise();
    };

    function constructData($list){

        var $deferred = $.Deferred();
        var $flattenedChildren = {};
        
        $modal.find('#category-container').html('');
        $.each($list, function($index, $item){
            var $category = $($item).data("value");
            if($index > 0){
                $.ajax({
                    url: Routing.generate('product_upload_get_child_category'),
                    type: 'POST',
                    data: {
                        categoryId : $category.id
                    },
                    beforeSend: function(){
                    },
                    success: function(response) {
                        if(response.isSuccessful){
                            $flattenedChildren[$category.id] = response.data.categories;
                        }
                    },
                    complete: function(){  
                        if((_.keys($flattenedChildren).length+1) === $list.length){
                            $deferred.resolve($flattenedChildren);
                        }  
                    }
                });
            }
        });
                        
        return $deferred.promise();
    }

    function refreshModalData($productCategory){

        var $breadcrumbList = $("#list-breadcrumb li");
        var $categoryIndex;

        $flattenedHeirarchy = [];

        clearTimeOuts();

        $.each($breadcrumbList, function($index, $item){
            $flattenedHeirarchy.push($($item).find("a").data("value"));
        });

        $categoryIndex = _.findIndex($flattenedHeirarchy, $productCategory);

        $.each($(".category-finder-container .category-column"), function($index, $column){
            var $column = $($column);
            var $selectedColumn;
                
            $selectedColumn = $column.find(".child-category-row.active");

            if($index > $categoryIndex){
                $column.remove();
            }
            else if($index == $categoryIndex){
                $selectedColumn.removeClass("active");
            }

            if(($index+1) == $categoryIndex){
                if($selectedColumn.hasClass("has-children")){
                    $("#submit-selected-category").attr("data-product-category", "");
                }
            }
        });
        
        $breadcrumb = $(document.createElement("ul"));
        
        $.each($flattenedHeirarchy, function($index, $category){
            if($index <= $categoryIndex){
                if(!_.isObject($category)){
                    $breadcrumb.append(constructBreadcrumbItem("All Categories", null));
                }
                else{
                    $breadcrumb.append(constructBreadcrumbItem($category.name, $category));
                }
            }
        });

        $("#list-breadcrumb").html($breadcrumb.children());

        instantiateCustomScroll();
    }

    function instantiateCustomScroll(){
        $(".category-list").mCustomScrollbar();
    }

    function generateSearchResults(){
        $categoryTimeOut = setTimeout(function(){
            if(!$ajaxLoading){
                $ajaxLoading = true;
                $searchedCategoriesCollection = [];
                $keyword = $("input[name='keyword']").val();

                if($previousKeyword == null || $keyword != $previousKeyword){
                    $previousKeyword = $keyword;
                    $currentPage = 1;
                    $(".search-result-container").addClass("scrollable");
                }
                else if($keyword == $previousKeyword){
                    $currentPage++;
                }

                $.ajax({
                    url: Routing.generate('product_upload_search_category'),
                    type: 'POST',
                    data: {
                        keyword : $keyword,
                        page    : $currentPage
                    },
                    beforeSend: function(){
                        $modal.trigger('loader.start');
                    },
                    success: function(response) {
                        if(response.isSuccessful){

                            if(response.data.categories.length < 1){
                                $(".search-result-container").removeClass("scrollable");

                                if($currentPage == 1){
                                    console.log("empty");
                                    $(".empty-category-search-results .content").removeClass("hidden");
                                }
                            }
                            else{
                                var $breadcrumbList;
                                var $listContainer = $(document.createElement("div"));

                                $(".empty-category-search-results .content").addClass("hidden");
                                $(".search-result-container").addClass("scrollable")

                                $.each(response.data.categories, function($resultIndex, $searchResult){
                                    var $container = $(document.createElement("div"));
                                    
                                    $container.addClass("category-search-item category-item");
                                    $breadcrumb = $(document.createElement("ul"));
                                    $breadcrumb.addClass("list-unstyled category-breadcrumb");
                                    $breadcrumb.append(constructBreadcrumbItem("All Categories", null));

                                    $.each($searchResult, function($index, $category){
                                        $breadcrumb.append(constructBreadcrumbItem($category.name, $category));
                                    });

                                    $breadcrumb.find("li:last-child").addClass("match");      

                                    $container.append($breadcrumb);

                                    $breadCrumbList += $container.prop("outerHTML");          
                                });

                                $listContainer.addClass("category-search-results").html($breadCrumbList);
                                $(".search-result-container").html("").append($listContainer);
                            }
                        }
                    },
                    complete: function(){
                        $ajaxLoading = false;
                        $modal.trigger('loader.stop');
                        instantiateCustomScroll();          
                    }
                });
            }

        }, 1000);
    
        instantiateCustomScroll();
    }

    function clearSearchedCategories(){
        clearTimeOuts();
    }

    function clearTimeOuts(){

        $searchedCategoriesCollection = [];
        clearTimeout($categoryTimeOut);
        clearTimeout($searchCollectionTimeOut);
        clearTimeout($renderTimeOut);
    }

    function addCategoryColumnAndRow($parent, $categoryDetails, $isPrepend, $subcategories){
        var $categoryName = "Main Categories";
        var $columnContainer = $(document.createElement("td"));
        var $rows = $(document.createElement("td"));
        var $column = '';
        var $cancelAppend = false;

        if(typeof $subcategories == "undefined"){
            $subcategories = {};
        }
        
        if($parent == null){
            $.map($nestedCategories, function($category){
                $rows.append(constructCategoryRow($category));
            });
        }
        else{

            $categoryName = $categoryDetails.name;

            if(_.isEmpty($subcategories)){
                $cancelAppend = true;
            }

            $.map($subcategories, function($category){
                $rows.append(constructCategoryRow($category));
            });
        }

        if(!$cancelAppend){

            $column = constructCategoryColumn($categoryName, $rows);

            if($columnCount == null){
                $columnCount = 0;
            }
            else{
                $columnCount = parseInt($columnCount) + 1;
            }

            $columnContainer.append($column).addClass("category-column").attr("data-col", $columnCount);

            if($isPrepend){
                $modal.find('#category-container').prepend($columnContainer);
            }
            else{
                $modal.find('#category-container').append($columnContainer);
            }
        }
    }

    function constructCategoryRow($category){
        var $row =  '<li>' +
                        '<a class="child-category-row">' +
                            '<span class="name">' + $category.name + '</span>' +
                        '</a>' +
                    '</li>';

        $row = $($.parseHTML($row));

        $row.find(".child-category-row").attr("data-id", $category.id).attr("data-value", JSON.stringify($category));

        if($category.hasChildren){
            $row.find('.child-category-row').append('<i class="icon icon-angle-right pull-right"></i>').addClass('has-children');
        }

        $row.find(".child-category-row").attr("data-value", JSON.stringify($category));

        return $row;
    }

    function constructBreadcrumbItem($name, $category){
    	$categoryDetails = ($category != null) ? JSON.stringify($category):'';
        $li = $.parseHTML('<li><a class="breadcrumb">' + $name + '</a></li>');

        $($li).find(".breadcrumb").attr("data-value", $categoryDetails);
        return $li;
    }

    function constructCategoryColumn($title, $rows){
        
        return  '<div class="category-group">' +
                    '<div class="category-group-title">' +
                        $title +
                    '</div>' +
                    '<div class="category-list">' +
                        '<ul class="list-unstyled list-category-selection">' +
                            $rows.html() +
                        '</ul>' +
                    '</div>' +
                '</div>';
    }
})(jQuery);
