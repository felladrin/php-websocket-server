//noinspection JSUnusedGlobalSymbols
var MessageController = {
    actionAdd: function (params) {
        Message.add(params.message)
    },

    actionRemove: function () {
        Message.add('Someone is trying to remove a message!');
    }
};