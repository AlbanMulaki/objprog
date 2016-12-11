<?php
/********************************************************************************
*
*  Chrome Extension Plugin (/inc/plugins/ChromeExtension.php)
*  Author: Technologx
*  Copyright: © 2015 Technologx
*  
*  Website: http://technologx.fulba.com
*  License: license.txt
*  Any codes in this plugin are copyrighted and not allowed to be reproduced.
* 
*  Allows you to add a Chrome Extension Link on your website.
*
********************************************************************************/


if(!defined("IN_MYBB"))
    die("This file cannot be accessed directly.");

// add hooks
$plugins->add_hook("global_start", "ChromeExtension_run");

function ChromeExtension_info()
{
    return array(
        "name"          => "Chrome Extension",
        "description"   => "Allows you to add a Chrome Extension Link on your website.",
        "website"       => "http://technologx.fulba.com",
        "author"        => "Technologx",
        "authorsite"    => "http://technologx.fulba.com",
        "version"       => "1.0",
        "guid"            => "",
        "compatibility"    => "*"
    );
}


function ChromeExtension_activate()
{
    global $db;
    $insertarray = array(
        'name'            => 'ChromeExtension', 
        'title'         => 'Chrome Extension', 
        'description'     => "Settings for Chrome Extension", 
        'disporder'     => 1, 
        'isdefault'     => 0
    );
    $gid = $db->insert_query("settinggroups", $insertarray);
    // add settings

    $furl = array(
        "sid"            => NULL,
        "name"           => "ChromeExtensionURL",
        "title"          => "URL",
        "description"    => "Enter the Chrome Extension URL here.",
        "optionscode"    => "text",
        "value"          => "",
        "disporder"      => 1,
        "gid"            => intval($gid)
    );

    $db->insert_query("settings", $furl);
    rebuild_settings();

    require_once MYBB_ROOT."inc/adminfunctions_templates.php";
    find_replace_templatesets("header", "#".preg_quote('{$pending_joinrequests}')."#i", "{\$pending_joinrequests}{\$cextension}");

}

function ChromeExtension_deactivate()
{
    global $db;

    require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
    find_replace_templatesets("header", "#".preg_quote('{$cextension}')."#i", '', 0);

    $db->delete_query('settings', "name IN ('ChromeExtensionURL')");
    $db->delete_query('settinggroups', "name = 'ChromeExtension'");

    rebuild_settings();
}

function ChromeExtension_run()
{
    global $mybb, $templates,  $cextension;
    $cextension = '';
    if(!empty($mybb->settings['ChromeExtensionURL']))
    {
       $cextension = '<br /><div align="left"><a href="'.$mybb->settings['ChromeExtensionURL'].'" target="_blank"><img src="'.$mybb->settings['bburl'].'/images/chrome.png" alt="chrome.png" />Add our extension</a></div><br />';
    }   
} 