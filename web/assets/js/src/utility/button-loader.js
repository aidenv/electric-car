function applyLoading($form){
	var $button = $form.find(".submit-button");
    $button.attr('disabled', true);
    $button.find(".text").hide();
    $button.append("<div class='ui active centered small inline inverted loader'>&nbsp;</div>").attr("disabled", true);
}

function unloadButton($form){
	var $button = $form.find(".submit-button");
    $button.find(".ui.loader").remove();
    $button.attr("disabled", false);
    $button.find(".text").show();
}
