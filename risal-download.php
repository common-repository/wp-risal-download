<?php
/*
Plugin Name: Risal Download, Lars Kettermen
Plugin URI: http://linuxuserjp.wordpress.com/
Description: Filter direct download of files. Manage how your files from outside ROOT_DIRECTORY will be downloaded. Monitor who download your files.
Version: 1.6
Author: Risal Affendie
Author URI: http://linuxuserjp.wordpress.com
Tags: abuse, download, session, browser, encrypt, security, secure, apache, token, linuxuserjp, risal, token, anti-leech, captcha
*/

global $table_prefix, $wpdb, $querySettings, $myrow;
$wpdb->hide_errors();
$wpdb->risfiles = $table_prefix . 'risfiles';
$wpdb->rissettings = $table_prefix . 'rissettings';
$wpdb->ristokens = $table_prefix . 'ristokens';
$wpdb->risparam = $table_prefix . 'risparameters'; 
$installed = $wpdb->get_results("SELECT popup FROM $wpdb->risparam");
if (mysql_errno() == 1146) 
{
	//Additional table for v1.4
	$sql = "CREATE TABLE " . $wpdb->ristokens . " (
			id mediumint(9) NOT NULL,
			token VARCHAR(255) NOT NULL,
			point timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
			PRIMARY KEY  (`token`)
			);";	
	$wpdb->query($sql);
	/* Uncomment below sql to delete 'timing' column from rissettings table 
	if you like to. V1.6
	$wpdb->query("alter table $wpdb->rissettings drop column timing"); */

	$sql = "CREATE TABLE " . $wpdb->rissettings . " (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			file varchar(128) NOT NULL,
			method VARCHAR(128) NOT NULL,
			outsidepath VARCHAR(255) NOT NULL,
			timing int(255) NOT NULL default 30,
			UNIQUE KEY id (id)
			);";	
	$wpdb->query($sql);
	$sql = "CREATE TABLE " . $wpdb->risfiles . " (
			  `id` int(11) NOT NULL auto_increment,
			  `file` varchar(128) NOT NULL,
			  `ip` varchar(16) NOT NULL,
			  `path` varchar(255) NOT NULL,
			  `valid` varchar(1) NOT NULL,
			  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
			  PRIMARY KEY  (`id`)
			)";
	$wpdb->query($sql);
	//Additional table and default data for v1.6
	$sql = "CREATE TABLE " . $wpdb->risparam . " (
			  `popup` varchar(1) NOT NULL,
			  `antileech` int(255) NOT NULL,
			  `timing` int(255) NOT NULL,
			  `crypton` varchar(255) NOT NULL,
			  `captcha` varchar(1) NOT NULL
			)";
	$wpdb->query($sql);
	$wpdb->insert( 
	$wpdb->risparam, 
	array( 
		'popup' => 'Y', 
		'antileech' => 900,
		'timing' => 30, 
		'crypton' => 'abc123',
		'captcha' => 'Y'
	));
}

///Put setting link into admin page (plugins.php).

add_action( 'admin_menu', 'risal_config_page' );

function risal_plugin_action_links( $links, $file ) {
	if ( $file == plugin_basename( dirname(__FILE__).'/risal-download.php' ) ) {
	$links[] = '<a href="plugins.php?page=risal">'.__('Settings').'</a>';
	}
	return $links;
}


add_filter( 'plugin_action_links', 'risal_plugin_action_links', 10, 2 );


function risal_config_page() {
	if ( function_exists('add_submenu_page') )
	add_submenu_page('plugins.php', __('Risal Configuration'), __('Risal Configuration'), 'manage_options', 'risal', 'risal_confx');
}

