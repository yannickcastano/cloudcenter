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
//Open Cloudcenter userenv file and search for a value
function extract_userenv($to_find) {
  $myfile = fopen("/usr/local/osmosix/etc/userenv", "r") or die("Unable to open file!");
  while(!feof($myfile)) {
    $myline = fgets($myfile);
    if (strpos($myline, $to_find)) {
      $pos = strpos($myline, "=");
      $found = substr($myline,$pos+2,-2);
    }
  }
  fclose($myfile);
  return $found;
}
$app_name = extract_userenv('CliqrAppName');
$app_host = extract_userenv('cliqrAppTierName');
$app_ip = extract_userenv('CliqrTier_'.$app_host.'_PUBLIC_IP');
$app_hostname = extract_userenv('CliqrTier_'.$app_host.'_HOSTNAME');
$db_host = extract_userenv('CliqrDependencies');
$db_ip = extract_userenv('CliqrTier_'.$db_host.'_PUBLIC_IP');
$db_hostname = extract_userenv('CliqrTier_'.$db_host.'_HOSTNAME');
$mysql_username = "admin";
$mysql_password = "S3cur1ty01";
$mysql_db_name = "simpleAppDB";
$aci_tenant = extract_userenv('Cloud_Setting_AciTenantName');

echo "<h1>".$app_name."</h1>";
echo "<table><tr><th>App Server</th><th>Connection</th><th>DB Server</th></tr>";
//App server part
echo "<tr><td>Name: ".$app_host."</br>Hostname: ".$app_hostname."</br> IP: ".$app_ip."</td>";
//MySQL connection
$conn = new mysqli($db_ip, $mysql_username, $mysql_password, $mysql_db_name);
if ($conn->connect_error) {
  echo "<td>MySQL connection failed".$conn->connect_error."</td>";
  die("MySQL connection failed: ".$conn->connect_error);
}
echo "<td>MySQL connection successful</td>";
//DB server part
echo "<td>Name: ".$db_host."</br>Hostname: ".$db_hostname."</br> IP: ".$db_ip;
//Get and display the content of 'people' table
$sql = "SELECT id, first_name, last_name FROM people";
$result = $conn->query($sql);
echo "</br>People in our Database are: </br>";
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
echo "</td></tr>";
// Close SQL connection
$conn->close();
echo "</table>";
?> 

</body>
</html>
