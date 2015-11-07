var MessageController = {
    actionAdd: function (params) {
        Chat.addMessage(params.message)
    },

    actionRemove: function (params) {
        Chat.addMessage('Someone is trying to remove a message!');
    }
};