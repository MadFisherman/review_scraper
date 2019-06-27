<?php
require_once "handler_functions.php";
require_once "connection.php";

header('Access-Control-Allow-Origin: *');

error_reporting(E_ALL);
ini_set('display_errors', 'on');

if ($_SERVER["REQUEST_METHOD"]=="POST"){
	
	$inputJSON = file_get_contents('php://input');
	$input = json_decode($inputJSON, TRUE);
	/*if(isset($_POST['button'])) 
	{
		parser_handler();
	}*/
	if(isset($input["user_id"]))
	{
		cookie_handler($input);
	}
	if(isset($input["item_name"]))
	{
		product_handler($input);
	}

}
?>
