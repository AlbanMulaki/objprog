<?php

/**
 * Codename	: Usergroup Legend
 * Version	: 1.1
 * Copyright 2016 MyBB Romania Community. All rights reserved.
 *
 * Website	: https://www.mybb.ro
 * License	: Free Plugin
 */

// Make sure we can't access this file directly from the browser.
if(!defined('IN_MYBB'))
{
	die('This file cannot be accessed directly.');
}

// Cache templates
if(defined('THIS_SCRIPT'))
{
    global $templatelist;

    if(isset($templatelist))
    {
        $templatelist .= ',';
    }

	if(THIS_SCRIPT== 'index.php')
	{
		$templatelist .= 'grouplegend_template, grouplegend_element';
	}
}

if(!defined('IN_ADMINCP'))
{
	// Add our grouplegend_show() function to the index_start hook so when that hook is run our function is executed
	$plugins->add_hook('index_start', 'grouplegend_show');
}

function grouplegend_info()
{
	global $lang;
	$lang->load('grouplegend');
	
	/*
	 * Array of information about the plugin.
	 * name: The name of the plugin
	 * description: Description of what the plugin does
	 * website: The website the plugin is maintained at (Optional)
	 * author: The name of the author of the plugin
	 * authorsite: The URL to the website of the author (Optional)
	 * version: The version number of the plugin
	 * compatibility: A CSV list of MyBB versions supported. Ex, '121,123', '12*'. Wildcards supported.
	 * codename: An unique code name to be used by updated from the official MyBB Mods community.
	 */
	return array(
		'name' => $lang->grouplegend_plugin_name,
		'description'	=> $lang->grouplegend_plugin_desc,
		'website'	=> 'https://mybb.ro',
		'author' => 'MyBB Romania',
		'authorsite' => 'http://mybb.ro',
		'version'	=> '1.2',
		'compatibility'	=> '18*',
		'codename' => 'grouplegend'
	);
}

/*
 * _activate():
 *    Called whenever a plugin is activated via the Admin CP. This should essentially make a plugin
 *    'visible' by adding templates/template changes, language changes etc.
 */
