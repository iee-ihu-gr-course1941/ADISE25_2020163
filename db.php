<?php
$user='iee2020163';
$pass='';
$host='localhost';
$db = 'xeri_game';


$mysqli = new mysqli($host, $user, $pass, $db,null,"/home/student/iee/2020/$user/mysql/run/mysql.sock");
if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: (" . 
    $mysqli->connect_errno . ") " . $mysqli->connect_error;
}
?>
