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
        Message.add('System', params['name'] + ' has been ranamed.');
    },

    actionLoadUserList: function (params) {
        params.forEach(function (user) {
            User.add(user['id'], user['name']);
        });
    }
};