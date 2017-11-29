'use strict';

var FormValidator = require('./formValidator');
var FormErrors = require('./formErrors');
var React = require('react');
var Request = require('superagent');
var ClassNames = require('classnames');

var formValidator = new FormValidator();

var ForgotPasswordCodeVerificationForm = React.createClass({

    getInitialState: function() {
        var timer = this.getTimeDiff();

        return {
            expiration: this.props.expiration,
            timer: timer,
            code: null,
            codeError: null,
            generalError: null,
            isLoading: false
        };
    },

    tick: function() {
        var date = new Date();
        var timeNow = Math.floor(date.getTime() / 1000);
        var expiration = this.state.expiration;
        var timer = this.getTimeDiff();

        if(expiration > timeNow){
            this.setState({timer: timer});
        }
        else{
            this.setState({
                timer: "00:00",
                generalError: ["Session has expired."]
            });
        }
    },

    getTimeDiff: function(){
        var date = new Date();
        var timeNow = Math.floor(date.getTime() / 1000);
        var expiration = this.props.expiration;
        var unixTimeDiff = expiration - timeNow;
        var minutes = Math.floor(unixTimeDiff / 60);
        var seconds = unixTimeDiff - minutes * 60;

        if(seconds < 10){
            seconds = "0" + seconds;
        }

        return minutes + ":" + seconds;
    },

    componentDidMount: function() {
        this.interval = setInterval(this.tick, 1000);
    },

    componentWillUnmount: function() {
        clearInterval(this.interval);
    },

    onChangeCode: function(e){
        this.setState({
            code: e.target.value,
            codeError: formValidator.validateField(e.target.value, ['required', 'positiveOrZeroInteger'], null, 6, 6)
        });
    },

    handleSubmit: function(e){
        e.preventDefault();

        var formObj = this;
        var isFormValid = formValidator.isFormValid(this.state, ['isLoading', 'generalError']);

        if(isFormValid !== true){
            this.setState(isFormValid);
        }

        var formData = {
            'user_forgot_password_code[code]' : this.state.code,
            'user_forgot_password_code[_token]' : this.props.csrf
        };

        if (!this.state.isLoading && isFormValid === true) {
            this.setState({isLoading: true});
            var reactThis = this;
            Request.post('/forgot-password-code-checker')
                .type('form')
                .send(formData)
                .end(function (err, res) {
                    reactThis.setState({isLoading: false});

                    if (!res.body.isSuccessful) {
                        formObj.setState({generalError:res.body.data.errors});
                    }
                    else {
                        var token = res.body.data.token;
                        window.location.replace('/reset-password?tk='+token);
                    }
                });
        }
    },

    render: function () {

        var codeError = (this.state.codeError != true && this.state.codeError != null)?
            <div className="form-error-prompt">{this.state.codeError}</div> : null;

        var generalError = this.state.generalError !== null?
            <div className="message-box red with-close-message">
                <FormErrors errors={this.state.generalError}/>
            </div>
            : null;

        var codeClass = (codeError == null)? "form mrg-bt-10" : "form mrg-bt-10 form error"

        return (
            <div className="login-tab-panel active" id="reset-password">
                <div className="login-form">
                    <div className="form">
                        {generalError}
                        <div className="light-color mrg-bt-10">
                            {this.props.timer}
                            Please enter the 6 digit code that was sent to your mobile phone.
                        </div>
                    </div>
                    <div className="form">
                        <h4 className="light align-center mrg-bt-20">
                            Time Left: <b>{this.state.timer}</b>
                        </h4>
                    </div>
                    <div className={codeClass}>
                        <input type="text" className="form-ui" placeholder="Enter verification code here" value={this.state.code} onChange={this.onChangeCode} />
                        {codeError}
                    </div>
                    <div className="form" onClick={this.handleSubmit}>
                        <button type="submit" className="button purple block"> Verify </button>
                    </div>
                </div>
            </div>
        );
    }
});

module.exports = ForgotPasswordCodeVerificationForm;
