<?php
 /*
    ===============================================================
    @author     : Seo Mybb ;
    @date       : 11th February 2015 ;
    @version    : 1.0 ;
    @mybb       : MyBB 1.8.4 ;
    @description: Optimize your web page for search engines crawlers by adding meta-tags.
    @copyright  : Seo Mybb based on http://mods.mybb.com/view/meta-tag-plugin by Seo Mybb (www.seo-mybb.blogspot.com)
	@homepage	: http://seo-mybb.blogspot.com
    ===============================================================
    @change log :
    ===============================================================
*/

if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$plugins->add_hook("index_start", "metag_index_start");

function metag_info()
{	
	return array(
		"name" 		  		=> "MetaTags Plugin",
		
		"description" 		=> "Set custom meta tags for your page (useful for search engines crawlers ie. google, yahoo, etc.) <br />Updated&improved version of <a 		  href=\"http://mods.mybb.com/view/meta-tag-plugin\">Meta - Tag Plugin / Meta - Tag Seo Mybb</a> for MyBB 1.8.4",
		
		"website" 	  		=> "http://seo-mybb.blogspot.com",
		
		"author" 			=> "Seo Mybb",
		"authorsite"  		=> "http://seo-mybb.blogspot.com",
		
		"version"		    => "1.0",
		
		"compatibility" 	=> "18*",
		
		"guid"        		=> '01049968ab2b6c32ca4fb183bf251591'
	);
}

function metag_install()
{
    global $settings, $mybb, $db;
	
if($db->field_exists("metagsis", "users"))
	{
	$db->write_query("ALTER TABLE ".TABLE_PREFIX."users DROP metagsis"); 
	}
	
    $settings_group = array(
        'gid'          => 'NULL',
        'name'         => 'metag',
        'title'        => 'MetaTags Plugin Settings',
        'description'  => 'Populate your meta tags.',
        'disporder'    => '2',
        'isdefault'    => 'no'
    );
    $db->insert_query('settinggroups', $settings_group);
    $gid = $db->insert_id();
	
    $setting = array(
        'sid'          => 'NULL',
        'name'         => 'desc',
        'title'        => 'Write your Description',
        'description'  => 'Please write your description.',
        'optionscode'  => 'textarea',
        'value'        => 'Goes here',
        'disporder'    => '1',
        'gid'          => intval( $gid )
    );
    $db->insert_query( 'settings', $setting );
	
	    $setting = array(
        'sid'          => 'NULL',
        'name'         => 'key',
        'title'        => 'Write your Keywords',
        'description'  => 'Please write your keywords.',
        'optionscode'  => 'textarea',
        'value'        => 'Goes here',
        'disporder'    => '1',
        'gid'          => intval( $gid )
    );
    $db->insert_query( 'settings', $setting );
	
	    $setting = array(
        'sid'          => 'NULL',
        'name'         => 'robots',
        'title'        => 'Robots META',
        'description'  => 'Please write your ROBOTS options.',
        'optionscode'  => 'textarea',
        'value'        => 'index, follow',
        'disporder'    => '1',
        'gid'          => intval( $gid )
    );
    $db->insert_query( 'settings', $setting );
	
	    $setting = array(
        'sid'          => 'NULL',
        'name'         => 'author',
        'title'        => 'Website Author',
        'description'  => 'Please write website copyright/author data.',
        'optionscode'  => 'textarea',
        'value'        => 'Goes here',
        'disporder'    => '1',
        'gid'          => intval( $gid )
    );
    $db->insert_query( 'settings', $setting );
	
	
	    $setting = array(
        'sid'          => 'NULL',
        'name'         => 'google_ver',
        'title'        => 'Google Website Verification Code',
        'description'  => 'Insert your Google Website verification code (if any)',
        'optionscode'  => 'textarea',
        'value'        => 'Goes here',
        'disporder'    => '1',
        'gid'          => intval( $gid )
    );
    $db->insert_query( 'settings', $setting );

	$db->write_query("ALTER TABLE ".TABLE_PREFIX."users ADD metagsis int NOT NULL default 0");

	rebuild_settings();
	
	$insertarray = array(
		"title" => "metag",
		"template" => "<meta name=\"description\" content=\"{\$mybb->settings[\'desc\']}\" /\>
<meta name=\"keywords\" content=\"{\$mybb->settings[\'key\']}\" />
<meta name=\"google-site-verification\" content=\"{\$mybb->settings[\'google_ver\']}\" />
<meta name=\"robots\" content=\"{\$mybb->settings[\'robots\']}\" />
<meta name=\"author\" content=\"{\$mybb->settings[\'author\']}\" />

",
		"sid" => -1,
		"dateline" => TIME_NOW
	);
	
	$db->insert_query("templates", $insertarray);
}

function metag_is_installed()
{
	global $db;
	
	if($db->field_exists("metagsis", "users"))
	{
		return true;
	}
	
	return false;
}

function metag_activate()
{
	global $db;
	
	include MYBB_ROOT."/inc/adminfunctions_templates.php";
	
	find_replace_templatesets("index", "#".preg_quote("{\$headerinclude}")."#i", "{\$headerinclude}\r\n{\$metag}");
}

function metag_deactivate()
{
	global $db;
	
	include MYBB_ROOT."/inc/adminfunctions_templates.php";
	
	find_replace_templatesets("index", "#".preg_quote("\r\n{\$metag}")."#i", "", 0);
}

function metag_uninstall()
{
	global $db;
	
	if($db->field_exists("metagsis", "users"))
	{
		$db->write_query("ALTER TABLE ".TABLE_PREFIX."users DROP metagsis"); 
	}
	$db->query("DELETE FROM ".TABLE_PREFIX."settinggroups WHERE name='metag'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='desc'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='key'");	
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='author'");	
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='robots'");	
		$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='google_ver'");	
	rebuild_settings();
	
	$db->delete_query("templates", "title = 'metag'");
}

function metag_index_start()
{
	global $db, $mybb, $templates, $metag;
	
	eval("\$metag = \"".$templates->get("metag")."\";");
}
?>