// Require map template
var context = require('./mapTemplate');

// Add react components used in indext.html.twig
context.ReactTemplates['countdown'] = require('./../src/countdown');
context.ReactTemplates['hello'] = require('./../src/hello');
context.ReactTemplates['register'] = require ('./../src/register');
context.ReactTemplates['forgotPassword'] = require ('./../src/forgotPassword');
context.ReactTemplates['resetPassword'] = require ('./../src/resetPassword');
context.ReactTemplates['forgotPasswordCodeVerification'] = require ('./../src/forgotPasswordCodeVerification');
