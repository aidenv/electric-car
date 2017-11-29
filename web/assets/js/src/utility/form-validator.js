var FormValidator = {

    // validate field
    validateField : function(field, rules, matchName, matchValue, min, max){
        var $locale = 'en';
        if(arguments[6]==='cn') {
            $locale = 'cn';
        }
        var length = 0;
        var validationRules = {
            validName:{
                // pattern: /^[a-zA-Z0-9_]+([ .,-]*?[a-zA-Z0-9_]+[.]?)*$/,
                pattern: /^[a-zA-z0-9 _\-'.,]*$/,
                error: "This field contains invalid characters.",
                errorCn: '此字段包含无效字符。'
            },
            requiredAlphaSpace:{
                pattern: /^[a-zA-Z]+([ -]*?[a-zA-Z]+)*$/,
                error: "This field contains invalid characters.",
                errorCn: '此字段包含无效字符。'
            },
            requiredNotWhitespace: {
                pattern: /\S/,
                error: "Whitespace is not allowed on this field.",
                errorCn: '空格不允许在此字段。'
            },
            positiveInteger: {
                pattern: /^\d*[1-9]\d*$/,
                error: "This field may only contain numbers.",
                errorCn: '此字段只能包含数字。'
            },
            oneVarcharOneNumber: {
                pattern: /^(?=.*[0-9])(?=.*[a-zA-Z])([a-zA-Z0-9!*_-]+)$/,
                error: "This field should contain alphabetical characters with at least one number.",
                errorCn: '这个字段应包含与至少一个数字字母字符。'
            },
            positiveOrZeroInteger: {
                pattern: /^\d+$/,
                error: "This field can only contain numbers.",
                errorCn: '此字段只能包含数字。'
            },
            integer: {
                pattern: /^-?\d+$/,
                error: "This field can only contain numbers.",
                errorCn: '此字段只能包含数字。'
            },
            decimal: {
                pattern: /^-?\d+(\.\d+)?$/,
                error: "This field can only contain decimals.",
                errorCn: '此字段只能包含小数。'
            },
            positiveDecimal: {
                pattern: /^\d+(\.\d+)?$/,
                error: "This field can only contain decimals.",
                errorCn: '此字段只能包含小数。'
            },
            email: {
                pattern: /^[\w\.\-+]+@([\w\-]+\.)+[a-zA-Z]+$/,
                error: "Invalid email address.",
                errorCn: '无效的邮件地址。'
            },
            required: {
                pattern: /./,
                error: "This field is required",
                errorCn: '这是必填栏'
            }
        };

        length = field.length;
        if($locale === 'cn') {
            if(min != null && length < min){
                return "不能少于 " + min + " 个字符。";
            }

            if(max != null && length > max){
                return "不能大于 " + max + " 个字符。";
            }

            for(var index in rules){
                if(rules[index] == "matches"){
                    if(field != matchValue){
                        return matchName + " 没有匹配项。";
                    }
                }
                else{
                    var rule = validationRules[rules[index]];
                    var pattern = rule.pattern;
                    var error = rule.errorCn;

                    if(!pattern.test(field)){
                        return error;
                    }
                }
            }
        }
        else {
            if(min != null && length < min){
                return "This field needs a minimum of " + min + " characters."
            }

            if(max != null && length > max){
                return "This field can only have a maximum of " + max + " characters."
            }

            for(var index in rules){
                if(rules[index] == "matches"){
                    if(field != matchValue){
                        return matchName + " not matched.";
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
        }

        return true;
    }
};
