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
$aci_token = "";
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
//Extract a substring between 2 strings
function get_string_between($string, $start, $end){
  $string = ' ' . $string;
  $ini = strpos($string, $start);
  if ($ini == 0) return '';
  $ini += strlen($start);
  $len = strpos($string, $end, $ini) - $ini;
  return substr($string, $ini, $len);
}
//Connect to ACI to get authentication token.
function aci_connect(){
  global $aci_apic_ip, $aci_apic_user, $aci_apic_password, $aci_token;
  $url = "https://".$aci_apic_ip."/api/aaaLogin.json";
  $data = '{"aaaUser":{"attributes":{"name":"'.$aci_apic_user.'","pwd":"'.$aci_apic_password.'"}}}';
  $request = curl_init($url);
  curl_setopt($request, CURLOPT_POST, 1);
  curl_setopt($request, CURLOPT_POSTFIELDS, $data);
  curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE);
  curl_setopt($request, CURLOPT_SSL_VERIFYHOST, 0);
  curl_setopt($request, CURLOPT_SSL_VERIFYSTATUS, FALSE);
  curl_setopt($request, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
  curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
  $result_json = curl_exec($request);
  if(curl_errno($request)){
    die('</br>Error connecting to ACI. Curl error: '.curl_error($request));
  }
  curl_close($request);
  $result = json_decode($result_json,true);
  $aci_token = $result["imdata"][0]["aaaLogin"]["attributes"]["token"];
}
//Get ACI resources.
function aci_get($uri){
  global $aci_apic_ip, $aci_token;
  $url = "https://".$aci_apic_ip.":443/api/".$uri;
  $request = curl_init($url);                                                                      
  curl_setopt($request, CURLOPT_CUSTOMREQUEST, "GET");                                                                 
  curl_setopt($request, CURLOPT_RETURNTRANSFER, true);  
  curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE);
  curl_setopt($request, CURLOPT_SSL_VERIFYHOST, 0);
  curl_setopt($request, CURLOPT_SSL_VERIFYSTATUS, FALSE);
  curl_setopt($request, CURLOPT_COOKIE, "APIC-cookie=$aci_token");
  $result_json = curl_exec($request);
  $result = json_decode($result_json,true);
  if(curl_errno($request)){
    die('</br>Error connecting to ACI. Curl error: '.curl_error($request));
  }
  curl_close($request);
  return $result;
}
//Get details about endpoint using ACI REST API
function aci_endpoint_extract($endpoint_ip){
  $endpoint_info = aci_get('node/class/fvCEp.json?query-target-filter=eq(fvCEp.ip,"'.$endpoint_ip.'")');
  switch ($endpoint_info["totalCount"]) {
    case 0:
      echo '</br>ERROR - IP endpoint not found in ACI';
      return NULL;
      break;
    case 1:
      $endpoint_dn = $endpoint_info["imdata"][0]["fvCEp"]["attributes"]["dn"];
      $endpoint_mac = $endpoint_info["imdata"][0]["fvCEp"]["attributes"]["mac"];
      $endpoint_tenant = get_string_between($endpoint_dn,'/tn-','/ap-');
      $endpoint_ap = get_string_between($endpoint_dn,'/ap-','/epg-');
      $endpoint_epg = get_string_between($endpoint_dn,'/epg-','/cep-');
      $endpoint_vlan = $endpoint_info["imdata"][0]["fvCEp"]["attributes"]["encap"];
      $endpoint_vm_info = aci_get('node/mo/'.$endpoint_dn.'.json?query-target=children&target-subtree-class=fvRsToVm');
      $endpoint_vm_dn = $endpoint_vm_info["imdata"][0]["fvRsToVm"]["attributes"]["tDn"];
      $endpoint_vm_data = aci_get('node/class/compVm.json?query-target-filter=eq(compVm.dn,"'.$endpoint_vm_dn.'")');
      $endpoint_vm_name = $endpoint_vm_data["imdata"][0]["compVm"]["attributes"]["name"];
      $endpoint_vm_os = $endpoint_vm_data["imdata"][0]["compVm"]["attributes"]["os"];
      $endpoint_vm_state = $endpoint_vm_data["imdata"][0]["compVm"]["attributes"]["state"];
      $endpoint_host_info = aci_get('node/mo/'.$endpoint_dn.'.json?query-target=children&target-subtree-class=fvRsHyper');
      $endpoint_host_dn = $endpoint_host_info["imdata"][0]["fvRsHyper"]["attributes"]["tDn"];
      $endpoint_host_data = aci_get('node/class/compHv.json?query-target-filter=eq(compHv.dn,"'.$endpoint_host_dn.'")');
      $endpoint_host_name = $endpoint_host_data["imdata"][0]["compHv"]["attributes"]["name"];
      $endpoint_host_state = $endpoint_host_data["imdata"][0]["compHv"]["attributes"]["state"];
      $endpoint_aci_path_info = aci_get('node/mo/'.$endpoint_dn.'.json?query-target=children&target-subtree-class=fvRsCEpToPathEp&query-target-filter=not(wcard(fvRsCEpToPathEp.dn,"__ui_"))');
      $endpoint_aci_path_dn = $endpoint_aci_path_info["imdata"][0]["fvRsCEpToPathEp"]["attributes"]["tDn"];
      $endpoint_aci_path_pod = get_string_between($endpoint_aci_path_dn,'/pod-','/paths-');
      $endpoint_aci_path_leaf = get_string_between($endpoint_aci_path_dn,'/paths-','/pathep-');
      $endpoint_aci_path_port = get_string_between($endpoint_aci_path_dn,'/pathep-[',']');
      $endpoint = [
        "mac" => $endpoint_mac,
        "ip" => $endpoint_ip,
        "vm_name" => $endpoint_vm_name,
        "vm_os" => $endpoint_vm_os,
        "vm_state" => $endpoint_vm_state,
        "host_name" => $endpoint_host_name,
        "host_state" => $endpoint_host_state,
        "aci_path_port" => $endpoint_aci_path_port,
        "aci_path_leaf" => $endpoint_aci_path_leaf,
        "aci_path_pod" => $endpoint_aci_path_pod,
        "vlan" => $endpoint_vlan,
        "epg" => $endpoint_epg,
        "ap" => $endpoint_ap,
        "tenant" => $endpoint_tenant,
      ];
      return $endpoint;
      break;
    default:
      echo '</br>ERROR - IP endpoint found multiple times in ACI';
      return NULL;
  }
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
////////////////////////////////////// ACI information display ////////////////////////////////////
if ($aci_tenant) {
  echo '<tr>ACI informations</tr>';
  aci_connect();
  $app_endpoint = aci_endpoint_extract($app_ip);
  $db_endpoint = aci_endpoint_extract($db_ip);
  //------------------------------------ App server part
  echo '<tr><td class="tg-0"><pre>';
  foreach($app_endpoint as $key => $value){
    echo '[' . $key . '] =>' . $value . PHP_EOL;
  }
  echo '</pre></td>';
  //------------------------------------ Connection part
  echo '<td class="tg-0"></td>';
  //------------------------------------ DB server part
  echo '<td class="tg-0"><pre>';
  foreach($db_endpoint as $key => $value){
    echo '[' . $key . '] =>' . $value . PHP_EOL;
  }
  echo '</pre></td></tr>';
}
echo "</table>";
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
