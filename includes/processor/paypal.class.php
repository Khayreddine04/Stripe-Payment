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

class paypal_class {
   var $response;
   var $pp_data = array(); 
   var $fields = array();           

   function __construct() {
      // constructor.  

      $this->response = '';
      $this->add_field('rm','2');

   }

   function add_field($field, $value) {
      $this->fields["$field"] = $value;
   }

   function submit_paypal_post() {
       $mess = '<div class="ui-widget"><div class="ui-state-info ui-corner-all" style="padding: 0 .7em;">
       Please wait, you will be redirected to the paypal website.<br />
       If you are not automatically redirected to paypal within 5 seconds...
       <form method="post" name="dps_paypal_form" id="dps_paypal_form" action="'.$this->paypal_url.'">';
       foreach ($this->fields as $name => $value) {
           $mess .= "<input type=\"hidden\" name=\"$name\" value=\"$value\"/>";
       	}
        $mess .= '<input type="submit" class="submitProcessing" value="Click Here">
        </form>
        </div></div><br /><script>setTimeout(function(){document.getElementById("dps_paypal_form").submit()},5000)</script>';
       return $mess;
   }

   
   function validate_ipn() {
      // parse the paypal URL
      $url_parsed=parse_url($this->paypal_url);        
	  
      $post_string = '';    
      foreach ($_REQUEST as $field=>$value) {
         $this->pp_data["$field"] = $value;
         $post_string .= $field.'='.urlencode(stripslashes($value)).'&'; 
      }
      $post_string.="cmd=_notify-validate";

      // open the connection to paypal
      $fp = fsockopen('ssl://'.$url_parsed["host"], "443", $err_num, $err_str, 30);
      if(!$fp) {
         return false;
      } else {
         // Post the data back to paypal
         fputs($fp, "POST ".$url_parsed["path"]." HTTP/1.1\r\n");
         fputs($fp, "Host: ".$url_parsed["host"]."\r\n");
         fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
         fputs($fp, "Content-length: ".strlen($post_string)."\r\n");
         fputs($fp, "Connection: close\r\n\r\n");
         fputs($fp, $post_string . "\r\n\r\n");
         // loop through the response from the server and append to variable
         while(!feof($fp)) {
            $this->response .= fgets($fp, 1024);
         }
         fclose($fp); // close connection
      }
        //print($this->response);
      if (preg_match("/VERIFIED/i",$this->response)) {
         // Valid IPN transaction.
         return true;
      } else {
         // Invalid IPN transaction.
         return false;
      }
   }

}  //class end

?>
