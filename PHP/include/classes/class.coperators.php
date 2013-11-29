<?php
/*
 * MSIUL version 0.1-alpha
 *
 * Copyright © 2013 MSIUL Developers
 * 
 * Please, see the doc/AUTHORS for more information about authors!
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */
 
class COperator {


	private static $operatorData = null;

	/**
	 * Return operator info or list operators ($operid = null)
	 *
	 * @param $db PDO instance
	 * @param int $operid to return operator info
	 * @param $operid = null - list operators
	 * @return array
	 */
	public function get($db, $operid = null) {
		global $LOCALES, $OPERATOR_GROUPS;

		$sysadmin_rights = (OPERATOR_TYPE_LINUX_ADMIN == self::$operatorData['type']);
		$admin_rights = (OPERATOR_TYPE_ADMIN == self::$operatorData['type']);

		if (isset($operid)) {

			// Show info for operator System Admin
			if ($sysadmin_rights) {
				
				$sql = 'SELECT operid,alias,name,url,lang,theme,refresh,type FROM operators WHERE operid = ? LIMIT 1';
				$sth = $db->dbh->prepare($sql);
				$sth->bindParam(1, $operid);
				$sth->execute();
				$result = $sth->fetch(PDO::FETCH_ASSOC);
			return $result;
			}

			// Show info for selected operator if are in Operators_Groups - Cashiers and Network Technicians for operator Admin
			if ($admin_rights) {
				
				$sql = 'SELECT operid,alias,name,url,lang,theme,refresh,type FROM operators WHERE operid = ? AND type < ? LIMIT 1';
				$sth = $db->dbh->prepare($sql);
				$sth->bindParam(1, $operid);
				$sth->bindValue(2, OPERATOR_TYPE_ADMIN);
				$sth->execute();
				$result = $sth->fetch(PDO::FETCH_ASSOC);
			return $result;
			}
		}

		// List all operators for operator System Admin
		if ($operid == null && $sysadmin_rights) {

			$sql = 'SELECT operid,alias,name,lang,type FROM operators';
			$sth = $db->dbh->prepare($sql);

			$sth->execute();
			$result = $sth->fetchAll(PDO::FETCH_ASSOC);

			for ($i = 0; $i < count($result); ++$i) {
			
				$result[$i]['lang'] = $LOCALES[$result[$i]['lang']];
				$result[$i]['type'] = $OPERATOR_GROUPS[$result[$i]['type']];
			}
		return $result;
		}

		// List operators in Operators_Groups - Cashiers and Network Technicians for operator Admin
		if ($operid == null && $admin_rights) {

			$sql = 'SELECT operid,alias,name,lang,type FROM operators WHERE type < ?';
			$sth = $db->dbh->prepare($sql);
			$sth->bindValue(1, OPERATOR_TYPE_ADMIN);

			$sth->execute();
			$result = $sth->fetchAll(PDO::FETCH_ASSOC);

			for ($i = 0; $i < count($result); ++$i) {
			
				$result[$i]['lang'] = $LOCALES[$result[$i]['lang']];
				$result[$i]['type'] = $OPERATOR_GROUPS[$result[$i]['type']];
			}
		return $result;
		}
	}

