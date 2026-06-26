<?php
/**
 * Author:     CriticalGears (http://www.CriticalGears.io)
 * Website:    http://www.CriticalGears.io
 * Support:    http://support.CriticalGears.io
 * Version:    2.3.3
 *
 * Copyright:   (c)    CriticalGears.io
 *
 *
 */
#[\AllowDynamicProperties]
 class PT_Settings{

     /**
      * @var string static | var
      */
     public $settingsType = "static";

     public static $type = "static";
     /**
      * @var array
      */
     public $settingsArray = array();

     public function __construct()
     {
         $this->db = new PT_Db();
        if ($this->db->is_connected) {
             $this->setCoreSettings();
         } else {
             $this->setStaticSettings();
        }
     }

     private static $_instance = null;
     static public function instance() {
         if(is_null(self::$_instance))
         {
             self::$_instance = new self();
         }
         return self::$_instance;
     }

     public static function type(){
        return self::$type;
     }

     /**
      * Set Settings from DB
      */
     private function setCoreSettings(){
         global $CURRENCY_SYMBOLS;
        $sql = "SELECT * FROM {$this->db->db_pr}settings";

        foreach($this->db->query($sql)->result_array() as $setting){
            $this->{$setting['option_name']} = $this->checkOption(stripslashes($setting['option_value']));
            $this->settingsArray[$setting['option_name']] = $this->checkOption(stripslashes($setting['option_value']));
        }
	     $this->site_url = $this->settingsArray['site_url'];
	     $this->settingsArray['site_url'] = preg_replace("(http:|https:)","",$this->settingsArray['site_url']);

         $this->set("admin_url",$this->siteUrl()."/backoffice");

         $this->set("currency_text",$this->multiple_currencies=='n'?$this->terminal_currency:$this->default_terminal_currency);


         if(!isset($this->display_currency)){
             $this->set("display_currency",isset($CURRENCY_SYMBOLS[$this->currency_text])?$CURRENCY_SYMBOLS[$this->currency_text]:"$");
         }
         $this->set("currency_position",isset($this->currency_position)?$this->currency_position:"before");


         $this->settingsType = "var";
         self::$type = "var";
    }

     /**
      * Check option, if JSON return array
      * @param $option
      * @return mixed
      */
     public function checkOption($option){
        if(is_string($option) && json_decode($option)!==null)
            return json_decode($option,true);
        return $option;
    }

     /**
      * @return string
      */
     public function siteUrl(){
        $url = preg_replace("(http:|https:)", "", $this->site_url);

        // In local/dev, prefer the current request host when configured URL misses the port.
        if (isset($_SERVER['HTTP_HOST']) && !empty($_SERVER['HTTP_HOST'])) {
            $requestHost = trim($_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST']);
            $configuredHost = ltrim(trim($url), '/');
            $configuredHost = explode('/', $configuredHost, 2)[0];

            $configuredHostNoPort = preg_replace('/:\\d+$/', '', $configuredHost);
            $requestHostNoPort = preg_replace('/:\\d+$/', '', $requestHost);

            $configuredHasPort = preg_match('/:\\d+$/', $configuredHost) === 1;
            $requestHasPort = preg_match('/:\\d+$/', $requestHost) === 1;

            if (
                $requestHasPort
                && !$configuredHasPort
                && in_array(strtolower($requestHostNoPort), ['localhost', '127.0.0.1'], true)
                && strtolower($configuredHostNoPort) === strtolower($requestHostNoPort)
            ) {
                $url = '//' . $requestHost;
            }
        }

        // Ensure the URL ends with a forward slash.
        return rtrim($url, '/') . '/';
    }

     /**
      * Set Settings from config.php
      */
     public function setStaticSettings(){

         global $countries,$states;
         $this->set("page_title",TERMINAL_TITLE);

         $this->set("email",ADMIN_EMAIL);
         $this->set("email_name",EMAIL_FROM_NAME);
         $this->set("email_signature",TXT_KIND_REGARDS);
         $this->set("email_from",EMAIL_FROM_EMAIL);
         $this->set("redirect_https",REDIRECT_TO_HTTPS);
         $this->set("live_mode",REDIRECT_TO_HTTPS);

         $this->set("terminal_payment_mode",TERMINAL_PAYMENT_MODE);

         $this->set("test_secret_key",SECRET_KEY);
         $this->set("test_public_key",PUBLIC_KEY);

         $this->set("live_secret_key",SECRET_KEY);
         $this->set("live_public_key",PUBLIC_KEY);

         $this->set("currency_text",CURRENCY);
         $this->set("display_currency",DISPLAY_CURRENCY);
         $this->set('currency_position',CURRENCY_POSITION);
         $this->set("page_title",TERMINAL_TITLE);
         $this->set("show_description",SHOW_DESCRIPTION);
         $this->set("show_billing",SHOW_BILLING_ADDRESS);
         $this->set("show_shipping",SHOW_SHIPPING_ADDRESS);

         $this->set("terminal_logo",TERMINAL_LOGO);
         $this->set("site_ssl",SSL_TEXT);

         $this->set("countries_list",$countries);
         $this->set("states_list",$states);

         $this->set("paypal_merchant_email", PAYPAL_MERCHANT_EMAIL);
         $this->set("paypal_merchant",PAYPAL_MERCHANT_EMAIL);
         $this->set("paypal_currency", PAYPAL_CURRENCY);
         $this->set("paypal_payment_mode", PAYPAL_PAYMENT_MODE);
         $this->set("paypal_custom_variable",PAYPAL_CUSTOM_VARIABLE);

         $this->set("payment_type",PAYMENT_TYPE);
         $this->set("selected_theme",TERMINAL_THEME);

        $this->set('use_recaptcha','n');
        $this->set('enable_paypal',ENABLE_PAYPAL?"y":"n");
         $this->set('show_terms','n');
         $this->set('thank_you_message',THANK_YOU_MESSAGE);
         $this->set('site_url',SCRIPT_URL);

         $this->set('tax_enabled','n');



    }

     /**
      * Set setting
      * @param $name setting name
      * @param $value setting value
      */
     public function set($name,$value){
        $this->$name = $value;
        $this->settingsArray[$name] = $value;
    }

     /**
      * Update / Add option
      * @param string $var
      * @param string $val
      * @param boolean
      */
     public function updateOption($var,$val,$isArray = false){
        $var = trim($var);
        //$exits = $this->$var!==false?true:false;

         $sql = "SELECT * FROM {$this->db->db_pr}settings WHERE option_name = '{$var}'" ;
         $exits = $this->db->query($sql)->count;

        if($isArray && is_array($val)){
            $this->set($var,$val);
            $val = json_encode($val,JSON_FORCE_OBJECT);

        }else{
            $this->set($var,stripslashes($val));
        }

        if($exits){
            $sql = "UPDATE {$this->db->db_pr}settings SET option_value='{$val}' WHERE option_name = '{$var}'" ;
            $this->db->query($sql);
        }else{
            $sql = "INSERT INTO {$this->db->db_pr}settings SET option_value='{$val}',option_name='{$var}'" ;
            $this->db->query($sql);
        }

    }

     /**
      * Get setting
      * @param $name setting name
      */
     public function get($name){
        if(isset($this->$name))
            return $this->checkOption($this->$name) ;
        return false;
     }
     /**
      * Magically sets  variable.
      *
      * @param   string   variable key
      * @param   string   variable value
      * @return  void
      */
    function __set($var,$value){
        $this->$var = $value;
    }

     /**
      * Magically gets variable.
      *
      * @param   string   variable key
      * @return  mixed
      */
	 function __get($var){
		 if(isset($this->settingsArray[$var]))
			 return $this->settingsArray[$var];
		 return false;
	 }

 }
