# uCPE
VNF Orchestration on ENEA uCPE
Installation
-------------

Place the Datafiles/ENEA_iso_files under /opt/fmc_repository/Datafiles/ENEA dir of MSA

Place the bpmn dir and the .meta_bpmn file under the /opt/fmc_repository/Datafiles/<OPERATOR_PREFIX>/<UBIQUBE_ID> directory

For the related subtenant Create 3 DS named:

	ENEA-VNF-mngt -> With MS files from: openmsa/Microservices/REST/Generic/ENEA
	
	Flexiwan-DS -> With MS files from: openmsa/Microservices/FLEXIWAN
	
	PFSense_DS -> With MS files from: openmsa/Microservices/PFSENSE/Firewall/fw_policy.xml
