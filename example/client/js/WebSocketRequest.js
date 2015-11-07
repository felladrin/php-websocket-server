var WebSocketRequest = {
    websocket: null,

    sendToServer: function(controller, action, parameters){
        if (this.websocket)
        {
            if (parameters)
            {
                this.websocket.send(JSON.stringify([
                    controller,
                    action,
                    parameters
                ]));
            }
            else
            {
                this.websocket.send(JSON.stringify([
                    controller,
                    action
                ]));
            }
        }
    },

    decode: function(receivedData) {
        try
        {
            var request = JSON.parse(receivedData);
            if (request[0] && request[1])
            {
                var controllerName = this.formatName('', request[0], 'Controller');
                var actionName = this.formatName('action', request[1]);
                var params = JSON.stringify(request[2]) || '{}';
                eval(controllerName + '.' + actionName + '(' + params + ')');
            }
        }
        catch(error)
        {
            console.log('WebSocket Error: ' + error.message);
        }
    },

    formatName: function(prefix, str, suffix) {
        str = str.replace(/-/g, ' ');
        str = str.toLowerCase().replace(/\b[a-z]/g, function(letter) {
            return letter.toUpperCase();
        });
        str = str.replace(/ /g, '');
        suffix = suffix || '';
        return prefix + str + suffix;
    }
};