<?php
/**
 * WoWRoster.net WoWRoster
 *
 *
 * @copyright  2002-2011 WoWRoster.net
 * @license    http://www.gnu.org/licenses/gpl.html   Licensed under the GNU General Public License v3. * @package    MembersList
 */

if ( !defined('IN_ROSTER') )
{
	exit('Detected invalid access to this file!');
}

include_once ($addon['inc_dir'] . 'memberslist.php');

$memberlist = new memberslist(array('group_alts'=>-1, 'page_size'=>25));

$mainQuery =
	'SELECT `members`.*, `guild`.`guild_name`, DATE_FORMAT( `members`.`update_time`, "' . $roster->locale->act['timeformat'] . '" ) AS date, '.
	"IF( `members`.`note` IS NULL OR `members`.`note` = '', 1, 0 ) AS 'nisnull', ".
	'UNIX_TIMESTAMP(`members`.`update_time`) AS date_stamp '.
	'FROM `'.$roster->db->table('memberlog').'` AS members '.
	'LEFT JOIN `'.$roster->db->table('alts',$addon['basename']).'` AS alts ON `members`.`member_id` = `alts`.`member_id` '.
	'LEFT JOIN `'.$roster->db->table('guild').'` AS guild ON `members`.`guild_id` = `guild`.`guild_id` ';
$where[] = '`members`.`server` = "'.$roster->db->escape($roster->data['server']).'"';
$order_last[] = '`date_stamp` DESC';

$FIELD['name'] = array(
	'lang_field' => 'name',
	'order'      => array( '`name` ASC' ),
	'order_d'    => array( '`name` DESC' ),
	'display'    => 3,
);

$FIELD['class'] = array(
	'lang_field' => 'class',
	'order'      => array( '`class` ASC' ),
	'order_d'    => array( '`class` DESC' ),
	'value'      => array($memberlist,'class_value'),
	'display'    => $addon['config']['log_class'],
);

$FIELD['level'] = array(
	'lang_field' => 'level',
	'order_d'    => array( '`level` DESC' ),
	'order_d'    => array( '`level` ASC' ),
	'value'      => array($memberlist,'level_value'),
	'display'    => $addon['config']['log_level'],
);

$FIELD['guild_name'] = array (
	'lang_field' => 'guild',
	'order'      => array( '`guild`.`guild_name` ASC' ),
	'order_d'    => array( '`guild`.`guild_name` DESC' ),
	'display'    => 2,
);

$FIELD['guild_title'] = array (
	'lang_field' => 'title',
	'order'      => array( '`guild_rank` ASC' ),
	'order_d'    => array( '`guild_rank` DESC' ),
	'display'    => $addon['config']['log_gtitle'],
);

$FIELD['type'] = array (
	'lang_field' => 'type',
	'order'      => array( '`type` ASC' ),
	'order_d'    => array( '`type` DESC' ),
	'value'      => 'type_value',
	'display'    => $addon['config']['log_type'],
);

$FIELD['date'] = array (
	'lang_field' => 'date',
	'order'      => array( '`date_stamp` DESC' ),
	'order_d'    => array( '`date_stamp` ASC' ),
	'display'    => $addon['config']['log_date'],
);

$FIELD['note'] = array (
	'lang_field' => 'note',
	'order'      => array( 'nisnull','`note` ASC' ),
	'order_d'    => array( 'nisnull','`note` DESC' ),
	'value'      => 'note_value',
	'display'    => $addon['config']['log_note'],
);

$FIELD['officer_note'] = array (
	'lang_field' => 'onote',
	'order'      => array( 'onisnull','`note` ASC' ),
	'order_d'    => array( 'onisnull','`note` DESC' ),
	'value'      => 'note_value',
	'display'    => $addon['config']['log_onote'],
);

$memberlist->prepareData($mainQuery, $where, null, null, $order_last, $FIELD, 'memberslist');

// Start output
echo $memberlist->makeMembersList('syellow');


/**
 * Controls Output of a Note Column
 *
 * @param array $row - of character data
 * @return string - Formatted output
 */
function note_value ( $row, $field )
{
	global $roster, $addon;

	if( !empty($row[$field]) )
	{
		$note = htmlspecialchars(nl2br($row[$field]));

		if( $addon['config']['compress_note'] )
		{
			$note = '<img src="'.$roster->config['theme_path'].'/images/note.gif" style="cursor:help;" '.makeOverlib($note,$roster->locale->act['note'],'',1,'',',WRAP').' alt="[]" />';
		}
		else
		{
			$value = $note;
		}
	}
	else
	{
		$note = '&nbsp;';
		if( $addon['config']['compress_note'] )
		{
			$note = '<img src="'.$roster->config['theme_path'].'/images/no_note.gif" alt="[]" />';
		}
		else
		{
			$value = $note;
		}
	}

	return '<div style="display:none;">'.$row['note'].'</div>'.$note;
}


/**
 * Controls Output of a Type Column
 *
 * @param array $row - of character data
 * @return string - Formatted output
 */
function type_value ( $row, $field )
{
	global $roster, $addon;

	if( $row['type'] == 0 )
	{
		$return = '<span class="red">' . $roster->locale->act['removed'] . '</span>';
	}
	else
	{
		$return = '<span class="green">' . $roster->locale->act['added'] . '</span>';
	}

	return '<div style="display:none;">'.$row['type'].'</div>'.$return;
}