	/**
	 * Add Operator
	 *
	 * @param $db - PDO instance
	 * @param array $operator multidimensional array with Operator data
	 * @param string $operator['alias']
	 * @param string $operator['name']
	 * @param string $operator['passwd']
	 * @param string $operator['url']
	 * @param string $operator['lang']
	 * @param string $operator['theme']
	 * @param int $operator['refresh']
	 * @param int $operator['type']
	 */
	public function create($db, $operator) {

		$sysadmin_rights = (OPERATOR_TYPE_LINUX_ADMIN == self::$operatorData['type']);
		$admin_rights = (OPERATOR_TYPE_ADMIN == self::$operatorData['type']);
		
		// Limitation Admin to create operators from groups Cashiers and Network Technicians
		if ($admin_rights && OPERATOR_TYPE_ADMIN >= $operator['type']) {
			
			$operator['type'] = 1;
		}
		
		//Grant all privilege on LINUX Admin to create operators from every groups
		if ($sysadmin_rights || $admin_rights) {
					
			$i = 1;
			foreach ($operator as $key => $value) {
				$keys[$i] = $key;
    			$values[$i] = $value;
				$question_mark[$i] = '?';
				
			$i++;
			}
							
			$sql = 'INSERT INTO `operators` (`'.implode('`,`', $keys).'`)
					VALUES ('.implode(', ', $question_mark).')';

			$db->prepare_array($sql, $values);

			// Get new operator operid and type 
			$sql2 = 'SELECT operid,type FROM operators WHERE alias = ? LIMIT 1';
			$sth = $db->dbh->prepare($sql2);
			$sth->bindParam(1, $operator['alias']);
			$sth->bindColumn('operid', $operid);
			$sth->bindColumn('type', $type);
			$sth->execute();
			$sth->fetch(PDO::FETCH_ASSOC);

			// Insert new operator on operators_groups
			$sql3 = 'INSERT INTO `operators_groups` (`opergrpid`,`operid`) VALUES (?, ?)';
			$sth = $db->dbh->prepare($sql3);
			$sth->bindParam(1, $type);
			$sth->bindParam(2, $operid);
			$sth->execute();
		}
	}

	/**
	 * Update Operator
	 *
	 * @param $db - PDO instance
	 * @param array $operator multidimensional array with Operator data
	 * @param string $operator['alias']
	 * @param string $operator['name']
	 * @param string $operator['passwd']
	 * @param string $operator['url']
	 * @param string $operator['lang']
	 * @param string $operator['theme']
	 * @param int $operator['refresh']
	 * @param int $operator['type']
	 * @param int $id - operid
	 */
	public function update($db, $operator, $id) {
		
		$sysadmin_rights = (OPERATOR_TYPE_LINUX_ADMIN == self::$operatorData['type']);
		$admin_rights = (OPERATOR_TYPE_ADMIN == self::$operatorData['type']);

		// Limitation Admin to update operators from groups Cashiers and Network Technicians
		if ($admin_rights && OPERATOR_TYPE_ADMIN >= $operator['type']) {
			
			header('Location: profile.php');
			exit;
		}

		//Grant all privilege on LINUX Admin to update operators
		if ($sysadmin_rights || $admin_rights) {

			$i = 1;
			foreach($operator as $key => $value) {
				$keys[$i] = $key;
    			$values[$i] = $value;

			$i++;
			}

			$sql = 'UPDATE operators SET '.implode(' = ?, ', $keys).' = ? WHERE operid = ?';

			array_push($values, $id);
			$db->prepare_array($sql, $values);
			
			$sql2 = 'UPDATE operators_groups SET opergrpid = ? WHERE operid = ?';
			$sth = $db->dbh->prepare($sql2);
			$sth->bindParam(1, $operator['type']);
			$sth->bindParam(2, $id);
			$sth->execute();
		}
	}

	/**
	 * Delete Operator
	 *
	 * @param $db - PDO instance
	 * @param array $operator multidimensional array with Operator data
	 * @param int $operator['operid']
	 * @param string $operator['alias']
	 * @param int $operator['type']
	 */	
	public function delete($db, $operator) {
	
		$sysadmin_rights = (OPERATOR_TYPE_LINUX_ADMIN == self::$operatorData['type']);
		$admin_rights = (OPERATOR_TYPE_ADMIN == self::$operatorData['type']);

		// Limitation Admin to update operators from groups Cashiers and Network Technicians
		if ($admin_rights && OPERATOR_TYPE_ADMIN >= $operator['type']) {
			
			header('Location: profile.php');
			exit;
		}
		
		//Grant all privilege on LINUX Admin to delete operators
		if ($sysadmin_rights || $admin_rights) {
			
			// Delete operator
			$sql = 'DELETE FROM operators WHERE operid = ? AND alias = ? AND type = ? LIMIT 1';
			$sth = $db->dbh->prepare($sql);
			$sth->bindParam(1, $operator['operid']);
			$sth->bindParam(2, $operator['alias']);
			$sth->bindParam(3, $operator['type']);
			$sth->execute();
		}
	}
	
