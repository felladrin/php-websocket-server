//noinspection JSUnusedGlobalSymbols
var MessageController = {
    actionAdd: function (params) {
        Message.add(params['author'], params['text'], params['datetime']);
    },
    actionLoadHistory: function (params) {
        params.forEach(function (message) {
            Message.add(message['author'], message['text'], message['datetime'])
        });
    }
};