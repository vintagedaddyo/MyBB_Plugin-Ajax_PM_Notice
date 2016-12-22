<?php
/*
 * MyBB: Ajax PM Notice
 *
 * File: ajaxpmnotice.php
 * 
 * Authors: Sebastian Wunderlich & Vintagedaddyo
 *
 * MyBB Version: 1.8
 *
 * Plugin Version: 1.8.2
 * 
 */

// Disallow direct access to this file for security reasons

if(!defined("IN_MYBB"))
{
    die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$plugins->add_hook('pre_output_page','ajaxpmnotice');
$plugins->add_hook('ajaxpmnotice_start','ajaxpmnotice_pm');

function ajaxpmnotice_info()
{
   global $lang;

    $lang->load("ajaxpmnotice");
    
    $lang->ajaxpmnotice_Desc = '<form action="https://www.paypal.com/cgi-bin/webscr" method="post" style="float:right;">' .
        '<input type="hidden" name="cmd" value="_s-xclick">' . 
        '<input type="hidden" name="hosted_button_id" value="AZE6ZNZPBPVUL">' .
        '<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">' .
        '<img alt="" border="0" src="https://www.paypalobjects.com/pl_PL/i/scr/pixel.gif" width="1" height="1">' .
        '</form>' . $lang->ajaxpmnotice_Desc;

    return Array(
        'name' => $lang->ajaxpmnotice_Name,
        'description' => $lang->ajaxpmnotice_Desc,
        'website' => $lang->ajaxpmnotice_Web,
        'author' => $lang->ajaxpmnotice_Auth,
        'authorsite' => $lang->ajaxpmnotice_AuthSite,
        'version' => $lang->ajaxpmnotice_Ver,
        'compatibility' => $lang->ajaxpmnotice_Compat,
        'codename' => $lang->ajaxpmnotice_Codename
    );
}

function ajaxpmnotice_activate()
{
	global $db;
	$info=ajaxpmnotice_info();
	$setting_group_array=array
	(
		'name'=>$info['codename'],
		'title'=>$info['name'],
		'description'=>'Here you can edit '.$info['name'].' settings.',
		'disporder'=>1,
		'isdefault'=>0
	);
	$db->insert_query('settinggroups',$setting_group_array);
	$group=$db->insert_id();
	$settings=array
	(
		'ajaxpmnotice_refresh'=>array
		(
			'Refresh interval',
			'Set the refresh interval (in milliseconds).',
			'text',
			20000
		)
	);
	$i=1;
	foreach($settings as $name=>$sinfo)
	{
		$insert_array=array
		(
			'name'=>$name,
			'title'=>$db->escape_string($sinfo[0]),
			'description'=>$db->escape_string($sinfo[1]),
			'optionscode'=>$db->escape_string($sinfo[2]),
			'value'=>$db->escape_string($sinfo[3]),
			'gid'=>$group,
			'disporder'=>$i,
			'isdefault'=>0
		);
		$db->insert_query('settings',$insert_array);
		$i++;
	}
	rebuild_settings();
}

function ajaxpmnotice_deactivate()
{
	global $db;
	$info=ajaxpmnotice_info();
	$result=$db->simple_select('settinggroups','gid','name="'.$info['codename'].'"',array('limit'=>1));
	$group=$db->fetch_array($result);
	if(!empty($group['gid']))
	{
		$db->delete_query('settinggroups','gid="'.$group['gid'].'"');
		$db->delete_query('settings','gid="'.$group['gid'].'"');
		rebuild_settings();
	}
}

function ajaxpmnotice(&$page)
{
	global $mybb;
	if($mybb->user['pmnotice']>0&&$mybb->settings['enablepms']!=0&&$mybb->usergroup['canusepms']!=0&&$mybb->usergroup['canview']!=0)
	{
		$page=str_replace('</head>','<script type="text/javascript">
<!--
function ajaxpmnotice()
{	
new $.ajax({url:\''.$mybb->settings['bburl'].'/pm.php\',type:\'get\',success:function(result){
    $("#ajaxpmnotice").html(result);
  }});
}
ajaxpmnotice();
setInterval("ajaxpmnotice()",'.$mybb->settings['ajaxpmnotice_refresh'].');
// -->
</script>
</head>',$page);
		$page=preg_replace('#<!-- start: global_pm_alert -->(.*)<!-- end: global_pm_alert -->#Usi',"<!-- start: global_pm_alert --><noscript>$1</noscript><!-- end: global_pm_alert -->",$page);
		$page=str_replace('</body>','<div id="ajaxpmnotice"></div></body>',$page);
		return $page;
	}
}

function ajaxpmnotice_pm()
{
	global $mybb,$lang;
	header('Cache-Control: no-cache, must-revalidate');
	header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
	header('Content-Type: text/html; charset='.$lang->settings['charset']);
	if($mybb->user['pms_unread']==1)
	{
		define('AJAXPMNOTICE',substr($lang->newpm_notice_one,0,strpos($lang->newpm_notice_one,'</strong>')+9));
	}
	if($mybb->user['pms_unread']>1)
	{
		define('AJAXPMNOTICE',$lang->sprintf(substr($lang->newpm_notice_multiple,0,strpos($lang->newpm_notice_multiple,'</strong>')+9),$mybb->user['pms_unread']));
	}
}

?>