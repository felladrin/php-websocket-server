//noinspection JSUnusedGlobalSymbols
var UserController = {
    actionConnected: function (params) {
        Message.add('System', params['name'] + ' has connected!');
        User.add(params['id'], params['name']);
    },

    actionDisconnected: function (params) {
        Message.add('System', params['name'] + ' has disconnected.');
        User.remove(params['id']);
    },

    actionIsTyping: function (params) {
        Message.add('System', params['name'] + ' is typing.');
    },

    actionRename: function (params) {
        User.remove(params['id']);
        User.add(params['id'], params['to']);
        Message.add('System', params['from'] + ' has been renamed to ' + params['to'] + '.');
    },

    actionLoadUserList: function (params) {
        params.forEach(function (user) {
            User.add(user['id'], user['name']);
        });
    },

    actionAlertUnknownCommand: function (params) {
        Message.add('System', 'Command /' + params['command'] + ' has not been implemented yet ;)');
    }
};