<?php 

class Maria
{
    protected $mysql;
    function __construct()
    {
        global $CONF;
        $this->mysql = new mysqli($CONF['MYSQL']['host'], $CONF['MYSQL']['user'], $CONF['MYSQL']['password'], $CONF['MYSQL']['database'],$CONF['MYSQL']['port']);
        $this->mysql->set_charset("utf8");

    }
/*
     function 	connect()
     {
        $this->mysql = new mysqli($CONF['MYSQL']['host'], $CONF['MYSQL']['user'], $CONF['MYSQL']['password'], $CONF['MYSQL']['database'],$CONF['MYSQL']['port']);
        $this->mysql->set_charset("utf8");
         $this->mysql = new mysqli($sqlserver, $sqluser, $sqlpassword, $database, $port);
     }
*/

    function select_db($database)
    {
	   $this->mysql->select_db($database);

    }

    function query($query) 
    {
	   $this->mysql->query($query);
    }
    function change_charset($charset)
    {
        $this->mysql->set_charset($charset);
    }    
    function sql_nextid()
    {
        return ($this->mysql) ? $this->mysql->insert_id : false;
    }
    
    function setResultQuery($query, $param)
    {
        $array = NULL;
        if(!$this->mysql->connect_errno)
        {
            $stmt = $this->setStatement($query, $param);
            try
            {
                if($stmt != NULL)
                {
                    if($stmt->execute())
                    {
                        //Obtener resultados
                        $stmt->store_result();
                        $variables = array();
                        $data = array();
                        $meta = $stmt->result_metadata();
                        while($field = $meta->fetch_field())
                        {
                            $variables[] = &$data[$field->name];
                        }
                        call_user_func_array(array($stmt, 'bind_result'), $variables);
                        $i=0;
                        while($stmt->fetch())
                        {
                            $array[$i] = array();
                            foreach($data as $k=>$v)
                                $array[$i][$k] = $v;
                                $i++;
                        }
                        $stmt->close();
                    }
                }
            }catch(Exception $e){
                $array = FALSE;
            }
        }
        return $array;
    }
    
    function setStatement($query, $param)
    {
        global $LOGGER;
        try
        {
            $stmt = $this->mysql->prepare($query);
            
            $ref = new ReflectionClass('mysqli_stmt');
            if(count($param) != 0)
            {
                $method = $ref->getMethod('bind_param');
                $method->invokeArgs($stmt, $param);

//                 call_user_func_array(array($stmt,'bind_param') , $param);
                
                
            }
        }catch(Exception $e){
            //             $LOGGER->Error("SQL Error [%s]", print_r($e, true));

            if($stmt != null)
            {
                $stmt->close();
            }
        }
        return $stmt;
    }
    
    /**
     * noResultQuery
     * @param string $query : query statement
     * @param array $param : array("sd",....);
     * @return boolean
     */
    function setNoResultQuery($query, $param)
    {
        $validation = false;
        if(!$this->mysql->connect_errno)
        {
            try
            {
                $stmt = $this->setStatement($query, $param);
                if($stmt != null)
                {
                    if($stmt->execute())
                    {
                        $stmt->close();
                        $validation = true;
                    }
                }
            }catch(Exception $e){
                $validation = false;
            }
        }
        return $validation;
    }
    
    function in_table($table, $cond)
    {
    	$stSQL = "SELECT *
    		    FROM $table
    		   WHERE $cond";
    	$rslt = $this->mysql->prepare($stSQL);
    	
    	$rslt->execute();
    	$rslt->store_result();
    	$num_rows = $rslt->num_rows;
    	$rslt->close();
    	
    	return $num_rows > 0;

    }

    function prepare($query)
    {
	   return $this->mysql->prepare($query);

    }

    function insert($table, $field = array(), $assoc = false)
    {
        $stSQL ="INSERT INTO $table SET ";
        if ( $assoc )
        {
            $t = array();
            foreach($field as $key => $val)
            {
                $t[] = "$key = $val";
            }
            $field = $t;
        }
        $stSQL .= implode(',' , $field);
    	return $this->mysql->query($stSQL);
    }    

    // Update any row that matches a WHERE clause
    function update($table,$field_values,$where, $assoc = false) {
        if ( $assoc )
        {
            $t = array();
            foreach($field_values as $key => $val)
            {
                $t[] = "$key = $val";
            }
            $field_values = $t;
        }
        
        if (is_array($field_values))
        {
            $query = 'UPDATE ' . $table . ' SET ' . implode(',', $field_values) .
              ' WHERE ' . $where;
        } else {
            $query = 'UPDATE ' . $table . ' SET ' . $field_values .
              ' WHERE ' . $where;
        }
        return $this->mysql->query($query);
    }

