<!DOCTYPE html>
<html>
<head>
<style>
table, th, td {
    border: 1px solid grey;
}
</style>
</head>
<body>
<title>My simple App</title>
<?php
echo "<h1>My simple App</h1>";
//Open userenv file and search for DB host
$myfile = fopen("/usr/local/osmosix/etc/userenv", "r") or die("Unable to open file!");
while(!feof($myfile)) {
  $myline = fgets($myfile);
  if (strpos($myline, "CliqrDependencies")) {
    $pos = strpos($myline, "=");
    $dbhost = substr($myline,$pos+2,-2);
  }
}
fclose($myfile);
//Open userenv file and search for DB IP address
$myfile = fopen("/usr/local/osmosix/etc/userenv", "r") or die("Unable to open file!");
while(!feof($myfile)) {
  $myline = fgets($myfile);
  if (strpos($myline, "CliqrTier_".$dbhost."_PUBLIC_IP")) {
    $pos = strpos($myline, "=");
    $dbip = substr($myline,$pos+2,-2);
  }
}
fclose($myfile);
// MySQL connection
$username = "admin";
$password = "S3cur1ty01";
$dbname = "simpleAppDB";
$conn = new mysqli($dbip, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connection to Database IP ".$dbip." successul</br>";
// Get and display the content of 'people' table
$sql = "SELECT id, first_name, last_name FROM people";
$result = $conn->query($sql);
echo "People in our Database are: </br>";
if ($result->num_rows > 0) {
    echo "<table><tr><th>ID</th><th>Name</th></tr>";
    // output data of each row
    while($row = $result->fetch_assoc()) {
        echo "<tr><td>" . $row["id"]. "</td><td>" . $row["first_name"]. " " . $row["last_name"]. "</td></tr>";
    }
    echo "</table>";
} else {
    echo "No data found";
}
// Close SQL connection
$conn->close();
?> 

</body>
</html>
