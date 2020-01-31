<?php
require_once '/opt/fmc_repository/Process/Reference/Common/common.php';
require_once '/opt/fmc_repository/Process/ENEA/VNF_Management/common.php';

function list_args()
{
  create_var_def('vnf_name', 'vnf_name');     
}

check_mandatory_param('vnf_name');

$HTTP_M = "POST";

$device_ip       = $context['device_ip'];
$port            = $context['port'];

//generate UUI for VNF id
$uuid            = uuid(openssl_random_pseudo_bytes(16));

//format required connection info

$connection_points =$context['selected_vnfd']['connectionPoints'];
$connection_info = $context['connection_info']['results'];
$props = array();


foreach($connection_info as $row)
{
  $props[] =  array(
             "name" => "bridge",
             "value" =>$row['name']
           );
}

$connection_info_fin = array();
foreach($connection_points as $row)
{
  $connection_info_fin[] = array( 
           "name" => $row['name'],
           "type" => "Dpdk",
           "props" =>$props,

     );
}
//========================================================================================
$device_ip = $context['device_ip'];
$port      = $context['port'];
$full_url  = "https://$device_ip$port/REST/v2/ServiceMethodExecution/modules/VnfManager/services/Configuration/methods/getVNFDescriptors";
$vnfd_list = curl_http_get($context['sessionToken'], $full_url,"", $HTTP_M);

if($vnfd_list['wo_status'] !== ENDED)
{               
    $vnfd_list = prepare_json_response($vnfd_list['wo_status'], "Failed to Import VNFD", $vnfd_list, true);   
    echo $vnfd_list;
    exit;
}

$vnfd_list = json_decode($vnfd_list['wo_newparams']['response_body'],TRUE);
$selected_vnfd = "";


foreach($vnfd_list as $row)
{
  
  //check if any vnfs matc the chosen descriptor.
  if($context['vnf_descriptor'] == $row["id"])
  {
    $selected_vnfd = $row;
    break;
  } 
}


//========================================================================================
$body = array(
  "vnfr"=>array(
      "id"            => $uuid,
      "vimEid"        => $context['device_data']['object_id'],
      "ecAutoRestart" => false,
      "ecManaged"     => false,
      "deviceName"    => $context['device_data']['name'],
      "connections"   => $connection_info_fin,
      "props"=>array(),
      "vnfdFlavour" => "Canonical",
      "vnfdVersion" => "1",
      "vnfd"     => $selected_vnfd,        
      "name"     => $context['vnf_name'],
      "_internal_objectType" => "VnfManager/VnfRecord"
    ));
$body = json_encode($body);
$full_url  = "https://$device_ip$port/REST/v2/ServiceMethodExecution/modules/VnfManager/services/Configuration/methods/instantiateVNF";

logToFile("**************Connection Info***********\n");
$instnatiate_vnf= curl_http_get($context['sessionToken'], $full_url,$body, $HTTP_M);
logToFile(debug_dump($instnatiate_vnf ,"**********BUG DATA**************\n"));

if($instnatiate_vnf['wo_status'] !== ENDED)
{               
    $instnatiate_vnf= prepare_json_response($instnatiate_vnf['wo_status'], "Failed to Instantiate VNF", $instnatiate_vnf, true);   
    echo $instnatiate_vnf;
    exit;
}

$context['instnatiate_vnf'] = $instnatiate_vnf;

task_exit(ENDED, "VNF instantiated");

?>