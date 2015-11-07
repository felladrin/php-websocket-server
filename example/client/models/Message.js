var Message = {
    add: function (message) {
        var date = new Date();

        var messageBubble = $("<div/>", {class: "message bubble-left"}).appendTo('.message-thread');
        $("<label/>", {class: "message-user", text: "Unknown"}).appendTo(messageBubble);
        $("<label/>", { class: "message-timestamp", text: date.getHours() + ':' + date.getMinutes()
        }).appendTo(messageBubble);
        $("<p/>", {text: message}).appendTo(messageBubble);
    },

    submit: function () {
        var messageToSubmit = document.getElementById('message-to-send');
        WebSocketRequest.sendToServer('chat', 'submit-message', {message: messageToSubmit.value});
        messageToSubmit.value = '';
    }
};