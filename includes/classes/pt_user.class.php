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
class PT_User{

    public $error = '';

    public $logon = false;

    public string $sessionID;

	public  PT_Db $db;

	public int $idUser;
	public $dateCraeted;
	public int $idRole;
	public string $username;
	public string $tmuser;
	public string $password;
	public string $tmpass;
	public string $tmhash;
	public string $name;
	public string $lname;
	public string $roles;

    public function __construct($idUser=null){
        $this->db = new PT_Db();

        if($this->db->is_connected=== false)
            return false;
        if(!empty($idUser)){
            $this->setUserData($idUser);
        }elseif(isset($_SESSION['idUser'])){
            $this->sessionID = $_SESSION['idUser'];
            $this->setUserData();
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

    public function is_main_admin(){
        return $this->idUser == 1;
    }

    public function login($username,$password,$remember=false){
        if (!empty($username) && !empty($password)) {

            $sql = "SELECT * FROM {$this->db->db_pr}users WHERE username = '$username' AND  password = MD5('{$password}')";
            $res = $this->db->query($sql);
            if($res->count){

                $_SESSION['idUser'] = md5(SALT.$res->result_row('idUser'));
                if($remember)
                    setcookie ("access_token",md5(SALT.$res->result_row('idUser')),strtotime("+1 week"));

                $this->sessionID = $_SESSION['idUser'];
                $this->setUserData();
                unset($_SESSION['idCustomer']);
                unset($_SESSION['login_failure']);
                return true;

            }else{
                $this->error =  "Wrong Username and/or Password";
                $_SESSION['login_failure'] = true;
            }
        }else{
            $this->error =  "Username and Password are required";
        }
        return false;
    }

    private function setUserData($idUser=null){
        if(empty($idUser)) {
            $sql = "SELECT * FROM {$this->db->db_pr}users WHERE MD5( CONCAT('" . SALT . "',idUser))='{$this->sessionID}'";
        }else{
            $sql = "SELECT * FROM {$this->db->db_pr}users WHERE idUser = '$idUser'";
        }

        $res = $this->db->query($sql);
        if($res->count){
            foreach($res->result_row() as $k=>$v){
                $this->$k = $v;
            }
            $this->logon  = true;
        }
    }

    public function passwordRetrieval($username){
        global $settings;
        $settings = PT_Settings::instance();

        $sql = "SELECT * FROM {$this->db->db_pr}users WHERE username = '{$username}'";
        $res = $this->db->query($sql);
        if(!$res->count)
            return $this->error =  "Something went wrong. Please try again.";
        $userData = $res->result_row();
        $password = PT_Core::randomPassword();
        $sql = "UPDATE {$this->db->db_pr}users SET password = MD5('{$password}') WHERE username = '{$username}'";
        $res = $this->db->query($sql);

        $logoBlock = "";
        if (!empty($settings->terminal_logo)) {
            $logoBlock = '<tr><td style="text-align: center;padding: 30px 0"> <img src="' . $settings->site_url . '/' . $settings->terminal_logo . '" width="70"/></td></tr>';
        }

        $mailData = array(
            "{%name%}"=>$userData['name'],
            "{%password%}"=>$password,
            "{%login%}"=>$userData['username'],
            "{%logo_block%}" => $logoBlock,
            "{%site_url%}" => $settings->site_url
            );

        $core = PT_Core::instance();
        $core->sendMail($userData['username'],'Stripe Payment Terminal - New Password',"password_retrieval.html",$mailData);
        return true;
    }

    public function adminChangeConfirm($idUser,$hash,$siteUrl){
        global $settings;
        $settings = PT_Settings::instance();

        $sql = "SELECT * FROM {$this->db->db_pr}users WHERE idUser = '{$idUser}'";
        $res = $this->db->query($sql);
        if(!$res->count)
            return $this->error =  "Something went wrong. Please try again.";
        $userData = $res->result_row();

        $logoBlock = "";
        if (!empty($settings->terminal_logo)) {
            $logoBlock = '<tr><td style="text-align: center;padding: 30px 0"> <img src="' . $settings->site_url . '/' . $settings->terminal_logo . '" width="70"/></td></tr>';
        }

        $mailData = array(
            "{%name%}"=>$userData['name'],
            "{%new_login%}"=>$userData['tmuser'],
            "{%new_password_identificator%}"=>(!empty($userData["tmpass"])?"Password was also changed.<br>":""),
            "{%approve%}"=>"<a href='".$siteUrl."/approveChange.php?h=".$hash."'>Approve Changes</a>",
            "{%logo_block%}" => $logoBlock,
            "{%site_url%}" => $settings->site_url
            );

        $core = PT_Core::instance();
        $core->sendMail($userData['username'],'[REQUIRES APPROVAL] Account Changes',"account_changes.html",$mailData);
        return true;
    }

}
