<?php

abstract class WebSocketServer
{
    /**
     * Host to bind to.
     * @var string
     */
    protected $host;

    /**
     * Port number where to bind to.
     * @var integer
     */
    protected $port;

    /**
     * Array of connected clients.
     * @var WebSocketClient[]
     */
    protected $clients = array();

    /**
     * The master socket acting as server.
     * @var resource
     */
    protected $socket;

    /**
     * Array of all connected sockets, includes the master.
     * @var resource[]
     */
    protected $sockets = array();

    protected $debugMode = false;

    protected $bufferSize = 4096;

    const FIN = 128;
    const MASK = 128;
    const OPCODE_CONTINUATION = 0;
    const OPCODE_TEXT = 1;
    const PAYLOAD_LENGTH_16 = 126;
    const PAYLOAD_LENGTH_63 = 127;

    /** @var self|null */
    protected static $instance = null;

    /**
     * @return self
     */
    public static function Instance()
    {
        if (is_null(static::$instance))
        {
            static::$instance = new static();
        }

        return static::$instance;
    }

    protected function __construct() { }

    protected function __clone() { }

    protected function __wakeup() { }

    /**
     * Called when a client sends a message to the server.
     *
     * @param WebSocketClient $sender Client that sent the message
     * @param string $message Sent message
     * @return mixed
     */
    abstract protected function onMessageRecieved(WebSocketClient $sender, $message);

    /**
     * Called when a new client connects to the server.
     *
     * @param WebSocketClient $client Client that connected
     */
    abstract protected function onClientConnected(WebSocketClient $client);

    /**
     * Called when a  client disconnects from the server.
     *
     * @param WebSocketClient $client Client that disconnected
     */
    abstract protected function onClientDisconnected(WebSocketClient $client);

    /**
     * Sets the host to use.
     *
     * Only has effect before starting the server.
     *
     * @param string $host Host to bind to
     */
    public function setHost($host)
    {
        $this->host = $host;
    }

    /**
     * Sets the host port to use.
     *
     * Only has effect before starting the server.
     *
     * @param integer $port Port to bind to
     */
    public function setPort($port)
    {
        $this->port = $port;
    }

    /**
     * Returns array of connected clients
     *
     * @return WebSocketClient[] Array of connected clients
     */
    public function getClients()
    {
        return $this->clients;
    }

    /**
     * Returns the number of connected clients
     *
     * @return integer Number of clients
     */
    public function getClientCount()
    {
        return count($this->clients);
    }

    /**
     * Returns last socket error as an object.
     *
     * The object is a basic stdClass with parameters:
     * - code: the code of the error
     * - message: translated error code as message
     *
     * @param resource $socket Optional socket resource
     * @return stdClass Error as stdClass instance with fields code and message
     */
    public static function getLastError($socket = null)
    {
        $lastErrorCode = socket_last_error($socket);
        $lastErrorMessage = socket_strerror($lastErrorCode);

        $error = new stdClass();
        $error->code = $lastErrorCode;
        $error->message = $lastErrorMessage;

        return $error;
    }

    /**
     * Starts the server by binding to a port
     *
     * @param string $host Socket host to bind to, defaults to localhost
     * @param integer $port Port to bind to, defaults to 8080
     * @param integer $maxConnections Max number of incoming backlog connections
     * @throws Exception If something goes wrong
     */
    public function start($host = '127.0.0.1', $port = 8080, $maxConnections = SOMAXCONN)
    {
        set_time_limit(0);
        ob_implicit_flush();

        $this->host = $host;
        $this->port = $port;

        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        if ($this->socket === false)
        {
            $error = self::getLastError();

            throw new Exception('Creating socket failed: ' . $error->message . ' [' . $error->code . ']');
        }

        $this->sockets[] = $this->socket;

        if (socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1) === false)
        {
            $error = self::getLastError($this->socket);

            throw new Exception('Setting socket option to reuse address to true failed: ' . $error->message . ' [' . $error->code . ']');
        }

        if (socket_bind($this->socket, $this->host, $this->port) === false)
        {
            $error = self::getLastError($this->socket);

            throw new Exception('Binding to port ' . $this->port . ' on host "' . $this->host . '" failed: ' . $error->message . ' [' . $error->code . ']');
        }

        if (socket_listen($this->socket, $maxConnections) === false)
        {
            $error = self::getLastError($this->socket);

            throw new Exception('Starting to listen on the socket on port ' . $this->port . ' and host "' . $this->host . '" failed: ' . $error->message . ' [' . $error->code . ']');
        }

        $this->log(get_called_class() . " started listening connections on {$this->host}:{$this->port}");

