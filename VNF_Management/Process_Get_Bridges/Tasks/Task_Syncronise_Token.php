<?php
require_once '/opt/fmc_repository/Process/Reference/Common/common.php';
require_once '/opt/fmc_repository/Process/ENEA/VNF_Management/common.php';

function list_args()
{
    create_var_def('device_id', 'Device');
}
/*
if(isset($parameters) ){
    $context['device_id'] = $parameters['device_id'];
    $context['vnf_descriptor'] = $parameters['vnf_descriptor'];
    $context['ucpe_devices'] = $parameters['ucpe_devices'];
    $context['vnf_name'] = $parameters['vnf_name'];
    $context['customer_id'] = $parameters['customer_id'];
    $context['manufacturer_id'] = $parameters['manufacturer_id'];
    $context['model_id'] = $parameters['model_id'];
    $context['device_ip_address'] = $parameters['device_ip_address'];
    $context['login'] = $parameters['login'];
    $context['password	'] = $parameters['password'];
    $context['new_password'] = $parameters['new_password'];
    $context['snmp_community'] = $parameters['snmp_community'];
    $context['conf_profile_reference'] = $parameters['conf_profile_reference'];
    $context['mon_profile_reference'] = $parameters['mon_profile_reference'];

 }
*/
$device_id = substr($context['device_id'], 3);
//call device to get device info
$response = _device_read_by_id($device_id);

$response = json_decode($response, true);
if ($response['wo_status'] !== ENDED) {
	$response = json_encode($response);
	echo $response;
	exit;
}

$device_ip = $response['wo_newparams']['managementAddress'];
$port = $response['wo_newparams']['managementInterface'];
if($device_id == 302)
{ $port = "31204";}

logToFile(debug_dump($response,"**************RESPONSE ***********\n" ));
$port = ($port != "") ? $port :"";



//Login to get the session key for api calls
$login_session = login($response['wo_newparams']['login'], $response['wo_newparams']['password'], $device_ip , $port);

logToFile("**************$login_session***********\n");
if ($login_session == "") {	
	echo $login_session;
	exit;
}
$port = ($port != "") ? ":".$port :"";
$context['sessionToken']= $login_session;
$context['port']= $port ;
$context['device_ip'] = $device_ip;


task_exit(ENDED, "Session Token retreived");
?>