function grouplegend_activate()
{
	global $lang, $db;
	$lang->load('grouplegend');
	
		// Add a new template (hello_index) to our global templates (sid = -1)
	$templatearray = array(
	'template' => '<script type="text/javascript">$(document).ready(function(){ $(".groupTooltip").hover(function(){var l=$(this).attr("leaders");$(this).data("tipText",l).removeAttr("leaders");$("<p class=\'glTooltip\'></p>").html(l).appendTo("body").fadeIn("slow");},function(){ $(this).attr("leaders",$(this).data("tipText"));$(".glTooltip").remove();}).mousemove(function(e){var x=e.pageX+10,y=e.pageY;$(".glTooltip").css({top:y,left:x,position:\'absolute\',$grouplegend_style})});});</script><tr><td class="trow1" style="text-align:center"><span class="smalltext">$grouplegend_content</span></td></tr>',
	'element' => '<span leaders="$grouplegend_leaders" class="groupTooltip">$grouplegend_element</span>'
	);

	$group = array(
		'prefix' => 'grouplegend',
		'title' => $db->escape_string($lang->grouplegend_plugin_name)
	);

	// Update or create template group:
	$query = $db->simple_select('templategroups', 'prefix', "prefix='{$group['prefix']}'");

	if($db->fetch_field($query, 'prefix'))
	{
		$db->update_query('templategroups', $group, "prefix='{$group['prefix']}'");
	}
	else
	{
		$db->insert_query('templategroups', $group);
	}

	// Query already existing templates.
	$query = $db->simple_select('templates', 'tid,title,template', "sid=-2 AND (title='{$group['prefix']}' OR title LIKE '{$group['prefix']}=_%' ESCAPE '=')");

	$templates = $duplicates = array();

	while($row = $db->fetch_array($query))
	{
		$title = $row['title'];
		$row['tid'] = (int)$row['tid'];

		if(isset($templates[$title]))
		{
			// PluginLibrary had a bug that caused duplicated templates.
			$duplicates[] = $row['tid'];
			$templates[$title]['template'] = false; // force update later
		}
		else
		{
			$templates[$title] = $row;
		}
	}

	// Delete duplicated master templates, if they exist.
	if($duplicates)
	{
		$db->delete_query('templates', 'tid IN ('.implode(",", $duplicates).')');
	}

	// Update or create templates.
	foreach($templatearray as $name => $code)
	{
		if(strlen($name))
		{
			$name = "grouplegend_{$name}";
		}
		else
		{
			$name = "grouplegend";
		}

		$template = array(
			'title' => $db->escape_string($name),
			'template' => $db->escape_string($code),
			'version' => 1,
			'sid' => -2,
			'dateline' => TIME_NOW
		);

		// Update
		if(isset($templates[$name]))
		{
			if($templates[$name]['template'] !== $code)
			{
				// Update version for custom templates if present
				$db->update_query('templates', array('version' => 0), "title='{$template['title']}'");

				// Update master template
				$db->update_query('templates', $template, "tid={$templates[$name]['tid']}");
			}
		}
		// Create
		else
		{
			$db->insert_query('templates', $template);
		}

		// Remove this template from the earlier queried list.
		unset($templates[$name]);
	}

	// Remove no longer used templates.
	foreach($templates as $name => $row)
	{
		$db->delete_query('templates', "title='{$db->escape_string($name)}'");
	}
	
	// Settings group array details
	$group = array(
		'name' => 'grouplegend',
		'title' => $db->escape_string($lang->grouplegend_plugin_name),
		'description' => $db->escape_string($lang->grouplegend_plugin_desc),
		'isdefault' => 0
	);
	
	// Check if the group already exists.
	$query = $db->simple_select('settinggroups', 'gid', "name='grouplegend'");

	if($gid = (int)$db->fetch_field($query, 'gid'))
	{
		// We already have a group. Update title and description.
		$db->update_query('settinggroups', $group, "gid='{$gid}'");
	}
	else
	{
		// We don't have a group. Create one with proper disporder.
		$query = $db->simple_select('settinggroups', 'MAX(disporder) AS disporder');
		$disporder = (int)$db->fetch_field($query, 'disporder');

		$group['disporder'] = ++$disporder;

		$gid = (int)$db->insert_query('settinggroups', $group);
	}

	// Deprecate all the old entries.
	$db->update_query('settings', array('description' => 'grouplegend'), "gid='{$gid}'");

	// add settings
	$settings = array(
	'enabled'	=> array(
		'optionscode'	=> 'yesno',
		'value' => 1
	),
	'only_groups'	=> array(
		'optionscode'	=> 'groupselect',
		'value' => -1
	),
	'only_team'	=> array(
		'optionscode'	=> 'yesno',
		'value' => 0
	),
	'sort_groups' => array(
		'optionscode' => 'select
gid=Group ID
title=Group Title
disporder=Group Display Order',
		'value' => 'gid'
	),
	'element_style'	=> array(
		'optionscode'	=> 'text',
		'value'	=> "[{usergroup}]"
	),
	'tooltip_style'	=> array(
		'optionscode'	=> 'text',
		'value'	=> "backgroundColor:'#161616',border:'1px dashed #333',borderRadius:'5px',color:'#fff',padding: '5px'"
	));

	$disporder = 0;

	// Create and/or update settings.
	foreach($settings as $key => $setting)
	{
		// Prefix all keys with group name.
		$prefix = "grouplegend";

		$lang_var_title = "{$prefix}_setting_{$key}";
		$lang_var_description = "{$prefix}_setting_{$key}_desc";

		$setting['title'] = $lang->{$lang_var_title};
		$setting['description'] = $lang->{$lang_var_description};

		// Filter valid entries.
		$setting = array_intersect_key($setting,
			array(
				'title' => 0,
				'description' => 0,
				'optionscode' => 0,
				'value' => 0,
		));

		// Escape input values.
		$setting = array_map(array($db, 'escape_string'), $setting);

		// Add missing default values.
		++$disporder;

		$setting = array_merge(
			array('description' => '',
				'optionscode' => 'yesno',
				'value' => 0,
				'disporder' => $disporder),
		$setting);

		$setting['name'] = "grouplegend_{$db->escape_string($key)}";
		$setting['gid'] = $gid;

		// Check if the setting already exists.
		$query = $db->simple_select('settings', 'sid', "gid='{$gid}' AND name='{$setting['name']}'");

		if($sid = $db->fetch_field($query, 'sid'))
		{
			// It exists, update it, but keep value intact.
			unset($setting['value']);
			$db->update_query('settings', $setting, "sid='{$sid}'");
		}
		else
		{
			// It doesn't exist, create it.
			$db->insert_query('settings', $setting);
		}
	}

	// Delete deprecated entries.
	$db->delete_query('settings', "gid='{$gid}' AND description='grouplegend'");
	
	// This is required so it updates the settings.php file as well and not only the database - they must be synchronized!
	rebuild_settings();
	
	// Include this file because it is where find_replace_templatesets is defined
	require_once MYBB_ROOT.'inc/adminfunctions_templates.php';
	
	// Edit the index template and add our variable to above {$forums}
	find_replace_templatesets('index_boardstats', '#'.preg_quote('{$whosonline}').'#', "{\$whosonline}{\$grouplegend}");
}

