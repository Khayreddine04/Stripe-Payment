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

class PT_Db
{

    public $sql = '';
    public $error_log = array();
    public $link;
    public $db_pr = "";
    public $is_connected = false;
	public $result = null;

    public function __construct($dbh = null, $dbn = null, $dbu = null, $dbp = null)
    {

        if (!empty($dbh) && !empty($dbn) && !empty($dbu) && !empty($dbp)) {
            @$this->link = mysqli_connect($dbh, $dbu, $dbp, $dbn);
        } else {

			if(defined('DB_HOST') && defined('DB_USER') && defined('DB_PASS') && defined('DB_NAME') ){
				$this->link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
			}
        }


        if (!$this->link) {
            return false;
        }else{
            $this->db_pr=DB_PREFIX;
        }
        $this->is_connected = true;
        $this->set_charset(DB_CHARSET);
    }

    public function connect($options = array())
    {

        @$this->link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if (!$this->link) {
            $this->is_connected = false;
            return false;
        }
        $this->is_connected = true;
        $this->set_charset(DB_CHARSET);

    }

    public function set_charset($charset)
    {
        $this->query('SET NAMES ' . $charset);
    }

    public function query($sql)
    {
        $this->sql = $sql;
        $this->result = new DB_result(mysqli_query($this->link, $sql), $sql, $this->link);

        $this->error_log = $this->result->error_log;

        return $this->result;
    }

    public function _query($sql)
    {
        print $sql;
    }

    public function checkIntVal($id)
    {
        if (is_numeric($id)) {
            if (intval($id) == $id)
                return true;
        }
        return false;
    }

    public function debug()
    {
        print $this->sql;
    }

}

class DB_result
{

    public $insert_id;
    public $count = 0;
    public $result;
    public $sql;
    public $error = false;
    public $error_log = array();

    public function __construct($resource, $sql, $link)
    {

        try {
            $this->result = $resource;
            $this->sql = $sql;
            // If the query is a resource, it was a SELECT, SHOW, DESCRIBE, EXPLAIN query

            if (is_object($resource)) {

                $this->count = mysqli_num_rows($this->result);
            } elseif (is_bool($resource)) {

                if ($resource == FALSE) {
                    // SQL error
                    $this->error_log[] = '<b>' . mysqli_error($link) . '</b><br>' . $sql;
                    $this->error = true;

                    throw new PT_Exception('<b>' . mysqli_error($link) . '</b><br>' . $sql);
                } else {
                    // Its an DELETE, INSERT, REPLACE, or UPDATE query
                    $this->insert_id = mysqli_insert_id($link);
                    $this->count = mysqli_affected_rows($link);
                }

            }
        } catch (PT_Exception $e) {
            //echo $e->getMessage();
        }
    }

    public function result_array()
    {

        if ($this->error) return array();

        $result = array();
        if ($this->count > 0) {
            mysqli_data_seek($this->result, 0);
            while ($row = mysqli_fetch_assoc($this->result)) {
                $result[] = $row;
            }
        }
        return $result;
    }

    public function result_row($field = null)
    {
        if ($this->error) return;

        $res = mysqli_fetch_assoc($this->result);
        if (empty($field)) {
            if ($this->count) {
                return $res;
            } else {
                return array();
            }
        } else {
            if ($this->count) {
                return $res[$field];
            } else {
                return array();
            }
        }

    }

}

class PT_Exception extends Exception {

	// Template file
	protected $template = 'error_page';

	// Header
	protected $header = FALSE;


	/**
	 * Set exception message.
	 *
	 * @param  string  i18n language key for the message
	 * @param  array   addition line parameters
	 */
	public function __construct($error)
	{

		PT_Core::_error_log($error);

		/*$core = PT_Core::instance();
		$core->dump($error);*/

		// Sets $this->message the proper way
		parent::__construct($error);
	}

	/**
	 * Magic method for converting an object to a string.
	 *
	 * @return  string  i18n message
	 */
	public function __toString(): string
	{
		return (string) $this->message;
	}


}
