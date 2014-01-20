<?php session_start();  // php kolacici 
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <title>forum/register</title>
    <script type="text/javascript">
        function tog_usr_change() { document.getElementById('usr').style.display = 'table'; 
        document.getElementById('chng_btn').style.display = 'none'; }
    </script>
  </head>
  <body alink="#6666cc" bgcolor="#ccccff" link="#6666cc" text="#000000"
    vlink="#6666cc">
 
 <?php
 
 include 'std.php';
 
$goal = 'view'; // sto korisnik hoce

// da ne baca warning ako na serveru nije postavljeno
date_default_timezone_set('Europe/Zagreb'); // trazi min PHP 5.1

// sto cemo raditi:
if (isset($_GET['post_register']))  $goal = 'post_register';
else $goal = "view";

$msg = 'Choose your usename and password (non-alphanumeric symbols will be discarded).';

if ($goal == 'post_register')
{
    $usrs = array(); $usrs_names = array(); $usrs_passes = array(); $usrs_perms = array(); 
    load_users($usrs, $usrs_names, $usrs_passes, $usrs_perms);
    $req_usr = '';
    $req_pass = '';
    $req_pass2 = '';
    if (isset($_POST['usr'])) $req_usr = preg_replace("/[^a-zA-Z0-9]+/", "", trim($_POST['usr']));
    if (isset($_POST['psw'])) $req_pass = preg_replace("/[^a-zA-Z0-9]+/", "", trim($_POST['psw']));
    if (isset($_POST['psw2'])) $req_pass2 = preg_replace("/[^a-zA-Z0-9]+/", "", trim($_POST['psw2']));
   // echo $_POST['bad'];
    $f = array_search($req_usr, $usrs_names);
    while ($f !== false && trim($_POST['bad']) == 'auto')
    {
            $req_usr .= '0';
            $f = array_search($req_usr, $usrs_names);
    }

    if ($f === false && $req_pass == $req_pass2 && isset($_POST['conditions']))
    {
        $usrs[] = $usrs[count($usrs) - 1] + 1;
        $usrs_names[] = $req_usr;
        $usrs_passes[] = $req_pass;
    
        if ($_POST['type'] == '2')
            $usrs_perms[] = explode(' ', "topic_add post_add");
        else if ($_POST['type'] == '1')
            $usrs_perms[] = explode(' ', "post_add");
        else
            $usrs_perms[] = explode(' ', "/");
        
        $outp = '';
        for ($i = 0; $i < count($usrs); ++ $i)
        {
            $outp.=$usrs[$i]."\n".$usrs_names[$i]."\n".$usrs_passes[$i]."\n";
            $outp.=implode(' ', $usrs_perms[$i])."\n";
        }
    
        $fh = file_or_die("usr.txt", "w");
        fwrite($fh, $outp);
        fclose($fh);
        
        $msg = "User $req_usr registered. You can login at <a href='index.php'>the main page</a>.";
    }
    else
        $msg = "Try again.";

}

 ?>
    
    
    <table style="margin-top: 50px;" align="center" bgcolor="#ffffff"
      border="0" cellpadding="15" cellspacing="10" width="1100">
      <tbody>
        <tr>
          <td colspan="3" rowspan="1" height="1" valign="top"><br />
          </td>
        </tr>
        <tr>
          <td align="right" valign="top" width="150px"><b>Dobrodosli!</b><br />
          
            <br />
            <br />
            Registracijom mozete otvarati teme, pisati odgovore, te uredivati (ali ne i brisati) vlastite postove.
            <br />
            
          </td>
          <td style="border-left: 2px solid gray; border-right: 2px
            solid gray;" align="left" valign="top">
         
            <hr>
            
            <form align="center" style="font-size:small;" action="?post_register" method="post">
                 <?php echo $msg; ?><br /><br />
                 <b>username:</b>  <input type="text" name="usr" size="15"> <br />
                 <b>password:</b>  <input type="password" name="psw" size="15"> <br />
                 <b>retype password:</b> <input type="password" name="psw2" size="15"> <br />
                 <b>account type:</b> <select name="type">
                        <option value="0">just read</option>
                        <option value="1">basic</option>
                        <option value="2" selected>full</option>
                        </select>
                 <br />
                 <b>if username not available: </b><input type="radio" name="bad" value="return" checked>ask again
                <input type="radio" name="bad" value="auto">find available

                <br /><br />
                 <input type="checkbox" name="conditions"> <b>Accept conditions</b> (da ih vlasnici foruma imaju...)


               <br /><br />
            [<button type="submit" style="margin:0;padding: 0;border: none;color: blue;background-color: transparent;">register</button>]
            </form>
            
            <br />
          </td>
          <td align="left" valign="top" width="220px" >
            

            <b>platform info:</b><br />
            agent: <?php echo $_SERVER['HTTP_USER_AGENT'].'<br />'; ?><br />
            
            
          </td>
        </tr>
        <tr>
          <td colspan="3" rowspan="1" height="50" align="center"
            valign="bottom"><font color="#666666"><small><small><a
                    href="mailto:lmikec@ffri.hr">Luka Mikec</a>, DWA1
                  projekt. </small></small></font><br />
          </td>
        </tr>
      </tbody>
    </table>
    <br />
  </body>
</html>