/*
 * _deactivate():
 *    Called whenever a plugin is deactivated. This should essentially 'hide' the plugin from view
 *    by removing templates/template changes etc. It should not, however, remove any information
 *    such as tables, fields etc - that should be handled by an _uninstall routine. When a plugin is
 *    uninstalled, this routine will also be called before _uninstall() if the plugin is active.
 */
function grouplegend_deactivate()
{
	global $cache;
	
	// Delete all caches added by us
	$cache->delete('grouplegend_leaders');
	
	require_once MYBB_ROOT.'inc/adminfunctions_templates.php';
	
	// Remove template edits
	find_replace_templatesets('index_boardstats', '#'.preg_quote('{$grouplegend}').'#', '');
}

/*
 * _install():
 *   Called whenever a plugin is installed by clicking the 'Install' button in the plugin manager.
 *   If no install routine exists, the install button is not shown and it assumed any work will be
 *   performed in the _activate() routine.
 */
function grouplegend_install()
{
	// Nothing to do.
}

/*
 * _is_installed():
 *   Called on the plugin management page to establish if a plugin is already installed or not.
 *   This should return TRUE if the plugin is installed (by checking tables, fields etc) or FALSE
 *   if the plugin is not installed.
*/
function grouplegend_is_installed()
{
	global $db;

	// Check if the group already exists.
	$query = $db->simple_select('settinggroups', 'gid', "name='grouplegend'");

	return $db->fetch_field($query, 'gid');
}

/*
 * _uninstall():
 *    Called whenever a plugin is to be uninstalled. This should remove ALL traces of the plugin
 *    from the installation (tables etc). If it does not exist, uninstall button is not shown.
 */
function grouplegend_uninstall()
{
	global $db;
	
	// Remove our templates group
	// Query the template groups
	$query = $db->simple_select('templategroups', 'prefix', "prefix='grouplegend'");

	// Build where string for templates
	$sqlwhere = array();

	while($prefix = $db->fetch_field($query, 'prefix'))
	{
		$tprefix = $db->escape_string($prefix);
		$sqlwhere[] = "title='{$tprefix}' OR title LIKE '{$tprefix}=_%' ESCAPE '='";
	}

	if($sqlwhere) // else there are no groups to delete
	{
		// Delete template groups.
		$db->delete_query('templategroups', "prefix='grouplegend'");

		// Delete templates belonging to template groups.
		$db->delete_query('templates', implode(' OR ', $sqlwhere));
	}
	
	// Delete settings group
	$db->delete_query('settinggroups', "name='grouplegend'");

	// Remove settings
	$db->delete_query('settings', "name LIKE 'grouplegend_%'");

	// This is required so it updates the settings.php file as well and not only the database - they must be synchronized!
	rebuild_settings();
}

/*
 * Callback function used to compare two usergroups in order to sort them.
 * The sort criteria is defined as a setting.
 */
function grouplegend_compare($group1, $group2)
{
	global $mybb;
	
	$id = $mybb->settings['grouplegend_sort_groups'];
	$value1 = $group1[$id];
	$value2 = $group2[$id];
	
	if ($id == 'title')
	{
  	return strcmp($value1, $value2);
	}
	else
	{
		return (int)$value1 - (int)$value2;
	}
}