    function select_array($stSQL)
    {
    	$stmt = $this->mysql->prepare($stSQL);
    	$stmt->execute();
    	$data = array();
    	$meta = $stmt->result_metadata();
    	while($field = $meta->fetch_field())
    	{
    	    $variables[] = &$data[$field->name];
    	}
    	call_user_func_array(array($stmt, 'bind_result'), $variables);
    	$i=0;
    	while($stmt->fetch())
    	{
    	    $retdata[$i] = array();
    	    foreach($data as $k=>$v) {
    		$retdata[$i][$k] = $v;
    	    }
    	    $i++;
    	}
    	$stmt->close();
    
    	return $retdata;


    }

    /**
     * Build sql statement from array for insert/update/select statements
     *
     * Idea for this from Ikonboard
     * Possible query values: INSERT, INSERT_SELECT, MULTI_INSERT, UPDATE, SELECT
     *
     */
    function sql_build_array($query, $assoc_ary = false)
    {
        if (!is_array($assoc_ary))
        {
            return false;
        }
        
        $fields = $values = array();
        
        if ($query == 'INSERT' || $query == 'INSERT_SELECT')
        {
            foreach ($assoc_ary as $key => $var)
            {
                $fields[] = $key;
                
                if (is_array($var) && is_string($var[0]))
                {
                    // This is used for INSERT_SELECT(s)
                    $values[] = $var[0];
                }
                else
                {
                    $values[] = $this->_sql_validate_value($var);
                }
            }
            
            $query = ($query == 'INSERT') ? ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')' : ' (' . implode(', ', $fields) . ') SELECT ' . implode(', ', $values) . ' ';
        }
        else if ($query == 'MULTI_INSERT')
        {
            $ary = array();
            foreach ($assoc_ary as $id => $sql_ary)
            {
                // If by accident the sql array is only one-dimensional we build a normal insert statement
                if (!is_array($sql_ary))
                {
                    return $this->sql_build_array('INSERT', $assoc_ary);
                }
                
                $values = array();
                foreach ($sql_ary as $key => $var)
                {
                    $values[] = $this->_sql_validate_value($var);
                }
                $ary[] = '(' . implode(', ', $values) . ')';
            }
            
            $query = ' (' . implode(', ', array_keys($assoc_ary[0])) . ') VALUES ' . implode(', ', $ary);
        }
        else if ($query == 'UPDATE' || $query == 'SELECT')
        {
            $values = array();
            foreach ($assoc_ary as $key => $var)
            {
                $values[] = "$key = " . $this->_sql_validate_value($var);
            }
            $query = implode(($query == 'UPDATE') ? ', ' : ' AND ', $values);
        }
        
        return $query;
    }
    /**
     * Function for validating values
     * @access private
     */
    function _sql_validate_value($var)
    {
        if (is_null($var))
        {
            return 'NULL';
        }
        else if (is_string($var))
        {
            return "'" . $this->sql_escape($var) . "'";
        }
        else
        {
            return (is_bool($var)) ? intval($var) : $var;
        }
    }
    
    /**
     * Escape string used in sql query
     * Note: Do not use for bytea values if we may use them at a later stage
     */
    function sql_escape($msg) {
        return $this->mysql->real_escape_string ($msg);
    }
    
    
    
    /**
     * SQL Transaction
     */
    function sql_transaction($status = 'begin')
    {
        switch ($status)
        {
            case 'begin':
                $this->mysql->autocommit(false);
                return $this->mysql->begin_transaction();
                break;
                
            case 'commit':
                $result = $this->mysql->commit();
                $this->mysql->autocommit(true);
                return $result;
                break;
                
            case 'rollback':
                $result = $this->mysql->rollback();
                $this->mysql->autocommit(true);
                return $result;
                break;
        }
        
        return true;
    }

    /**
     * 테이블 사용량 확인(byte 단위)
     * @param array $tables // 확인할 테이블 명
     */
    function table_usage($tables = array())
    {
        $stSQL = "SELECT table_schema, sum(data_length) data_length, sum(index_length) index_length
                    FROM information_schema.tables";
        if ( is_array($tables)) {
            $stSQL .= "\nWHERE table_schema IN ('". implode("','", $tables) . "')";
        }
        $stSQL .= "\nGROUP BY table_schema";
        $stSQL .= "\nORDER BY table_schema";
        
        $rtn = $this->setResultQuery($stSQL,array());
        return $rtn;
                
        
    }
    
    function errorno()
    {

	   return $this->mysql->errno;
    }
    function error()
    {
	   return $this->mysql->error;
    }
    function __destruct()
    {
        $this->mysql->close();
    }
}


