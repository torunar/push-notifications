<?php

// local address and port to bind server to
define('NOTIFICATION_SERVER_HOST', '127.0.0.1');
define('NOTIFICATION_SERVER_PORT', '9001');

// URLs of services
define('NOTIFICATION_SERVER_APPLE',  'ssl://gateway.sandbox.push.apple.com:2195');
define('NOTIFICATION_SERVER_GOOGLE', 'https://android.googleapis.com/gcm/send');

/*
 * To create valid certificate, follow instructions here:
 * http://www.raywenderlich.com/32960/apple-push-notification-services-in-ios-6-tutorial-part-1
 *
 * Don't forget to install Entrust.net Certification Authority (2048):
 * https://www.entrust.net/downloads/root_request.cfm
 *
 * Also install Apple Root Certificate:
 * https://www.apple.com/certificateauthority/
*/
define('NOTIFICATION_APPLE_CERT_PATH', '/path/to/certificate.pem');
// use Server API key from Google Developers Console
define('NOTIFICATION_GOOGLE_API_KEY',  'NOTIFICATION_GOOGLE_API_KEY');

// service descriptors
define('NOTIFICATION_IOS', 'ios');
define('NOTIFICATION_AND', 'and');

define('NOTIFICATION_DELIMITER',  '::');
define('NOTIFICATION_TERMINATOR', '/ntf');