/*
 * Constructs an advanced table of leaders for all existing groups.
 * This table will be saved in cache and it will be updated every hour.
 */
function grouplegend_leaders()
{
	global $db, $cache;
	
	$leaders_cache = $cache->read('grouplegend_leaders');
	
  // Groups cache is invalid. We need to regenerate this information.
  if(!is_array($leaders_cache) || (int)$leaders_cache['timestamp'] < TIME_NOW - 3600) {
		$leaders_cache = array();
		
		// Obtain list of leaders for each group.
		$leaders = $cache->read('groupleaders');
				
		if(is_array($leaders) && !empty($leaders))
		{
			// Obtain more information about users from database.
			// Construct a simple user database.
			$database = array();
			$query = $db->simple_select('users', 'uid,username', 'uid IN ('.implode(',', array_keys($leaders)).')');
			while($row = $db->fetch_array($query))
			{
				$database[$row['uid']] = $row['username'];
			}
			
			// Iterate over all leaders and add additional information about them.
			foreach($leaders as $leader)
			{
				foreach ($leader as $key => $value)
				{
					$uid = $value['uid'];
					$leaders_cache['info'][$value['gid']][$uid] = $database[$uid];
				}
			}
		}
		
		$leaders_cache['timestamp'] = TIME_NOW;		
		$cache->update('grouplegend_leaders', $leaders_cache);
	}
	
	return $leaders_cache['info'];	
}

/*
 * Shows a user group legend near board statistics section.
 */
function grouplegend_show()
{
	global $mybb;
	
	// Defines groups allowed to be visible for users.
	$allowed_groups = $mybb->settings['grouplegend_only_groups'];

	// Only run this function is the setting is set to yes
	if($mybb->settings['grouplegend_enabled'] == 0 || empty($allowed_groups))
	{
		return;
	}
	
	global $cache, $grouplegend, $lang, $templates;
	$lang->load('grouplegend');
	
	$groups_cache = $cache->read('usergroups');
  // Groups cache is invalid. We need to regenerate this information.
  if(!is_array($groups_cache)) {
		$cache->update_usergroups();
		$groups_cache = $cache->read('usergroups');
	}
	
	$grouplegend_style = strip_tags($mybb->settings['grouplegend_tooltip_style']);
	
	// Obtain all leaders for current usergroup configurations.
	$leaders = grouplegend_leaders();
	
	// Filter some usergroups accordingly to user input
	if($allowed_groups !== '-1')
	{
		$groups_cache = array_intersect_key($groups_cache, array_fill_keys(explode(',', $allowed_groups), ''));
	}
	
	// Sort an array with a user-defined comparison function and maintain index association
	uasort($groups_cache, 'grouplegend_compare');
	
	$content = array();
	
	// Is only forum team setting activated?
	$only_team = $mybb->settings['grouplegend_only_team'];
	
	// For each group found
  foreach($groups_cache as $group)
  {
		if($only_team == 1 && $group['showforumteam'] == 0)
		{
			continue;
		}
		
		$format = $group['namestyle'];
		if(strpos($format, "{username}") === false)
		{
			$format = "{username}";
		}
		$format = stripslashes($format);
		$grouplegend_element = str_replace("{username}", $group['title'], $format);
		
		$group_leaders = $leaders[$group['gid']];
		$final_leaders = array();
		if (isset($group_leaders))
		{
			foreach($group_leaders as $leader_uid => $leader_username)
			{
				$final_leaders[] = $leader_username;
			}
		}
		if(empty($final_leaders))
		{
			$grouplegend_leaders = $lang->grouplegend_no_leaders;
		}
		else
		{
			$grouplegend_leaders = $lang->sprintf($lang->grouplegend_leaders, implode(',', $final_leaders));
		}
		
		$content[] = eval($templates->render('grouplegend_element'));
  }
	
	$style = $mybb->settings['grouplegend_element_style'];
	if(strpos($style, '{usergroup}') !== false)
	{
		foreach($content as $key => $value)
		{
				$content[$key] = str_replace('{usergroup}', $value, $style);
		}
		
		$style = '';
	}
	
	$grouplegend_content = implode($style, $content);
	
	$grouplegend = eval($templates->render('grouplegend_template'));
}
