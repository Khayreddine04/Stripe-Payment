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
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

class PT_Core extends PT_Db{

     private $dumpList = array();
     private $styleList = array();
     private $scriptList = array();
     private $errorList = array();
     private $successList = array();
     private $warningList = array();
     private $infoList = array();
     public $post = array();
     public $error = false;
     public $userLogon = false;
     public $userId = 0;
     public $debug = array();

     public $language = 'english';

     public $baseDir;

	 public PT_Settings $settings;


     private static $_instance = null;

     static public function  _dump($val){
         print  "<pre>" . print_r($val, true) . "</pre>";
     }


     static public function instance() {
         if(is_null(self::$_instance))
         {
             self::$_instance = new self();
         }
         return self::$_instance;
     }


     public  function __construct($connect=true) {
         global $baseDir;
            if($connect)
                $this->connect();

         $this->settings = PT_Settings::instance();
     }

     static public function _error_log($message) {

     	 if(!DEBUG)
     	    return;

         $logDir = HOME_DIR . '/log/';
         try {
             if (!is_dir($logDir)) {
                 mkdir($logDir , 0777);
             }else{
	             @chmod($logDir, 0777);
             }
             $logDir.=date("Y");
             if (!is_dir($logDir)) {
                 mkdir($logDir , 0777);
             }
             @chmod($logDir , 0777);

             $logDir = $logDir . "/" . date("m");

             if (!is_dir($logDir )) {
                 mkdir($logDir , 0777);
             }
             @chmod($logDir , 0777);


	         $logFileName = "/" . date("Y_m_d") . '-log.txt';

	         @chmod($logDir . $logFileName, 0777);

             $message = PHP_EOL."[" . date("d/m/Y H:i:s") . " file ({$_SERVER['PHP_SELF']})]".PHP_EOL . $message.PHP_EOL.PHP_EOL."----------------------------------------------------------------------".PHP_EOL;
             error_log($message, 3, $logDir . $logFileName);
         } catch (Exeception $e) {
             error_log("\nERROR: [" . date("d/m/Y H:i:s") . "]Cant create dir " . $logDir . ")", 3, $_SERVER['DOCUMENT_ROOT'] . '/log.txt');
         }
     }

     public function getSiteUrl(){
          return rtrim($this->settings->siteUrl(), '/');
     }


     public function getDebug(){
         if(DEBUG){
             $this->debug = array_merge($this->debug,$this->error_log);
             if(count($this->debug)){
                 print "<ul class='debug'>";
                 foreach($this->debug as $d){
                     print "<li>$d</li>";
                 }
                 print "</ul>";
             }
         }
     }

     public function getDebugString(){
        $str = "";
         if(DEBUG){
             $this->debug = array_merge($this->debug,$this->error_log);
             if(count($this->debug)){
                 $str = "<ul class='debug'>";
                 foreach($this->debug as $d){
                     $str .= "<li>$d</li>";
                 }
                 $str .= "</ul>";
             }
         }
     }

     public function userLogOn(){
         if(isset($_SESSION['logged_in_terminal']) && $_SESSION['logged_in_terminal'] ){
             $this->userLogon = true;
             $this->userId = $_SESSION['idCustomer'];
             return true;
         }else{
             return false;
         }
     }

