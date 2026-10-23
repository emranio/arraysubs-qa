<?php
defined('ABSPATH') || exit;
$path='/tmp/arraysubs-final-access-tokens.json';
$tokens=json_decode(file_get_contents($path),true);$checks=[];
foreach($tokens as $entry){$manager=WP_Session_Tokens::get_instance((int)$entry['id']);$manager->destroy($entry['token']);$checks[]=['user'=>(int)$entry['id'],'temporaryTokenRevoked'=>!$manager->verify($entry['token'])];}
$paths=[$path,'/tmp/arraysubs-final-access-admin-state.json','/tmp/arraysubs-final-access-member-state.json','/tmp/arraysubs-final-access-customer-state.json'];
foreach($paths as $file){if(is_file($file))unlink($file);}
echo 'QA_RESULT:'.wp_json_encode(['tokens'=>$checks,'privateFilesRemoved'=>count(array_filter($paths,'file_exists'))===0]);
