<!DOCTYPE html>
<html>
<head>
<style>
table, th, td {
    border: 1px solid black;
}
</style>
</head>
<body>

<?php
echo "My simple App</br>";
//Open userenv file and search for DB IP address
$myfile = fopen("/usr/local/osmosix/etc/userenv", "r") or die("Unable to open file!");
while(!feof($myfile)) {
  $myline = fgets($myfile);
  if (strpos($myline, "CliqrDependencies")) {
    $pos = strpos($myline, "=");
    $dbhost = substr($myline,$pos+2,-2);
    echo "DEBUG: DB host is: ".$dbhost."</br>";
  }
  if (strpos($myline, "CliqrTier_".$dbhost."_PUBLIC_IP")) {
    $pos = strpos($myline, "=");
    $dbip = substr($myline,$pos+2,-2);
    echo "DEBUG: DB IP is: ".$dbip."</br>";
  }
}
fclose($myfile);
  
$username = "admin";
$password = "S3cur1ty01";
$dbname = "simpleAppDB";

// Create connection
$conn = new mysqli($dbip, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT id, first_name, last_name FROM people";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<table><tr><th>ID</th><th>Name</th></tr>";
    // output data of each row
    while($row = $result->fetch_assoc()) {
        echo "<tr><td>" . $row["id"]. "</td><td>" . $row["first_name"]. " " . $row["last_name"]. "</td></tr>";
    }
    echo "</table>";
} else {
    echo "0 results";
}

$conn->close();
?> 

</body>
</html>
