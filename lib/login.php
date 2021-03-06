<?php
/**
 * WoWRoster.net WoWRoster
 *
 * Login and authorization
 *
 *
 * @copyright  2002-2011 WoWRoster.net
 * @license    http://www.gnu.org/licenses/gpl.html   Licensed under the GNU General Public License v3.
 * @package    WoWRoster
 * @subpackage User
*/

if( !defined('IN_ROSTER') )
{
	exit('Detected invalid access to this file!');
}

define('ROSTERLOGIN_ADMIN',1); // user id for master admin

/**
 * Login and authorization
 *
 * @package    WoWRoster
 * @subpackage User
 */
class RosterLogin
{
	var $allow_login;
	var $message;
	var $action;
	var $logout;
	var $uid;
	var $levels = array();
	var $valid = 0;
	var $radid = 55;
	var $approved;
	var $access = 0;
	var $user = array();
	var $groups = array();

	var $groups_p = array();

	/**
	 * Constructor for Roster Login class
	 * Accepts an action for the form
	 * And an array of additional fields
	 *
	 * @param $script_filename
	 * @return RosterLogin
	 */
	function RosterLogin( $script_filename='' )
	{
		global $roster;
		
		$this->getgroups();
		$this->getgroupper();
		$this->access = '0:'.$roster->config['default_group'].'';
		$this->setAction($script_filename);
		if( isset( $_POST['logout'] ) && $_POST['logout'] == '1' )
		{
			$this->endSession($this->getUID($_COOKIE['roster_user'], $_COOKIE['roster_pass']));
			setcookie('roster_user',     NULL, time() - (60*60*24*30*100), ROSTER_PATH);
			setcookie('roster_u',        '0',  time() + (60*60*24*30),     ROSTER_PATH);
			setcookie('roster_pass',     NULL, time() - (60*60*24*30*100), ROSTER_PATH);
			setcookie('roster_remember', NULL, time() - (60*60*24*30*100), ROSTER_PATH);

			$this->allow_login = false;
			$this->valid = 0;
			$this->uid = 0;
			$this->access = '0:'.$roster->config['default_group'].'';
			//
			$this->message = $roster->locale->act['logged_out'] . $this->getLoginForm();
			$this->getLoginForm();
		}
		elseif( isset($_POST['password']) && $_POST['password'] != '' && isset($_POST['username']) && $_POST['username'] != '')
		{
			$roster->debug->_debug( 0, false, 'try to login with user and pass', true );
			$this->checkPass(md5($_POST['password']), $_POST['username'],'1');
			return;
		}
		elseif( isset($_COOKIE['roster_pass']) && isset($_COOKIE['roster_user']) )
		{
			$this->checkPass($_COOKIE['roster_pass'], $_COOKIE['roster_user'],'0');
			$roster->debug->_debug( 0, false, 'try to login with cookies', true );
			return;
		}
		else
		{
			$roster->debug->_debug( 0, false, 'no login detected', true );
			$this->allow_login = false;
			$this->message = '';
			setcookie('roster_user',     NULL, time() - (60*60*24*30*100), ROSTER_PATH);
			setcookie('roster_u',        '0',  time() + (60*60*24*30),     ROSTER_PATH);
			setcookie('roster_pass',     NULL, time() - (60*60*24*30*100), ROSTER_PATH);
			setcookie('roster_remember', NULL, time() - (60*60*24*30*100), ROSTER_PATH);
		}
	}
	
	function endSession($uid=null)
	{
		global $roster;
		if ($uid == '')
		{
			$uid = $this->getuserid();
		}
		$query = "DELETE FROM `".$roster->db->table('sessions')."` WHERE `session_user_id`='". $uid ."'";
		$roster->db->query($query);
		$params = session_get_cookie_params();
		setcookie(session_name(), '', time() - 42000,
			$params['path'], $params['domain'],
			$params['secure'], $params['httponly']
		);

		// Finally, destroy the session.
		session_destroy();

	}

