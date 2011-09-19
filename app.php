<?php
/*
 * Project:     OMEN
 * Description: Feelings support sms-based hotline.
 * Website:     http://ezraezraezra.com/?p=1482
 * 
 * Author:      Ezra Velazquez
 * Website:     http://ezraezraezra.com
 * Date:        September 2011
 * 
 */
require 'Omen.php';
$from_number = $_REQUEST['From'];
$from_body = $_REQUEST['Body'];

/*
 * 1)  Create Omen app object
 * 2)  Connect to database &  Twilio
 * 3)  Check if number is client or volunteer
 * 4a) If client, randomly pick a free volunteer and forward the message to them
 * 4b) If volunteer, find client and forward message to them
 * 5)  Close database connection
 */

// 1
$omen_app = new Omen($from_number, $from_body);
// 2
$omen_app->startApp();
// 3
$omen_app->numberStatus();
// 4a
if($omen_app->is_volunteer == 0) {
	$omen_app->pickRecipient('volunteer');
	$omen_app->addAndLinkClient();
	$omen_app->forwardMessage();
}
// 4b
else {
	$omen_app->pickRecipient('client');
	$omen_app->makeAvailable();
	$omen_app->forwardMessage();
}
// 5
$omen_app->closeApp();
?>