<?php
session_start();
#specify host or domain (needed for wp-includes/ms-settings.php:100)
$_SERVER[ 'HTTP_HOST' ] = 'localhost';

#location of wp-load.php so we have access to database and $wpdb object
$wp_load_loc = "../../../wp-load.php";

require_once($wp_load_loc);

global $wpdb; 
$risfiles = $wpdb->prefix . 'risfiles';
$rissettings = $wpdb->prefix . 'rissettings';
$ristokens = $wpdb->prefix . 'ristokens';
// Get parameters (v1.6)
$risparam = $wpdb->prefix . 'risparameters';
$mylink = $wpdb->get_row("SELECT * FROM $risparam", ARRAY_A);
$antileech = $mylink['antileech'];
$mylimit = $mylink['timing'];
$captcha = $mylink['captcha'];
$crypton = $mylink['crypton'];
$popap = $mylink['popup'];


// Delete other expired tokens.(v1.4)
$pointer = time() - $antileech;
$pointer = date("Y-m-d H:i:s",$pointer);

$wpdb->query("DELETE FROM $ristokens WHERE TIMEDIFF(point,'$pointer') < 0");


if ( basename(__FILE__) == 'downfile.php' && $_GET['filename'] !== "" && $_GET['token'] !== "" )
{
$filename = $_GET['filename'];
$tokex = $_GET['token'];
// Check token first (v1.4)
$tokencount = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $ristokens WHERE id = '$filename' and token = '$tokex'" ) );
$tokenvalid = 'NO';
if ( $tokencount == 1 ) {
$mylink = $wpdb->get_row("SELECT * FROM $ristokens WHERE id = '$filename' and token = '$tokex'", ARRAY_A);
$pointime = strtotime($mylink['point']);
	// Only few minutes for a token (V1.4)
	if ( ($pointime + $antileech) >=  time() ) {
	$tokenvalid = 'YES';
	}
}

if ( $tokenvalid == 'YES' ) {
$res = $wpdb->get_results("SELECT file, method, outsidepath FROM $rissettings WHERE id = '$filename'");
$thecount = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $rissettings WHERE id = '$filename'" ) );
foreach ( $res as $fives ) 
{
$actname = $fives->file;
$method = $fives->method;
$actpath = $fives->outsidepath;
} // end foreach

/// This is direct download and less secure v1.6
if ( $popap == 'N' ) {
	$wpdb->query("DELETE FROM $ristokens WHERE id = '$filename' and token = '$tokex'");
	//////// ..Download here.. ////////
	$mymime = mime_content_type($actpath.'/'.$actname);
	header('Content-type: '.$mymime);
	// It will be called by it's original name 
	header('Content-Disposition: attachment; filename="'.$actname.'"');
	// The PDF source is file with full path.
	readfile($actpath.'/'.$actname);
	exit;
}
/// End of less secure //

	if ( isset($_SESSION['id']) && isset($_SESSION['ip']) && isset($_SESSION['mytime']) && $_SESSION['mybrows'] !== "non" && isset($_SESSION['mynum']) && $_POST['submitRisal'] == "Download" )
	{
	if ( $captcha == 'Y' ) {
		if ( $_POST['char'] !== $_SESSION['thechar'] ) {
		echo 'Sorry character not match! Try again!
	<BR><form method="post">
	<input type="button" value="Close Window" onclick="self.close()">
	</form>';		
		exit;
		}
	}

	//if match in db (isset viarable only):
	
	switch ($method) {
    	case 'md5':
        $thepath = md5($_SESSION['ip'].$_SESSION['mytime'].$_SESSION['mynum']);
        break;
    	case 'sha1':
        $thepath = sha1($_SESSION['ip'].$_SESSION['mytime'].$_SESSION['mynum']);
        break;
	} //end switch
	// if browser match
	$test = $_SERVER['HTTP_USER_AGENT'];
	$mybrow = $_SESSION['mybrows'];
	if ( preg_match("/$mybrow/i",$test) ) {
	// if file exist in server
	if (file_exists($actpath.'/'.$actname)) {
	// if record exist (file and encrypt match)
	$mycount = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $risfiles WHERE file = '$actname' and path = '$thepath' and valid = 'A';" ) );
	if ( $mycount == 1 ) {
	// if within time
	$sesTime = strtotime($_SESSION['mytime']);
	$timeNow = time();
	if ( ($sesTime + $mylimit) >=  $timeNow ) {
	$res = $wpdb->get_results("SELECT * FROM $risfiles WHERE file = '$actname' and path = '$thepath' and valid = 'A'");
	foreach ( $res as $fives ) 
	{
	$myip = $fives->ip;
	$mytiming = $fives->timestamp;
	} // end foreach
	$wpdb->query(
	"
	UPDATE $risfiles 
	SET valid = 'F'
	WHERE file = '$actname' and path = '$thepath' and valid = 'A'
	"
	);
	unset($_SESSION['id']);
	unset($_SESSION['ip']);
	unset($_SESSION['mytime']);
	unset($_SESSION['mybrows']);
	unset($_SESSION['mynum']);
	session_destroy();

	// Delete current token if exist (V1.4)
	$wpdb->query("DELETE FROM $ristokens WHERE id = '$filename' and token = '$tokex'");

	//////// ..Download here.. ////////
	$mymime = mime_content_type($actpath.'/'.$actname);
	header('Content-type: '.$mymime);
	// It will be called by it's original name 
	header('Content-Disposition: attachment; filename="'.$actname.'"');
	// The PDF source is file with full path.
	readfile($actpath.'/'.$actname);
	exit;
	} else {
	echo "<p>TIME EXPIRED.</p>";
	$wpdb->query(
	"
	UPDATE $risfiles 
	SET valid = 'E'
	WHERE file = '$actname' and path = '$thepath' and valid = 'A'
	"
	);
	} // end if (time expired)
	} else {
	echo "<p>RECORD ID [ ".$_SESSION['id']." ] NOT FOUND</p><form method=\"post\">
	<input type=\"button\" value=\"Close Window\" onclick=\"self.close()\">
	</form>";
	}// end if record exist(count)
	} else {
	echo "<p>FILE NOT EXIST IN SERVER.</p><form method=\"post\">
	<input type=\"button\" value=\"Close Window\" onclick=\"self.close()\">
	</form>";
	} // end file_exists
	} else {
	echo "<p>WRONG BROWSER.</p><form method=\"post\">
	<input type=\"button\" value=\"Close Window\" onclick=\"self.close()\">
	</form>";
	} // end if (browser)
	//// Delete current token if exist (V1.4) even failed to download.
	$wpdb->query("DELETE FROM $ristokens WHERE id = '$filename' and token = '$tokex'");
	unset($_SESSION['id']);
	unset($_SESSION['ip']);
	unset($_SESSION['mytime']);
	unset($_SESSION['mybrows']);
	unset($_SESSION['mynum']);
	session_destroy();
	exit;
	} else { //ELSE FOR SESSION AND POST EXISTENTCY
	
	if ( $popap == 'Y' ){
	echo '<html><head><title>Download</title>
	<script type="text/javascript">
	function timedMsg()
	{
	var t=setTimeout("alert(\'I am displayed after 3 seconds!\')",3000)
	}
	</script>
	<link rel="stylesheet" type="text/css" href="' .plugins_url('risal.css', __FILE__). '">
	</head><body><center>';
	}
	$myip = $_SERVER['REMOTE_ADDR'];
	$mytiming = date('Y-m-d H:i:s');
	$test = $_SERVER['HTTP_USER_AGENT'];
	if (preg_match("/MSIE/i",$test)) {
        $brows = "MSIE";
	} elseif (preg_match("/Firefox/i",$test)) {
	$brows = "Firefox";
	} elseif (preg_match("/Opera/i",$test)) {
	$brows = "Opera";
	} elseif (preg_match("/Chrom/i",$test)) {
	$brows = "Chrom";
	} elseif (preg_match("/Arora/i",$test)) {
	$brows = "Arora";
	} else {
	$brows = "non";
	} //end if
	$myrand = rand();
	$_SESSION['id'] = $filename;
	$_SESSION['ip'] = $myip;
	$_SESSION['mytime'] = $mytiming;
	$_SESSION['mybrows'] = $brows;
	$_SESSION['mynum'] = $myrand;
	echo '<form id=popbox action="" method="post" onSubmit="setTimeout(\'self.close()\',12000)">';
	if ( $captcha == 'Y' ) {

	echo '<div id="captcha-wrap">
		<div class="captcha-box">
			<img src="riscaptcha.php" alt="" id="captcha" />
		</div>
		<div class="text-box">
			<label>Type above characters:</label>
			<input name="char" type="text" id="captcha-code">
			<input type="submit" name="submitRisal" value="Download" />
		</div>
		<div class="captcha-action">
			<a href=""><img src="refresh.jpg"  alt="" id="captcha-refresh" /></a>
		</div></div>';


	} else {
	echo '<input type="submit" name="submitRisal" value="Download" />';
	}
	echo '
	</form>';
	switch ($method) {
    	case 'md5':
        $thepath = md5($myip.$mytiming.$myrand);
        break;
    	case 'sha1':
        $thepath = sha1($myip.$mytiming.$myrand);
        break;
	} // end switch
	if ( $thecount == 1 ) {
	$wpdb->insert( $risfiles, 
	array( 
		'file' => $actname, 
		'ip' => $myip,
		'timestamp' => $mytiming, 
		'valid' => 'A', 
		'path' => $thepath
	));
	}
	} //end if
} elseif ( $tokenvalid == 'NO' ) {
// Notification for invalid token (V1.4)
echo ' SORRY. TOKEN EXPIRED!<BR><form method="post">
<input type="button" value="Close Window"
onclick="self.close()">
</form>';
}
} elseif ( !isset($_GET["filename"]) || empty($_GET["filename"]) || $_GET['filename'] == "" || $_GET['token'] == "" || $_GET['filename'] == NULL || $_GET['token'] == NULL ) {
echo "I can see abuse out there";
}
echo '</center></body></html>';
?>