     public function addError($mess,$args=array()){
         $this->errorList[]=$this->__tr($mess,$args);
         $this->error = true;
     }
     public function addSuccess($mess,$args=array()){
         $this->successList[]=$this->__tr($mess,$args);
     }
     public function addWarning($mess,$args=array()){
         $this->warningList[]=$this->__tr($mess,$args);
     }
    public function addInfo($mess,$args=array()){
        $this->infoList[]=$this->__tr($mess,$args);
    }
     public function getMessages(){
         $messages="";
         if (count($this->errorList)) {
             $messages.="<div role=\"alert\" class=\"alert alert-danger alert-dismissible fade in\">
            <button aria-label=\"Close\" data-dismiss=\"alert\" class=\"close\" type=\"button\"><span aria-hidden=\"true\">×</span></button>";
             foreach ($this->errorList as $error) {
                 $messages.="$error<br>";
             }
             $messages.="</div>";
         }
         if (count($this->successList)) {
             $messages.="<div role=\"alert\" class=\"alert alert-success alert-dismissible fade in\">
            <button aria-label=\"Close\" data-dismiss=\"alert\" class=\"close\" type=\"button\"><span aria-hidden=\"true\">×</span></button>";
             foreach ($this->successList as $error) {
                 $messages.="$error<br>";
             }
             $messages.="</div>";
         }
         if (count($this->warningList)) {
             $messages.="<div role=\"alert\" class=\"alert alert-warning alert-dismissible fade in\">
            <button aria-label=\"Close\" data-dismiss=\"alert\" class=\"close\" type=\"button\"><span aria-hidden=\"true\">×</span></button>";
             foreach ($this->warningList as $error) {
                 $messages.="$error<br>";
             }
             $messages.="</div>";
         }
         if (count($this->infoList)) {
             $messages.="<div role=\"alert\" class=\"alert alert-info alert-dismissible fade in\">
            <button aria-label=\"Close\" data-dismiss=\"alert\" class=\"close\" type=\"button\"><span aria-hidden=\"true\">×</span></button>";
             foreach ($this->infoList as $error) {
                 $messages.="$error<br>";
             }
             $messages.="</div>";
         }

         return $messages;
     }


     public function addStyle($src){
         $this->styleList[] = $src;

     }
     public function addScript($src){
         $this->scriptList[] = $src;
     }

     public function getScripts(){
         $list = "";
         foreach ($this->scriptList as $script){
             $list.='<script type="text/javascript" src="'.$script.'"></script>'.PHP_EOL;
         }
         return $list;
     }
     public function getStyles(){
         $list = "";
         foreach ($this->styleList as $script){
             $list.='<link href="'.$script.'" rel="stylesheet" type="text/css" />'.PHP_EOL;
         }
         return $list;
     }

     public function esc($val, $default="", $intval=false) {

         $value = $default;
         if ($intval) {
             $value = (isset($_REQUEST[$val])) ? intval($_REQUEST[$val]) : $default;
         } else {
             if(!empty($_REQUEST[$val]) && is_array($_REQUEST[$val])){
                 $value = (isset($_REQUEST[$val])) ? $_REQUEST[$val] : $default;
             }else{
                 $value = (isset($_REQUEST[$val])) ? addslashes(rtrim($_REQUEST[$val])) : $default;
             }
         }
         $this->post[$val] = $value;
         return $value;
     }

     public function _esc($val, $default="", $intval=false) {

         $value = $default;
         if ($intval) {
             $value = (isset($_REQUEST[$val])) ? intval($_REQUEST[$val]) : $default;
         } else {
             if(!empty($_REQUEST[$val]) && is_array($_REQUEST[$val])){
                 $value = (isset($_REQUEST[$val])) ? $_REQUEST[$val] : $default;
             }else{
                 $value = (isset($_REQUEST[$val])) ? addslashes(rtrim($_REQUEST[$val])) : $default;
             }
         }
         $this->post[$val] = strip_tags(htmlspecialchars($value,ENT_COMPAT));
         return strip_tags(htmlspecialchars($value,ENT_COMPAT));
     }



     public static function r($val){
         echo(stripslashes($val));
     }


     public function crl($val) {
         return stripslashes(htmlentities($val,ENT_QUOTES));
     }
     public function _crl($val) {
         return stripslashes($val);
     }
     public function dump($val) {

         $this->debug[] = "<pre>" . print_r($val, true) . "</pre>";

     }
     public function checkEmail($email){
         return preg_match("(^[-\w\.]+@([-a-z0-9]+\.)+[a-z]{2,4}$)i", $email);
     }

     public function printJS($text,$escape=true){
         $pattern = array("/\n/","/\r\n/","/\s\s/","/\t/");
         $replace = array("","","","");
         print  $escape?addslashes(preg_replace($pattern,$replace,$text)):preg_replace($pattern,$replace,$text);
     }

     function _tr($text,$args=array()){

           _tr($text);

     }
     function __tr($text,$args=array()){

         return  __tr($text);

     }


