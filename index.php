<?php
/**
 * WoWRoster.net WoWRoster
 *
 * The only file anyone should directly access in Roster
 *
 * @copyright  2002-2011 WoWRoster.net
 * @license    http://www.gnu.org/licenses/gpl.html   Licensed under the GNU General Public License v3.
 * @package    WoWRoster
 */

//---[ Text File Downloader ]-----------------------------
if( isset($_POST['send_file']) && !empty($_POST['send_file']) && !empty($_POST['data']) )
{
	$file = $_POST['data'];

	header('Content-Length: ' . strlen(stripslashes($file)));
	header('Content-Type: text/x-delimtext; name="' . $_POST['send_file'] . '.txt"');
	header('Content-Disposition: attachment; filename="' . $_POST['send_file'] . '.txt"');

	// We need to stripslashes no matter what the setting of magic_quotes_gpc is
	echo stripslashes($file);

	exit();
}

define('IN_ROSTER', true);


require_once (dirname(__FILE__) . DIRECTORY_SEPARATOR . 'settings.php');
$auth_url = makelink('redirect&state=login');
$js1 = "
var oAuth2AuthWindow;
	function popupClosing() {
		alert('About to refresh');
		window.location.href = '".makelink('')."';
	}
	
	function closepopup()
	{
		self.close();
		opener.location.href = '".ROSTER_URL."';
	}
	function openWin()
	{
		oAuth2AuthWindow = window.open('".$auth_url."', 'masheryOAuth2AuthWindow', 'width=430,height=660');
	}
	jQuery(document).ready( function($){

		jQuery('#bnetlogin').click(function(e)
		{
			e.preventDefault();
			//alert('boo');
			oAuth2AuthWindow = window.open('".$auth_url."', 'masheryOAuth2AuthWindow', 'width=430,height=660');
		});

	});
";
roster_add_js($js1, 'inline', 'header', false, false);
// ----[ Get path info based on scope ]----
if( !isset($roster->pages[1]) )
{
	$roster->pages[1] = '';
}

switch( $roster->pages[0] )
{
	case 'char':
		$path = ROSTER_ADDONS . $roster->pages[1] . DIR_SEP . 'char' . DIR_SEP
			. (isset($roster->pages[2]) ? $roster->pages[2] : 'index') . '.php';
		if( !file_exists($path) )
		{
			$path = ROSTER_ADDONS . $roster->pages[1] . DIR_SEP . 'char' . DIR_SEP . 'index.php';
		}
		break;

	case 'user':
		$path = ROSTER_ADDONS . $roster->pages[1] . DIR_SEP . 'user' . DIR_SEP
			. (isset($roster->pages[2]) ? $roster->pages[2] : 'index') . '.php';
		if( !file_exists($path) )
		{
			$path = ROSTER_ADDONS . $roster->pages[1] . DIR_SEP . 'user' . DIR_SEP . 'index.php';
		}
		break;

	case 'guild':
		$path = ROSTER_ADDONS . $roster->pages[1] . DIR_SEP . 'guild' . DIR_SEP
			. (isset($roster->pages[2]) ? $roster->pages[2] : 'index') . '.php';
		if( !file_exists($path) )
		{
			$path = ROSTER_ADDONS . $roster->pages[1] . DIR_SEP . 'guild' . DIR_SEP . 'index.php';
		}
		break;

	case 'realm':
		$path = ROSTER_ADDONS . $roster->pages[1] . DIR_SEP . 'realm' . DIR_SEP
			. (isset($roster->pages[2]) ? $roster->pages[2] : 'index') . '.php';
		if( !file_exists($path) )
		{
			$path = ROSTER_ADDONS . $roster->pages[1] . DIR_SEP . 'realm' . DIR_SEP . 'index.php';
		}
		break;

	case 'util':
		$path = ROSTER_ADDONS . $roster->pages[1] . DIR_SEP
			. (isset($roster->pages[2]) ? $roster->pages[2] : 'index') . '.php';
		if( !file_exists($path) )
		{
			$path = ROSTER_ADDONS . $roster->pages[1] . DIR_SEP . 'index.php';
		}
		break;

	default:
		// OK, so it isn't a scope. Prolly a file in pages.
		if( file_exists($file = ROSTER_PAGES . $roster->pages[0] . '.php') )
		{
			$content = '';
			ob_start();
				require ($file);
			$content = ob_get_clean();

			if( $roster->output['show_header'] )
			{
				include_once (ROSTER_BASE . 'header.php');
			}

			echo $content;

			if( $roster->output['show_footer'] )
			{
				include_once (ROSTER_BASE . 'footer.php');
			}

			exit();
		}
		else
		{
			// Send a 404. Then the browser knows what's going on as well.
			header('HTTP/1.0 404 Not Found');
			roster_die(sprintf($roster->locale->act['module_not_exist'], ROSTER_PAGE_NAME), $roster->locale->act['roster_error']);
		}
}
//d($roster);
if( empty($roster->pages[1]) )
{
	// Send a 404. Then the browser knows what's going on as well.
	header('HTTP/1.0 404 Not Found');
	roster_die(sprintf($roster->locale->act['module_not_exist'], ROSTER_PAGE_NAME), $roster->locale->act['roster_error']);
}

