var UserController = {
    actionConnected: function (params) {
        Chat.addMessage('A new user (#' + params.id + ') has connected!');
    },

    actionDisconnected: function (params) {
        Chat.addMessage('User (#' + params.id + ') has disconnected.');
    },

    actionIsTyping: function (params) {
        Chat.addMessage('User (#' + params.id + ') is typing.');
    },

    actionRenamed: function (params) {
        Chat.addMessage('User (#' + params.id + ') has been ranamed to ' + params.name + '.');
    },

    actionWelcome: function (params) {

    }
};