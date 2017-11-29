'use strict';

var FormValidator = require('./formValidator');
var FormErrors = require('./formErrors');
var React = require('react');
var Request = require('superagent');
var ClassNames = require('classnames');

var USER_TYPE_SELLER = 1;
var STORE_TYPE_RESELLER = 1;

var formValidator = new FormValidator();

var ForgotPasswordForm = React.createClass({

    getInitialState: function() {
        return {
            request: null,
            requestError: null,
            generalError: null,
            isLoading: false
        };
    },

    onChangeRequest: function(e){
        this.setState({
            request: e.target.value,
            requestError: formValidator.validateField(e.target.value, ['required'], null, null, 60)
        });
    },

    handleSubmit: function(e){
        e.preventDefault();

        var formObj = this;
        var request = this.refs.request.getDOMNode().value.trim();
        var grecaptcha = this.refs.grecaptcha.getDOMNode().value.trim();
        var isFormValid = formValidator.isFormValid(this.state, ['isLoading', 'generalError']);

        if(isFormValid !== true){
            this.setState(isFormValid);
        }

        var formData = {
            'user_forgot_password[request]' : request,
            'user_forgot_password[grecaptcha]' : grecaptcha,
            'user_forgot_password[_token]' : this.props.csrfToken
        };

        if(grecaptcha == ""){
            isFormValid = false;
            this.setState({generalError: ["Captcha is required."]});
        }
        else{
            this.setState({generalError: null});
        }

        if (!this.state.isLoading && isFormValid === true) {
            this.setState({isLoading: true});
            var reactThis = this;
            var url = (this.props.userType == USER_TYPE_SELLER && this.props.storeType == STORE_TYPE_RESELLER)? '/affiliate-program/forgot-password' : '/forgot-password'; 
            Request.post(url)
                .type('form')
                .send(formData)
                .end(function (err, res) {
                    reactThis.setState({isLoading: false});

                    if (!res.body.isSuccessful) {
                        formObj.setState({generalError:res.body.data.errors});
                        resetRecaptcha();
                    }
                    else {
                        if(res.body.data.type == "email"){
                            jQuery(".success.modal").modal("show");
                            resetRecaptcha();
                        }
                        else{
                            window.location.replace('/reset-password/verification-code');
                        }
                    }
                });
        }
    },

    render: function () {

        var requestError = (this.state.requestError != true && this.state.requestError != null)?
            <div className="form-error-prompt">{this.state.requestError}</div> : null;

        var generalError = this.state.generalError !== null?
            <div className="message-box red with-close-message">
                <FormErrors errors={this.state.generalError}/>
            </div>
            : null;

        var requestClass = (requestError == null)? "" : "form error";

        var loadingText = (this.state.isLoading == true)? "Please wait" : "Submit";

        return (
            <div className="forgot-password-form hidden">
                <form>
                    <div className="form">
                        {generalError}
                        <label htmlFor="">Forgot Password?</label>
                        <div className={requestClass}>
                            <input type="text" className="form-ui" onChange={this.onChangeRequest} name="request" ref="request" value={this.state.request}  placeholder="Enter your email or contact number here"/>
                            <input type="hidden" ref="grecaptcha" name="grecaptcha" />
                            {requestError}
                        </div>
                        <div id="g-recaptcha"></div>
                        <span className="form-ui-note">You will receive a confirmation email or SMS message where you can reset your password</span>
                    </div>
                    <div className="form" onClick={this.handleSubmit}>
                        <button type="submit" className="button purple block button-forgot-password-submit trigger-forgot-password-success-email-modal">
                            {loadingText}
                        </button>
                    </div>
                    <div className="horizontal-divider">
                        OR
                    </div>
                    <div className="form align-center">
                        <a className="forgot-password-hide-trigger">Back to sign in</a>
                    </div>
                </form>
            </div>
        );
    }
});

module.exports = ForgotPasswordForm;