$addon = getaddon($roster->pages[1]);

//---[ Check if the module exists ]-----------------------
if( !file_exists($path) )
{
	// Send a 404. Then the browser knows what's going on as well.
	header('HTTP/1.0 404 Not Found');
	roster_die(sprintf($roster->locale->act['module_not_exist'], ROSTER_PAGE_NAME), $roster->locale->act['roster_error']);
}

if( !$roster->auth->getAuthorized($addon['basename'].'_access') )
{
	roster_die(sprintf($roster->locale->act['addon_no_access'], $addon['basename']), $roster->locale->act['addon_error']);
}

if( $addon['active'] == '1' )
{
	// Check if this addon is in the process of an upgrade and deny access if it hasn't yet been upgraded
	$installfile = $addon['inc_dir'] . 'install.def.php';
	$install_class = $addon['basename'] . 'Install';

	if( file_exists($installfile) )
	{
		include_once ($installfile);

		if( class_exists($install_class) )
		{
			$addonstuff = new $install_class();

			// -1 = overwrote newer version
			//  0 = same version
			//  1 = upgrade available

			if( version_compare($addonstuff->version, $addon['version']) )
			{
				roster_die(sprintf($roster->locale->act['addon_upgrade_notice'], $addon['basename'])
					. '<br /><a href="' . makelink('rostercp-install') . '">'
					. sprintf($roster->locale->act['installer_click_upgrade'], $addon['version'], $addonstuff->version)
					. '</a>', $roster->locale->act['addon_error']);
			}
			unset($addonstuff);
		}
	}

	// Include addon's locale files if they exist
	foreach( $roster->multilanguages as $lang )
	{
		$roster->locale->add_locale_file($addon['locale_dir'] . $lang . '.php', $lang);
	}

	// Include addon's inc/conf.php file
	if( file_exists($addon['conf_file']) )
	{
		include_once ($addon['conf_file']);
	}

	// The addon will now assign its output to $content
	$content = '';
	ob_start();
		require ($path);
	$content .= ob_get_clean();

	// Pass all the css to roster_add_css() which is a placeholder in roster_header for more css style defines
	if( $addon['css_url'] != '' )
	{
		roster_add_css($addon['css_url'], 'theme');
	}
	if( $addon['tpl_css_url'] != '' )
	{
		roster_add_css($addon['tpl_css_url'], 'theme');
	}

	if( $roster->output['show_header'] )
	{
		include_once (ROSTER_BASE . 'header.php');
	}

	
	echo $content;

	if( $roster->output['show_footer'] )
	{
		include_once (ROSTER_BASE . 'footer.php');
	}
}
else
{
	roster_die(sprintf($roster->locale->act['addon_disabled'], $addon['basename']), $roster->locale->act['addon_error']);
}

$roster->db->close_db();