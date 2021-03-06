<?php
/**
 * WoWRoster.net WoWRoster
 *
 * Functions file for template
 *
 *
 * @copyright  2002-2011 WoWRoster.net
 * @license    http://www.gnu.org/licenses/gpl.html   Licensed under the GNU General Public License v3.
 * @version    SVN: $Id: theme.php 2631 2014-08-21 17:54:35Z ulminia@gmail.com $
 * @link       http://www.wowroster.net
 * @package    WoWRoster
 * @subpackage Theme
 */

if( !defined('IN_ROSTER') )
{
	exit('Detected invalid access to this file!');
}

define('R_TPL_VERSION', '2.2.0.0');

roster_add_css('templates/' . $roster->tpl->tpl . '/style/colorpicker.css', 'theme');
roster_add_css('templates/' . $roster->tpl->tpl . '/style/jquery.multiselect.css', 'theme');
roster_add_css('templates/' . $roster->tpl->tpl . '/style/droplist.css', 'theme');
//roster_add_css('templates/' . $roster->tpl->tpl . '/style/bootv3/bootstrap.css', 'theme');

/*
if ( file_exists('templates/' . $roster->tpl->tpl . '/style/bootstrap/'.$roster->config['bootstrap'].'/bootstrap.min.css') )
{
	roster_add_css('templates/' . $roster->tpl->tpl . '/style/bootstrap/'.$roster->config['bootstrap'].'/bootstrap.min.css', 'theme');
}
*/
/*
we load the ui script but dont use the style
roster_add_css('templates/' . $roster->tpl->tpl . '/style/jquery-ui.css', 'theme');
*/
roster_add_css('pages/forum/style.css', 'theme');

/*
	base has to be loaded last to override bootstrap
*/
/*
	new tooltip functions js files
*/
roster_add_js('templates/' . $roster->tpl->tpl . '/js/jquery/jquery-3.2.1.min.js', 'core');

roster_add_js('templates/' . $roster->tpl->tpl . '/js/jquery/jquery-ui.min.js', 'core');
//roster_add_js('templates/' . $roster->tpl->tpl . '/js/ui.checkbox.js', 'theme');
roster_add_js('templates/' . $roster->tpl->tpl . '/js/script.js', 'core');
roster_add_js('templates/' . $roster->tpl->tpl . '/js/tabcontent.js', 'core');
roster_add_js('templates/' . $roster->tpl->tpl . '/js/mainjs.js', 'core');
roster_add_js('templates/' . $roster->tpl->tpl . '/js/jquery.sumoselect.js', 'core');
//
roster_add_js('templates/' . $roster->tpl->tpl . '/js/apitips.js',null,'footer');
roster_add_js('templates/' . $roster->tpl->tpl . '/js/tooltip.js',null,'footer');

roster_add_js('templates/' . $roster->tpl->tpl . '/js/markitup/jquery.markitup.js', 'core');
roster_add_js('templates/' . $roster->tpl->tpl . '/js/markitup/sets/default/set.js', 'core');
roster_add_css('templates/' . $roster->tpl->tpl . '/style/markitup/skins/markitup/style.css', 'theme');
roster_add_css('templates/' . $roster->tpl->tpl . '/style/markitup/sets/default/style.css', 'theme');

if ( file_exists('templates/' . $roster->tpl->tpl . '/style/bootstrap/'.$roster->config['bootstrap'].'/'.$roster->config['bootstrap'].'.php') )
{
	include('templates/' . $roster->tpl->tpl . '/style/bootstrap/'.$roster->config['bootstrap'].'/'.$roster->config['bootstrap'].'.php');
}
roster_add_css('templates/' . $roster->tpl->tpl . '/style/overrides.css', 'theme');
roster_add_css('templates/' . $roster->tpl->tpl . '/style/base.css', 'theme');
roster_add_js('templates/' . $roster->tpl->tpl . '/theme.js');

/**
 * Starts or ends fancy bodering containers
 *
 * @param string $style What bordering style to use
 * @param string $mode ( start | end )
 * @param string $header_text Place text in a styled header
 * @param string $hwidth Set a fixed width for the box
 * @return string
 */