	function checkPass( $pass, $user, $createsession )
	{
		global $roster;

		$query = "SELECT * FROM `" . $roster->db->table('user_members') . "` WHERE `usr`='" . $user . "' AND `pass`='" . $pass . "' LIMIT 1;";
		$result = $roster->db->query($query);
		//echo (bool)$result;
		$row = $roster->db->fetch($result);
		$count = $roster->db->num_rows($result);
		//echo $count;

		if( $count == 0 )
		{
			setcookie('roster_user',     NULL, time() - (60*60*24*30*100), ROSTER_PATH);
			setcookie('roster_u',        '0',  time() + (60*60*24*30),     ROSTER_PATH);
			setcookie('roster_pass',     NULL, time() - (60*60*24*30*100), ROSTER_PATH);
			setcookie('roster_remember', NULL, time() - (60*60*24*30*100), ROSTER_PATH);

			$this->allow_login = false;
			$this->valid = 0;
			$this->message = $roster->locale->act['login_fail'];
			$roster->debug->_debug( 0, false, 'login failed', false );
			return false;
		}

		if( $count == 1 )
		{
			$remember = (isset($_POST['rememberme']) ? (int)$_POST['rememberme'] : (int)$_COOKIE['roster_remember'] );
			setcookie('roster_user',     $user,      time() + (60*60*24*30), ROSTER_PATH);
			setcookie('roster_u',        $row['id'], time() + (60*60*24*30), ROSTER_PATH);
			setcookie('roster_pass',     $pass,      time() + (60*60*24*30), ROSTER_PATH);
			setcookie('roster_remember', $remember,  time() + (60*60*24*30), ROSTER_PATH);

			$this->valid = 1;
			$this->uid = $row['id'];
			$this->allow_login = true;
			$this->user = $row;
			if ($row['active'] != 1)
			{
				//roster_die('Your account is nto active or has not been approved by the admin only "Public" areas can be accessed');
				$roster->set_message($roster->locale->act['login_inactive'], 'Roster Auth', 'error');
				$this->access = '0:'.$roster->config['default_group'].'';
			}
			else
			{
				$this->access = $row['access'];
			}

			$this->message = sprintf($roster->locale->act['welcome_user'], $row['user_display']);
			$roster->db->free_result($result);

			$roster->tpl->assign_vars(array(
				'U_LOGIN_ACTION'  => $this->action,
				'S_LOGIN_MESSAGE' => (bool)$this->message,
				'L_LOGIN_MESSAGE' => $this->message,
				'L_REGISTER'      => '<a href="'.makelink('register').'" class="btn btn-block">'.$roster->locale->act['register'].'</a>',
				'U_LOGIN'         => $this->valid
			));
		
			return true;
		}

		$roster->db->free_result($result);

		setcookie('roster_u', '0', time() + 60*60*24*30, ROSTER_PATH);
		$this->allow_login = false;
		$this->message = $roster->locale->act['login_invalid'];
		
		$roster->tpl->assign_vars(array(
			'U_LOGIN_ACTION'  => $this->action,
			'S_LOGIN_MESSAGE' => (bool)$this->message,
			'L_LOGIN_MESSAGE' => $this->message,
			'L_REGISTER'      => '<a href="'.makelink('register').'" class="btn btn-block">'.$roster->locale->act['register'].'</a>',
			'U_LOGIN'         => $this->valid
		));
		return;
	}
	
