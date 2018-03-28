<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');


include ('include/MVC/preDispatch.php');
require_once('include/entryPoint.php');
$db = DBManagerFactory::getInstance();

function select_query($db, $action){
	$result = $db->fetchRow($db->query("SELECT `gluu_value` FROM `gluu_table` WHERE `gluu_action` LIKE '$action'"))["gluu_value"];
	return $result;
}
function get_protection_access_token(){
    require_once("modules/Gluussos/oxd-rp/Get_client_access_token.php");
    $db = DBManagerFactory::getInstance();
    $gluu_config =   json_decode(select_query($db, "gluu_config"),true);
    $gluu_provider = select_query($db, 'gluu_provider');
    if($gluu_config["has_registration_endpoint"] != 1 || $gluu_config["has_registration_endpoint"] != true){
        return null;
    }
    $get_client_access_token = new Get_client_access_token();
    $get_client_access_token->setRequest_client_id($gluu_config['gluu_client_id']);
    $get_client_access_token->setRequest_client_secret($gluu_config['gluu_client_secret']);
    $get_client_access_token->setRequestOpHost($gluu_provider);

    if($gluu_config['oxd_request_pattern'] == 2){
        $status = $get_client_access_token->request(trim($gluu_config['gluu_oxd_host'],"/")."/get-client-token");
    } else {
        $status = $get_client_access_token->request();
    }
    if($status == false){
        return false;
    }

    return $get_client_access_token->getResponse_access_token();
}
if(isset($_SESSION['session_in_op'])){
	if(time()<(int)$_SESSION['session_in_op']) {
		require_once("modules/Gluussos/oxd-rp/Logout.php");
		$db = DBManagerFactory::getInstance();
		$gluu_provider = select_query($db, 'gluu_provider');
		$arrContextOptions=array(
			"ssl"=>array(
				"verify_peer"=>false,
				"verify_peer_name"=>false,
			),
		);
		$json = file_get_contents($gluu_provider.'/.well-known/openid-configuration', false, stream_context_create($arrContextOptions));
		$obj = json_decode($json);

		$oxd_id = select_query($db, 'gluu_oxd_id');
		$gluu_config = json_decode(select_query($db, 'gluu_config'), true);
		if (!empty($obj->end_session_endpoint ) or $gluu_provider == 'https://accounts.google.com') {
			if (!empty($_SESSION['user_oxd_id_token'])) {
				if ($oxd_id && $_SESSION['user_oxd_id_token'] && $_SESSION['session_in_op']) {
					$logout = new Logout();
					$logout->setRequestOxdId($oxd_id);
					$logout->setRequestIdToken($_SESSION['user_oxd_id_token']);
					$logout->setRequestPostLogoutRedirectUri($gluu_config['post_logout_redirect_uri']);
					$logout->setRequestSessionState($_SESSION['session_state']);
					$logout->setRequestState($_SESSION['state']);
					$logout->setRequest_protection_access_token(get_protection_access_token());
                                        if($gluu_config["oxd_request_pattern"] == 2){
                                            $logout->request(trim($gluu_config["gluu_oxd_host"],"/")."/get-logout-uri");
                                        } else {
                                            $logout->request();
                                        }
					unset($_SESSION['user_oxd_access_token']);
					unset($_SESSION['user_oxd_id_token']);
					unset($_SESSION['session_state']);
					unset($_SESSION['state']);
					unset($_SESSION['session_in_op']);
					header("Location: " . $logout->getResponseObject()->data->uri);
					exit;
				}
			}
		} else {
			unset($_SESSION['user_oxd_access_token']);
			unset($_SESSION['user_oxd_id_token']);
			unset($_SESSION['session_state']);
			unset($_SESSION['state']);
			unset($_SESSION['session_in_op']);
		}
	}
}
// record the last theme the user used
$current_user->setPreference('lastTheme',$theme);
$GLOBALS['current_user']->call_custom_logic('before_logout');

// submitted by Tim Scott from SugarCRM forums
foreach($_SESSION as $key => $val) {
	$_SESSION[$key] = ''; // cannot just overwrite session data, causes segfaults in some versions of PHP
}
if(isset($_COOKIE[session_name()])) {
	setcookie(session_name(), '', time()-42000, '/',null,false,true);
}

//Update the tracker_sessions table
// clear out the authenticating flag
session_destroy();

LogicHook::initialize();
$GLOBALS['logic_hook']->call_custom_logic('Users', 'after_logout');

/** @var AuthenticationController $authController */
session_start();
session_destroy();
ob_clean();
$gluu_custom_logout = select_query($db, 'gluu_custom_logout');
if(!empty($gluu_custom_logout)){
	header("Location: $gluu_custom_logout");
}else{
	header('Location: index.php?module=Users&action=Login');
}
sugar_cleanup(true);

