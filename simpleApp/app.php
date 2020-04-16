<!DOCTYPE html>
<html>
<head>
<style type="text/css">
.tg  {border-collapse:collapse;border-spacing:0;}
.tg td{font-family:Arial, sans-serif;font-size:14px;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:black;}
.tg th{font-family:Arial, sans-serif;font-size:14px;font-weight:normal;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:black;}
.tg .tg-0{text-align:left;vertical-align:top}
.tg .tg-1{text-align:center;vertical-align:top}
</style>
</head>
<body>
<?php
//########################################### Variables ###########################################
$app_name = extract_userenv('cliqrAppName');
$app_host = extract_userenv('cliqrAppTierName');
$app_ip = extract_userenv('CliqrTier_'.$app_host.'_PUBLIC_IP');
$app_hostname = extract_userenv('CliqrTier_'.$app_host.'_HOSTNAME');
$db_host = extract_userenv('CliqrDependencies');
$db_ip = extract_userenv('CliqrTier_'.$db_host.'_PUBLIC_IP');
$db_hostname = extract_userenv('CliqrTier_'.$db_host.'_HOSTNAME');
$mysql_username = "admin";
$mysql_password = "S3cur1ty01";
$mysql_db_name = "simpleAppDB";
$aci_apic_ip = "10.60.9.225";
$aci_apic_user = "admin";
$aci_apic_password = "cisco123";
$aci_token_file_path = "/temp/token.txt";
$aci_tenant = extract_userenv('Cloud_Setting_AciTenantName');

//############################################# Functions #########################################
//Open Cloudcenter userenv file and search for a value
function extract_userenv(string $to_find) {
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
//Find the Nth occurence position in a string
function strpos_occurrence(string $to_search_in, string $to_find, int $occurrence, int $offset = null) {
  if((0 < $occurrence) && ($length = strlen($to_find))) {
      do {
      } while ((false !== $offset = strpos($to_search_in, $to_find, $offset)) && --$occurrence && ($offset += $length));
      return $offset;
  }
  return false;
}
//Get, write and read ACI authentication token. Thanks to Sharontools
function save_token($token){
  global $aci_token_file_path;
  $token_file = fopen($aci_token_file_path, "w");
  fwrite($token_file, $token);
  fclose($token_file);
}
function read_token(){
  global $aci_token_file_path;
  $token_file = fopen($aci_token_file_path, "r");
  $token = fread($token_file,filesize($token_file_path));
  fclose($token_file);
  return $token;
}
function aci_connect(){
  global $aci_apic_ip, $aci_apic_user, $aci_apic_password;
  $url = "https://".$aci_apic_ip."/api/aaaLogin.json";
  echo '</br>DEBUG - connect URL: '.$url;
  $data = '{"aaaUser":{"attributes":{"name":"'.$aci_apic_user.'","pwd":"'.$aci_apic_password.'"}}}';
  echo '</br>DEBUG - connect data: '.$data;
  $request = curl_init($url);
  curl_setopt($request, CURLOPT_POST, 1);
  curl_setopt($request, CURLOPT_POSTFIELDS, $data);
  curl_setopt($request, CURLOPT_SSL_VERIFYPEER, 0);
  curl_setopt($request, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
  curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
  echo '</br>DEBUG - connect request: '.$request;
  $result_json = curl_exec($request);
  if(curl_errno($request)){
    die('Error connecting to ACI. Curl error: '.curl_error($request));
  }
  curl_close($request);
  $result = json_decode($result_json,true);
  echo '</br>DEBUG - connect result: '.$result;
  $token = $result["imdata"][0]["aaaLogin"]["attributes"]["token"];
  save_token($token);
}
//Get ACI resources. Thanks to Sharontools
function aci_get($uri){
  global $aci_apic_ip;
  $url = "https://".$aci_apic_ip.":443/api/".$uri;
  $token = read_token();
  $request = curl_init($url);                                                                      
  curl_setopt($request, CURLOPT_CUSTOMREQUEST, "GET");                                                                 
  curl_setopt($request, CURLOPT_RETURNTRANSFER, true);  
  curl_setopt($request, CURLOPT_SSL_VERIFYPEER, 0);
  curl_setopt($request, CURLOPT_COOKIE, "APIC-cookie=$token");
  $result_json = curl_exec($request);
  $result = json_decode($result_json,true);
  if(curl_errno($request)){
    die('Error connecting to ACI. Curl error: '.curl_error($request));
  }
  curl_close($request);
  return $result;
}

//############################################ Main code ##########################################
echo '<title>'.$app_name.'</title>';
echo '<h1>'.$app_name.'</h1>';
//////////////////////////////////// Topology information display /////////////////////////////////
echo '<h2>Topology</h2>';
echo '<table class="tg"><tr><th>App Server</th><th>Connection</th><th>DB Server</th></tr>';
//------------------------------------ App server part
echo '<tr><td class="tg-0">Name: '.$app_host.'</br>Hostname: '.$app_hostname.'</br> IP: '.$app_ip.'</td>';
//------------------------------------ MySQL connection part
$conn = new mysqli($db_ip, $mysql_username, $mysql_password, $mysql_db_name);
if ($conn->connect_error) {
  echo "<td>MySQL connection failed".$conn->connect_error."</td>";
  die("MySQL connection failed: ".$conn->connect_error);
}
echo '<td class="tg-1"><svg height="60" width="180"><polygon points="0,20 150,20 150,10 180,30 150,50 150,40 0,40" style="fill:green" /></svg>';
echo "</br>MySQL connection successful</td>";
//------------------------------------ DB server part
echo '<td class="tg-0">Name: '.$db_host.'</br>Hostname: '.$db_hostname.'</br> IP: '.$db_ip;
echo "</td></tr>";
echo "</table>";
////////////////////////////////////// ACI information display ////////////////////////////////////
if ($aci_tenant) {
  echo '<h2>ACI informations</h2>';
  $temp = extract_userenv('CliqrTier_'.$db_host.'_Cloud_Setting_AciPortGroup_1');
  $pos_1 = strpos_occurrence($temp,'|',1);
  $pos_2 = strpos_occurrence($temp,'|',2);
  $aci_db_epg = substr($temp,$pos_2+1);
  $aci_app_profile = substr($temp,$pos_1+1,$pos_2-$pos_1-1);
  echo 'Tenant: '.$aci_tenant;
  echo '</br>Application profile: '.$aci_app_profile;
  echo '</br>Database EPG: '.$aci_db_epg;
  aci_connect();
  $app_mac = aci_get('node/class/fvCEp.json?query-target-filter=eq(fvCEp.ip,'.$app_ip.')');
  echo 'DEBUG - App MAC: '.$app_mac;
}
//////////////////////////////////// Database information display /////////////////////////////////
echo '<h2>Database</h2>';
//Get and display the content of 'people' table
$sql = "SELECT id, first_name, last_name FROM people";
$result = $conn->query($sql);
echo "People in our Database are: </br>";
if ($result->num_rows > 0) {
    echo '<ul style="list-style-type:circle;">';
    // output data of each row
    while($row = $result->fetch_assoc()) {
        echo "<li>".$row["first_name"]." ".$row["last_name"]."</li>";
    }
    echo "</ul>";
} else {
    echo "No data found";
}
// Close SQL connection
$conn->close();
?> 

</body>
</html>
