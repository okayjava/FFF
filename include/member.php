<?php
class member
{
	var $DB;
	function __construct()
	{
		global $DB;
		$this->DB = &$DB;
	}

	function join($par)
	{
		$user_seq = $this->is_exists($par['user_id']);
		if ( $user_seq === false )
		{
			$user_seq = $this->user_add($par);
			if ( !$user_seq )
			{
				return DB_ERROR;
			} 
			return SUCCESS;
		} 
		return ALREADY_USER_EXISTS;
	}
	private function user_add($par)
	{
		$user_pass = Encoder($par['user_pass']);
		$stSQL = "INSERT INTO user(user_id, user_pass,client_hash, regist_dt)
				VALUES (?, ?, ?, NOW())";
		$param = array("sss"
				,&$par['user_id']
				,&$user_pass
				,&$par['client_hash']);
		$rtn = $this->DB->setNoResultQuery($stSQL, $param);
		return $this->DB->sql_nextid();
	}

	private function is_exists($user_id)
	{
		$stSQL = "SELECT user_seq
			    FROM user
			   WHERE user_id = ?";
		$param = array("s", &$user_id);
		$rows = $this->DB->setResultQuery($stSQL, $param);
		if ( !$rows || !$rows[0]['user_seq'] ) {
			return false;
		} else {
			return $rows[0]['user_seq'];
		}
	}
	private function user_password_check($user_seq, $user_pass)
	{
		$stSQL = "SELECT user_pass
			    FROM user
			   WHERE user_seq = ?";
		$param = array("d", &$user_seq);
		$row = $this->DB->setResultQuery($stSQL, $param);
		$row = $row[0];
		if ( $user_pass != $row['user_pass'] )
		{
			return USER_ACCOUNT_INCORRECT;
		}
		return SUCCESS;
	}
	private function get_user_hash_info($user_seq)
	{
		$stSQL = "SELECT client_hash, (client_hash_update_dt < date_add(now(), interval - 5 minute)) as isover
			    FROM user
			   WHERE user_seq = ?";
		$param = array("d", &$user_seq);
		$row = $this->DB->setResultQuery($stSQL, $param);
		return $row[0];
	}
	private function user_hash_update($user_seq, $user_hash)
	{
		$stSQL = "UPDATE user
		    	     SET client_hash = ?
			        ,client_hash_update_dt = now()
			   WHERE user_seq = ? ";
		$param = array("sd", &$user_hash, &$user_seq);
		$this->DB->setNoResultQuery($stSQL, $param);
	}

	function login($par)
	{
		$user_seq = $this->is_exists($par['user_id']);
		if ( $user_seq === false )
		{
			return NOT_EXISTS_USER;
		} 
		
		$pass_check_result = $this->user_password_check($user_seq, $par['user_pass']) ;
		if ( SUCCESS !== $pass_check_result)
		{
			return USER_ACCOUNT_INCORRECT;
		}

		$user_hash_info = $this->get_user_hash_info($user_seq);	
		if ( $user_hash_info['client_hash'] == $par['client_hash'] )
		{
			// 최종 pc 정보가, 현재 pc정보와 동일 할때는
			// update 시간과 상관없이 업데이트함
			//echo "same client hash\n";
			$this->user_hash_update($user_seq,$par['client_hash']);
			return SUCCESS;
		} elseif ( $user_hash_info['client_hash'] != $par['client_hash']
				&& $user_hash_info['isover'] == 1) {
			// 기존 PC 정보가 만료됨.
			// 새로은 PC정보로 업데이트함
			//echo "client hash expire\n";
			$this->user_hash_update($user_seq,$par['client_hash']);
			return SUCCESS;
		} elseif ( $user_hash_info['client_hash'] != $par['client_hash']
				&& $user_hash_info['isover'] != 1) {
			// 이미 다른 PC에서 사용중임
			return ALREADY_CONNECTED;
		} else {
			return UNKNOWN_ERROR;
		}
	}

}
