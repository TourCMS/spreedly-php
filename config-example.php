<?php 
// Set up Spreedly object

	// Environment (e.g. Test)
	$sly_environment = '';
	
	// Access secret (can be personal or app specific)
	$sly_access_secret = '';
	
	// Optional, used for signing callbacks, e.g. for PayPal etc
	$sly_signing_secret = '';
	
	$sly = new Spreedly($sly_environment, $sly_access_secret, $sly_signing_secret);
?>