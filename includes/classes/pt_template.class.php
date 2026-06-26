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

class PT_Template{

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
     *  path to template file
     * @var string
     */
    protected  $templatePath = "";

    /**
     * Template data
     * @var array
     */
    public $templateData = array();

    /**
     * @param $template
     * @param array $data
     */
    public function __construct($template, $data=array()){
        $this->template = $template;
        $this->templateData = $data;

        // Get the current theme from settings or default to CardStyle
        $settings = PT_Settings::instance();
        $theme = isset($data['selected_theme']) ? $data['selected_theme'] : 
                (isset($settings->settingsArray['selected_theme']) ? $settings->settingsArray['selected_theme'] : 'CardStyle');

        // First try the theme-specific template
        $templatePath = HOME_DIR."/{$this->templateDir}/form/{$theme}/" . basename($template);
        
        // If theme template doesn't exist, fall back to the default template
        if(!is_file($templatePath)) {
            $templatePath = HOME_DIR."/{$this->templateDir}/{$this->template}";
        }
        
        if(!is_file($templatePath)) {
            error_log("Template {$this->template} not found in theme '{$theme}' or default location");
        }

        $this->templatePath = $templatePath;

        // add global settings to template
        $settings = PT_Settings::instance();
        $this->setData($settings->settingsArray);

        $this->site_url = $settings->siteUrl();

    }


    /**
     * Add template data
     * @param array $data
     */
    public function setData($data){
        $this->templateData = array_merge( $data,$this->templateData);


    }

    /**
     * @param bool $print  set to TRUE to echo the output instead of returning it
     * @return bool|string
     */
    public function render($print=false){
    global $c;
        if(count($this->templateData))
            foreach($this->templateData as $k=>$v){
                $$k=$v;
            }
        if($print){
            include_once $this->templatePath;
        }else{
            ob_start();
            include_once $this->templatePath;
            $content = ob_get_contents();
            ob_end_clean();
            return $content;
        }
        return true;

    }
    /**
     * Magically sets a view variable.
     *
     * @param   string   variable key
     * @param   string   variable value
     * @return  void
     */
    public function __set($key, $value)
    {
        $this->templateData[$key] = $value;
    }


}
