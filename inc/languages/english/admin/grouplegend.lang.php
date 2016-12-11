<?php

/**
 * Codename	: Usergroup Legend
 * Version	: 1.1
 * Copyright 2016 MyBB Romania Community. All rights reserved.
 *
 * Website	: https://www.mybb.ro
 * License	: Free Plugin
 */

// Generic information about current plugin.
$l['grouplegend_plugin_name'] = 'Usergroup Legend';
$l['grouplegend_plugin_desc'] = 'This mod will display the membergroups color legend under the list of online members.';

// All plugin settings.
$l['grouplegend_setting_enabled'] = 'Plugin Enabled?';
$l['grouplegend_setting_enabled_desc'] = 'Do you want to make this plugin enabled? (Default: Yes)';
$l['grouplegend_setting_only_groups'] = 'Only Usergroups';
$l['grouplegend_setting_only_groups_desc'] = 'Filters which usergroups will be diplayed. (Default: All groups)';
$l['grouplegend_setting_only_team'] = 'Only Forum Team';
$l['grouplegend_setting_only_team_desc'] = 'Only usergroups part of the forum team are displayed? (Default: No)';
$l['grouplegend_setting_sort_groups'] = "Sort Usergroups By";
$l['grouplegend_setting_sort_groups_desc'] = "Usergroups can be ascending sorted by multiple database fields. (Default: Group ID)";
$l['grouplegend_setting_element_style'] = 'Usergroup Style';
$l['grouplegend_setting_element_style_desc'] = 'Gives the ability to customize how a usergroup appears on the list. If the input does not contains "{usergroup}" substring then the value specified is interpreted and used as separator, otherwise the customization is applied for each usergroup found. (Default: "[{usergroup}]")';
$l['grouplegend_setting_tooltip_style'] = 'Tooltip Style';
$l['grouplegend_setting_tooltip_style_desc'] = 'Gives the ability to customize CSS style for leader tooltips. (Default: "backgroundColor:\'#161616\',border:\'1px dashed #333\',borderRadius:\'5px\',color:\'#fff\',padding: \'5px\'")';