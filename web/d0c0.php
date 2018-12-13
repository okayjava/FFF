<?php
include dirname(__FILE__)."/../lib.php";

class API extends REST {
    
    public $data = "";
    private $ar_query = array();
    
    public function __construct(){
        parent::__construct();              // Init parent contructor
    }
    
    
    /*
     * Public method for access api.
     * This method dynmically call the method based on the query string
     *
     */
    public function processApi(){
        $this->ar_query = explode("/", $_REQUEST['rquest']);
        array_shift($this->ar_query);
        $func = $this->ar_query[0];
        
        //             $func = strtolower(trim(str_replace("/","",$_REQUEST['rquest'])));
        if((int)method_exists($this,$func) > 0)
        {
            $this->$func();
        } else {
            //$this->response('Error code 404, Page not found',404);   // If the method not exist with in this class, response would be "Page not found".
	    http_response_code(404);
        }
    }
    
    function enc_test()
    {
	foreach($this->_request as $key => $val)
	{
		printf("%s Enc Before : %s\n", $key, $val);
		printf("%s Enc After : %s\n", $key, Encoder($val));
	}
     } 
    function dec_test()
    {
	foreach($this->_request as $key => $val)
	{
		printf("%s Enc : %s\n", $key, Decoder($val));
	}
     } 
    
    function test()
    {
	foreach($this->_request as $key => $val)
	{
		printf("%s : %s\n", $key, $val);
	}
     } 
     function jj_test()
     {
	echo "ok";
     } 

     function join()
     {
	$par = array();
	$par['user_id'] = $this->_request['id'];
	$par['user_pass'] = $this->_request['pass'];
	$par['client_hash'] = $this->_request['hash'];
	
	$MEMBER = new member();
	$join_result = $MEMBER->join($par);
	if ( $join_result === true )
	{
		echo "SUCCESS";
	} else {
		switch ($join_result)
		{
			case NOT_EXISTS_USER;
				echo "not exists user\n";
				break;
			case DB_ERROR:
				echo "db error \n";
				break;
			case ALREADY_USER_EXISTS:
				echo "ALREADY_USER_EXISTS \n";
				break;
			default :
				echo "unknow error\n";
				break;
		}
	}
     }

     function login()
     {
	$par = array();
	$par['user_id'] = $this->_request['id'];
	$par['user_pass'] = $this->_request['pass'];
	$par['client_hash'] = $this->_request['hash'];
	
	$MEMBER = new member();
	$login_result = $MEMBER->login($par);
	if ( $login_result === true )
	{
		echo "SUCCESS";
	} else {
		switch ($login_result)
		{
			case ALREADY_CONNECTED;
				echo "ALREADY CONNECTED\n";
				break;
			case USER_ACCOUNT_INCORRECT:
				echo "incorrect account info \n";
				break;
			case NOT_EXISTS_USER;
				echo "not exists user\n";
				break;
			case DB_ERROR:
				echo "db error \n";
				break;
			default :
				echo "unknow error\n";
				break;
		}
	}


     }

}

// Initiiate Library

$api = new API;
$api->processApi();


?>
