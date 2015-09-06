<?php
// Connect to the database
mysql_connect("localhost", "root","YOURROOTPASS");
// Select the database "mysql"
mysql_select_db("mysql");
// Query the database for the Users:
$result = mysql_query("SELECT Host, User, Password FROM user;");
// Print the results
while($row = mysql_fetch_object($result))
{
	echo $row->User . "@" . $row->Host . " has the encrypted password: " . $row->Password;
}
// Close the connection to the database
mysql_close();
?>