 	// ******************************************************************************
	// LOGIN
	// ******************************************************************************

	public function login($operator) {

		//Creat new instance and connect to database
		$db = new CPDOinstance();

		if ($this->dblogin($operator, $db) == true) {

			if ($this->attempt_check_update($operator, $db) == true) {

				sleep(30); 
				//echo 'Your account is blocked.';
            	return false;
			}
			else {
				$operatorData = $this->getOperatorData($operator, $db);
				$operatorData['operip'] = $operator['ip'];
				$operatorData['login_string'] = hash('sha512', $operator['ip'].$operator['browser']);
				
				self::$operatorData = $operatorData;
				
		        // Chek Operator permission
				self::$operatorData['type'] = $this->check_permission($db);

				if (!self::$operatorData['type']) {

					return false;
				}
				else {

					// Start session
					$db->start_session_handler();

					//Save operator data on session
					$_SESSION['operatorData'] = array();
					$_SESSION['operatorData'] = self::$operatorData;
					$_SESSION['form_key'] = null;
					$_SESSION['msg'] = null;

					return self::$operatorData;
				}
			}
 		}
		else {

		return false;
		}
	}

	private function dblogin($operator, $db) {

		if (!$operator['ip']) {

			return false;
		}
		//Check ip-count and if exist block IP whit first query to DB 
		elseif ($this->attempt_block($operator, $db) == true) {

			sleep(30);
			//echo 'Your account is blocked.';
			return false;
		}
		else {

			$sql = 'SELECT passwd,salt FROM operators WHERE alias = ? LIMIT 1';
			$sth = $db->dbh->prepare($sql);
			$sth->bindParam(1, $operator['alias'], PDO::PARAM_STR);
			$sth->bindColumn('passwd', $db_password);
			$sth->bindColumn('salt', $salt);
			$sth->execute();

			if ($sth->rowCount() == 1) { 

				$sth->fetch(PDO::FETCH_ASSOC);
				$operator['password'] = hash('sha512', $operator['password'].$salt);

				if ($db_password == $operator['password']) {

					return true;
				}
				else {

					$this->attempt_failed($operator, $db);
					sleep(30);
					//echo 'Login name or password is incorrect.';					
					return false;
				}
			}
			else {
				$this->attempt_failed($operator, $db);
				sleep(30);
				//echo 'Login name or password is incorrect.';				
				return false;
			}
		}
	}

	private function getOperatorData($operator, $db) {

		$sql = 'SELECT operid,alias,name,url,lang,theme,refresh,type FROM operators WHERE alias = ? LIMIT 1';
		$sth = $db->dbh->prepare($sql);
		$sth->bindParam(1, $operator['alias']);
		$sth->execute();
		$operatorData = $sth->fetch(PDO::FETCH_ASSOC);

		return $operatorData;
	}

	// Chek Operator permission
	public function check_permission($db) {

		$sql = 'SELECT operators_groups.opergrpid FROM operators_groups INNER JOIN opergrp 
           		ON operators_groups.opergrpid = opergrp.opergrpid
           		WHERE operid = ? LIMIT 1';
		$sth = $db->dbh->prepare($sql);
		$sth->bindParam(1, self::$operatorData['operid']);
		$sth->bindColumn('opergrpid', $db_opergrpid);
		$sth->execute();

		if ($sth->rowCount() == 1) {

			$sth->fetch(PDO::FETCH_ASSOC);
		
			if (self::$operatorData['type'] == $db_opergrpid) {
				
				return $db_opergrpid;
			}
			else {
				
				return false;
			}	
		}
		else {

			return false;
		}
	}	

