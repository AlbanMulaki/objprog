<?php

/*
*
*kingstatPlugin
* Copyright 2011 mostafa shirali
* http://www.kingofpersia.ir
* http://www.pctricks.ir
*
*/
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}
$plugins->add_hook('global_start','kingstat');



function kingstat_info()
{
return array(
		"name" => "kingstat",
		"description" => "This plugin show a complete statistics users and forums",
		"website" => "http://www.pctricks.ir",
		"author" => "Mostafa shirali",
		"authorsite" => "http://www.pctricks.ir",
		"version" => "1.0",
        "guid"=> "kingstat",
		"compatibility"	=> "18*"
);
}
function kingstat_activate()
{
global $mybb, $db, $templates;

   $settings_group = array(
        "name" => "kingstat",
        "title" => "kingstat",
        "description" => "setting for kingstat",
        "disporder" => "88",
        "isdefault" => "0",
        );

		    $db->insert_query("settinggroups", $settings_group);
    $gid = $db->insert_id();
	
	$setting_1 = array("sid" => "","name" => "kingstatenable","title" => "Active","description" => "Do you want the plugin is enabled?","optionscode" => "yesno","value" => "0","disporder" => 1,"gid" => intval($gid),);	
$db->insert_query("settings", $setting_1);
require '../inc/adminfunctions_templates.php';

    find_replace_templatesets("header", '#<div id="container">#', '<script type="text/javascript" src="jscripts/kingstat.js"></script> <div id="container">');
	find_replace_templatesets("header","#".preg_quote('{$pm_notice}')."#i", "{\$pm_notice}\n {\$kingstat}");
	find_replace_templatesets("header","#".preg_quote('{$pm_notice}')."#i", "{\$pm_notice}\n {\$stat_box}");

    $kingstat_template = array(
		"title"		=> 'kingstat',
		"template"	=> $db->escape_string('<div id="kingstatbox" onclick="showbox()"><img src="'.$mybb->settings['bburl'].'/images/kingstat.png" style="position: fixed; left:0px; bottom: 0px;"></div>'),
		"sid"		=> "-1",
		"version"	=> "1.0",
		"dateline"	=> "1157735635",
	);
	$db->insert_query("templates", $kingstat_template);
	    $stat_box_template = array(
		"title"		=> 'stat_box',
		"template"	=> $db->escape_string('<div id="box_stat" style="background:url(images/kingstat.gif)center center no-repeat;background-size:180px 260px;display:none;position:fixed;left:40%;top:20%;width:180px;height:260px;">
		{$kingstat_box}
</div>'),
		"sid"		=> "-1",
		"version"	=> "1.0",
		"dateline"	=> "1245657012",
	);
	$db->insert_query("templates", $stat_box_template);

	
}

function kingstat()
{
global $mybb,$lang,$kingstat,$templates,$stat_box,$db;
    $lang->load("kingstat");

if($mybb->settings['kingstatenable'] == 1)
{ 
eval("\$kingstat = \"".$templates->get("kingstat")."\";");


$expire = 600;
$filename = "counter.txt";

if (file_exists($filename)) 
{
   $ignore = false;
   $current_agent = (isset($_SERVER['HTTP_USER_AGENT'])) ? addslashes(trim($_SERVER['HTTP_USER_AGENT'])) : "no agent";
   $current_time = time();
   $current_ip = $_SERVER['REMOTE_ADDR']; 
      
   // daten einlesen
   $c_file = array();
   $handle = fopen($filename, "r");
   
   if ($handle)
   {
      while (!feof($handle)) 
      {
         $line = trim(fgets($handle, 4096)); 
		 if ($line != "")
		    $c_file[] = $line;		  
      }
      fclose ($handle);
   }
   else
      $ignore = true;
   
   // bots ignorieren   
   if (substr_count($current_agent, "bot") > 0)
      $ignore = true;
	  
   
   // hat diese ip einen eintrag in den letzten expire sec gehabt, dann igornieren?
   for ($i = 1; $i < sizeof($c_file); $i++)
   {
      list($counter_ip, $counter_time) = explode("||", $c_file[$i]);
	  $counter_time = trim($counter_time);
	  
	  if ($counter_ip == $current_ip && $current_time-$expire < $counter_time)
	  {
	     // besucher wurde bereits gezنhlt, daher hier abbruch
		 $ignore = true;
		 break;
	  }
   }
   
   // counter hochzنhlen
   if ($ignore == false)
   {
      if (sizeof($c_file) == 0)
      {
	     // wenn counter leer, dann füllen      
		 $add_line1 = date("z") . ":1||" . date("W") . ":1||" . date("n") . ":1||" . date("Y") . ":1||1||1||" . $current_time . "\n";
		 $add_line2 = $current_ip . "||" . $current_time . "\n";
		 
		 // daten schreiben
		 $fp = fopen($filename,"w+");
		 if ($fp)
         {
		    flock($fp, LOCK_EX);
			fwrite($fp, $add_line1);
		    fwrite($fp, $add_line2);
			flock($fp, LOCK_UN);
		    fclose($fp);
		 }
		 
		 // werte zur verfügung stellen
		 $day = $week = $month = $year = $all = $record = 1;
		 $record_time = $current_time;
		 $online = 1;
	  }
      else
	  {
	     // counter hochzنhlen
		 list($day_arr, $week_arr, $month_arr, $year_arr, $all, $record, $record_time) = explode("||", $c_file[0]);
		 
		 // day
		 $day_data = explode(":", $day_arr);
		 $day = $day_data[1];
		 if ($day_data[0] == date("z")) $day++; else $day = 1;
		 
		 // week
		 $week_data = explode(":", $week_arr);
		 $week = $week_data[1];
		 if ($week_data[0] == date("W")) $week++; else $week = 1;
		 
		 // month
		 $month_data = explode(":", $month_arr);
		 $month = $month_data[1];
		 if ($month_data[0] == date("n")) $month++; else $month = 1;
		 
		 // year
		 $year_data = explode(":", $year_arr);
		 $year = $year_data[1];
		 if ($year_data[0] == date("Y")) $year++; else $year = 1;
		  
		 // all
		 $all++;
		 
		 // neuer record?
		 $record_time = trim($record_time);
		 if ($day > $record)
		 {
		    $record = $day;
			$record_time = $current_time;
		 }
		 
		 // speichern und aufrنumen und anzahl der online leute bestimmten
		 
		 $online = 1;
		 
		 // daten schreiben
		 $fp = fopen($filename,"w+");
		 if ($fp)
         {
		    flock($fp, LOCK_EX);
			$add_line1 = date("z") . ":" . $day . "||" . date("W") . ":" . $week . "||" . date("n") . ":" . $month . "||" . date("Y") . ":" . $year . "||" . $all . "||" . $record . "||" . $record_time . "\n";		 
		    fwrite($fp, $add_line1);
		 
		    for ($i = 1; $i < sizeof($c_file); $i++)
            {
               list($counter_ip, $counter_time) = explode("||", $c_file[$i]);
	  
	           // übernehmen
		   	   if ($current_time-$expire < $counter_time)
	           {
	              $counter_time = trim($counter_time);
				  $add_line = $counter_ip . "||" . $counter_time . "\n";
			      fwrite($fp, $add_line);
			      $online++;
	           }
            }
		    $add_line = $current_ip . "||" . $current_time . "\n";
		    fwrite($fp, $add_line);
		    flock($fp, LOCK_UN);
		    fclose($fp);
	     }
	  }
   }
   else
   {
      // nur zum anzeigen lesen
	  if (sizeof($c_file) > 0)
	     list($day_arr, $week_arr, $month_arr, $year_arr, $all, $record, $record_time) = explode("||", $c_file[0]);
	  else
		 list($day_arr, $week_arr, $month_arr, $year_arr, $all, $record, $record_time) = explode("||", date("z") . ":1||" . date("W") . ":1||" . date("n") . ":1||" . date("Y") . ":1||1||1||" . $current_time);
	  
	  // day
	  $day_data = explode(":", $day_arr);
      $day = $day_data[1];
	  
	  // week
	  $week_data = explode(":", $week_arr);
	  $week = $week_data[1];
	
	  // month
	  $month_data = explode(":", $month_arr);
	  $month = $month_data[1];
	  
	  // year
	  $year_data = explode(":", $year_arr);
	  $year = $year_data[1];
	  
	  $record_time = trim($record_time);
	  
	  $online = sizeof($c_file) - 1;
   }
   
}
	$timecut = time() - 86400;
$users_forum=$db->query("select * from ".TABLE_PREFIX."users ");
$usernum=$db->num_rows($users_forum);
$forum=$db->query("select * from ".TABLE_PREFIX."forums ");
$forumnum=$db->num_rows($forum);
$thread=$db->query("select * from ".TABLE_PREFIX."threads ");
$threadnum=$db->num_rows($thread);
$post=$db->query("select * from ".TABLE_PREFIX."posts ");
$postnum=$db->num_rows($post);
$banned=$db->query("select * from ".TABLE_PREFIX."banned ");
$bannednum=$db->num_rows($banned);
$newusers = $db->fetch_field($db->simple_select("users", "COUNT(*) AS newusers", "regdate>'$timecut'"), "newusers"); 
$banned_today = $db->fetch_field($db->simple_select("banned", "COUNT(*) AS banned_today", "dateline>'$timecut'"), "banned_today"); 
$kingstat_box='<center><table style="align:center;font-size:11px;font-weight:normal;font-family:tahoma;text-align:center;width:180px;height:240px;">
<tr><td>'.$lang->user_online.':'.$online.'</td></tr>
<tr><td>'.$lang->user_today.':'.$day.'</td></tr>
<tr><td>'.$lang->user_week.':'.$week.'</td></tr>
<tr><td>'.$lang->user_month.':'.$month.'</td></tr>
<tr><td>'.$lang->user_year.':'.$year.'</td></tr>
<tr><td>'.$lang->user_all.':'.$all.'</td></tr>
<tr><td>'.$lang->forums.':'.$forumnum.'</td></tr>
<tr><td>'.$lang->forums_thread.':'.$threadnum.'</td></tr>
<tr><td>'.$lang->forums_post.':'.$postnum.'</td></tr>
<tr><td>'.$lang->users.':'.$usernum.'</td></tr>
<tr><td>'.$lang->users_register.':'.$newusers.'</td></tr>
<tr><td>'.$lang->users_banned_today.':'.$banned_today.'</td></tr>
<tr><td>'.$lang->users_all_banned.':'.$bannednum.'</td></tr>
</table></center>';
eval("\$stat_box = \"".$templates->get("stat_box")."\";");


}


}

function kingstat_deactivate()
{
require '../inc/adminfunctions_templates.php';

global  $db;
$db->query("DELETE FROM ".TABLE_PREFIX."settinggroups WHERE name='kingstat'");
$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='kingstatenable'");
find_replace_templatesets("header", '#<script type="text/javascript" src="jscripts/kingstat.js"></script>#', '',0);
    $db->query("DELETE FROM ".TABLE_PREFIX."templates WHERE title='kingstat'");
    $db->query("DELETE FROM ".TABLE_PREFIX."templates WHERE title='stat_box'");
    find_replace_templatesets("header", '#'.preg_quote('{$kingstat}').'#', '',0);
    find_replace_templatesets("header", '#'.preg_quote('{$stat_box}').'#', '',0);

}




?>
