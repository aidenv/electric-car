(function ($) {

    $(document).ready(function () {

        $(document).on('keypress', '#txt-search-keyword', function (e) {

            if (e.keyCode === 13) {
                searchSeller ();
            }

        });

        $(document).on('click', '#btn-search', function () {
            searchSeller ();
        });

        //Single selection select box
        $(".single.selection").dropdown();

        displayDataInUrl ();
    });

    function displayDataInUrl ()
    {
        var searchKeyword = getParameterByName('searchKeyword');
        var userApplicationType = getParameterByName('userApplicationType');

        $('#txt-search-keyword').val(searchKeyword);
        $('#drop-down-user-type').dropdown('set selected', userApplicationType);

    }

    /**
     * Search filter for seller
     * @returns {boolean}
     */
    function searchSeller ()
    {
        var searchKeyword = $('#txt-search-keyword').val().trim();
        var userApplicationType = $('#drop-down-user-type').val().trim();
        var resourceId =  $('#drop-down-resource').val().trim();
        var params = '';

        if (searchKeyword !== '') {
            params += '?searchKeyword=' + searchKeyword;
        }

        if (userApplicationType !== '') {
            params += (params === '' ? '?' : '&') + 'userApplicationType=' + userApplicationType;
        }
        
        if (resourceId !== '') {
            params += (params === '' ? '?' : '&') + 'resourceId=' + resourceId;
        }

        window.location = location.protocol + '//' + location.host + location.pathname + params;
    }

    //Popup Quick Info
    $('.quick-info a.action').popup({
        on    : 'click',
        position : 'left center',
    });


})(jQuery);
