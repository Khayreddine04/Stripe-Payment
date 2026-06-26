<?php
/**
 * Author:     CriticalGears (http://www.CriticalGears.io)
 * Website:    http://www.CriticalGears.io
 * Support:    http://CriticalGears.io/support-tickets/
 * Version:    2.3.3
 *
 * Copyright:   (c)    CriticalGears.io
 *
 *
 */

class PT_Admin_Core extends PT_Core{

    public $styles = array();

    public $footer_scripts = array();

    public $header_scripts = array();

	public $header_template = '';

	public $footer_template = '';

	public $menu = '';

	public PT_User $user;


    private static $_instance = null;

    static public function instance() {
        if(is_null(self::$_instance))
        {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public  function __construct(){
        parent::__construct();
        $this->connect();

        $this->header_template = new PT_Admin_Template("header.php");
        $this->footer_template = new PT_Admin_Template("footer.php");
        $this->user = PT_User::instance();
    }

    public function getHeader(){
        global $admin_menu;

        $admin_menu = st_apply_filter('main_admin_menu',$admin_menu);

        $styles = "";
        foreach($this->styles as $style){
            $styles .= "<link href=\"{$style}\" rel=\"stylesheet\">\n";
        }
        $header_scripts = "";
        foreach($this->header_scripts as $script){
            $header_scripts .= "<script src=\"{$script}\" type=\"application/javascript\"></script>\n";

        }

        $this->header_template->title = "Payment Terminal";
        $this->header_template->header_styles = $styles;
        $this->header_template->header_scripts = $header_scripts;
        $this->header_template->user_logon = $this->user->logon;

        $this->menu = new PT_Admin_Template("menu.php");
        $this->menu->menu_list = st_apply_filter('main_menu',$admin_menu);

        $view_menu = $this->user->logon;
        $this->header_template->menu = st_apply_filter('view_main_menu',$view_menu)?$this->menu->render():"";

        $this->header_template->render(true);
    }

    public function getFooter(){
        $footer_scripts = "";
        foreach($this->footer_scripts as $script){
            $footer_scripts .= "<script src=\"{$script}\"></script>\n";
        }
        $this->footer_template->footer_scripts = $footer_scripts;
        $this->footer_template->dump = $this->getDebug();
        $this->footer_template->render(true);

        ob_end_flush();
    }

    public function addStyle($style){
        $this->styles[$style]=$style;
    }

    public function addScripts($script,$header=true){

        if($header){
            $this->header_scripts[$script]=$script;
        }else{
            $this->footer_scripts[$script]=$script;
        }
    }

}
