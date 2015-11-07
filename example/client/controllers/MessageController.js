//noinspection JSUnusedGlobalSymbols
var MessageController = {
    actionAdd: function (params) {
        Message.add(params['author'], params['text']);
    }
};