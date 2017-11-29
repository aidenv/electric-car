/**
 * Number Format
 *
 * @param thisnumber
 * @param decimalPlace
 * @returns {string}
 */
function numberFormat(thisnumber, decimalPlace)
{

    if (typeof decimalPlace === "undefined") {
        decimalPlace = 2;
    }

    var number = parseFloat(thisnumber).toFixed(decimalPlace);
    var n = number.toString().split(".");
    n[0] = n[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");

    return n.join(".");
}

/**
 * Get Params in Url by Name
 *
 * @param name
 * @returns {string}
 */
function getParameterByName(name)
{
    name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
    var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
        results = regex.exec(location.search);
    return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
}

function array_key_exists(key, search) {

  if (!search || (search.constructor !== Array && search.constructor !== Object)) {
    return false;
  }

  return key in search;
}

/**
 * Get Date
 *
 * @param incrementDateByMonth
 * @returns {*}
 */
function getDate (incrementDateByMonth)
{

    if (typeof incrementDateByMonth == "undefined" || incrementDateByMonth === null || incrementDateByMonth === 0) {
        incrementDateByMonth = 0;
    }

    var date = new Date();
    var month = date.getMonth();

    if (incrementDateByMonth !== 0) {
        month = new Date(date.setMonth(date.getMonth() + incrementDateByMonth)).getMonth();
    }

    return (month + 1) + "/" + date.getDate() + "/" + date.getFullYear();
}

/**
 * Set cookie
 *
 * @param cname
 * @param cvalue
 * @param exdays
 */
function setCookie(cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays*24*60*60*1000));
    var expires = "expires="+d.toUTCString();
    document.cookie = cname + "=" + cvalue + "; " + expires + ";path=/";
}

/**
 * Get Cookie by name
 *
 * @param cname
 * @returns {*}
 */
function getCookie(cname) {
    var name = cname + "=";
    var ca = document.cookie.split(';');

    for (var i=0; i<ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1);

        if (c.indexOf(name) == 0) {
            return c.substring(name.length,c.length)
        }

    }

    return "";
}
/**
 * Remove cookie by name
 * @param name
 */
function deleteCookie (name)
{
    document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:01 GMT;';
}

function supplyReferralCode (domain)
{
    var referralCode = getCookie('referralCode');
    var urlReferralCode = getParameterByName('referralCode');

    if (urlReferralCode !== '') {
        deleteCookie('referralCode');
        setCookie('referralCode', urlReferralCode, 1);
        $('#domain-container').html('<img id="domain" src="' + domain + '/set-cookie/' + urlReferralCode + '">');
        $("form[name='register']").find("input[name='referralCode']").val(urlReferralCode);
    }
    else if (referralCode !== '') {
        $("form[name='register']").find("input[name='referralCode']").val(referralCode);
    }

}

/**
 * Resize image
 *
 * @param $this
 * @param file
 * @param maxWidth
 * @param maxHeight
 */
function resizeImage ($this, file, maxWidth, maxHeight)
{
    var dataurl = null;
    var img = document.createElement("img");
    var reader = new FileReader();

    reader.onload = function(e)
    {
        img.src = e.target.result;

        img.onload = function () {
            var canvas = document.createElement("canvas");
            var ctx = canvas.getContext("2d");
            ctx.drawImage(img, 0, 0);

            var width = img.width;
            var height = img.height;

            if (width > height) {
                if (width > maxWidth) {
                    height *= maxWidth / width;
                    width = maxWidth;
                }
            }
            else {
                if (height > maxHeight) {
                    width *= maxHeight / height;
                    height = maxHeight;
                }
            }
            canvas.width = width;
            canvas.height = height;
            var ctx = canvas.getContext("2d");
            ctx.drawImage(img, 0, 0, width, height);

            dataurl = canvas.toDataURL("image/jpeg");
            $this.val(dataurl);
        }
    };

    reader.readAsDataURL(file);
}
