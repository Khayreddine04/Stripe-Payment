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

class PT_Data_Table {

    /**
     *  MY_SQL table name
     * @var string
     */
    public $table = "";

    /**
     * MY_SQL table id
     * @var string
     */
    public $id = "";

    /**
     * Data table settings
     * @var array
     */
    public $data = array();


    /**
     * Backoffice section
     * @var string
     */
    public $section = "";


	public PT_Admin_Core $core;

	public int $pt_order_col;

	public string $pt_order_type;

	/**
     * @var string
     */
    public $request_url = "/backoffice/ajax/get_table_data.php";


    public function __construct($data){
        $this->core = PT_Admin_Core::instance();

        $this->data = $data;

        $this->pt_order_col=0;
        $this->pt_order_type='desc';

        $url = $this->core->getSiteUrl();

        $this->core->addStyle("{$url}/assets/js/data_table/css/dataTable.bootstrap.css");
        $this->core->addStyle("//cdn.datatables.net/responsive/1.0.7/css/responsive.dataTables.min.css");

        //$this->core->addScripts("{$url}/assets/js/data_table/js/jquery.dataTables.js");
        $this->core->addScripts("https://cdn.datatables.net/1.10.20/js/jquery.dataTables.min.js",false);

        $this->core->addScripts("{$url}/assets/js/data_table/js/dataTable.bootstrap.js",false);
        $this->core->addScripts("//cdn.datatables.net/responsive/1.0.7/js/dataTables.responsive.js",false);

        $this->core->addStyle("{$url}/backoffice/assets/vendors/sweetalert2/sweetalert2.min.css");
        $this->core->addScripts("{$url}/backoffice/assets/vendors/sweetalert2/sweetalert2.min.js",false);

        $this->core->addStyle("//cdn.datatables.net/buttons/1.6.1/css/buttons.dataTables.min.css");

        $this->core->addScripts("//cdn.datatables.net/buttons/1.6.1/js/dataTables.buttons.min.js",false);
        $this->core->addScripts("//cdn.datatables.net/buttons/1.6.1/js/buttons.flash.min.js",false);
        $this->core->addScripts("//cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js",false);
        $this->core->addScripts("//cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js",false);
        $this->core->addScripts("//cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js",false);
        $this->core->addScripts("//cdn.datatables.net/buttons/1.6.1/js/buttons.html5.min.js",false);
        $this->core->addScripts("//cdn.datatables.net/buttons/1.6.1/js/buttons.print.min.js",false);
    }

    public function getAjaxTable($can_delete=true){

        $url = $this->core->getSiteUrl();

        $this->request_url = $url.$this->request_url."?section=".$this->section;

        $tableView = new PT_Admin_Template("ajax_data_table.php");

        $tableView->columns = $this->getColumnsArray();
        $tableView->request_uri = $this->request_url;
        $tableView->pt_order_col = $this->pt_order_col;
        $tableView->pt_order_type = $this->pt_order_type;
        $tableView->can_delete = $can_delete;

        $tableView->render(true);

    }


    /**
     * Return columns titles array
     * @return array
     */
    public function getColumnsArray()
    {
        $data = array();
        foreach ($this->data as $v) {
            if(isset($v['hidden']) && $v['hidden'])
                continue;
            $data[] = $v['title'];
        }
        return $data;
    }



    static function data_output ( $columns, $data )
    {
        $out = array();

        for ( $i=0, $ien=count($data) ; $i<$ien ; $i++ ) {
            $row = array();

            for ( $j=0, $jen=count($columns) ; $j<$jen ; $j++ ) {
                $column = $columns[$j];

                if ( isset( $column['hidden'] ) && $column['hidden'])
                    continue;
                // Is there a formatter?
                if ( isset( $column['formatter'] ) ) {
                    $row[ $j ] = call_user_func($column['formatter'], $data[$i][ $column['field'] ], $data[$i] );
                }
                else {
                    $row[ $j ] = $data[$i][ $columns[$j]['field'] ];
                }
            }

            $out[] = $row;
        }

        return $out;
    }


    /**
     * Paging
     *
     * Construct the LIMIT clause for server-side processing SQL query
     *
     *  @param  array $request Data sent to server by DataTables
     *  @param  array $columns Column information array
     *  @return string SQL limit clause
     */
    static function limit ( $request, $columns )
    {
        $limit = '';

        if ( isset($request['start']) && $request['length'] != -1 ) {
            $limit = "LIMIT ".intval($request['start']).", ".intval($request['length']);
        }

        return $limit;
    }


    /**
     * Ordering
     *
     * Construct the ORDER BY clause for server-side processing SQL query
     *
     *  @param  array $request Data sent to server by DataTables
     *  @param  array $columns Column information array
     *  @return string SQL order by clause
     */
    static function order ( $request, $columns )
    {
        $order = '';

        if ( isset($request['order']) && count($request['order']) ) {
            $orderBy = array();
            //$dtColumns = self::pluck( $columns, 'dt' );

            for ( $i=0, $ien=count($request['order']) ; $i<$ien ; $i++ ) {
                // Convert the column index into the column data property
                $columnIdx = intval($request['order'][$i]['column']);
                $requestColumn = $request['columns'][$columnIdx];
                $column = $columns[ $columnIdx ];
                if ( $requestColumn['orderable'] == 'true' ) {
                    $dir = $request['order'][$i]['dir'] === 'asc' ?
                        'ASC' :
                        'DESC';

                    $orderBy[] = '`'.$column['field'].'` '.$dir;
                }
            }

            $order = 'ORDER BY '.implode(', ', $orderBy);
        }

        return $order;
    }


