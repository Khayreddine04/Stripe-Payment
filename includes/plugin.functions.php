<?php
/******************************************************************************
 * #                         BookingWizz v6.0.2
 * #******************************************************************************
 * #      Author:     CriticalGears (http://www.CriticalGears.io)
 * #      Website:    http://www.criticalgears.io
 * #      Support:    http://criticalgears.com/support-tickets/
 * #      Version:    6.0.2
 * #
 * #      Copyright:   (c)   criticalgears.io
 * #
 * #******************************************************************************/

define("PLUGIN_DIR", "plugins");
define("PLUGIN_MAIN_FILE", "main.php");
define("PLUGIN_PATH",HOME_DIR."/" . PLUGIN_DIR."/");


$ST_install_actions = array();
$ST_uninstall_actions = array();
$ST_admin_pages = array();
$ST_actions = array();
$ST_pages = array();
$ST_custom_menu = array();


function _get_plugins_list(){
    $pluginList = array();

    foreach (scandir(PLUGIN_PATH) as $plugin) {

        $pluginMainFile = PLUGIN_PATH.$plugin."/".PLUGIN_MAIN_FILE;

        if (is_file($pluginMainFile)) {
            $plugin_data = st_get_file_data($pluginMainFile);
            $plugin_data['name'] = $plugin;
            $pluginList[] = $plugin_data;
        }

    }
    return $pluginList;
}

function get_plugins_list(){
    global $AVAILABLE_PLUGINS;
    $pluginList = array();
    foreach ($AVAILABLE_PLUGINS as $plugin){
        $pluginMainFile = PLUGIN_PATH.$plugin['name']."/".PLUGIN_MAIN_FILE;
        if(is_file($pluginMainFile)){
            $plugin_data = st_get_file_data($pluginMainFile);
            $plugin_data['name'] = $plugin['name'];
            $plugin_data['present'] = true;
            $plugin_data['plugin_link'] = $plugin['plugin_link'];
            $pluginList[] = $plugin_data;
        }else{
            $pluginList[] = $plugin;
        }
    }

    return $pluginList;
}

function load_plugins(){
    global $a;

    st_do_action("st_before_load_plugins");
    $activePlugins = getOption('active_plugins');
    if(is_array($activePlugins)){
        foreach($activePlugins as $plugin){
            $pluginMainFile = PLUGIN_PATH.$plugin."/".PLUGIN_MAIN_FILE;
            //print st_dump($pluginMainFile);

            if (is_file($pluginMainFile)) {

                require_once $pluginMainFile;


            }
        }
    }
    st_do_action("st_init");
}

function st_activate_plugin($plugin_name){
    global $mysqli;
    global $ST_install_actions;

    require_once PLUGIN_PATH . $plugin_name . "/" . PLUGIN_MAIN_FILE;

    //st_dump($ST_install_actions);

    foreach($ST_install_actions as $item){
        if($item['plugin_name'] == $plugin_name && !empty($item['install_function'])){

            if(register_plugin($plugin_name)){
                call_user_func($item['install_function']);
                return TRUE;
            }


        }
    }
    return FALSE;
}
function  st_deactivate_plugin($plugin_name){
    global $mysqli;
    global $ST_uninstall_actions;

    require_once PLUGIN_PATH . $plugin_name . "/" . PLUGIN_MAIN_FILE;

    //st_dump($ST_uninstall_actions);
    if(unregister_plugin($plugin_name)){
        foreach($ST_uninstall_actions as $item){

            if($item['plugin_name'] == $plugin_name && !empty($item['uninstall_function'])){
                call_user_func($item['uninstall_function']);
                //deleteOption("custom_menu");

            }

        }
        return TRUE;
    }

    return true;
}

function add_install_action($path,$action_function){
    global $mysqli;
    global $ST_install_actions;

    $pluginName = basename(dirname($path));
    $ST_install_actions[] = array("plugin_name"=>$pluginName,"install_function"=>$action_function);
}

function add_uninstall_action($path,$action_function){
    global $mysqli;
    global $ST_uninstall_actions;

    $pluginName = basename(dirname($path));
    $ST_uninstall_actions[] = array("plugin_name"=>$pluginName,"uninstall_function"=>$action_function);
}

function is_active_plugin($plugin_name){
    global $mysqli;
    $activePlugins = getOption("active_plugins");
    //$activePlugins = unserialize($activePlugins);
    if(is_array($activePlugins)){

        if(in_array($plugin_name, $activePlugins)) return TRUE;

    }
    return FALSE;
}

function register_plugin($plugin_name){
    global $mysqli;

    $activePlugins = getOption("active_plugins");
    if(is_array($activePlugins)){
        $activePlugins = $activePlugins;
        if(in_array($plugin_name, $activePlugins)) return FALSE;

    }else{
        $activePlugins = array();
    }

    $activePlugins[] = $plugin_name;

    updateOption('active_plugins', $activePlugins, true);

    return TRUE;
}

