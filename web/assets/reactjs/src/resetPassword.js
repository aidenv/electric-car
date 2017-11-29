'use strict';

var FormValidator = require('./formValidator');
var FormErrors = require('./formErrors');
var React = require('react');
var Request = require('superagent');
var ClassNames = require('classnames');

var formValidator = new FormValidator();

var ResetPasswordForm = React.createClass({

    getInitialState: function() {
        return {
            password : null,
            confirmPassword : null,
            passwordError: null,
            confirmPasswordError: null,
            generalError: null,
            isLoading:false,
        };
    },

    onChangePassword: function(e){
        this.setState({
            password: e.target.value,
            passwordError: formValidator.validateField(e.target.value, ['required', 'oneVarcharOneNumber'], null, 8, 25)
        });
    },

    onChangeConfirmPassword: function(e){
        this.setState({
            confirmPassword: e.target.value,
            confirmPasswordError: formValidator.validateField(e.target.value, ['required', 'matches'], this.state.password)
        });
    },

    handleSubmit: function (e) {

        var formObj = this;
        var isFormValid = formValidator.isFormValid(this.state, ['isLoading', 'generalError']);

        if(isFormValid !== true){
            this.setState(isFormValid);
        }

        var formData = {
            'forgotPasswordToken': this.props.token,
            '_token': this.props.csrfToken,
            'password': this.state.password,
            'confirmPassword': this.state.confirmPassword
        };

        if (!this.state.isLoading && isFormValid === true) {
            this.setState({isLoading: true});
            var reactThis = this;
            Request.post('/confirm-reset-password')
                .type('form')
                .send(formData)
                .end(function (err, res) {
                    reactThis.setState({isLoading: false});

                    if (!res.body.isSuccessful) {
                        formObj.setState({generalError:res.body.data.errors});
                    }
                    else {
                        if(res.body.data.hasOwnProperty("userType") && res.body.data.userType == 1 && res.body.data.storeType == 1){
                            window.location.replace('/affiliate-program/login');
                        }
                        else{
                            window.location.replace('/login');
                        }

                    }
                });
        }
    },

    render: function () {

        var passwordError = (this.state.passwordError != true && this.state.passwordError != null)?
            <div className="form-error-prompt">{this.state.passwordError}</div> : null;

        var confirmPasswordError = (this.state.confirmPasswordError != true && this.state.confirmPasswordError != null)?
            <div className="form-error-prompt">Password not match.</div> : null;

        var generalError = this.state.generalError !== null?
            <div className="message-box red with-close-message">
                <FormErrors errors={this.state.generalError}/>
            </div>
            : null;

        var passwordClass = (passwordError == null)? "" : "form error"
        var confirmPasswordClass = (confirmPasswordError == null)? "" : "form error"

        return (
            <div>
                {generalError}
                <div className={passwordClass}>
                    <input type="password" onChange={this.onChangePassword} value={this.state.password} className="form-ui" placeholder="New Password"/>
                    {passwordError}
                </div>
                <div className={confirmPasswordClass}>
                    <input type="password" onChange={this.onChangeConfirmPassword} value={this.state.confirmPassword} className="form-ui" placeholder="Confirm new password"/>
                    {confirmPasswordError}
                </div>
                <div className="form" onClick={this.handleSubmit}>
                    <button className="button purple block">
                        { this.state.isLoading ? 'Please wait' :  'Reset Password' }
                    </button>
                </div>
            </div>
        );
    }
});

module.exports = ResetPasswordForm;
