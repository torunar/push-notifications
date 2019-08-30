<?php

require_once('config.php');

/*
* Notify device with message
* @param  $serviceID   - target service based on device OS
* @param  $deviceToken - token of target device
* @param  $message     - text message to be sent
* @return                boolean
*                        true on success
*/

function notify($serviceID, $deviceToken, $message) {
  // create socket
  if (!($sock = socket_create(AF_INET, SOCK_STREAM, 0))) {
    $eCode = socket_last_error();
    $eMsg = socket_strerror($eCode);

    error_log("NOTIFICATION ERROR (CREATE): [{$eCode}] {$eMsg}" . PHP_EOL);
    return false;
  }

  // connect socket to local server
  if (!socket_connect($sock, NOTIFICATION_SERVER_HOST, NOTIFICATION_SERVER_PORT)) {
    $eCode = socket_last_error();
    $eMsg = socket_strerror($eCode);

    error_log("NOTIFICATION ERROR (CONNECT): [{$eCode}] {$eMsg}" . PHP_EOL);
    return false;
  }

  // send notification via local server
  $notification = $serviceID . NOTIFICATION_DELIMITER . $deviceToken . NOTIFICATION_DELIMITER . $message . NOTIFICATION_TERMINATOR;
  if (!socket_send($sock, $notification, strlen($notification), 0)) {
    $eCode = socket_last_error();
    $eMsg = socket_strerror($eCode);

    error_log("NOTIFICATION ERROR (SEND): [{$eCode}] {$eMsg}" . PHP_EOL);
    return false;
  }

  return true;
}