	//Ban IP after 5 attempts
	private function attempt_block($operator, $db) {

		$sql = 'SELECT attempt_failed,attempt_time,attempt_ip
 				FROM login_attempts WHERE attempt_failed >= ? AND attempt_ip = ? LIMIT 1';
		$sth = $db->dbh->prepare($sql);
		$sth->bindValue(1, 5);
		$sth->bindParam(2, $operator['ip']);
		$sth->execute();

		if ($sth->rowCount() == 1) {

			//echo 'Your account is blocked.';
			return true;
		}
		else {

			return false;
		}
	}

	//Block IP after 3 incorrect attempts for 2 hours else reset attempt_failed
	private function attempt_check_update($operator, $db) {

		$valid_time = time() - (2 * 60 * 60); 

		$sql = 'SELECT attempt_failed,attempt_time,attempt_ip
				FROM login_attempts WHERE attempt_failed >= ? AND attempt_time > ? AND attempt_ip = ? LIMIT 1';
		$sth = $db->dbh->prepare($sql);
		$sth->bindValue(1, 3);
		$sth->bindParam(2, $valid_time);
		$sth->bindParam(3, $operator['ip']);
		$sth->execute();
		
		if ($sth->rowCount() == 1) {

			//echo 'Your account is blocked.';
		return true;
		}
		elseif ($sth->rowCount() == 0) {

			$sql = 'UPDATE login_attempts SET attempt_failed = ? WHERE attempt_ip = ?';
			$sth = $db->dbh->prepare($sql);
			$sth->bindValue(1, 0);
			$sth->bindParam(2, $operator['ip']);
			$sth->execute();
			
		return false;
		}
	}

	// Update or creat failed attempt
	private function attempt_failed($operator, $db) {

		$ip = $operator['ip'];
		$alias = $operator['alias'];
		$time = time();

		$sql = 'SELECT attempt_failed FROM login_attempts WHERE attempt_ip = ? LIMIT 1';
		$sth = $db->dbh->prepare($sql);
		$sth->bindParam(1, $ip);
		$sth->bindColumn('attempt_failed', $attempt_failed);
		$sth->execute();

		if ($sth->rowCount() == 1) {

			$sth->fetch(PDO::FETCH_ASSOC);
			$counter = $attempt_failed + 1;

			$sql = 'UPDATE login_attempts SET attempt_failed = ?, attempt_time = ?, alias = ? 
					WHERE attempt_ip = ?';
			$sth = $db->dbh->prepare($sql);
			$sth->bindParam(1, $counter);
			$sth->bindParam(2, $time);
			$sth->bindParam(3, $alias);
			$sth->bindParam(4, $ip);
			$sth->execute();
		} 
		else {
			$sql = "INSERT INTO `login_attempts` (`attempt_time`,`attempt_ip`,`alias`) 
					VALUES (?, ?, ?)";
			$sth = $db->dbh->prepare($sql);
			$sth->bindParam(1, $time);
			$sth->bindParam(2, $ip);
			$sth->bindParam(3, $alias);
			$sth->execute();
		}
	}

    public function checkAuthentication($sessionid) {

        $ip = $_SERVER['REMOTE_ADDR'];
        $browser = $_SERVER['HTTP_USER_AGENT'];

        $login_string = hash('sha512', $ip.$browser);

        //Creat new instance and connect to database
        $db = new CPDOinstance();
        $sql = 'SELECT sessionid FROM sessions 
                WHERE sessionid = :sessionid AND login_string = :login_string LIMIT 1';
        $sth = $db->dbh->prepare($sql);
        $sth->bindValue(':sessionid', $sessionid, PDO::PARAM_STR);
        $sth->bindValue(':login_string', $login_string, PDO::PARAM_STR);
        $sth->execute();

        //if session exist 
        if ($sth->rowCount() == 1) { 

            //Continue a session
            $db->start_session_handler();

            if (!empty($_SESSION['operatorData'])) {

                self::$operatorData = $_SESSION['operatorData'];
	
                // Chek Operator permission
                self::$operatorData['type'] = $this->check_permission($db);

                if (!self::$operatorData['type']) {

                    return false;
                }
                else {

                    return self::$operatorData;
                }
            }
            else {

                return false;
            }
        }
        else {

            return false;
        }	
    }

}	
		
	
?>