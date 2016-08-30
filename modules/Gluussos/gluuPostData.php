<?php
require_once("modules/Gluussos/oxd-rp/Register_site.php");
require_once("modules/Gluussos/oxd-rp/Update_site_registration.php");
ob_start();
require_once('include/MVC/SugarApplication.php');
$app = new SugarApplication();
$app->startSession();
$base_url  = @( $_SERVER["HTTPS"] != 'on' ) ? 'http://'.$_SERVER["SERVER_NAME"] :  'https://'.$_SERVER["SERVER_NAME"];
$db = DBManagerFactory::getInstance();
function remove_http($url) {
    $disallowed = array('http://', 'https://');
    foreach($disallowed as $d) {
        if(strpos($url, $d) === 0) {
            return str_replace($d, '', $url);
        }
    }
    return $url;
}
if( isset( $_REQUEST['form_key'] ) and strpos( $_REQUEST['form_key'], 'general_register_page' ) !== false ) {
    if(!isset($_SERVER['HTTPS']) or $_SERVER['HTTPS'] != "on") {
        $_SESSION['message_error'] = 'OpenID Connect requires https. This plugin will not work if your website uses http only.';
        SugarApplication::redirect('index.php?module=Gluussos&action=general');
        return;
    }
    $sugar_config['http_referer']['list'][] = remove_http($_POST['gluu_server_url']);
    $config_option = json_encode(array(
        "op_host" => $_POST['gluu_server_url'],
        "oxd_host_ip" => '127.0.0.1',
        "oxd_host_port" =>$_POST['oxd_port'],
        "admin_email" => $_POST['loginemail'],
        "authorization_redirect_uri" => $base_url.'/gluu.php?gluu_login=Gluussos',
        "logout_redirect_uri" => $base_url.'/gluu_logout.php?gluu_login=Gluussos',
        "scope" => ["openid","profile","email","address","clientinfo","mobile_phone","phone"],
        "grant_types" =>["authorization_code"],
        "response_types" => ["code"],
        "application_type" => "web",
        "acr_values" => [],
    ));
    $db->query("UPDATE `gluu_table` SET `gluu_value` = '$config_option' WHERE `gluu_action` LIKE 'oxd_config';");
    $config_option = array(
        "op_host" => $_POST['gluu_server_url'],
        "oxd_host_ip" => '127.0.0.1',
        "oxd_host_port" =>$_POST['oxd_port'],
        "admin_email" => $_POST['loginemail'],
        "authorization_redirect_uri" => $base_url.'/gluu.php?gluu_login=Gluussos',
        "logout_redirect_uri" => $base_url.'/gluu_logout.php?gluu_login=Gluussos',
        "scope" => ["openid","profile","email","address","clientinfo","mobile_phone","phone"],
        "grant_types" =>["authorization_code"],
        "response_types" => ["code"],
        "application_type" => "web",
        "acr_values" => [],
    );
    $register_site = new Register_site();
    $register_site->setRequestOpHost($config_option['op_host']);
    $register_site->setRequestAcrValues($config_option['acr_values']);
    $register_site->setRequestAuthorizationRedirectUri($config_option['authorization_redirect_uri']);
    $register_site->setRequestGrantTypes($config_option['grant_types']);
    $register_site->setRequestResponseTypes(['code']);
    $register_site->setRequestLogoutRedirectUri($config_option['logout_redirect_uri']);
    $register_site->setRequestContacts([$config_option["admin_email"]]);
    $register_site->setRequestApplicationType('web');
    $register_site->setRequestClientLogoutUri($config_option['logout_redirect_uri']);
    $register_site->setRequestScope($config_option['scope']);
    $status = $register_site->request();
    if(!$status['status']){
        $_SESSION['message_error'] = $status['message'];
        SugarApplication::redirect('index.php?module=Gluussos&action=general');
        return;
    }
    if($register_site->getResponseOxdId()){
        $oxd_id = $register_site->getResponseOxdId();
        if($db->query("SELECT `gluu_value` FROM `gluu_table` WHERE `gluu_action` LIKE 'oxd_id'")){
            $db->query("INSERT INTO gluu_table (gluu_action, gluu_value) VALUES ('oxd_id','$oxd_id')");
        }
    }
    $_SESSION['message_success'] = 'Site registered Successful. You can configure Gluu and Social Login now.';
    SugarApplication::redirect('index.php?module=Gluussos&action=general');
}
else if( isset( $_REQUEST['form_key'] ) and strpos( $_REQUEST['form_key'], 'openid_config_delete_scop' ) !== false ) {
    $get_scopes =   json_decode($db->fetchRow($db->query("SELECT `gluu_value` FROM `gluu_table` WHERE `gluu_action` LIKE 'scopes'"))["gluu_value"],true);
    $up_cust_sc =  array();
    foreach($get_scopes as $custom_scop){
        if($custom_scop !=$_REQUEST['value_scope']){
            array_push($up_cust_sc,$custom_scop);
        }
    }
    $get_scopes = json_encode($up_cust_sc);
    $result = $db->query("UPDATE `sugar`.`gluu_table` SET `gluu_value` = '$get_scopes' WHERE `gluu_action` LIKE 'scopes';");
    $_SESSION['message_success'] = 'Scope deleted Successfully.';
    SugarApplication::redirect('index.php?module=Gluussos&action=general');
}
else if( isset( $_REQUEST['form_key'] ) and strpos( $_REQUEST['form_key'], 'general_oxd_id_reset' )  !== false and !empty($_REQUEST['resetButton'])) {
    $db->query("DROP TABLE IF EXISTS `gluu_table`;");
    $_SESSION['message_success'] = 'Configurations deleted Successfully.';
    SugarApplication::redirect('index.php?module=Gluussos&action=general');
}
else if( isset( $_REQUEST['form_key'] ) and strpos( $_REQUEST['form_key'], 'openid_config_delete_custom_scripts' ) !== false ) {
    $get_scopes =   json_decode($db->fetchRow($db->query("SELECT `gluu_value` FROM `gluu_table` WHERE `gluu_action` LIKE 'custom_scripts'"))["gluu_value"],true);
    $up_cust_sc =  array();
    foreach($get_scopes as $custom_scop){
        if($custom_scop['value'] !=$_REQUEST['value_script']){
            array_push($up_cust_sc,$custom_scop);
        }
    }
    $get_scopes = json_encode($up_cust_sc);
    $db->query("UPDATE `sugar`.`gluu_table` SET `gluu_value` = '$get_scopes' WHERE `gluu_action` LIKE 'custom_scripts';");
    $_SESSION['message_success'] = 'Custom script deleted Successfully.';
    SugarApplication::redirect('index.php?module=Gluussos&action=general');
}
else if( isset( $_REQUEST['form_key'] ) and strpos( $_REQUEST['form_key'], 'sugar_crm_config_page' ) !== false ) {

    $db->query("UPDATE `gluu_table` SET `gluu_value` = '".$_REQUEST['gluuoxd_openid_login_theme']."' WHERE `gluu_action` LIKE 'loginTheme';");
    $db->query("UPDATE `gluu_table` SET `gluu_value` = '".$_REQUEST['gluuoxd_openid_login_custom_theme']."' WHERE `gluu_action` LIKE 'loginCustomTheme';");
    $db->query("UPDATE `gluu_table` SET `gluu_value` = '".$_REQUEST['gluuox_login_icon_space']."' WHERE `gluu_action` LIKE 'iconSpace';");
    $db->query("UPDATE `gluu_table` SET `gluu_value` = '".$_REQUEST['gluuox_login_icon_custom_size']."' WHERE `gluu_action` LIKE 'iconCustomSize';");
    $db->query("UPDATE `gluu_table` SET `gluu_value` = '".$_REQUEST['gluuox_login_icon_custom_width']."' WHERE `gluu_action` LIKE 'iconCustomWidth';");
    $db->query("UPDATE `gluu_table` SET `gluu_value` = '".$_REQUEST['gluuox_login_icon_custom_height']."' WHERE `gluu_action` LIKE 'iconCustomHeight';");
    $db->query("UPDATE `gluu_table` SET `gluu_value` = '".$_REQUEST['gluuox_login_icon_custom_color']."' WHERE `gluu_action` LIKE 'iconCustomColor';");
    $_SESSION['message_success'] = 'Your configuration has been saved.';
    SugarApplication::redirect('index.php?module=Gluussos&action=general');
}
else if( isset( $_REQUEST['form_key'] ) and strpos( $_REQUEST['form_key'], 'openid_config_page' ) !== false ) {
    $params = $_REQUEST;
    $message_success = '';
    $message_error = '';
    if(!empty($params['scope']) && isset($params['scope'])){
        $oxd_config =   json_decode($db->fetchRow($db->query("SELECT `gluu_value` FROM `gluu_table` WHERE `gluu_action` LIKE 'oxd_config'"))["gluu_value"],true);
        $oxd_config['scope'] = $params['scope'];
        $oxd_config = json_encode($oxd_config);
        $result = $db->query("UPDATE `gluu_table` SET `gluu_value` = '$oxd_config' WHERE `gluu_action` LIKE 'oxd_config';");
    }
    if(!empty($params['scope_name']) && isset($params['scope_name'])){
        $get_scopes = json_decode($db->fetchRow($db->query("SELECT `gluu_value` FROM `gluu_table` WHERE `gluu_action` LIKE 'scopes'"))["gluu_value"],true);
        foreach($params['scope_name'] as $scope){
            if($scope && !in_array($scope,$get_scopes)){
                array_push($get_scopes, $scope);
            }
        }
        $get_scopes = json_encode($get_scopes);
        $db->query("UPDATE `gluu_table` SET `gluu_value` = '$get_scopes' WHERE `gluu_action` LIKE 'scopes';");
    }
    $config_option = json_decode($db->fetchRow($db->query("SELECT `gluu_value` FROM `gluu_table` WHERE `gluu_action` LIKE 'oxd_config'"))["gluu_value"],true);
    $oxd_id = $db->fetchRow($db->query("SELECT `gluu_value` FROM `gluu_table` WHERE `gluu_action` LIKE 'oxd_id'"))["gluu_value"];
    $update_site_registration = new Update_site_registration();
    $update_site_registration->setRequestOxdId($oxd_id);
    $update_site_registration->setRequestAcrValues($config_option['acr_values']);
    $update_site_registration->setRequestAuthorizationRedirectUri($config_option['authorization_redirect_uri']);
    $update_site_registration->setRequestGrantTypes($config_option['grant_types']);
    $update_site_registration->setRequestResponseTypes(['code']);
    $update_site_registration->setRequestLogoutRedirectUri($config_option['logout_redirect_uri']);
    $update_site_registration->setRequestContacts([$oxd_config['admin_email']]);
    $update_site_registration->setRequestApplicationType('web');
    $update_site_registration->setRequestClientLogoutUri($config_option['logout_redirect_uri']);
    $update_site_registration->setRequestScope($config_option['scope']);
    $status = $update_site_registration->request();
    $new_oxd_id = $update_site_registration->getResponseOxdId();
    if($new_oxd_id){
        $result = $db->query("UPDATE `gluu_table` SET `gluu_value` = '$new_oxd_id' WHERE `gluu_action` LIKE 'oxd_id';");
    }
    $custom_scripts =   json_decode($db->fetchRow($db->query("SELECT `gluu_value` FROM `gluu_table` WHERE `gluu_action` LIKE 'custom_scripts'"))["gluu_value"],true);

    foreach($custom_scripts as $custom_script){
        $action = $custom_script['value']."Enable";
        $value = $params['gluuoxd_openid_'.$custom_script['value'].'_enable'];
        $typeLogin =  $db->fetchRow($db->query("SELECT `gluu_value` FROM `gluu_table` WHERE `gluu_action` LIKE '$action'"))["gluu_value"];
        if(!$typeLogin){
            $db->query("INSERT INTO gluu_table (gluu_action, gluu_value) VALUES ('$action','$value')");
        }
        if($value != NULL){
            $db->query("UPDATE `gluu_table` SET `gluu_value` = '1' WHERE `gluu_action` LIKE '$action';");
        }else{
            $db->query("UPDATE `gluu_table` SET `gluu_value` = '0' WHERE `gluu_action` LIKE '$action';");
        }

    }

    if(isset($params['count_scripts'])){
        $error_array = array();
        $error = true;

        $custom_scripts = json_decode($db->fetchRow($db->query("SELECT `gluu_value` FROM `gluu_table` WHERE `gluu_action` LIKE 'custom_scripts'"))["gluu_value"],true);
        for($i=1; $i<=$params['count_scripts']; $i++){
            if(isset($params['name_in_site_'.$i]) && !empty($params['name_in_site_'.$i]) && isset($params['name_in_gluu_'.$i]) && !empty($params['name_in_gluu_'.$i]) && isset($_FILES['images_'.$i]) && !empty($_FILES['images_'.$i])){
                foreach($custom_scripts as $custom_script){
                    if($custom_script['value'] == $params['name_in_gluu_'.$i] || $custom_script['name'] == $params['name_in_site_'.$i]){
                        $error = false;
                        array_push($error_array, $i);
                    }
                }
                if($error){
                    $target_dir = "modules/Gluussos/GluuOxd_Openid/images/icons/";
                    $target_file = $target_dir . basename($_FILES['images_'.$i]["name"]);
                    $uploadOk = 1;
                    $imageFileType = pathinfo($target_file,PATHINFO_EXTENSION);
                    if (file_exists($target_file)) {
                        $target_file= $target_dir.file_newname($target_dir, basename($_FILES['images_'.$i]["name"]));

                    }

                    if (move_uploaded_file($_FILES['images_'.$i]["tmp_name"], $target_file)) {
                        array_push($custom_scripts, array('name'=>$params['name_in_site_'.$i],'image'=>$target_file,'value'=>$params['name_in_gluu_'.$i]));
                        $custom_scripts_json = json_encode($custom_scripts);
                        $db->query("UPDATE `gluu_table` SET `gluu_value` = '$custom_scripts_json' WHERE `gluu_action` LIKE 'custom_scripts';");

                    } else {
                        $message_error.= "Sorry, there was an error uploading ".$_FILES['images_'.$i]["name"]." file.<br/>";
                        break;
                    }

                }else{
                    $message_error.='Name = '.$params['name_in_site_'.$i]. ' or value = '. $params['name_in_gluu_'.$i]. ' is exist.<br/>';
                    break;
                }
            }else{
                if(!empty($params['name_in_site_'.$i]) || !empty($params['name_in_gluu_'.$i]) || !empty($_FILES['images_'.$i]["name"])){
                    $message_error.='Necessary to fill the hole row.<br/>';
                }
            }
        }
        //$storeConfig ->saveConfig('gluu/oxd/oxd_openid_custom_scripts',serialize($custom_scripts), 'default', 0);
    }
    $_SESSION['message_success'] = 'Your OpenID connect configuration has been saved.';
    $_SESSION['message_error'] = $message_error;
    SugarApplication::redirect('index.php?module=Gluussos&action=general');
    exit;
}
function file_newname($path, $filename){
    if ($pos = strrpos($filename, '.')) {
        $name = substr($filename, 0, $pos);
        $ext = substr($filename, $pos);
    } else {
        $name = $filename;
    }

    $newpath = $path.'/'.$filename;
    $newname = $filename;
    $counter = 0;
    while (file_exists($newpath)) {
        $newname = $name .'_'. $counter . $ext;
        $newpath = $path.'/'.$newname;
        $counter++;
    }

    return $newname;
}

?>
