<?php
/**
 * Created by Vlad Karapetyan
 */
require_once("modules/Gluussos/oxd-rp/Get_tokens_by_code.php");
require_once("modules/Gluussos/oxd-rp/Get_user_info.php");
include ('include/MVC/preDispatch.php');
$startTime = microtime(true);
require_once('include/entryPoint.php');
ob_start();
require_once('include/MVC/SugarApplication.php');
$app = new SugarApplication();
$app->startSession();
function ketBasePath($str='') {
    if ( isset($_SERVER['HTTP_HOST']) ) { $host = $_SERVER['HTTP_HOST']; }
    else if ( isset($_SERVER['SERVER_NAME']) ) { $host = $_SERVER['SERVER_NAME']; }
    else { $host = ''; }
    if (!$str) {
        if ($_SERVER['SCRIPT_NAME']) { $currentPath = dirname($_SERVER['SCRIPT_NAME']); }
        else { $currentPath = dirname($_SERVER['PHP_SELF']); }
        $currentPath = str_replace("\\","/",$currentPath);
        if ($currentPath == '/') { $currentPath = ''; }
        if ($host) { $currpath = 'http://' . $host . $currentPath .'/'; }
        else { $currpath = ''; }
        return $currpath;
    }
}
$base_url  = ketBasePath();
$db = DBManagerFactory::getInstance();
if( isset( $_REQUEST['gluu_login'] ) and strpos( $_REQUEST['gluu_login'], 'Gluussos' ) !== false ) {
    if (isset($_REQUEST['error']) and strpos($_REQUEST['error'], 'session_selection_required') !== false) {
        header("Location: " . login_url('login'));
        exit;
    }

    $oxd_id = select_query($db, 'gluu_oxd_id');
    $gluu_user_role = select_query($db, 'gluu_user_role');
    $http = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? "https://" : "http://";
    $parts = parse_url($http . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
    parse_str($parts['query'], $query);
    $get_tokens_by_code = new Get_tokens_by_code();
    $get_tokens_by_code->setRequestOxdId($oxd_id);
    $get_tokens_by_code->setRequestCode($_REQUEST['code']);
    $get_tokens_by_code->setRequestState($_REQUEST['state']);
    $get_tokens_by_code->request();
    //var_dump($get_tokens_by_code->getResponseObject());exit;
    $get_tokens_by_code_array = $get_tokens_by_code->getResponseObject()->data->id_token_claims;
    $get_user_info = new Get_user_info();
    $get_user_info->setRequestOxdId($oxd_id);
    $get_user_info->setRequestAccessToken($get_tokens_by_code->getResponseAccessToken());
    $get_user_info->request();
    $get_user_info_array = $get_user_info->getResponseObject()->data->claims;
    $_SESSION['session_in_op'] = $get_tokens_by_code->getResponseIdTokenClaims()->exp[0];
    $_SESSION['user_oxd_id_token'] = $get_tokens_by_code->getResponseIdToken();
    $_SESSION['user_oxd_access_token'] = $get_tokens_by_code->getResponseAccessToken();
    $_SESSION['session_state'] = $_REQUEST['session_state'];
    $_SESSION['state'] = $_REQUEST['state'];

    $get_user_info_array = $get_user_info->getResponseObject()->data->claims;

    $reg_first_name = '';
    $reg_user_name = '';
    $reg_last_name = '';
    $reg_email = '';
    $reg_avatar = '';
    $reg_display_name = '';
    $reg_nikname = '';
    $reg_website = '';
    $reg_middle_name = '';
    $reg_country = '';
    $reg_city = '';
    $reg_region = '';
    $reg_gender = '';
    $reg_postal_code = '';
    $reg_fax = '';
    $reg_home_phone_number = '';
    $reg_phone_mobile_number = '';
    $reg_street_address = '';
    $reg_street_address_2 = '';
    $reg_birthdate = '';
    if (!empty($get_user_info_array->website[0])) {
        $reg_website = $get_user_info_array->website[0];
    } elseif (!empty($get_tokens_by_code_array->website[0])) {
        $reg_website = $get_tokens_by_code_array->website[0];
    }
    if (!empty($get_user_info_array->nickname[0])) {
        $reg_nikname = $get_user_info_array->nickname[0];
    } elseif (!empty($get_tokens_by_code_array->nickname[0])) {
        $reg_nikname = $get_tokens_by_code_array->nickname[0];
    }
    if (!empty($get_user_info_array->name[0])) {
        $reg_display_name = $get_user_info_array->name[0];
    } elseif (!empty($get_tokens_by_code_array->name[0])) {
        $reg_display_name = $get_tokens_by_code_array->name[0];
    }
    if (!empty($get_user_info_array->given_name[0])) {
        $reg_first_name = $get_user_info_array->given_name[0];
    } elseif (!empty($get_tokens_by_code_array->given_name[0])) {
        $reg_first_name = $get_tokens_by_code_array->given_name[0];
    }
    if (!empty($get_user_info_array->family_name[0])) {
        $reg_last_name = $get_user_info_array->family_name[0];
    } elseif (!empty($get_tokens_by_code_array->family_name[0])) {
        $reg_last_name = $get_tokens_by_code_array->family_name[0];
    }
    if (!empty($get_user_info_array->middle_name[0])) {
        $reg_middle_name = $get_user_info_array->middle_name[0];
    } elseif (!empty($get_tokens_by_code_array->middle_name[0])) {
        $reg_middle_name = $get_tokens_by_code_array->middle_name[0];
    }
    if (empty($get_user_info_array->email[0])) {
        $reg_email = $get_user_info_array->email[0];
    } elseif (empty($get_tokens_by_code_array->email[0])) {
        $reg_email = $get_tokens_by_code_array->email[0];
    }else{
        echo "<script type='application/javascript'>
					alert('Missing claims : (email or username). Please talk to your organizational system administrator.');
					location.href='".$base_url."';
				 </script>";
        exit;
    }
    if (!empty($get_user_info_array->country[0])) {
        $reg_country = $get_user_info_array->country[0];
    } elseif (!empty($get_tokens_by_code_array->country[0])) {
        $reg_country = $get_tokens_by_code_array->country[0];
    }
    if (!empty($get_user_info_array->gender[0])) {
        if ($get_user_info_array->gender[0] == 'male') {
            $reg_gender = '1';
        } else {
            $reg_gender = '2';
        }

    } elseif (!empty($get_tokens_by_code_array->gender[0])) {
        if ($get_tokens_by_code_array->gender[0] == 'male') {
            $reg_gender = '1';
        } else {
            $reg_gender = '2';
        }
    }
    if (!empty($get_user_info_array->locality[0])) {
        $reg_city = $get_user_info_array->locality[0];
    } elseif (!empty($get_tokens_by_code_array->locality[0])) {
        $reg_city = $get_tokens_by_code_array->locality[0];
    }
    if (!empty($get_user_info_array->postal_code[0])) {
        $reg_postal_code = $get_user_info_array->postal_code[0];
    } elseif (!empty($get_tokens_by_code_array->postal_code[0])) {
        $reg_postal_code = $get_tokens_by_code_array->postal_code[0];
    }
    if (!empty($get_user_info_array->phone_number[0])) {
        $reg_home_phone_number = $get_user_info_array->phone_number[0];
    } elseif (!empty($get_tokens_by_code_array->phone_number[0])) {
        $reg_home_phone_number = $get_tokens_by_code_array->phone_number[0];
    }
    if (!empty($get_user_info_array->phone_mobile_number[0])) {
        $reg_phone_mobile_number = $get_user_info_array->phone_mobile_number[0];
    } elseif (!empty($get_tokens_by_code_array->phone_mobile_number[0])) {
        $reg_phone_mobile_number = $get_tokens_by_code_array->phone_mobile_number[0];
    }
    if (!empty($get_user_info_array->picture[0])) {
        $reg_avatar = $get_user_info_array->picture[0];
    } elseif (!empty($get_tokens_by_code_array->picture[0])) {
        $reg_avatar = $get_tokens_by_code_array->picture[0];
    }
    if (!empty($get_user_info_array->street_address[0])) {
        $reg_street_address = $get_user_info_array->street_address[0];
    } elseif (!empty($get_tokens_by_code_array->street_address[0])) {
        $reg_street_address = $get_tokens_by_code_array->street_address[0];
    }
    if (!empty($get_user_info_array->street_address[1])) {
        $reg_street_address_2 = $get_user_info_array->street_address[1];
    } elseif (!empty($get_tokens_by_code_array->street_address[1])) {
        $reg_street_address_2 = $get_tokens_by_code_array->street_address[1];
    }
    if (!empty($get_user_info_array->birthdate[0])) {
        $reg_birthdate = $get_user_info_array->birthdate[0];
    } elseif (!empty($get_tokens_by_code_array->birthdate[0])) {
        $reg_birthdate = $get_tokens_by_code_array->birthdate[0];
    }
    if (!empty($get_user_info_array->region[0])) {
        $reg_region = $get_user_info_array->region[0];
    } elseif (!empty($get_tokens_by_code_array->region[0])) {
        $reg_region = $get_tokens_by_code_array->region[0];
    }

    $username = '';
    if (!empty($get_user_info_array->user_name[0])) {
        $username = $get_user_info_array->user_name[0];
    } else {
        $email_split = explode("@", $reg_email);
        $username = $email_split[0];
    }

    $user_hash = User::getPasswordHash($reg_email);
    $ut = $GLOBALS['current_user']->getPreference('ut');
    include_once('modules/Users/authentication/AuthenticationController.php');
    $login = new AuthenticationController();
    if ($login->login($reg_email, $reg_email, $PARAMS = array())) {
        $user = new User();
        $user->id = $GLOBALS['current_user']->id;
        $user->user_name = $reg_email;
        $user->email1 = $reg_email;
        $user->employee_status = 'Active';
        $user->status = 'Active';
        $user->user_hash = $user_hash;
        $user->last_name = $reg_last_name;
        $user->first_name = $reg_first_name;
        $user->is_admin = $gluu_user_role;
        $user->phone_home = $reg_home_phone_number;
        $user->phone_mobile = $reg_phone_mobile_number;
        $user->address_street = $reg_street_address;
        $user->address_city = $reg_city;
        $user->address_country = $reg_country;
        $user->address_postalcode = $reg_postal_code;
        $user->external_auth_only = 0;
        $user->save();

        header("Location: index.php?action=index&module=Home");
    } else {
        $user = new User();
        $user->user_name = $reg_email;
        $user->email1 = $reg_email;
        $user->employee_status = 'Active';
        $user->status = 'Active';
        $user->user_hash = $user_hash;
        $user->last_name = $reg_last_name;
        $user->first_name = $reg_first_name;
        $user->is_admin = $gluu_user_role;
        $user->phone_home = $reg_home_phone_number;
        $user->phone_mobile = $reg_phone_mobile_number;
        $user->address_street = $reg_street_address;
        $user->address_city = $reg_city;
        $user->address_country = $reg_country;
        $user->address_postalcode = $reg_postal_code;
        $user->external_auth_only = 0;
        $user->save();
        $login1 = new AuthenticationController();
        $login1->login($reg_email, $get_user_info_array->sub, $PARAMS = array());
        header("Location: index.php?action=index&module=Home");
    }
}


function select_query($db, $action){
    $result = $db->fetchRow($db->query("SELECT `gluu_value` FROM `gluu_table` WHERE `gluu_action` LIKE '$action'"))["gluu_value"];
    return $result;
}

function login_url($prompt){
    $db = DBManagerFactory::getInstance();
    $gluu_config           = json_decode(select_query($db, 'gluu_config'),true);
    $gluu_auth_type        = select_query($db, 'gluu_auth_type');
    $gluu_oxd_id        = select_query($db, 'gluu_oxd_id');
    $oxd_id = select_query($db, 'gluu_oxd_id');
    require_once("modules/Gluussos/oxd-rp/Get_authorization_url.php");

    $get_authorization_url = new Get_authorization_url();
    $get_authorization_url->setRequestOxdId($oxd_id);


    $get_authorization_url->setRequestScope($gluu_config['config_scopes']);
    if($gluu_auth_type != "default"){
        $get_authorization_url->setRequestAcrValues([$gluu_auth_type]);
    }else{
        $get_authorization_url->setRequestAcrValues(null);
    }


    $get_authorization_url->setRequestPrompt($prompt);
    $get_authorization_url->request();

    return $get_authorization_url->getResponseAuthorizationUrl();
}



