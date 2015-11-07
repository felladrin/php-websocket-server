var Chat = {
    addMessage: function (message) {
        var li = document.createElement("li");
        var text = document.createTextNode(message);
        li.appendChild(text);
        document.getElementById('message-list').appendChild(li);
    },
    submitMessage: function () {
        var messageToSubmit = document.getElementById('message-to-send');
        WebSocketRequest.sendToServer('chat', 'submit-message', {message: messageToSubmit.value});
        messageToSubmit.value = '';
    }
};