//select port to use
const HTTPS_PORT = 8432;
const fs = require('fs');
const https = require('https');
const WebSocket = require('ws');
const WebSocketServer = WebSocket.Server;

// SSL certificates
const serverConfig = {
   key: fs.readFileSync('certificate.key'),
   cert: fs.readFileSync('certificate.crt'),
   ca:fs.readFileSync('ca.crt')
};


const handleRequest = function(request, response) {
  // Render the single client html file for any request the HTTP server receives
  response.statusCode = 200;
  response.setHeader('Content-Type', 'text/plain');
  response.end('Hello World');
};

const httpsServer = https.createServer(serverConfig, handleRequest);
httpsServer.listen(HTTPS_PORT, '0.0.0.0');



// Create a server for handling websocket calls
const wss = new WebSocketServer({server: httpsServer});

wss.on('connection', function(ws) {
  ws.on('message', function(message) {
    // Broadcast any received message to all clients
    wss.broadcast(message);
  });
});

wss.broadcast = function(data) {
  this.clients.forEach(function(client) {
    if(client.readyState === WebSocket.OPEN) {
      client.send(data);
    }
  });
};

console.log('Server running. on ' + HTTPS_PORT );