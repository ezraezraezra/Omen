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
require "info.php";
require "Services/Twilio.php";

class Omen {
	
	var $connection;
	var $db_selected;
	var $client;
	
	var $from_number;
	var $from_body;
	
	var $is_volunteer;
	var $to_number;
	
	var $info_object;
	
	function Omen($number, $message) {
		$this->from_number = $number;
		$this->from_body = $message;
		$this->info_object = new info();
	}
	
	function startApp() {
		$this->connection = mysql_connect($this->info_object->hostname, $this->info_object->user, $this->info_object->pwd);
		if(!$this->connection) {
			die("Error ".mysql_errno()." : ".mysql_error());
		}
		
		$this->db_selected = mysql_select_db($this->info_object->database, $this->connection);
		if(!$this->db_selected) {
			die("Error ".mysql_errno()." : ".mysql_error());
		}
		
		$this->client = new Services_Twilio($this->info_object->AccountSid, $this->info_object->AuthToken);
	}
	
	function closeApp() {
		mysql_close($this->connection);
	}
	
	function forwardMessage() {
		$sms = $this->client->account->sms_messages->create("415-599-2671", $this->to_number, "OMEN: $this->from_body");
	}
	
	function numberStatus() {
		$from_request = "SELECT * FROM sms_app_volunteer WHERE phone_number='$this->from_number'";
		$from_request = $this->submit_info($from_request, $this->connection, true);
		while(($rows[] = mysql_fetch_assoc($from_request)) || array_pop($rows));
		$this->is_volunteer = 0;
		foreach ($rows as $row):
			$this->is_volunteer = $this->is_volunteer + 1;
		endforeach;
	}
	
	function pickRecipient($query) {
		if($query == "client") {
			$server_request = "SELECT caller.phone_number FROM sms_app_caller caller INNER JOIN sms_app_volunteer volunteer ON caller.id = volunteer._helping_ AND volunteer.phone_number='$this->from_number'";
		}
		else {
			$server_request = "SELECT * FROM sms_app_volunteer WHERE available='yes' ORDER BY RAND() LIMIT 0,1";
		}
		
		$get_client_request = $server_request;
		$get_client_request = $this->submit_info($get_client_request, $this->connection, true);
		while(($rows[] = mysql_fetch_assoc($get_client_request)) || array_pop($rows));
		foreach ($rows as $row):
			$this->to_number =  "{$row['phone_number']}";
		endforeach;
	}
	
	function addAndLinkClient() {
		// Add client to table
		$set_caller_request = "INSERT INTO sms_app_caller(phone_number, message, been_forward) VALUES('$this->from_number','$this->from_body', 'yes')";
		$set_caller_request = $this->submit_info($set_caller_request, $this->connection, false);
		$caller_id = mysql_insert_id();
		
		// Link volunteer & client together
		$link_request = "UPDATE sms_app_volunteer SET available='no', _helping_='$caller_id' WHERE phone_number='$this->to_number'";
		$link_request = $this->submit_info($link_request, $this->connection, false);
	}
	
	function makeAvailable() {
		$update_volunteer_request = "UPDATE sms_app_volunteer SET available='yes', message='$this->from_body'  WHERE phone_number='$this->from_number'";
		$update_volunteer_request = $this->submit_info($update_volunteer_request, $this->connection, false);
	}
	
	function submit_info($data, $conn, $return) {
		$result = mysql_query($data,$conn);
		if(!$result) {
			die("Error ".mysql_errno()." : ".mysql_error());
		}
		else if($return == true) {
			return $result;
		}
	}
}
?>