function unregister_plugin($plugin_name){
    global $mysqli;

    $activePlugins = getOption("active_plugins");
    if (is_array($activePlugins)) {
        $activePlugins = $activePlugins;
        if (in_array($plugin_name, $activePlugins)) {
            $activePlugins = array_flip($activePlugins);
            unset($activePlugins[$plugin_name]);
            $activePlugins = array_flip($activePlugins);
            updateOption("active_plugins", $activePlugins, true);
            return true;
        } else {

        }
    } else {
        return true;
    }

}

function st_get_file_data( $file ) {
    global $mysqli;
    // We don't need to write to the file, so just open for reading.
    $fp = fopen( $file, 'r' );

    // Pull only the first 8kiB of the file in.
    $file_data = fread( $fp, 8192 );

    // PHP will close file handle, but we are good citizens.
    fclose( $fp );
    $all_headers = array(
        "plugin_name"=>"Plugin Name",
        "plugin_description"=>"Description",
        "plugin_version"=>"Version"
    );


    foreach ( $all_headers as $field => $regex ) {
        preg_match( '/^[ \t\/*#]*' . preg_quote( $regex, '/' ) . ':(.*)$/mi', $file_data, ${$field});
        if ( !empty( ${$field} ) )
            ${$field} =  ${$field}[1] ;
        else
            ${$field} = '';
    }

    $file_data = compact( array_keys( $all_headers ) );

    return $file_data;
}

function st_add_action($action,$action_function){
    global $mysqli;

    return st_add_filter($action, $action_function);

}

function st_add_filter($filter,$action_function){
    global $mysqli, $ST_actions;
    //print $action_function;

    $ST_actions[$filter][] = array("install_function"=>$action_function);
    return TRUE;
}

function st_do_action($action,$args=''){
    global $mysqli, $ST_actions;
    //$args = array();
    $args = func_get_args();
    if(isset($ST_actions[$action])){

        foreach ($ST_actions[$action] as $do){

            call_user_func_array($do['install_function'],array_slice($args, 1));
            //st_dump($do);
        }

    }
    return TRUE;
}

function st_apply_filter($action,$value){
    global $mysqli, $ST_actions;
    $args = array();


    $args = func_get_args();
    if(isset($ST_actions[$action])){

        foreach ($ST_actions[$action] as $do){

            $value = call_user_func_array($do['install_function'],array_slice($args, 1));
            $args[1] = $value;
        }
        return $value;
    }
    return $value;
}

function add_admin_page($path, $menu_name, $action, $icon="") {
    global $ST_admin_pages, $_menuList, $ST_custom_menu,$mysqli;
    $plugin = basename(dirname($path));
    if (is_active_plugin($plugin)) {
        $ST_admin_pages[] = array(
            "path" => $path,
            "menu_name" => $menu_name
        );

        $menuItem = array(
            "menu_title" => $menu_name,
            "menu_link" => "getAdminPage.php?p=" . urlencode($action),
            "menu_action"=>urlencode($action),
            "menu_icon" => $icon
        );
        $ST_custom_menu[$plugin]=$menuItem;
        //$menuList [$plugin] = $menuItem;
        //st_add_action("get_menu","get_menu");
        //st_dump($ST_admin_pages);
        st_add_action("get_admin_page_$action", $action);

    }
    updateOption("custom_menu",  $ST_custom_menu,true);
}

function add_page($path, $page_name, $action, $icon="") {
    global $ST_pages, $menuList,$mysqli;
    $plugin = basename(dirname($path));
    if (is_active_plugin($plugin)) {
        $ST_pages[] = array(
            "path" => $path,
            "menu_name" => $page_name
        );
        //st_dump($ST_pages);
        st_add_action("st_get_page_$action", $action);
    }
}

function build_plugins_menu(){
    global $mysqli, $ST_custom_menu;
    $menu = '';

    $menu.=count($ST_custom_menu)>1?'<span>Plugins</span><ul>':'';
    foreach($ST_custom_menu as $menuItem){
        $active = strpos($_SERVER['REQUEST_URI'],$menuItem['menu_link'] )?"active":"";
        $menu.="
					<li><a href=\"{$menuItem['menu_link']}\" class=\"{$active}\">{$menuItem['menu_title']}</a></li>
					";
    }
    $menu.=count($ST_custom_menu)>1?'</ul>':'';

    echo $menu;
}

function getOption($option){
    $settings = PT_Settings::instance();
    return $settings->get($option);
}

function updateOption($option,$value,$is_array=false){
    $settings = PT_Settings::instance();
    $settings->updateOption($option,$value,$is_array);
}

function st_get_admin_page($page) {
    global $mysqli;
    //print "bw_get_page_$page";
    st_do_action("get_admin_page_$page");
    return true;
}

function st_get_page($page) {
    global $mysqli;
    //print "bw_get_page_$page";
    st_do_action("get_page_$page");
    return true;
}