     function uploadFile($inputFile, $sFolderPictures,$maxSize = 20,
                         $allowedExtentions=array("jpeg", "jpg", "png", "gif")) {

         $image_path = $inputFile['tmp_name'];
         $photoFileNametmp = $inputFile['name'];
         $fileInfo = pathinfo($photoFileNametmp);
         $fileExtensiontmp = $fileInfo['extension']; // part behind last dot

         $err = false;

         if ($inputFile['size'] > $maxSize*1048576) {
             $ssize = sprintf("%01.2f", $inputFile['size'] / 1048576);
             $err = "Your file is " . $ssize . ". Max file size is {$maxSize} MB.<br>";
         }
         if (!in_array(strtolower($fileExtensiontmp), $allowedExtentions)) {
             $err.= "File extension should be ." . join(" ,.", $allowedExtentions) . "<br />";
         }

         if (empty($err)) {

             $newFile = HOME_DIR."/uploads/{$sFolderPictures}.{$fileExtensiontmp}";
             $ret = move_uploaded_file($inputFile['tmp_name'], $newFile);
             if (!$ret) {
                 $err.="Upload failed. No file received.";
             } else {
                 $imgPath = "/uploads/{$sFolderPictures}.{$fileExtensiontmp}";
             }
         }
         if (file_exists($inputFile['tmp_name'])) {
             @unlink($inputFile['tmp_name']);
         }

         return array("error"=>$err,'imgPath'=>$imgPath);
     }


	function sendMail( $email, $subject, $template, $message = null, $showSignature = true, $cc = false, $bcc = false ) {


		$emailFrom = $this->settings->email_from;
		$fromName  = $this->settings->email_name;
		$signature = $this->settings->email_signature;

		$_message = "<div style='width:515px;color:#003366;font-size:13px;padding:20px;line-height:130%'>";

		if ( is_array( $message ) ) {
			$templ = $this->getMailTemplate( $template );
			if ( $templ === false ) {
				$_message = "Template '$template' - not found";
			} else {
				$message['{%server%}'] = $_SERVER['SERVER_NAME'];
				$_mess                 = array();
				foreach ( $message as $k => $m ) {
					$_mess[ $k ] = stripslashes( $m );
				}
				$_message .= strtr( $templ, $_mess );
			}
		} else {
			$_message .= nl2br( $message );
		}
		$_message .= "</div>";
		if ( $showSignature ) {
			$_message .= "<br><br>" . $signature;
		}


		require_once HOME_DIR . "/includes/classes/phpmailer/src/PHPMailer.php";
		require_once HOME_DIR . "/includes/classes/phpmailer/src/SMTP.php";
		require_once HOME_DIR . "/includes/classes/phpmailer/src/Exception.php";
		$mail = new PHPMailer( true );

		try {
			//Server settings
			$mail->SMTPDebug = SMTP::DEBUG_OFF;
			if ( $this->settings->send_mail != 'php' ) {
				//$mail->Debugoutput ='html';                               // Enable verbose debug output
				$mail->isSMTP();                                            // Send using SMTP
				$mail->Host       = $this->settings->smtp_host;             // Set the SMTP server to send through
				$mail->SMTPAuth   = true;                                   // Enable SMTP authentication
				$mail->Username   = $this->settings->smtp_username;         // SMTP username
				$mail->Password   = $this->settings->smtp_password;         // SMTP password
				$mail->SMTPSecure = $this->settings->smtp_secure;           // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` also accepted
				$mail->Port       = $this->settings->smtp_port;             // TCP port to connect to
			} else {
				$mail->isMail();
			}

			//Recipients
			$mail->setFrom( $emailFrom, $fromName );
			$mail->addAddress( $email );     // Add a recipient
			if ( ! empty( $cc ) ) {
				$mail->addCC( $cc );
			}
			if ( ! empty( $bcc ) ) {
				$mail->addBCC( $bcc );
			}

			// Content
			$mail->isHTML( true );                                  // Set email format to HTML
			$mail->Subject = $subject;
			$mail->Body    = $_message;

			$mail->send();
			return true;
		} catch ( Exception $e ) {
			return "Mailer Error: {$e->errorMessage()}";
		}

	}

     public function getMailTemplate($tpl) {
         if (empty($tpl)) {
             return false;
         }
         $templ = file_get_contents(HOME_DIR . "/templates/email/{$tpl}");
         if ($templ === false)
             return false;
         $templ = explode("<--start_template-->", $templ);
         if (is_array($templ) && isset($templ[1])) {
             return $templ[1];
         } else {
             return $templ;
         }
     }

