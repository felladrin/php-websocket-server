//noinspection JSUnusedGlobalSymbols
var UserController = {
    actionConnected: function (params) {
        Message.add('System', 'A new user (#' + params.id + ') has connected!');
        User.add(params.id, "User" + params.id, params.id + "@users.com");
    },

    actionDisconnected: function (params) {
        Message.add('System', 'User (#' + params.id + ') has disconnected.');
        User.remove(params.id);
    },

    actionIsTyping: function (params) {
        Message.add('System', 'User (#' + params.id + ') is typing.');
    },

    actionRenamed: function (params) {
        Message.add('System', 'User (#' + params.id + ') has been ranamed to ' + params.name + '.');
    }
};