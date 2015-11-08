var Message = {
    add: function (author, text, datetime) {
        var date = new Date();

        datetime = datetime || date.getHours() + ':' + date.getMinutes();

        var divClass = "message bubble-" + ((author == 'System') ? 'right' : 'left');

        var messageThread = $(".message-thread");

        var messageBubble = $("<div/>", {class: divClass}).appendTo(messageThread);
        $("<label/>", {class: "message-user", text: author}).appendTo(messageBubble);
        $("<label/>", {class: "message-timestamp", text: datetime}).appendTo(messageBubble);
        $("<p/>", {text: text}).appendTo(messageBubble);

        messageThread.scrollTop(1E10);
    },

    submit: function () {
        var messageToSubmit = document.getElementById('message-to-send');

        var messageValue = messageToSubmit.value.trim();

        if (messageValue.charAt(0) === '/')
        {
            WebSocketRequest.sendToServer('command', 'decode', {message: messageValue});
        }
        else
        {
            WebSocketRequest.sendToServer('message', 'submit', {message: messageValue});
        }

        messageToSubmit.value = '';
    }
};