function border( $style , $mode , $header_text='' , $width='' )
{
	$backg_css = $style . 'border';
	if( substr($style,0,1) == 's' )
	{
		$style = 'simple';
	}
	$style .= 'border';

	if( $header_text != '' && $style != 'end' )
	{
		$header_text = "\n" . '<div class="tier-2-title">' . $header_text . "</div>\n";
	}
	else
	{
		$header_text = '';
	}

	// Dynamic Bordering
	$start = '<div class="tier-2-a' . ($backg_css != '' ? ' ' . $backg_css : '') . '" style="width:' . ( $width != '' ? $width : 'auto' ) . ';">
	<div class="tier-2-b">
' . $header_text;

	$end = "\n</div>\n</div>\n";

	switch( $mode )
	{
		case 'start':
			return $start;
			break;

		case 'end':
			return $end;
			break;
	}
}

/**
 * Draw a message box with the specified border color.
 *
 * @param string $message | The message to display inside the box
 * @param string $title | The box title
 * @param string $style | The border style
 * @param string $width | Set a fixed width
 * @return string $html | The HTML for the messagebox
 */
function messagebox( $message , $title='Message' , $style='sgray' , $width='' )
{
	return
		border($style, 'start', $title, $width)
			. '<div class="info-text-h">' . $message . '</div>'
			. border($style, 'end');
}

/**
 * Draw a 300x550px scrolling messagebox with the specified border color.
 *
 * @param string $message | The message to display inside the box
 * @param string $title | The box title
 * @param string $style | The border style
 * @param string $width | Initial width with unit
 * @param string $height | Initial height with unit
 * @return string $html | The HTML for the messagebox
 */
function scrollbox( $message , $title='Message' , $style='sgray' , $width='550px' , $height='300px' )
{
	return
		border($style, 'start', $title, $width)
			. '<div style="height:' . $height . ';width:100%;overflow:auto;">'
			. '<div class="info-text-h">' . $message . '</div>'
			. '</div>'
			. border($style, 'end');
}

/**
 * Draw a message box with the specified border color.
 *
 * @param string $message | The message to display inside the box
 * @param string $title | The box title
 * @param string $style | The border style
 * @param boolean $open | True if initially open
 * @param string $width | Initial width with unit
 * @return string $html | The HTML for the messagebox
 */
function messageboxtoggle( $message , $title='Message' , $style='sgray' , $open=false , $width='550px' )
{
	global $toggleboxes, $roster;

	$toggleboxes++;

	$title = "<div style=\"cursor:pointer;width:100%;\" onclick=\"showHide('msgbox_" . $toggleboxes . "','msgboximg_" . $toggleboxes . "','" . $roster->config['theme_path'] . "/images/button_open.png','" . $roster->config['theme_path'] . "/images/button_close.png');\">"
		. "<img src=\"" . $roster->config['theme_path'] . '/images/button_' . ($open ? 'open' : 'close') . ".png\" style=\"float:right;\" alt=\"\" id=\"msgboximg_" . $toggleboxes . "\" />" . $title . "</div>";

	return
		border($style, 'start', $title, $width)
			. '<div style="display:' . ($open ? 'inline' : 'none') . ';" id="msgbox_' . $toggleboxes . '">'
			. '<div class="info-text-h">' . $message . '</div>'
			. '</div>'
			. border($style, 'end');
}

/**
 * Draw a 300x550px scrolling messagebox with the specified border color.
 *
 * @param string $messages | The message to display inside the box
 * @param string $title | The box title
 * @param string $style | The border style
 * @param string $width | Initial width with unit
 * @param string $height | Initial height with unit
 * @return string $html | The HTML for the messagebox
 */
function scrollboxtoggle( $message , $title='Message' , $style='sgray' , $open=false , $width='550px' , $height='300px' )
{
	global $toggleboxes, $roster;

	$toggleboxes++;

	$title = "<div style=\"cursor:pointer;width:100%;\" onclick=\"showHide('msgbox_" . $toggleboxes . "','msgboximg_" . $toggleboxes . "','" . $roster->config['theme_path'] . "/images/button_open.png','" . $roster->config['theme_path'] . "/images/button_close.png');\">"
		. "<img src=\"" . $roster->config['theme_path'] . '/images/button_' . ($open ? 'open' : 'close') . ".png\" style=\"float:right;\" alt=\"\" id=\"msgboximg_" . $toggleboxes . "\" />" . $title . "</div>";

	return
		border($style,'start',$title, $width)
			. '<div style="height:' . $height . ';width:100%;overflow:auto;display:' . ($open ? 'inline' : 'none') . ';" id="msgbox_' . $toggleboxes . '">'
			. '<div class="info-text-h">' . $message . '</div>'
			. '</div>'
			. border($style, 'end');
}
