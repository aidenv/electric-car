'use strict';

var React = require('react');

var Error = React.createClass({

    render : function(){

        return(
            <li>{this.props.details}</li>
        );
    }
});

var FormErrors = React.createClass({

    render : function(){
        var errors = this.props.errors.map(function(error, i){
            return <Error details={error} />
        });

        return (
            <ul>{errors}</ul>
        );
    }
});

module.exports = FormErrors;