        $this->run();
    }

    /**
     * Runs the server as an infinite loop
     *
     * @throws Exception
     * @return void
     */
    protected function run()
    {
        while (true)
        {
            $changedSockets = $this->sockets;

            $write = $except = $tv = $tvu = null;

            $result = socket_select($changedSockets, $write, $except, $tv, $tvu);

            if ($result === false)
            {
                socket_close($this->socket);

                $error = self::getLastError($this->socket);

                throw new Exception('Checking for changed sockets failed: ' . $error->message . ' [' . $error->code . ']');
            }

            foreach ($changedSockets as $socket)
            {
                if ($socket == $this->socket)
                {
                    $newSocket = socket_accept($this->socket);

                    if ($newSocket !== false)
                    {
                        $this->connectClient($newSocket);
                    }
                    else
                    {
                        $error = self::getLastError($this->socket);

                        trigger_error('Failed to accept incoming client: ' . $error->message . ' [' . $error->code . ']', E_USER_WARNING);
                    }
                }
                else
                {
                    $client = $this->getClientBySocket($socket);

                    if (!isset($client))
                    {
                        trigger_error('Failed to match given socket to client', E_USER_WARNING);

                        socket_close($socket);

                        continue;
                    }

                    $buffer = '';
                    $message = '';

                    $bytes = @socket_recv($socket, $buffer, 4096, 0);

                    if ($bytes === false)
                    {
                        $error = self::getLastError($this->socket);

                        trigger_error('Failed to receive data from client #' . $client->id . ': ' . $error->message . ' [' . $error->code . ']', E_USER_WARNING);

                        $this->disconnectClient($client->socket);

                        continue;
                    }

                    $len = ord($buffer[1]) & 127;

                    $masks = null;
                    $data = null;

                    if ($len === 126)
                    {
                        $masks = substr($buffer, 4, 4);
                        $data = substr($buffer, 8);
                    }
                    else if ($len === 127)
                    {
                        $masks = substr($buffer, 10, 4);
                        $data = substr($buffer, 14);
                    }
                    else
                    {
                        $masks = substr($buffer, 2, 4);
                        $data = substr($buffer, 6);
                    }

                    for ($index = 0; $index < strlen($data); $index++)
                    {
                        $message .= $data[$index] ^ $masks[$index % 4];
                    }

                    if ($bytes == 0)
                    {
                        $this->disconnectClient($socket);
                    }
                    else
                    {
                        if ($client->state == WebSocketClient::STATE_OPEN)
                        {
                            $client->lastRecieveTime = time();

                            $this->debug('< [' . $client->id . '] ' . $message);

                            $this->onMessageRecieved($client, $message);
                        }
                        else if ($client->state == WebSocketClient::STATE_CONNECTING)
                        {
                            $client->performHandshake($buffer);
                        }
                    }
                }
            }
        }
    }

    /**
     * Connects a client by socket.
     *
     * Creates a new instance of the WebSocketClient class and adds it to the list
     * of clients. Also adds the socket to the list of sockets.
     *
     * @param resource $socket Socket to use
     */
    protected function connectClient($socket)
    {
        $client = new WebSocketClient($this, $socket);

        $this->clients[] = $client;
        $this->sockets[] = $socket;

        $this->debug('+ [' . $client->id . '] connected');

        $this->onClientConnected($client);
    }

    /**
     * Disconnects a client by socket.
     *
     * @param resource $clientSocket Socket to use
     */
    public function disconnectClient($clientSocket)
    {
        foreach ($this->sockets as $socketKey => $socket)
        {
            if ($socket === $clientSocket)
            {
                socket_close($clientSocket);

                unset($this->sockets[$socketKey]);
            }
        }

        foreach ($this->clients as $clientKey => $client)
        {
            if ($client->socket === $clientSocket)
            {
                $this->debug('- [' . $client->id . '] client disconnected');

                $this->onClientDisconnected($client);

                $this->clients[$clientKey]->state = WebSocketClient::STATE_CLOSED;

                unset($this->clients[$clientKey]);
            }
        }
    }

    /**
     * Returns client by socket reference.
     *
     * @param resource $socket Socket resource
     * @return WebSocketClient The client on the socket or null if not found
     */
    protected function getClientBySocket($socket)
    {
        foreach ($this->clients as $client)
        {
            if ($client->socket == $socket)
            {
                return $client;
            }
        }

        return null;
    }

    /**
     * Sends a message to given socket
     *
     * @param resource $socket Socket to send the message to
     * @param mixed $message Message to send
     * @return bool
     */
    public function send($socket, $message)
    {
        $opcode = self::OPCODE_TEXT;

        if (is_object($message))
        {
            $message = (string)$message;
        }

        $messageLength = strlen($message);

        $frameCount = ceil($messageLength / $this->bufferSize);

        if ($frameCount == 0)
        {
            $frameCount = 1;
        }

        $maxFrame = $frameCount - 1;
        $lastFrameBufferLength = ($messageLength % $this->bufferSize) != 0 ? ($messageLength % $this->bufferSize) : ($messageLength != 0 ? $this->bufferSize : 0);

        for ($i = 0; $i < $frameCount; $i++)
        {
            $fin = $i != $maxFrame ? 0 : self::FIN;
            $opcode = $i != 0 ? self::OPCODE_CONTINUATION : $opcode;

            $bufferLength = $i != $maxFrame ? $this->bufferSize : $lastFrameBufferLength;

            if ($bufferLength <= 125)
            {
                $payloadLength = $bufferLength;
                $payloadLengthExtended = '';
                $payloadLengthExtendedLength = 0;
            }
            else if ($bufferLength <= 65535)
            {
                $payloadLength = self::PAYLOAD_LENGTH_16;
                $payloadLengthExtended = pack('n', $bufferLength);
                $payloadLengthExtendedLength = 2;
            }
            else
            {
                $payloadLength = self::PAYLOAD_LENGTH_63;
                $payloadLengthExtended = pack('xxxxN', $bufferLength);
                $payloadLengthExtendedLength = 8;
            }

            $buffer = pack('n', (($fin | $opcode) << 8) | $payloadLength) . $payloadLengthExtended . substr($message, $i * $this->bufferSize, $bufferLength);

            $left = 2 + $payloadLengthExtendedLength + $bufferLength;

            do
            {
                $sent = @socket_send($socket, $buffer, $left, 0);
                if ($sent === false)
                {
                    return false;
                }

                $left -= $sent;
                if ($sent > 0)
                {
                    $buffer = substr($buffer, $sent);
                }
            }
            while ($left > 0);
        }

        $client = $this->getClientBySocket($socket);

        $clientId = -1;

        if ($client != null)
        {
            $client->lastSendTime = time();
            $clientId = $client->id;
        }

        $this->debug('> [' . $clientId . '] ' . $message);

        return true;
    }

    /**
     * Sends a message to all connected sockets.
     *
     * @param mixed $message Message to send
     * @return bool
     */
    public function broadcast($message)
    {
        foreach ($this->clients as $client)
        {
            $this->send($client->socket, $message);
        }
    }

    /**
     * Logs a message to console.
     *
     * @param string $message Message to log
     */
    public function log($message)
    {
        echo '[' . gmdate('Y-m-d H:i:s') . ' GMT] ' . $message . PHP_EOL;
    }

    /**
     * Logs a message to console if running on Debug Mode.
     *
     * @param string $message Message to log
     */
    public function debug($message)
    {
        if ($this->debugMode)
        {
            $this->log($message);
        }
    }

}

