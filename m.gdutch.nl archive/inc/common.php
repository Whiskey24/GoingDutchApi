<?php 

//Some Handy configuration for debugging - will display all PHP errors and warnings if any 
//error_reporting(E_ALL); 
ini_set("display_errors",0); 
error_reporting(E_ALL & ~E_NOTICE);

// file Inclusion 
include("functions.php"); 
include("class.uFlex.php"); 


// Connect to DB
mysql_connect("localhost", "dutch", "58r4huw239") or die(mysql_error()); 
mysql_select_db("goingdutch") or die(mysql_error());


//Sample common globals and constant
define("TITLE","Going Dutch"); 
define("SITE","m.gdutch.nl"); 
define("DEFAULTPAGE","group.php"); 
define("LOGINPAGE","index.php"); 
define("HELPPAGE","help.php"); 
define("EXPENSEPAGE","expenses.php"); 
define("DIR","/");   // begin and end with slash
define("TIME", time());
define("DECIMALS", 2); 	
define("TSEP", ".");      // Thousand separator
define("DSEP", ",");      // Decimal separator

define("LOGIN_URL", 'http://localhost/m.gdutch.nl/httpdocs/');

$user = new uFlex(); 

if($user->signed){ 
	// user is validated, no login required
	// if we are on the login page, sent through to default page

	if ($_SERVER['PHP_SELF'] == DIR . LOGINPAGE ) {
		$host  = $_SERVER['HTTP_HOST'];
		$uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
		$extra = DEFAULTPAGE;
		header("Location: http://$host$uri/$extra");
		exit;
	}
} else {
	// user is not signed in, direct to login page if not already there
  // exception is the help page
  if ($_SERVER['PHP_SELF'] == DIR . HELPPAGE) {
    // continue
  } elseif ($_SERVER['PHP_SELF'] != DIR . LOGINPAGE) {
		$host  = $_SERVER['HTTP_HOST'];
		$uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
		$extra = LOGINPAGE;
		header("Location: http://$host$uri/$extra");
		exit;
	}
} 
// save page in page history
save_page_history($_SERVER['REQUEST_URI']);

function print_header($js='') { ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">	

  <head>
    <link rel="icon" href="./favicon.ico" type="image/x-icon" />
    <link rel="shortcut icon" href="./favicon.ico" type="image/x-icon" />
    <link rel="icon" type="image/gif" href="./animated_favicon1.gif" />
	
  <meta content="minimum-scale=1.0, width=device-width, maximum-scale=0.6667, user-scalable=no" name="viewport" />
  <link href="iwebkit/css/style.css" rel="stylesheet" media="screen" type="text/css" />
  <script src="iwebkit/javascript/functions.js" type="text/javascript"></script>
  
  <?php  if (!empty($js))   echo $js; ?>
   <title><?php echo TITLE?></title>
    <meta http-equiv="Content-Type"
          content="text/html; charset=utf-8" />
    <meta name="robots" content="noindex,nofollow" />
  <?php if ($_SERVER['HTTP_HOST'] == 'm.gdutch.nl') { ?>
  <script type="text/javascript">
    var _gaq = _gaq || [];
    _gaq.push(['_setAccount', 'UA-24007859-8']);
    _gaq.push(['_trackPageview']);
    (function() {
      var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
      ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
      var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
    })();
  </script>
  <?php } ?>
  </head>
  
  <body>

<?php
}

function print_body_start () {
  echo n(4)."<div id=\"content\">\n";
}
function print_footer($user, $help=0, $groupid=0) {
  $_SESSION['back'] = htmlentities($_SERVER['REQUEST_URI']);
 
	echo "  </div>\n";
  echo "  <div id=\"footerbar\">\n";
  echo "    <div id=\"settings\">\n";
  echo "      <a href=\"help.php?h=$help\"><img src=\"images/help.png\" /></a>\n";
  if($user->signed)  {
    echo "      <a href=\"profile.php\"><img src=\"images/cog20.png\" /></a>\n";
    echo "      <a href=\"home.php\"><img src=\"images/home.png\" /></a>\n";
    if ($_SERVER['PHP_SELF'] == DIR . EXPENSEPAGE && $groupid > 0 ) {
      echo "      <a href=\"excel.php?groupid=$groupid\"><img src=\"images/excel.png\" /></a>\n";
    }
  }
  echo "    </div>\n";
  if($user->signed){ 
    echo "    <div id=\"exit\">\n";
    echo "      <a href=\"logout.php\"><img src=\"images/logout.png\" /></a>\n";
    echo "    </div>\n";
  }
  echo "  </div>\n  </body>\n</html>";

  }
?>
