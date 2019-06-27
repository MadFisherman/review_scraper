<?php
header('Content-Type: text/html; charset=utf-8');
require_once 'connection.php';
include('simple_html_dom.php');

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

/*обработка согласно mysql
$link = mysqli_connect($host, $user, $pass, $db) 
    or die("Ошибка " . mysqli_error($link));

$query ="SELECT * FROM products";
$result = mysqli_query($link, $query) or die("Ошибка " . mysqli_error($link)); 
$array = $result->fetch_all();

mysqli_close($link);
*/



if(isset($_POST['btw']))
{
	$ch = curl_init(); 
	curl_setopt($ch, CURLOPT_URL, $_POST["parse_url"]);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$result = curl_exec($ch);
	curl_close($ch); 
	echo $result;
}

?>