     function sendMailFile($email, $subject, $template, $data=null,$file=null,$showSignature = true,$cc=false,$bcc=false) {

         $emailFrom = $this->settings->email_from;
         $fromName = $this->settings->email_name;
         $signature = $this->settings->email_signature;

         $message='';
         if ($data == null) {
             $message = $template;
         } else {
             $templ = $this->getMailTemplate($template);
             if ($templ === false) {
                 $message = "Template '$template' - not found";
             } else {
                 $data['{%server%}'] = $_SERVER['SERVER_NAME'];

                 $message .= strtr($templ, $data);
             }
         }
         if($showSignature)
             $message.="<br><br>".$signature.",<br><a href='http://{$_SERVER['SERVER_NAME']}'>{$_SERVER['SERVER_NAME']}</a>";


	         require_once HOME_DIR . "/includes/classes/phpmailer/src/PHPMailer.php";
	         require_once HOME_DIR . "/includes/classes/phpmailer/src/SMTP.php";
	         require_once HOME_DIR . "/includes/classes/phpmailer/src/Exception.php";
	         $mail = new PHPMailer(true);

	         try {
		         //Server settings
		         $mail->SMTPDebug = SMTP::DEBUG_OFF;
		         //$mail->Debugoutput ='html';                               // Enable verbose debug output
		         if($this->settings->send_mail == 'php') {
			         $mail->isMail();
		         }else {
			         $mail->isSMTP();                                            // Send using SMTP
			         $mail->Host       = $this->settings->smtp_host;             // Set the SMTP server to send through
			         $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
			         $mail->Username   = $this->settings->smtp_username;         // SMTP username
			         $mail->Password   = $this->settings->smtp_password;         // SMTP password
			         $mail->SMTPSecure = $this->settings->smtp_secure;           // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` also accepted
			         $mail->Port       = $this->settings->smtp_port;             // TCP port to connect to
		         }
		         //Recipients
		         $mail->setFrom($emailFrom, $fromName);
		         $mail->addAddress($email);     // Add a recipient
		         if ( ! empty( $cc ) ) {
			         $mail->addCC( $cc );
		         }
		         if ( ! empty( $bcc ) ) {
			         $mail->addBCC( $bcc );
		         }
		         // Content
		         $mail->isHTML(true);                                  // Set email format to HTML
		         $mail->Subject = $subject;
		         $mail->Body    = $message;

		         foreach ( $file as $name => $_file ) {
			         $mail->AddStringAttachment($_file, $name);
		         }

		         $mail->send();
		         return true;
	         } catch (Exception $e) {
		         return "Mailer Error: {$e->errorMessage()}";
	         }

     }


     public function dateFromIso($_date){
         if(preg_match("/^\d{1,2}\/\d{1,2}\/\d{2,4}/",$_date)){
             $date = DateTime::createFromFormat('m/d/Y', $_date);
             if($date!==false)
                return $date->format('Y-m-d');
         }else{
            return $_date;
         }
     }

     public function dateToIso($_date){
         if(preg_match("/^\d{4}-\d{2}-\d{2}/",$_date)){
             $date = DateTime::createFromFormat('Y-m-d', $_date);
             if($date!==false)
                return $date->format('m/d/Y');
         }else{
             return $_date;
         }
     }

     public function getDateFormat($_date,$format = "Y-m-d H:i:s"){

        if($_date =='0000-00-00 00:00:00' || $_date =='0000-00-00' || !$_date)
            return "N/A";
        $date = DateTime::createFromFormat($format, $_date);
        return $date===false?"N/A":$date->format("M. d, Y");
     }

     public static  function _getDateFormat($_date,$from_format = "Y-m-d H:i:s"){

         if($_date =='0000-00-00 00:00:00' || $_date =='0000-00-00')
             return "N/A";
         $date = new DateTime($_date);
         return $date===false?"N/A":$date->format("M. d, Y");
     }

     public static  function _getTimeFormat($_date,$from_format = "Y-m-d H:i:s"){

         if($_date =='0000-00-00 00:00:00' || $_date =='0000-00-00')
             return "N/A";
         $date = DateTime::createFromFormat($from_format, $_date);
         return $date===false?"N/A":$date->format("H:i");
     }

     public static  function dateDiff($dateTo,$dateFrom){
         if(strtotime($dateTo)<strtotime($dateFrom))
             return 0;
         $st = new DateTime($dateFrom);
         $cd = new DateTime($dateTo);

         $daysToDate = intval($cd->diff($st)->format('%a'));
         return $daysToDate===false?0:$daysToDate;

     }

