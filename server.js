var express 	= require('express');
var http        = require('http');
var https       = require('https');
var fs 		    = require('fs');
var redis 		= require("redis");
var yamljs      = require('yamljs');
var request     = require('request');
var app         = express();
var env         = app.get('env');

var config      = yamljs.load(__dirname + '/app/config/parameters.yml');
var parameters  = config.parameters;

var NODE_HOST   = parameters.node_internal;
var NODE_PORT   = parameters.node_messaging_port;
var REDIS_HOST 	= parameters.redis_host;
var REDIS_PORT	= parameters.redis_port;

if(parameters.protocol == "http"){
    var server = http.createServer(app).listen(NODE_PORT, NODE_HOST);
}
else{
    if(env == "development"){
        process.env.NODE_TLS_REJECT_UNAUTHORIZED = "0";
    }

    var sslOptions  = {
        key: fs.readFileSync(parameters.server_key_location),
        cert: fs.readFileSync(parameters.server_crt_location),
        requestCert: true,
        rejectUnauthorized: false
    };

    var server = https.createServer(sslOptions, app).listen(NODE_PORT, NODE_HOST);
}

console.log('Express server listening on %s:%s', NODE_HOST, NODE_PORT);
console.log('environment[%s] - protocol[%s]', env, parameters.protocol);

var io = require('socket.io').listen(server);
var clients = [];

var messagingSubscriber = redis.createClient(REDIS_PORT, REDIS_HOST);
var contactSubscriber = redis.createClient(REDIS_PORT, REDIS_HOST);
var statusSubscriber = redis.createClient(REDIS_PORT, REDIS_HOST);

var messageHandler = function(channel, jsonString){
    var data = JSON.parse(jsonString);
    if(channel == "new_message"){
        io.to(data.namespace).emit('update_message', data);
    }
    else if(channel == "update_head"){
        io.to(data.namespace).emit('resort_head', data);
    }
    else if(channel == "message_seen"){
        io.to(data.namespace).emit('update_message_status', data);
    }
    else if(channel == "unread_messages"){
        io.to(data.namespace).emit('update_unread_messages', data);
    }
};

var contactHandler = function(channel, jsonString){
    var data = JSON.parse(jsonString);
    if(channel == "new_contact"){
        io.to(data.namespace).emit('update_contacts', data);
    }
};

var statusHandler = function(channel, jsonString){
    var data = JSON.parse(jsonString);

    if(channel == "account_online"){
        io.to(data.namespace).emit('contact_online', data);
    }
    else if(channel == "account_offline"){
        io.to(data.namespace).emit('contact_offline', data);
    }
};

messagingSubscriber.subscribe("new_message");
messagingSubscriber.subscribe("update_head");
messagingSubscriber.subscribe("message_seen");
messagingSubscriber.subscribe("unread_messages");

contactSubscriber.subscribe("new_contact");

statusSubscriber.subscribe("account_online");
statusSubscriber.subscribe("account_offline");

messagingSubscriber.on("message", messageHandler);
contactSubscriber.on("message", contactHandler);
statusSubscriber.on("message", statusHandler);

io.sockets.on("connection", function(socket) {

    socket.on("disconnect", function(){
        var rooms = io.sockets.in(socket.room).adapter.rooms;

        clients.forEach(function(client){
            if(!rooms.hasOwnProperty(client)){
                disconnectDevice(client);
                clients.splice(clients.indexOf(client), 1);
            }
        });
    });

    socket.on("join room", function (room) {
        socket.join(room);
    });

    socket.on("subscribe socket", function (token) {
        socket.join(token);

        if(clients.indexOf(token) == -1){
            clients.push(token);
            connectDevice(token);
        }
    });
});

function connectDevice(token){
    request.post({
        url   :parameters.frontend_hostname + "/device/connectDevice",
        form  :{
            token : token
        } 
    }, function(err, response){
        if(err){
            return console.error("Failed to connect device.");
        }
    });
}

function disconnectDevice(token){
    setTimeout(function(){
        if(clients.indexOf(token) < 0){
            request.post({
                url   :parameters.frontend_hostname + "/device/disconnectDevice",
                form  :{
                    token : token
                } 
            }, function(err, response){
                if(err){
                    return console.error("Failed to connect device.");
                }
            });
        }
    }, 90000);
}