    /**
     * Searching / Filtering
     *
     * Construct the WHERE clause for server-side processing SQL query.
     *
     * NOTE this does not match the built-in DataTables filtering which does it
     * word by word on any field. It's possible to do here performance on large
     * databases would be very poor
     *
     *  @param  array $request Data sent to server by DataTables
     *  @param  array $columns Column information array
     *  @param  array $bindings Array of values for PDO bindings, used in the
     *    sql_exec() function
     *  @return string SQL where clause
     */
    static function filter ( $request, $columns, &$bindings )
    {
        $globalSearch = array();
        $columnSearch = array();
        //$dtColumns = self::pluck( $columns, 'dt' );

        if ( isset($request['search']) && $request['search']['value'] != '' ) {
            $str = $request['search']['value'];

            for ( $i=0, $ien=count($request['columns']) ; $i<$ien ; $i++ ) {
                $requestColumn = $request['columns'][$i];
                //$columnIdx = array_search( $requestColumn['data'], $dtColumns );
                $column = $columns[ $i ];

                if ( $requestColumn['searchable'] == 'true' ) {
                    $binding = "'%".addslashes($str)."%'";
                    $globalSearch[] = "`".$column['field']."` LIKE ".$binding;
                }
            }
        }

        // Individual column filtering
        for ( $i=0, $ien=count($request['columns']) ; $i<$ien ; $i++ ) {
            $requestColumn = $request['columns'][$i];
            //$columnIdx = array_search( $requestColumn['data'], $dtColumns );
            $column = $columns[ $i ];

            $str = $requestColumn['search']['value'];

            if ( $requestColumn['searchable'] == 'true' &&
                $str != '' ) {
                $binding = "'%".addslashes($str)."%'";
                $columnSearch[] = "`".$column['field']."` LIKE ".$binding;
            }
        }

        // Combine the filters into a single string
        $where = '';

        if ( count( $globalSearch ) ) {
            $where = '('.implode(' OR ', $globalSearch).')';
        }

        if ( count( $columnSearch ) ) {
            $where = $where === '' ?
                implode(' AND ', $columnSearch) :
                $where .' AND '. implode(' AND ', $columnSearch);
        }

        if ( $where !== '' ) {
            $where = 'WHERE '.$where;
        }

        return $where;
    }


    /**
     * Perform the SQL queries needed for an server-side processing requested,
     * utilising the helper functions of this class, limit(), order() and
     * filter() among others. The returned array is ready to be encoded as JSON
     * in response to an SSP request, or can be modified if needed before
     * sending back to the client.
     *
     *  @param  array $request Data sent to server by DataTables
     *  @param  array $sql_details SQL connection details - see sql_connect()
     *  @param  string $table SQL table to query
     *  @param  string $primaryKey Primary key of the table
     *  @param  array $columns Column information array
     *  @return array          Server-side processing response array
     */
    static function simple ( $request, $table, $primaryKey, $columns )
    {
        $db = PT_Admin_Core::instance();

        $bindings = array();

        // Build the SQL query string from the request
        $limit = self::limit( $request, $columns );
        $order = self::order( $request, $columns );
        $where = self::filter( $request, $columns, $bindings );

        // Main query to actually get the data
        $sql = "SELECT SQL_CALC_FOUND_ROWS `".implode("`, `", self::pluck($columns, 'field'))."`
			 FROM `$table`
			 $where
			 $order
			 $limit";
        $data = $db->query($sql)->result_array();

        // Data set length after filtering
        $recordsFiltered = $db->query("SELECT FOUND_ROWS() as count")->result_row("count");


        // Total data set length
        $sql = "SELECT COUNT(`{$primaryKey}`) as count FROM `$table`";
        $recordsTotal = $db->query($sql)->result_row("count");

        /*
         * Output
         */
        return array(
            "draw"            => intval( $request['draw'] ),
            "recordsTotal"    => intval( $recordsTotal ),
            "recordsFiltered" => intval( $recordsFiltered ),
            "data"            => self::data_output( $columns, $data )
        );
    }

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * Internal methods
     */

    /**
     * Throw a fatal error.
     *
     * This writes out an error message in a JSON string which DataTables will
     * see and show to the user in the browser.
     *
     * @param  string $msg Message to send to the client
     */
    static function fatal ( $msg )
    {
        echo json_encode( array(
            "error" => $msg
        ) );

        exit(0);
    }

    /**
     * Pull a particular property from each assoc. array in a numeric array,
     * returning and array of the property values from each item.
     *
     *  @param  array  $a    Array to get data from
     *  @param  string $prop Property to read
     *  @return array        Array of property values
     */
    static function pluck ( $a, $prop )
    {
        $out = array();

        for ( $i=0, $len=count($a) ; $i<$len ; $i++ ) {
            $out[] = $a[$i][$prop];
        }

        return $out;
    }
}