     public static function  getCurrencyText($amount,$showCurrencyISO = true){
        global $CURRENCY_SYMBOLS;
        $settings = PT_Settings::instance();

        $c = $settings->multiple_currencies=='n'?$settings->terminal_currency:$settings->default_terminal_currency;
        $p = $settings->currency_position;
        //$s = isset($CURRENCY_SYMBOLS[$c]) ? $CURRENCY_SYMBOLS[$c] : "";
        $s = $settings->display_currency;

        return $p=='before'?$s.self::decFormat($amount)." ".($showCurrencyISO?$c:""):
            ($showCurrencyISO?$c:"")." ".self::decFormat($amount)." ".$s;
     }

     public static function  _getCurrencyText($amount,$p,$s){

         return htmlentities($p=='before'?$s.self::decFormat($amount):self::decFormat($amount).$s);
     }

     public static function  getPaypalCurrencyText($amount){
         $settings = PT_Settings::instance();
         $s = $settings->display_currency;
         $c = $settings->paypal_currency;
         return $s.self::decFormat($amount)." ".$c;
     }

    public static function getCurSymb($currency){
        global $CURRENCY_SYMBOLS;
        if(isset($CURRENCY_SYMBOLS[$currency]))
            return $CURRENCY_SYMBOLS[$currency];
        return "";

    }
     public static function decFormat($dec){
        return $dec?number_format($dec,2):0;
     }

     public static function _decFormat($dec){
         return self::decFormat($dec);
     }

     public static function randomPassword
     (

         $length=7,
         $uselower=1,
         $useupper=1,
         $usespecial=1,
         $usenumbers=1,
         $prefix=''
     ) {
         $key = $prefix;
         // Seed random number generator
         srand((int) microtime() * rand(1000, 9999));
         $charset = "";
         if ($uselower == 1)
             $charset .= "abcdefghijkmnopqrstuvwxyz";
         if ($useupper == 1)
             $charset .= "ABCDEFGHIJKLMNPQRSTUVWXYZ";
         if ($usenumbers == 1)
             $charset .= "0123456789";
         if ($usespecial == 1)
             $charset .= "#*_+-";
         while ($length > 0) {
             $key .= $charset[rand(0, strlen($charset) - 1)];
             $length--;
         }
         return $key;
     }

     public function checkCaptcha(){
        if($this->settings->use_recaptcha=='n')
            return true;

        if(isset($this->post['stripeButton']))
            return true;

        $api_secret = $this->settings->recaptcha_secret_key;
        $response = $this->esc("g-recaptcha-response");

        $ch = curl_init();

	     curl_setopt_array($ch, [
		     CURLOPT_URL => 'https://www.google.com/recaptcha/api/siteverify',
		     CURLOPT_POST => true,
		     CURLOPT_POSTFIELDS => [
			     'secret' => $api_secret,
			     'response' => $response,
			     'remoteip' => $_SERVER['REMOTE_ADDR']
		     ],
		     CURLOPT_RETURNTRANSFER => true
	     ]);

	     $api_call = curl_exec($ch);
	     curl_close($ch);

        if($api_array = json_decode($api_call,true)){
            if(isset($api_array['success'])){
                if($api_array['success']==true){
                    return true;
                }else{
                    $errors = is_array($api_array['error-codes'])?join("<br>",$api_array['error-codes']):"";
                    $this->addError("Captcha error<br>".$errors);
                }
            }else{
                $this->addError("Wrong Captcha response. Please, try later");
            }
        }else{
            $this->addError("Captcha error. Please, try later");
        }
         return false;
     }

     public function isValidMd5($md5 ='')
     {
         return preg_match('/^[a-f0-9]{32}$/', $md5);
     }

    public static function getTimeZonesList() {
        global $mysqli;
        $tza = array();
        $tab = file(dirname(__DIR__).'/zone.tab');
        foreach ($tab as $buf) {
            if (substr($buf, 0, 1) == '#')
                continue;
            $rec = preg_split('/\s+/', $buf);
            $key = $rec[2];
            $val = $rec[2];
            $c = count($rec);
            for ($i = 3; $i < $c; $i++) {
                $val.= ' ' . $rec[$i];
            }
            $tza[$key] = $val;

        }
        ksort($tza);
        return $tza;
    }

 }

