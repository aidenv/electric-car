'use strict';

var FormValidator = require('./formValidator');
var FormErrors = require('./formErrors');
var React = require('react');
var Request = require('superagent');
var ClassNames = require('classnames');

var formValidator = new FormValidator();

var RegisterForm = React.createClass({

    getInitialState: function() {
        return {
            firstName : null,
            lastName : null,
            password : null,
            confirmPassword : null,
            email : null,
            contactNumber : null,
            referral: null,
            isLoading:false,
            generalError: null,
            firstNameError: null,
            lastNameError: null,
            emailError: null,
            passwordError: null,
            confirmPasswordError: null,
            contactNumberError: null
        };
    },

    onChangeFirstName: function(e){
        this.setState({
            firstName: e.target.value,
            firstNameError: formValidator.validateField(e.target.value, ['required', 'requiredAlphaSpace'], null, null, 25)
        });
    },

    onChangeLastName: function(e){
        this.setState({
            lastName: e.target.value,
            lastNameError: formValidator.validateField(e.target.value, ['required', 'requiredAlphaSpace'], null, null, 25)
        });
    },

    onChangeEmail: function(e){
        this.setState({
            email: e.target.value,
            emailError: formValidator.validateField(e.target.value, ['required', 'email'], null, null, 60)
        });
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
            confirmPasswordError: formValidator.validateField(e.target.value, ['required', 'matches', 'oneVarcharOneNumber'], this.state.password)
        });
    },

    onChangeContactNumber: function(e){
        this.setState({
            contactNumber: e.target.value,
            contactNumberError: formValidator.validateField(e.target.value, ['required', 'integer'], null, 10, 60)
        });
    },

    onChangeReferral: function(e) {
        this.setState({
            referral: e.target.value
        });
    },

    handleSubmit: function (e) {

        var formObj = this;
        var isFormValid;
        
        formObj.setState({generalError:null});

        isFormValid = formValidator.isFormValid(this.state, ['isLoading', 'generalError']);
        if(isFormValid !== true){
            this.setState(isFormValid);
        }

        var formData = {
            'firstName' : this.state.firstName,
            'lastName' : this.state.lastName,
            'password' : this.state.password,
            'confirmPassword' : this.state.confirmPassword,
            'email' : this.state.email,
            'contactNumber' : this.state.contactNumber,
            'referralCode' : this.state.referral,
            'token' : this.props.token
        };


        if (!this.state.isLoading && isFormValid === true) {
            this.setState({isLoading: true});
            var reactThis = this;
            var actionUrl = this.props.actionUrl;
            var successUrl = this.props.successUrl;
            Request.post(actionUrl)
                .type('form')
                .send(formData)
                .end(function (err, res) {
                    reactThis.setState({isLoading: false});
                    if (!res.body.isSuccessful) {
                        formObj.setState({generalError:res.body.data.errors});
                    }
                    else {
                        window.location.replace(successUrl);
                    }
                });
        }
    },

    render: function () {
        var formClass = ClassNames({
            'form': true,
            'error': false
        });
        var firstNameError = (this.state.firstNameError != true && this.state.firstNameError != null)?
            <div className="form-error-prompt">{this.state.firstNameError}</div> : null;

        var lastNameError = (this.state.lastNameError != true && this.state.lastNameError != null)?
            <div className="form-error-prompt">{this.state.lastNameError}</div> : null;

        var emailError = (this.state.emailError != true && this.state.emailError != null)?
            <div className="form-error-prompt">{this.state.emailError}</div> : null;

        var passwordError = (this.state.passwordError != true && this.state.passwordError != null)?
            <div className="form-error-prompt">{this.state.passwordError}</div> : null;

        var confirmPasswordError = (this.state.confirmPasswordError != true && this.state.confirmPasswordError != null)?
            <div className="form-error-prompt">Password not match.</div> : null;

        var contactNumberError = (this.state.contactNumberError != true && this.state.contactNumberError != null)?
            <div className="form-error-prompt">{this.state.contactNumberError}</div> : null;

        var referralCodeElement = this.props.isRefferalCode ?
            <div>
                <input type="text" onChange={this.onChangeReferral} value={this.state.referral} className="form-ui" placeholder="Referral Code"/>
            </div>: null;

        var generalError = this.state.generalError !== null?
            <div className="message-box red with-close-message">
                <FormErrors errors={this.state.generalError}/>
            </div>
            : null;

        var firstNameClass = (firstNameError == null)? "col-xs-6" : "col-xs-6 form error"
        var lastNameClass = (lastNameError == null)? "col-xs-6" : "col-xs-6 form error"
        var emailClass = (emailError == null)? "" : "form error"
        var passwordClass = (passwordError == null)? "" : "form error"
        var confirmPasswordClass = (confirmPasswordError == null)? "" : "form error"
        var contactNumberClass = (contactNumberError == null)? "" : "form error"

        return (
            <div>
                <div>
                    <div className="row">
                        {generalError}
                        <div className={firstNameClass}>
                            <input type="text" onChange={this.onChangeFirstName} value={this.state.firstName} className="form-ui" placeholder="First name*"/>
                            {firstNameError}
                        </div>
                        <div className={lastNameClass}>
                            <input type="text" onChange={this.onChangeLastName} value={this.state.lastName} className="form-ui" placeholder="Last name*"/>
                            {lastNameError}
                        </div>
                    </div>
                </div>
                <div className={emailClass}>
                    <input type="email" onChange={this.onChangeEmail} value={this.state.email} className="form-ui" placeholder="Email Address*"/>
                    {emailError}
                </div>
                <div className={passwordClass}>
                    <input type="password" onChange={this.onChangePassword} value={this.state.password} className="form-ui" placeholder="Password*"/>
                    {passwordError}
                </div>
                <div className={confirmPasswordClass}>
                    <input type="password" onChange={this.onChangeConfirmPassword} value={this.state.confirmPassword} className="form-ui" placeholder="Confirm your password*"/>
                    {confirmPasswordError}
                </div>
                <div className={contactNumberClass}>
                    <input type="text" onChange={this.onChangeContactNumber} value={this.state.contactNumber} className="form-ui" placeholder="Contact Number*"/>
                    {contactNumberError}
                </div>
                { referralCodeElement }
                <div className="form" onClick={this.handleSubmit}>
                    <button className="button purple block">
                        { this.state.isLoading ? 'Please wait' :  'Register now' }                        
                    </button>
                </div>
            </div>
        );
    }
});

module.exports = RegisterForm;
