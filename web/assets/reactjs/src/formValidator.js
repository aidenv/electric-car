'use strict';

var React = require('react');

var FormValidator = React.createClass({

    // validate field
    validateField : function(field, rules, matchValue = null, min = null, max = null){

        var lenght = 0;
        var validationRules = {
            required: {
                pattern: /./,
                error: "This field is required"
            },
            requiredAlphaSpace:{
                pattern: /^[a-zA-Z]+([ -]*?[a-zA-Z]+)*$/,
                error: "This field contains invalid characters."
            },
            requiredNotWhitespace: {
                pattern: /\S/,
                error: "Whitespace is not allowed on this field."
            },
            positiveInteger: {
                pattern: /^\d*[1-9]\d*$/,
                error: "This field may only contain numbers." 
            },
            oneVarcharOneNumber: {
                pattern: /^(?=.*[0-9])(?=.*[a-zA-Z])([a-zA-Z0-9]+)$/,
                error: "This field should contain characters with atleast one number." 
            },
            positiveOrZeroInteger: {
                pattern: /^\d+$/,
                error: "This field can only contain numbers." 
            },
            integer: {
                pattern: /^-?\d+$/,
                error: "This field can only contain numbers."
            },
            decimal: {
                pattern: /^-?\d+(\.\d+)?$/,
                error: "This field can only contain decimals."
            },
            positiveDecimal: {
                pattern: /^\d+(\.\d+)?$/,
                error: "This field can only contain decimals."
            },
            email: {
                pattern: /^[\w\.\-+]+@([\w\-]+\.)+[a-zA-Z]+$/,
                error: "Invalid email address."
            },
        };

        lenght = field.length;

        if(min != null && lenght < min){
            return "This field needs a minimum of " + min + " characters."
        }

        if(max != null && lenght > max){
            return "This field can only have a maximum of " + max + " characters."
        }

        for(var index in rules){
            if(rules[index] == "matches"){
                if(field != matchValue){
                    return false;
                }
            }
            else{
                var rule = validationRules[rules[index]];
                var pattern = rule.pattern;
                var error = rule.error;

                if(!pattern.test(field)){
                    return error;
                }
            }
        }

        return true;
    },

    //check if form is ready to submit
    isFormValid : function(formData, exceptFields){
        var hasErrors = false;

        for(var field in formData){
            if(exceptFields.indexOf(field) == -1 && field.indexOf("Error") != -1){
                if(formData[field] != true){
                    hasErrors = true;
                }

                if(formData[field] == null){
                    formData[field] = "This field is required."
                }
            }
        }

        if(hasErrors){
            return formData;
        }

        return true;
    },

    render : function(){

    }
});

module.exports = FormValidator;