	function bnetlogin($data,$token = null)
	{
		global $roster;

		$pass = md5($data['battletag'].'#'.$data['id']);
		//first see if the user exists
		$query = "SELECT * FROM `" . $roster->db->table('user_members') . "` WHERE `bnet_id`='" . $data['id'] . "' LIMIT 1;";
		$result = $roster->db->query($query);

		$row = $roster->db->fetch($result);
		$count = $roster->db->num_rows($result);

		// ok the user does not exist create the user
		if( $count == 0 )
		{
			$display = explode('#',$data['battletag']);
			$data = array(
				'usr'			=> $data['battletag'],
				'user_display'	=> $display[0],
				'pass'			=> $pass,
				'email'			=> '',
				'bnet_id'		=> $data['id'],
				'bnet_token'	=> $token,
				'regIP'			=> $_SERVER['REMOTE_ADDR'],
				'dt'			=> $roster->db->escape(gmdate('Y-m-d H:i:s')),
				'access'		=> '0:2',
				'active'		=> '1',
				'user_permissions' => $this->groups[2]['group_permissions']
			);
			$query = 'INSERT INTO `' . $roster->db->table('user_members') . '` ' . $roster->db->build_query('INSERT', $data);
			if( $roster->db->query($query) )
			{
				// ok we created the user LOGTHEM IN!
				return $this->checkPass( $pass, $data['battletag'], true );
				
			}
		}
		if ( $count == 1 )
		{
			// OK IF THEY HAVE AN ACCOUNT
			// but update the token and battle id
			$data = array(
				'usr'			=> $data['battletag'],
				'pass'			=> $pass,
				'bnet_token'	=> $token
			);
			$query = 'UPDATE `' . $roster->db->table('user_members') . '` SET (`bnet_token` = "'.$token.'",`usr`="'.$data['battletag'].'") WHERE `bnet_id` = "'.$data['id'].'"';
			return $this->checkPass( $pass, $data['battletag'], true );
		}
		return false;
	}

	// this function should no longer be used
	// if it is send it to the new proper function
	function _getAuthorized( $access )
	{
		global $roster;

		return $this->getAuthorized( $access );
		
	}

	function getAuthorized( $access )
	{
		global $roster;
		
		$this->getgroups();
		$this->getgroupper();
		// this is allways set so we dont need to check
		$groups = explode(':',$this->access);
		//BUT see if the user is loged in and has permissions
		$user = '';
		if ( isset($this->user['user_permissions']) )
		{
			$user = json_decode($this->user['user_permissions'],true);
		}
		else
		{
			$user = json_decode($this->groups_p[$roster->config['default_group']]['group_permissions'],true);
		}
		
		// roster cp override
		if ($user['roster_cp'] == 1)
		{
			return true;
		}
		
		/*
			see if user has permissions first
		*/
		if ($user[$access] == 1)
		{
			return true;
		}
		
		// user permissions not set check if user has many groups
		if (isset($this->access))
		{
			$groups = explode(':',$this->access);
		}
		else
		{
			$groups = array($default);
		}
		foreach ($groups as $id)
		{
			if(isset($this->groups_p[$id][$access]) && $this->groups_p[$id][$access] == 1)
			{
				return true;
			}
		}
		
		// if all fail return false
		return false;		
	}
	
	function getgroups()
	{
		global $roster;
		
		$dm_query = "SELECT * FROM `" . $roster->db->table('user_groups') . "` ORDER BY `group_id` ASC";

		$dm_result = $roster->db->query($dm_query);
		$g = array();
		while( $row = $roster->db->fetch($dm_result) )
		{
			$g[$row['group_id']] = $row;
		}
		$this->groups = $g;
	}
	function getgroupper()
	{
		global $roster;
		
		$dm_query = "SELECT * FROM `" . $roster->db->table('user_groups') . "` ORDER BY `group_id` ASC";

		$dm_result = $roster->db->query($dm_query);
		$g = array();
		while( $row = $roster->db->fetch($dm_result) )
		{
			$g[$row['group_id']] = json_decode($row['group_permissions'],true);
		}
		$this->groups_p = $g;
	}
	
	function getMessage()
	{
		return $this->message;
	}

	function GetMemberLogin()
	{
		$this->getLoginForm();
	}
	function getLoginForm(  )
	{
		global $roster;

		if( !$this->allow_login && !isset($_POST['logout']) )
		{
			$roster->tpl->assign_vars(array(
				'U_LOGIN_ACTION'  => $this->action,
				'L_LOGIN_WORD'    => '',
				'S_LOGIN_MESSAGE' => (bool)$this->message,
				'L_LOGIN_MESSAGE' => $this->message,
				'L_REGISTER'      => '<a href="'.makelink('register').'" class="btn btn-block">'.$roster->locale->act['register'].'</a>',
				'U_LOGIN'         => 0
			));

			$roster->tpl->set_handle('roster_login', 'login.html');
			return $roster->tpl->fetch('roster_login');
		}
		else
		{
			return $this->getMessage();
		}
	}