class WebSocketClient
{
    /**
     * Number of instances created.
     *
     * @var integer
     */
    static $nextId = 0;

    /**
     * Reference to server that created the client.
     *
     * @var WebSocketServer
     */
    public $server;

    /**
     * Client id.
     *
     * This starts from one and is incremented for every connecting user.
     *
     * @var integer
     */
    public $id;

    /**
     * Client socket.
     *
     * @var resource
     */
    public $socket;

    /**
     * Client state.
     *
     * One of WebSocketClient::STATE_.. constants.
     *
     * @var integer
     */
    public $state;

    /**
     * The ip of the client.
     *
     * @var string
     */
    public $ip;

    /**
     * The port of the client.
     *
     * @var integer
     */
    public $port;

    /**
     * The time data was last recieved from the client.
     *
     * @var integer
     */
    public $lastRecieveTime = 0;

    /**
     * Last time data was sent to this client.
     *
     * @var integer
     */
    public $lastSendTime = 0;

    /**
     * Any data associated with the user.
     *
     * @var mixed
     */
    public $data = array();

    /**
     * User is connecting, handshake not yet performed.
     */
    const STATE_CONNECTING = 0;

    /**
     * Connection is valid.
     */
    const STATE_OPEN = 1;

    /**
     * Connection has been closed.
     */
    const STATE_CLOSED = 2;

    /**
     * Constructor, sets the server that spawned the client and the socket.
     *
     * @param WebSocketServer $server Parent server
     * @param resource $socket User socket
     * @param integer $state Initial state
     */
    public function __construct(WebSocketServer $server, $socket, $state = self::STATE_CONNECTING)
    {
        self::$nextId++;

        $this->server = $server;
        $this->id = self::$nextId;
        $this->socket = $socket;
        $this->state = $state;
        $this->lastRecieveTime = time();

        socket_getpeername($socket, $this->ip, $this->port);
    }

