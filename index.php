<!DOCTYPE html>
<html>
  <head>
    <script src="https://webrtc.github.io/adapter/adapter-latest.js"></script>
   
    <style>#localVideo{width: 140px;
    position: absolute;z-index: 99999999;top:30px;}
    #remoteVideo{max-width:60%;}
    #start{    position: absolute;
    top: 4px;
    right: 10px;
    padding: 6px;
    font-size: 25px;}
  
    </style>
  </head>

  <body>
  
    <div id="geoLocationElm">Click Start video to make call</div>
    <video id="localVideo" autoplay playsinline muted  ></video>
    <video id="remoteVideo" autoplay playsinline  ></video>

    <br />

    <input type="button" id="start" onclick="start(true)" value="Start Video">
   


    
<script>


var geoLocation;
var localVideo;
var localStream;
var remoteVideo;
var geoLocationElm;
var peerConnection;
var uuid;
var serverConnection;

// get my location to send to other user
const Http = new XMLHttpRequest();
const url='https://ipapi.co/json';
Http.open("GET", url);
Http.send();

Http.onreadystatechange = (e) => {
  console.log(Http.responseText)
  var ipinfo=JSON.parse(Http.responseText);
  
geoLocation=ipinfo.city+','+ipinfo.country_name;
}


//start
pageReady();
function pageReady() {
 //create unique user ID    
  uuid = createUUID();

//html element selecting
  localVideo = document.getElementById('localVideo');
  remoteVideo = document.getElementById('remoteVideo');
  geoLocationElm = document.getElementById('geoLocationElm');

//set node js websocket signalling server
  serverConnection = new WebSocket('wss://tspnetwork.com:8432');
  
 //get all messages(data) from server
  serverConnection.onmessage = function(message){
       if(!peerConnection) start(false);

  var signal = JSON.parse(message.data);

  // Ignore messages from ourself
  if(signal.uuid == uuid) return;
  if(signal.geoLocation) {
      geoLocationElm.innerHTML='User From :'+geoLocation;
  }
  if(signal.sdp) {
    peerConnection.setRemoteDescription(new RTCSessionDescription(signal.sdp)).then(function() {
      // Only create answers in response to offers
      if(signal.sdp.type == 'offer') {
        peerConnection.createAnswer().then(createdDescription).catch(errorHandler);
      }
    }).catch(errorHandler);
  } else if(signal.ice) {
    peerConnection.addIceCandidate(new RTCIceCandidate(signal.ice)).catch(errorHandler);
  } 
  }
  
//check device compatibility and add own video to <video> tag and set stream 
  if(navigator.mediaDevices.getUserMedia) {
    navigator.mediaDevices.getUserMedia({
    video: true,
    audio: true,
  }).then(
        function(stream) {
  localStream = stream;
  localVideo.srcObject = stream;
}).catch(errorHandler);
  } else {
    alert('Your browser does not support getUserMedia API');
  }
}


//send,receive video to remote user with webRTCPeerConnection

function start(isCaller) {
  peerConnection = new RTCPeerConnection({
  'iceServers': [
    {'urls': 'stun:stun.stunprotocol.org:3478'},
    {'urls': 'stun:stun.l.google.com:19302'},
  ]
});

  peerConnection.onicecandidate = function(event){
     if(event.candidate != null) {
    serverConnection.send(JSON.stringify({'ice': event.candidate, 'uuid': uuid,'geoLocation':geoLocation}));
    } 
  }
  
  peerConnection.ontrack = function(event) {
  console.log('got remote stream');
  remoteVideo.srcObject = event.streams[0];
};

  peerConnection.addStream(localStream);

  if(isCaller) {
    peerConnection.createOffer().then(createdDescription).catch(errorHandler);
  }
}

function createdDescription(description) {
  console.log('got description');

  peerConnection.setLocalDescription(description).then(function() {
    serverConnection.send(JSON.stringify({'sdp': peerConnection.localDescription, 'uuid': uuid,'location':location}));
  }).catch(errorHandler);
}

function errorHandler(error){ console.log(error);}

// unique ID generate
function createUUID() {
  function s4() {
    return Math.floor((1 + Math.random()) * 0x10000).toString(16).substring(1);
  }

  return s4() + s4() + '-' + s4() + '-' + s4() + '-' + s4() + '-' + s4() + s4() + s4();
}
     </script>     
  </body>
</html>