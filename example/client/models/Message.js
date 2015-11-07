var Message = {
    add: function (author, message) {
        var date = new Date();

        var messageBubble = $("<div/>", {class: "message bubble-left"}).appendTo('.message-thread');
        $("<label/>", {class: "message-user", text: author}).appendTo(messageBubble);
        $("<label/>", { class: "message-timestamp", text: date.getHours() + ':' + date.getMinutes()
        }).appendTo(messageBubble);
        $("<p/>", {text: message}).appendTo(messageBubble);
    },

    submit: function () {
        var messageToSubmit = document.getElementById('message-to-send');
        WebSocketRequest.sendToServer('message', 'submit', {message: messageToSubmit.value});
        messageToSubmit.value = '';
    }
};