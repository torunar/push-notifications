#!/usr/bin/env php
<?php
/*
 * This file, while run on your server, will bind to specified address and port
 * and handle sending notifications to Apple Push Notification Server and Google Cloud Messaging.
 *
 * Wrapping it in some kind of system service is a good idea.
 *
 * Usage:
 * $ chmod +x server.php
 * $ ./server.php
 *
 */

require_once('config.php');

/*
* Connect to Apple Push Notification Server
* @return socket to APNS
*         null on failure
*/
function connect_apns(){
  $fp     = null;
  $err    = '';
  $errstr = '';

  $ctx = stream_context_create();
  stream_context_set_option($ctx, 'ssl', 'local_cert', NOTIFICATION_APPLE_CERT_PATH);
  // open a connection to the APNS
  $fp = stream_socket_client(NOTIFICATION_SERVER_APPLE, $err,
    $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx
  );
  if (!$fp) {
    error_log("APNS ERROR: {$err} {$errstr}" . PHP_EOL);
  }

  return $fp;
}

/*
* Build notification in format used by target service
* @param  $serviceID   - target service
* @param  $deviceToken - token of target device
* @param  $message     - text message to be sent
* @return                string, containing encoded message
*/
function build_notification($serviceID, $deviceToken, $message) {
  $msg = '';

  switch($serviceID) {
    case NOTIFICATION_IOS:
      // create the payload body
      $body['aps'] = [
        'alert' => $message,
        'sound' => 'default'
      ];
      // encode the payload as JSON
      $payload = json_encode($body);
      // build the binary notification
      $msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;
      break;
    case NOTIFICATION_AND:
      // create the payload body
      $fields = [
        'registration_ids' 	=> [$deviceToken],
        'data'			        => [
          'message' 	=> $message,
          'vibrate'	=> 1,
          'sound'		=> 1,
        ]
      ];
      // encode the payload as JSON
      $msg = json_encode($fields);
      break;
  }

  return $msg;
}

/*
* Send notification to target device via target service
* @param  $serviceID           - target service
* @param  $serverSocket        - socket of target service
* @param  $encodedNotification - notification built with build_notification method
* @return                        boolean
*                                true on success
*/
function send_notification($serviceID, $serverSocket = null, $encodedNotification) {
  switch($serviceID) {
    case NOTIFICATION_IOS:
      return (fwrite($serverSocket, $encodedNotification, strlen($encodedNotification)) !== false);
    case NOTIFICATION_AND:
      $headers = [
        'Authorization: key=' . NOTIFICATION_GOOGLE_API_KEY,
        'Content-Type: application/json'
      ];
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL,            NOTIFICATION_SERVER_GOOGLE);
      curl_setopt($ch, CURLOPT_POST,           true);
      curl_setopt($ch, CURLOPT_HTTPHEADER,     $headers);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_POSTFIELDS,     $encodedNotification);
      $exData = curl_exec($ch);
      $result = (json_decode($exData, true)['success'] == 1);
      curl_close($ch);
      return $result;
    default:
      return false;
  }
}

/*
* Init local server
* @param  $address - local address to bind to
* @param  $port    - local port to bind to
* @return            socket to local server
*/
function init_local_server($address = NOTIFICATION_SERVER_HOST, $port = NOTIFICATION_SERVER_PORT) {
  set_time_limit(0);

  $sock = socket_create(AF_INET, SOCK_STREAM, 0);
  socket_bind($sock, $address, $port);

  return $sock;
}

/*
* Local server routine
*/
function local_server_routine() {
  // connect to APNS
  $apns = connect_apns(NOTIFICATION_IOS);
  if (!$apns) {
    exit('NOTIFICATION ERROR: unbale to connect to APNS');
  }
  error_log(date('Y-m-d H:i:s') . ' APNS: connected');

  // start local server
  $sock = init_local_server();
  socket_listen($sock);

  while (true) {
    $client = socket_accept($sock);
    // read data chunk
    $chunk = socket_read($client, 8192);

    if (strpos($chunk, NOTIFICATION_DELIMITER) && strpos($chunk, NOTIFICATION_TERMINATOR)) {
      // split data chunk into details
      list($osType, $deviceToken, $message) = explode(NOTIFICATION_DELIMITER, $chunk, 3);
      // cut notification terminator
      $message = substr($message, 0, -1 * strlen(NOTIFICATION_TERMINATOR));
      // build encoded notification
      $encodedNotification = build_notification($osType, $deviceToken, $message);
      // send encoded notification
      switch($osType) {
        case NOTIFICATION_IOS:
          $status = send_notification($osType, $apns, $encodedNotification);
          while (!$status) {
            // reconnect
            socket_close($apns);
            error_log(date('Y-m-d H:i:s') . " APNS: reconnect");
            $apns = connect_apns(NOTIFICATION_IOS);
            $status = send_notification($osType, $apns, $encodedNotification);
          }
          error_log(date('Y-m-d H:i:s') . " NOTIFICATION SENT: '{$message}' to {$deviceToken}");
          break;
        case NOTIFICATION_AND:
          send_notification($osType, null, $encodedNotification);
          error_log(date('Y-m-d H:i:s') . " NOTIFICATION SENT: '{$message}' to {$deviceToken}");
          break;
      }
    }
  }
}

local_server_routine();