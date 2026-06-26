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

class PT_Admin_Template extends PT_Template{

     /**
      * Template string
      * @var string
      */
     public $template='';

     /**
      * @var string
      */
     private $templateDir="templates";


     /**
      * @param $template
      * @param array $data
      */
     public function __construct($template,$data=array()){
         $this->template = $template;
         $this->templateData = $data;

        //TODO exception
         $templatePath = HOME_DIR."/backoffice/includes/{$this->templateDir}/{$this->template}";
         if(!is_file($templatePath))
             echo("Template {$this->template} not found");

         $this->templatePath = $templatePath;
         $settings = PT_Settings::instance();
         $this->setData($settings->settingsArray);
         $this->site_url = $settings->siteUrl();
     }


}