	function getMenuLoginForm()
	{
		global $roster;

		$roster->tpl->assign_vars(array(
			'U_LOGIN_ACTION'  => $this->action,
			'S_LOGIN_MESSAGE' => (bool)$this->message,
			'L_LOGIN_MESSAGE' => $this->message,
			'L_REGISTER'      => '<a href="'.makelink('register').'" class="btn btn-block">'.$roster->locale->act['register'].'</a>',
			'U_LOGIN'         => $this->valid
		));

		$roster->tpl->set_handle('roster_menu_login', 'menu_login.html');
		return $roster->tpl->fetch('roster_menu_login');
	}

	function setAction( $action )
	{
		$this->action = makelink($action);
	}

	function makeAccess( $values )
	{
		trigger_error('$roster->auth->makeAccess is deprecated. Please update to use $roster->auth->rosterAccess.');
		$this->rosterAccess($values);
	}

	function rosterAccess( $values )
	{
		global $roster;

		// Only add levels if we have none stored
		if( count($this->levels) == 0 )
		{
			// Add built-in levels

			$query = "SELECT * FROM `" . $roster->db->table('user_groups') . "` ORDER BY `group_id` ASC";
			$result = $roster->db->query($query);

			if( !$result )
			{
				die_quietly($roster->db->error, 'Roster Auth', __FILE__,__LINE__,$query);
			}

			while( $row = $roster->db->fetch($result) )
			{
				$this->levels[$row['group_id']] = $row['group_name'];
			}
		}

		$name = $values['name'];
		$title = isset($values['title']) ? $values['title'] : $roster->locale->act['access_level'] .':';
		$this->radid++;

		$output = $title .' <select id="rad_config_'. $this->radid .'" name="config_'. $name .'[]" class="multiselect" multiple="multiple">';
		$lvl = explode(':', $values['value']);

		foreach ($this->levels as $acc => $a)
		{
			$output .= '<option value="'. $acc .'" '. (in_array($acc, $lvl) ? 'selected' : '') .'>'. ucfirst ($a) ."</option>\n";
		}
		$output .= '</select>';

		return $output;
	}

	function GetUserInfo($uid)
	{
		global $roster;
		
		$query = "SELECT * FROM `" . $roster->db->table('user_members') . "` WHERE `id`='" . $uid . "';";
		$result = $roster->db->query($query);
		//echo (bool)$result;
		$row = $roster->db->fetch($result);
		return $row;
	}
	
	function _ingroup( $groups, $user_group )
	{
		
		$this->approved = false;
		$addon = array();
		$addon = explode(":",$groups);
		$user = array();
		$user = explode(":",$user_group);
		foreach ($user as $x => $ac)
		{
			foreach ($addon as $a => $as)
			{
				if ( (int)$as ==  (int)$ac)
				{
					return true;
				}
			}
		}
		return false;
	}
	
	function getUID($user, $pass)
	{
		global $roster;

		$query = "SELECT `id` FROM `" . $roster->db->table('user_members') . "` WHERE `usr`='".$user."' AND `pass`='".$pass."';";
		$result = $roster->db->query($query);
		$rows = $roster->db->num_rows($result);
		$row = $roster->db->fetch($result);

		if ($rows == 1)
		{
			return $row['id'];
		}
		else
		{
			return '0';
		}
	}

	function checkEMail($mail_address)
	{
		global $roster, $addon, $user;
		
		if (preg_match("/^[0-9a-z]+(([\.\-_])[0-9a-z]+)*@[0-9a-z]+(([\.\-])[0-9a-z-]+)*\.[a-z]{2,4}$/i", $mail_address))
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	function getUUID( $d )
	{
		global $roster;

		return hash('ripemd128', $d);
	}

}