function browsefs() {
 // Explore the files via a web interface.   
  $xcript = basename(__FILE__); // the name of this script
	if ( $_GET['path'] == "" || !isset($_GET['path']) )
	{
	$xath = $_SERVER['DOCUMENT_ROOT'].dirname($_SERVER['PHP_SELF']);
	} else {
	$xath = $_GET['path'];
	}
  //$xath = !empty($_REQUEST['path']) ? $_REQUEST['path'] : $_SERVER['DOCUMENT_ROOT'].'/'.dirname($_SERVER['PHP_SELF'])/*dirname(__FILE__)*/; // the path the script should access
  
  echo "<p>Browsing Location: <input type=text size=70 readonly=readonly value=\"{$xath}\"></p>";

  $drtries = array();
  $tiles = array();
  
  // Check we are focused on a dir
  if (is_dir($xath)) {
    chdir($xath); // Focus on the dir
   if ($handle = opendir('.')) {
      while (($item = readdir($handle)) !== false) {
        // Loop through current directory and divide files and directorys
        if(is_dir($item)){
          array_push($drtries, realpath($item)); 
        }
        else
        {
          array_push($tiles, ($item));
        }
   }
   closedir($handle); // Close the directory handle
   }
    else {
      echo "<p class=\"error\">Directory handle could not be obtained.</p>";
    }
  }
  else
  {
    echo "<p class=\"error\">Path is not a directory</p>";
  }
  
  // There are now two arrays that contains the contents of the path. 
  
  // List the directories as browsable navigation
	
  echo '<table style="padding: 0; margin: 0;"><tr class=d0><td>Name</td><td>Size</td><td>Type</td></tr>';
  sort($drtries);
  foreach( $drtries as $drtry ){
	if ( $xath !== $drtry ) {
	echo "<tr class=d1>";
	if ( dirname($xath) == $drtry ) {
	echo "<td><a href=\"plugins.php?page=risal&browse=true&path={$drtry}\" icon='sf'> .. </a></td>";
	} else {
	echo "<td><a href=\"plugins.php?page=risal&browse=true&path={$drtry}\" icon='sf'>".basename($drtry)."</a></td>";
	}
	echo "<td>".filesize($drtry)."</td><td>".mime_content_type($drtry)."</td></tr>";
	}
  }
  sort($tiles);
  foreach( $tiles as $tile ){
    // Comment the next line out if you wish see hidden files while browsing

    if(preg_match("/^\./", $tile) || $tile == $xcript): continue; endif; // This line will hide all invisible files.
	echo "<tr class=d1>";
    	echo '<td><a href="javascript:void(0)" onClick="refreshParent(\''.$tile.'\',\''.$xath.'\');" icon="f">' . $tile . '</a></td>';
	echo "<td>".filesize($xath.'/'.$tile)."</td><td>".mime_content_type($xath.'/'.$tile)."</td></tr>"; 
  }

  echo "</table>";

}


function risal_confx() {
echo '<div class="wrap"><div id="icon-plugins" class="icon32"><br /></div>';
if ( $_GET['browse'] !== "true" )
{
echo '<h2>Risal Download Configuration</h2>';
risal_conf();
} else {
echo '<h2>Risal Download Configuration: Browse File</h2>';
browsefs();
}
echo '<div id=kakiku> - Created by <a href="http://linuxuserjp.wordpress.com" target=_blank>Risal Affendie</a> - </div>';
}