    /**
     * Sends a message to the client.
     *
     * @param mixed $message Message to send
     * @throws Exception
     */
    public function send($message)
    {
        if ($this->state == self::STATE_CLOSED)
        {
            $this->server->debug('Unable to send message, connection has been closed.');
            return;
        }

        $this->server->send($this->socket, $message);
    }

    /**
     * Sends a message to all other clients, excluding the sender.
     *
     * @param mixed $message Message to send
     */
    public function broadcast($message)
    {
        foreach ($this->server->getClients() as $client)
        {
            if ($client != $this)
            {
                $this->server->send($client->socket, $message);
            }
        }
    }

    /**
     * Sets client property.
     *
     * @param string $name Property name
     * @param mixed $value Property value
     */
    public function set($name, $value)
    {
        $this->data[$name] = $value;
    }

    /**
     * Returns client property.
     *
     * @param string $name Name of the property
     * @param mixed $default Default value returned when property does not exist
     * @return mixed
     */
    public function get($name, $default = null)
    {
        if (array_key_exists($name, $this->data))
        {
            return $this->data[$name];
        }
        else
        {
            return $default;
        }
    }

    /**
     * Disconnects the client.
     */
    public function disconnect()
    {
        if ($this->state == self::STATE_CLOSED)
        {
            return;
        }

        $this->server->disconnectClient($this->socket);
    }

    /**
     * Does the magic handshake to begin the connection.
     *
     * @param string $buffer Buffer sent by the client
     * @return bool Was the handshake successful
     * @throws Exception If something goes wrong
     */
    public function performHandshake($buffer)
    {
        if ($this->state != self::STATE_CONNECTING)
        {
            throw new Exception('Unable to perform handshake, client is not in connecting state');
        }

        $headers = $this->parseRequestHeader($buffer);
        $key = $headers['Sec-WebSocket-Key'];
        $hash = base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));

        $headers = array(
            'HTTP/1.1 101 Switching Protocols',
            'Upgrade: websocket',
            'Connection: Upgrade',
            'Sec-WebSocket-Accept: ' . $hash
        );

        $headers = implode("\r\n", $headers) . "\r\n\r\n";

        $left = strlen($headers);

        do
        {
            $sent = @socket_send($this->socket, $headers, $left, 0);

            if ($sent === false)
            {
                $error = $this->server->getLastError();

                throw new Exception('Sending handshake failed: : ' . $error->message . ' [' . $error->code . ']');
            }

            $left -= $sent;

            if ($sent > 0)
            {
                $headers = substr($headers, $sent);
            }
        }
        while ($left > 0);

        $this->state = self::STATE_OPEN;
    }

    /**
     * Parses the request header into resource, headers and security code
     *
     * @param string $request The request
     * @return array Array containing the resource, headers and security code
     */
    private function parseRequestHeader($request)
    {
        $headers = array();

        foreach (explode("\r\n", $request) as $line)
        {
            if (strpos($line, ': ') !== false)
            {
                list($key, $value) = explode(': ', $line);

                $headers[trim($key)] = trim($value);
            }
        }

        return $headers;
    }
}

class WebSocketRequest
{
    /** @type WebSocketController[] $controllers */
    private static $controllers = array();

    public static function encode($controller, $action, array $parameters = array())
    {
        return json_encode(array(
            $controller,
            $action,
            $parameters
        ));
    }

    public static function decode(WebSocketClient $sender, $message)
    {
        $request = json_decode($message, true);

        if (is_null($request) || empty($request[0]) || empty($request[1]))
        {
            return;
        }

        $controllerName = str_replace(' ', '', ucwords(str_replace('-', ' ', $request[0]))) . 'Controller';
        $actionName = 'action' . str_replace(' ', '', ucwords(str_replace('-', ' ', $request[1])));

        if (!isset(self::$controllers[$controllerName]))
        {
            if (!class_exists($controllerName) || !is_subclass_of($controllerName, 'WebSocketController'))
            {
                return;
            }

            self::$controllers[$controllerName] = new $controllerName();
        }

        $controller = self::$controllers[$controllerName];
        $controller->setSender($sender);

        if (!empty($request[2]) && is_array($request[2]))
        {
            $controller->setParameters($request[2]);
        }

        if (is_callable(array($controller, $actionName)))
        {
            $controller->$actionName();
        }
    }
}

abstract class WebSocketController
{
    protected $parameters;
    protected $sender;

    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    public function getParameter($name, $default = null)
    {
        if (array_key_exists($name, $this->parameters))
        {
            return $this->parameters[$name];
        }
        else
        {
            return $default;
        }
    }

    public function setSender(WebSocketClient $sender)
    {
        $this->sender = $sender;
    }

    /**
     * @return WebSocketClient|null
     */
    public function getSender()
    {
        return $this->sender;
    }
}