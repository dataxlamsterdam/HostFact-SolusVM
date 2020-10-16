<?php

$version['name']            = "SolusVM";
$version['api_version']     = "1.0";
$version['date']            = "2020-10-16"; // Last modification date
$version['version']         = "5.1.6"; // Version released for HostFact

// Information for customer (will be showed at registrar-show-page)
$version['dev_logo']		= 'https://dataxl.nl/images/dataxl.svg'; // URL to your logo
$version['dev_author']		= 'DataXL B.V.'; // Your companyname
$version['dev_website']		= 'https://dataxl.nl'; // URL website
$version['dev_email']		= 'info@dataxl.nl'; // Your e-mailaddress for support questions
$version['dev_phone']		= '+31 (0)20 213 4100'; // Your phone number for support questions

// when you need additional settings when creating a VPS node, set this to true. See function showSettingsHTML()
$version['hasAdditionalSettings']           = TRUE; 

// if the VPS platform does not support creating a VPS server with custom properties (eg diskspace, cpu cores), set this to false
// the result is that you cannot adjust package properties when creating/editing a VPS server in HostFact
$version['supports_custom_vps_properties']  = FALSE;
                                                
?>