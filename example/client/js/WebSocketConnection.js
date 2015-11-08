var WebSocketConnection = {
    start: function(address) {
        var websocket = new WebSocket(address);

        websocket.onopen = function () {
            WebSocketRequest.websocket = websocket;
            WebSocketRequest.sendToServer('user', 'setup');
        };

        websocket.onmessage = function (e) {
            WebSocketRequest.decode(e.data);
        };

        websocket.onerror = function (error) {
            console.log('WebSocket Error: ' + error);
        };
    }
};