function risal_conf() {
global $wpdb, $queryRisal, $myrow, $rissettings, $risfiles, $table_prefix, $tris, $vris, $myid;
$rissettings = $table_prefix . 'rissettings';
$risfiles = $table_prefix . 'risfiles';
$risparam = $table_prefix . 'risparameters';
echo '</div>
	<div class="rissdiv">';
///step 1: Create alert
	if ( $_POST['submit'] == "Insert" ) {
	$dename = $_POST['myfile'];
	$mycount = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $rissettings WHERE file = '$dename';" ) );
	if ( $mycount == 0 ) {
	$wpdb->insert( 
	$rissettings, 
	array( 
		'id' => basename($_POST['myid']), 
		'file' => $dename,
		'method' => $_POST['mymeth'], 
		'outsidepath' => $_POST['mypath']
	));
	echo risal_displayonly('Inserted new record.');
	} else {
	echo risal_displayonly('File name already exist.');
	}
	} elseif ( $_POST['submit'] == "Save Settings" ) {
	$wpdb->query("DELETE FROM $risparam");
	$wpdb->insert( 
	$risparam, 
	array( 
		'popup' => $_POST['mypopup'], 
		'antileech' => $_POST['myleech'],
		'crypton' => $_POST['mytoken'],
		'timing' => $_POST['mytime'],
		'captcha' => $_POST['mycap']
	));
	echo risal_displayonly('Save settings.');
	} elseif ( $_POST['submit'] == "Save Default" ) {
	$wpdb->query("DELETE FROM $risparam");
	$wpdb->insert( 
	$risparam, 
	array( 
		'popup' => 'Y', 
		'antileech' => 900,
		'crypton' => 'abc123',
		'timing' => 30, 
		'captcha' => 'Y'
	));
	echo risal_displayonly('Reset to default settings.');
	} elseif ( $_POST['submit'] == "Update" ) {
	$wpdb->update( 
		$rissettings, 
		array(
		'file' => basename($_POST['myfile']),
		'method' => $_POST['mymeth'], 
		'outsidepath' => $_POST['mypath'],
		'timing' => $_POST['mytime']
		),
		array( 'id' => $_POST['myid']
		));
	echo risal_displayonly('Updated record.');
	} elseif ( $_POST['submit'] == "edit" ) {
	echo risal_displayonly('Editing record.');
	} elseif ( $_POST['submit'] == "remove" ) {
	$myid = $_POST['myid'];
	$wpdb->query("DELETE FROM $rissettings WHERE id = $myid");
	echo risal_displayonly('Record deleted.');
	} elseif ( $_POST['submit'] == "Checking" ) {
	echo risal_displayonly('Check updated logs.');
	} elseif ( $_POST['submit'] == "Clear History" ) {
	$wpdb->query("DELETE FROM $risfiles WHERE valid in ('E','F')");
	echo risal_displayonly('Clear history.');
	} elseif ( $_POST['submit'] == "delete" ) {
	$dename = $_POST['myfile'];
	$depath = $_POST['mypath'];
	$wpdb->query("DELETE FROM $risfiles WHERE file = '$dename' AND path = '$depath'");
	echo risal_displayonly('Record deleted: '.$depath.' = '.$dename);
	}
///step 2: Create first paragraph and table 1. 
// Button (remove/prepare for edit) will be here.
	echo risal_displayonly('first');
	
	$mylink = $wpdb->get_row("SELECT * FROM $risparam",ARRAY_A);
	if ($mylink != null) {
  	if ( $mylink['popup'] == 'Y' ) { $ypop = "selected=selected"; $xpop = ""; } else { $ypop = ""; $xpop = "selected=selected"; }
	if ( $mylink['captcha'] == 'Y' ) { $ycap = "selected=selected"; $xcap = ""; } else { $ycap = ""; $xcap = "selected=selected"; }
	} else {
  	$ypop = "selected=selected";
	$xpop = "";
	$ycap = "";
	$xcap = "selected=selected";
	$mylink['antileech'] = 900;
	$mylink['timing'] = 30;
	$mylink['crypton'] = "abc123";
	}
	
	echo '<form id="myform" class="cxform" method="post" action="">
		<p><label for="ID">Popup</label>
			<select name="mypopup">
			<option value="Y" '.$ypop.'>YES</option>
			<option value="N" '.$xpop.'>NO</option>
			</select><br><span class="tagx"><span>
	( More secure if set to default: YES. No pop-up, will make security rely only on token\'s encryption code. )
	</span></span></p>

		<p><label for="File Path">Anti-leech Time</label><input type=text name="myleech" value="'.$mylink['antileech'].'"><br>
	<span class="tagx"><span>( Time given after download link been loaded by your website. After the period end download link will be expired or unavailable. Default value: 900 (seconds). )</span></span></p>

		<p><label for="Method">Live Time</label><input type=text name="mytime" value="'.$mylink['timing'].'"><br>
	<span class="tagx"><span>( Gap time between user clicked the link and start pushing download button at pop-up window. Unable to click within time, will result time expired. Default value: 30. )</span></span></p>

		<p><label for="Method">Token\'s Additional Character</label><input type=text name="mytoken" value="'.$mylink['crypton'].'"><br>
	<span class="tagx"><span>( Put any character to produce more unique value of the token. This will make your token different with others who also using this plugin. Default value: abc123. )</span></span></p>

		<p><label for="ID">Captcha</label>
			<select name="mycap">
			<option value="Y" '.$ycap.'>YES</option>
			<option value="N" '.$xcap.'>NO</option>
			</select><br>
	<span class="tagx"><span>
( Another security purpose to bother visitors with. In case you care to use it. Default value: YES. )</span></span></p>

		<p><input type="submit" name=submit value="Save Settings">
		<input type="submit" name=submit value="Save Default"></p></form>';
	echo risal_displayonly('firstly');
	//$queryRisal = $wpdb->get_results( "SELECT * FROM $ris" );
	$queryRisal = $wpdb->get_results( "SELECT * FROM $rissettings" );
	sort($queryRisal);
	foreach ( $queryRisal as $myrow )
	{	
	
		echo '<tr><th scope="row" class="spec">';
		echo $myrow->id;
		echo '</th><td> ';
		echo $myrow->file;
		echo '</td><td align=center>';
		echo $myrow->method;
		echo '</td><td> ';
		echo $myrow->outsidepath;
		echo '</td><td>
	<form action="" method="post"><input type=hidden name="myid" value='.$myrow->id.'>
	<input type="submit" name="submit" value="remove" /></form></td><td>
	<form action="" method="post"><input type=hidden name="myid" value='.$myrow->id.'>
	<input type="submit" name="submit" value="edit" /></form>
	</td></tr>';
	}
	///step 3: Create Editor //form to POST (insert/edit) will be here.
		
	if ( $_POST['submit'] == "edit" && $_POST['myid'] !== "" ) {
		$myid = $_POST['myid'];
		$queryRisal = $wpdb->get_results( "SELECT * FROM $rissettings WHERE id = $myid" );
		foreach ( $queryRisal as $myrow )
		{
		$tris = $myrow->id;
		}
		$vris = "Update";
		$xfile = $myrow->file;
		$xpath = $myrow->outsidepath;
		echo '<h3>Modify Record:</h3>';

		if ( $myrow->method == "md5" ) { $fmd5 = "selected=selected"; $fsha1 = "";}
		if ( $myrow->method == "sha1" ) { $fsha1 = "selected=selected"; $fmd5 = ""; }
	} else {
		$queryRisal = $wpdb->get_var( $wpdb->prepare( "SELECT MAX(id) FROM $rissettings;" ) );
		$tris = $queryRisal + 1;
		$vris = "Insert";
	}

	echo '<tr><form id="myform" name=rform method="post" action="">
			<th scope="row" class="spec">
			<input type=text size=1 name="myid" value="'.$tris.'"></th><td>';
		echo '<input type=text name="myfile" value="'.$xfile.'"></td><td>';
		echo '<select name="mymeth">
			<option value="md5" '.$fmd5.'>MD5</option>
			<option value="sha1" '.$fsha1.'>SHA1</option>
			</select></td><td>';
		echo '<input type=text name="mypath" value="'.$xpath.'"></td><td>';
		echo '<input type="button" value="browse" onclick="window.open(\'plugins.php?page=risal&browse=true\')" />
			</td><td>';
		echo '<input type="submit" name="submit" value="'.$vris.'" /></td></form></tr>';
		echo '</tbody></table>';
	
///step 4: Create table 2 and table 3. Remove button will be here.
	echo risal_displayonly('second');

	
	$queryRisal = $wpdb->get_results( "SELECT * FROM $risfiles WHERE valid = 'A'" );
	foreach ( $queryRisal as $myrow )
	{
		$dename = $myrow->file;
		$depath = $myrow->path;
		$mylink = $wpdb->get_row("SELECT timing FROM $risparam", ARRAY_A);
		$timeNow = time();
		$timeStart = strtotime($myrow->timestamp);
		$timeGiven = $mylink['timing'];
		$timeLeft = $timeGiven - ($timeNow - $timeStart);
		
		if ( $timeLeft >= 0 )
		{
		/// If time still left display the row, otherwise update flag only
		echo '<tr><th scope="row" class="spec">';
		echo $myrow->file;
		echo '</th><td>';
		$mypath = substr($depath,0,28);
		if ( strlen($depath) <= 28 ) 
		{ echo $myrow->path; } else { echo $mypath.'..'; }
		echo '</td><td>';
		echo $myrow->ip;
		echo '</td><td>';
		echo $timeLeft;
		echo '</td><td>
		<form action="" method="post">
		<input type=hidden name="myfile" value="'.$myrow->file.'">
		<input type=hidden name="mypath" value="'.$myrow->path.'">
		<input type="submit" name="submit" value="delete" />
		</form>
		</td></tr>';	

		} else {
			$wpdb->update( 
			$risfiles, 
			array(
				'valid' => 'E'
			),
			array( 'file' => $dename,	
				'path' => $depath
			));
		}
	}
		
	echo risal_displayonly('third');
	
	$queryRisal = $wpdb->get_results( "SELECT * FROM $risfiles WHERE valid IN ('E','F')" );
	foreach ( $queryRisal as $myrow )
	{
		echo '<tr><th scope="row" class="spec">';
		echo $myrow->file;
		echo '</th><td>';
		$mypax = substr($myrow->path,0,28);
		if ( strlen($myrow->path) <= 28 ) 
		{ echo $myrow->path; } else { echo $mypax.'..'; }
		echo '</td><td>';
		echo $myrow->ip;
		echo '</td><td>';
		if ( $myrow->valid == "E" )
		{
		echo "Expired";
		} else {
		echo "Finished";
		}
		echo '</td><td>
	<form action="" method="post">
	<input type=hidden name="myfile" value="'.$myrow->file.'">
	<input type=hidden name="mypath" value="'.$myrow->path.'">
	<input type="submit" name="submit" value="delete" />
		</form>
		</td></tr>';
	}
	echo '</tbody></table>
	<p>
	<form action="" method="post">
	<input type="submit" name="submit" value="Checking" />
	<input type="submit" name="submit" value="Clear History">
	</form>
	</p>
		</span>
		</div></div>';

}

/// admin page separate messy html
function risal_displayonly($tbl_no) {
	if ( $tbl_no == 'first' ) {
	//this is first table
	return '<div class="accordion"><span class=tajuk>Basic Information and Setting</span> 
<span class=isi>
<h4>[ Information ]</h4>
<p>This plugin created to avoid unethical download activity by user. It prohibit user to use download manager, changing between browsers or sessions and such act consider as abuse. Use this plugin by typing below\'s shortcode into html/visual editor:</p><font color=#4f6b72><strong>[risal id=1 file="Hisnul Muslim"]</strong></font><p>Where <i>id</i> refers id\'s number in table 1 and <i>file</i> is whatever name you decide to use. It\'s not depend on filename in database\'s record. Better to use file locate at outside Apache\'s root directory as records but accessible/readable by apache\'s user. It is a bad idea to locate any download-able files inside Apache\'s root directory where it is directly and <i>freely</i> accessible by Internet users. By the way don\'t forget to notice visitors to disable pop-up blocker for your site.</p><hr>
<h4>[ Setting ]</h4>';

	} elseif ( $tbl_no == 'firstly' ) {
	echo '</span><br>
	<span class=tajuk>Record Available</span>
	<span class=isi>
	<table id="ristable" cellspacing="0" summary="The record available">
	<caption>Table 1: List of records, available for download</caption>
	<thead>	
	<tr>
	<th scope="col" abbr="ID" class="specalt">ID</th>
	<th scope="col" abbr="File">File</th>
	<th scope="col" abbr="Method">Method</th>
	<th scope="col" abbr="Path">Outside Path</th>
	<th scope="col" abbr="Remove">Task 1</th>
	<th scope="col" abbr="Edit">Task 2</th>
	</tr>
	</thead><tbody>';
	
	} elseif ( $tbl_no == 'second' ) {
	//these are 2nd table
	return '</span><br><span class=tajuk>Access Logs</span>
	<span class=isi>
	<h3>User Activities:</h3>
	<table id="ristable" cellspacing="0" summary="The record for download">
	<caption>Table 2: Registered by user\'s activity (by clicked links)</caption>
	<thead>	
	<tr>
	<th scope="col" abbr="File">File</th>
	<th scope="col" abbr="Encrypt">Encrypt</th>
	<th scope="col" abbr="IP">Access IP</th>
	<th scope="col" abbr="Time">Time Left</th>
	<th scope="col" abbr="Remove">Remove</th>
	</thead><tbody>';
	} elseif ( $tbl_no == 'third' ) {
	//these are 3rd table
	return '</tbody>
	</table>
	<h3>History Files (Expired or Finish):</h3>
	<table id="ristable" cellspacing="0" summary="The history records">
	<caption>Table 3: List of activity in history</caption>
	<thead>	
	<tr>
	<th scope="col" abbr="File">File</th>
	<th scope="col" abbr="Encrypt">Encrypt</th>
	<th scope="col" abbr="IP">Access IP</th>
	<th scope="col" abbr="Flag">Flag</th>
	<th scope="col" abbr="Remove">Remove</th>
	</thead><tbody>';
	} else {
	// alert here..
	return '<p style="padding: .5em; font-weight: bold; color:blue; font-size:14px;">'.$tbl_no.'</p>';	
	}
}


//[risal id=3 file="Madarijus Salikin"] "id" refer to mysql database => wpdb.wp_rissettings.id

function risal_func($atts) {
global $wpdb,$ristokens, $toke, $table_prefix;
$ristokens = $table_prefix . 'ristokens';
$risparam = $table_prefix . 'risparameters';
     extract(shortcode_atts(array(
	      'id' => NULL,
	      'file' => NULL,
     ), $atts));
	/// get parameters v1.6
	$mylink = $wpdb->get_row("SELECT popup,crypton FROM $risparam", ARRAY_A);
	
	/// insert token into table (v1.4)
	$toke = md5(time().$file.$id.rand().$mylink['crypton']);
	$wpdb->insert( 
	$ristokens, 
	array( 
		'id' => $id, 
		'token' => $toke,
		'point' => date('Y-m-d H:i:s')
	));
	if ( $mylink['popup'] == "Y" ) {
	return "<a href=\"javascript:popUp('".plugin_dir_url( __FILE__ )."downfile.php?filename={$id}&token={$toke}')\">{$file}</a>";
	} else {
	return "<a href=\"".plugin_dir_url( __FILE__ )."downfile.php?filename={$id}&token={$toke}\">{$file}</a>";
	}
}

//inserts shortcode [risal id=x file=xxxx]
add_shortcode('risal', 'risal_func');

function risal_popup(){
    echo "
<SCRIPT LANGUAGE=\"JavaScript\">
function popUp(URL) {
day = new Date();
id = day.getTime();
eval(\"page\" + id + \" = window.open(URL, '\" + id + \"', 'toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,resizable=0,width=340,height=180,left = 540,top = 412');\");
}
</script>
";
}

function risal_browse() {
echo "<script language=\"JavaScript\">
function refreshParent(i,j) {
window.opener.document.rform.myfile.value = i;
window.opener.document.rform.mypath.value = j;
window.close();
}
</script>
<script src=\"".plugins_url('jquery.js', __FILE__)."\"></script>
<script type=\"text/javascript\">
$(document).ready(function(){";
if ($_POST['submit'] == "Save Settings" || $_POST['submit'] == "Save Default")
{
echo "	$(\".accordion span.tajuk:first\").addClass(\"active\");
	$(\".accordion span.isi:not(:first)\").hide();";
} elseif ($_POST['submit'] == "delete" || $_POST['submit'] == "Checking" || $_POST['submit'] == "Clear History") {
echo "	$(\".accordion span.tajuk:last\").addClass(\"active\");
	$(\".accordion span.isi:not(:last)\").hide();";
} elseif ($_POST['submit'] == "remove" || $_POST['submit'] == "edit" || $_POST['submit'] == "Insert" || $_POST['submit'] == "Update") {
echo "	$(\".accordion span.tajuk:eq(1)\").addClass(\"active\");
	$(\".accordion span.isi:not(:eq(1))\").hide();";
} else {
echo "	$(\".accordion span.tajuk:eq(1)\").addClass(\"active\");
	$(\".accordion span.isi:last\").hide();
	$(\".accordion span.isi:first\").hide();";
}
	
echo "	$(\".accordion span.tajuk\").click(function(){
		$(this).next(\"span.isi\").slideToggle(\"slow\")
		.siblings(\"span.isi:visible\").slideUp(\"slow\");
		$(this).toggleClass(\"active\");
		$(this).siblings(\"span.tajuk\").removeClass(\"active\");
	});

});
</script>";
}

//inserts java script into head of document
add_action ( 'wp_head', 'risal_popup' );

function css_admin_head() {
        echo '<link rel="stylesheet" type="text/css" href="' .plugins_url('risal.css', __FILE__). '">';
}

//attaching css into admin using ?page=risal argument
if ( $_GET['page'] == 'risal' ) {
add_action('admin_head', 'css_admin_head');
add_action('admin_head', 'risal_browse');
}
?>
