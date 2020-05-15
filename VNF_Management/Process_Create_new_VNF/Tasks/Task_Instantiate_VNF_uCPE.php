<?php
require_once '/opt/fmc_repository/Process/Reference/Common/common.php';
require_once '/opt/fmc_repository/Process/ENEA/VNF_Management/common.php';

function list_args()
{
  create_var_def('vnf_name', 'vnf_name');     
create_var_def('nics_1_id', 'String'); 
create_var_def('nics_1_type', 'String');  
create_var_def('nics_1_interfacename', 'String');

create_var_def('nics_2_id', 'String'); 
create_var_def('nics_2_type', 'String');  
create_var_def('nics_2_interfacename', 'String');

create_var_def('nics_3_id', 'String'); 
create_var_def('nics_3_type', 'String');  
create_var_def('nics_3_interfacename', 'String');  
}

check_mandatory_param('vnf_name');

$HTTP_M = "POST";

$device_ip       = $context['device_ip'];
$port            = $context['port'];

//generate UUI for VNF id
$uuid            = uuid(openssl_random_pseudo_bytes(16));



/*
$nics = $context['nics'];
foreach($nics as $row)
{
    $name = explode("-",$row['id']);
    $bridge[] = array("name" => "bridge","value" =>$row["interfacename"]);

	$arr = array( 
           "name" => $name[1],
           "type" => "Dpdk",
           "props" =>array(array("name" => "bridge",
             	"value" =>$row["interfacename"]))
    );
    
    if($row['type'] == "Tap" && isset($row['nicmodel']) && $row['nicmodel'] != "")
	{
		$arr["model"] = $row['nicmodel'];
 	}

    $connection_info_fin2[] = $arr;
}	
*/
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

logToFile(debug_dump($selected_vnfd ['name'],"*********** DESCRIPT data**************\n"));

//format required connection info
$connection_info_fin2 = array();

    $connection_info_fin2[] = array(
    	  "name" => $context['nics_1_id'],
           "type" => $context['nics_1_type'],
           "props" =>array(array("name" => "bridge",
             	"value" =>$context['nics_2_interfacename']))
         );
    $connection_info_fin2[]   =  array("name" => $context['nics_2_id'],
           "type" =>$context['nics_2_type'],
           "props" =>array(array("name" => "bridge",
             	"value" =>$context['nics_2_interfacename']))
         );

 

if($selected_vnfd ['name'] == "pfsesne2")
{
      $connection_info_fin2[]   =  array("name" => $context['nics_3_id'],
           "type" =>$context['nics_3_type'],
           "props" =>array(array("name" => "bridge",
             	"value" =>$context['nics_3_interfacename']))
         );

}

//========================================================================================
$body = array(
  "vnfr"=>array(
      "id"            => $uuid,
      "vimEid"        => $context['device_data']['object_id'],
      "ecAutoRestart" => false,
      "ecManaged"     => false,
      "deviceName"    => $context['device_data']['name'],
      "connections"   => $connection_info_fin2 ,
      "props"=>array(),
      "vnfdFlavour" => "Canonical",
      "vnfdVersion" => "1",
      "vnfd"     => $selected_vnfd,        
      "name"     => $context['vnf_name'],
      "_internal_objectType" => "VnfManager/VnfRecord"
    ));
$body = json_encode($body);
$full_url  = "https://$device_ip$port/REST/v2/ServiceMethodExecution/modules/VnfManager/services/Configuration/methods/instantiateVNF";


$instnatiate_vnf= curl_http_get($context['sessionToken'], $full_url,$body, $HTTP_M);

$vnfr_id = json_decode($instnatiate_vnf['wo_newparams']['response_body'],true);

if($instnatiate_vnf['wo_status'] !== ENDED)
{               
    $instnatiate_vnf= prepare_json_response($instnatiate_vnf['wo_status'], "Failed to Instantiate VNF", $instnatiate_vnf, true);   
    echo $instnatiate_vnf;
    exit;
}

$context['instnatiate_vnf'] = $instnatiate_vnf;
$context['vnfr_id'] = $vnfr_id['_internal_objectId'] ;
task_exit(ENDED, "VNF instantiated");

?>