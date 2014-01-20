<?php session_start();  // php kolacici 
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <title>forum</title>
    <script type="text/javascript">
        function tog_usr_change() { document.getElementById('usr').style.display = 'table'; 
        document.getElementById('chng_btn').style.display = 'none'; }
    </script>
  </head>
  <body alink="#6666cc" bgcolor="#ccccff" link="#6666cc" text="#000000"
    vlink="#6666cc">
 <?php
 
 date_default_timezone_set('Europe/Zagreb'); // trazi min PHP 5.1
 
 include 'std.php';
 
$goal = 'view'; // sto korisnik hoce
$req_topic = 0; // tema za pregledati, brisati, ili raditi nesto s njenim postovima (pregl, stvoriti, urediti, brisati, odgovoriti)
$req_post = ''; 

$req_usr = -1; // ako je zatrazena promjena korisnika
$req_pass = '';

$edit_post_title = ''; // kod editiranja posta, da prikaze stari sadrzaj
$edit_post_content = '';


// da ne baca warning ako na serveru nije postavljeno
date_default_timezone_set('UTC'); // trazi min PHP 5.1

// sto cemo raditi:
if (isset($_GET['tid']))  $req_topic = $_GET['tid'];
if (isset($_GET['pid'])) $req_post = $_GET['pid'];
if (isset($_POST['usr'])) $req_usr = $_POST['usr'];
if (isset($_POST['psw'])) $req_pass = $_POST['psw'];
if (isset($_GET['switch']))
{
    if (isset($_POST['login'])) $goal = 'switch';
}
else if (isset($_GET['del']))    $goal = 'del';
else if (isset($_GET['edit']))    $goal = 'edit';
else if (isset($_GET['post_edit']))    $goal = 'post_edit';
else if (isset($_GET['create'])) $goal = 'reply'; // za tid == -1, nova tema

// ucitavanje baze korisnika
$usrs = array(); $usrs_names = array(); $usrs_passes = array(); $usrs_perms = array(); 
load_users($usrs, $usrs_names, $usrs_passes, $usrs_perms); // iz std.php

// default: guest
$usr = -1; 

// ako ima cookie, provjeri je li ok i ucitaj:
if (isset($_SESSION['usr']) && isset($_SESSION['psw']))
    if ($usrs_passes[$_SESSION['usr']] == $_SESSION['psw'])
        $usr = $_SESSION['usr'];

if ($goal == 'switch')
{
    $f = array_search($req_usr, $usrs_names);

    if ($f !== $usr && $f !== FALSE) // ako se trenutno loginirani razlikuje od trazenog, i postoji taj trazeni
    {
        if ($usrs_passes[$f] == $req_pass)
        {
            $_SESSION['stamp'] = date('j.n. H:i'); 
            $usr = $f;
            $_SESSION['usr'] = $f;
            $_SESSION['psw'] = $req_pass;
        }
        else
        {
            // nista, neka ostane na starom korisniku 
        }
    }
}

 // provjera sto moze trenutni korisnik
 $dop_topic_add = false; $dop_post_add = false; $dop_post_del = false;
 
 if ($usr != -1) // -1 je guest, nema posebnih dop
 {
        $dop_topic_add = array_search('topic_add', $usrs_perms[$usr]) !== FALSE;
        $dop_post_add = array_search('post_add', $usrs_perms[$usr]) !== FALSE;
        $dop_post_del = array_search('post_del', $usrs_perms[$usr]) !== FALSE;
 }
$perm_status = 'view content'.($dop_post_add ? ', reply to posts' : '').($dop_post_del ? ', remove content' : '').($dop_topic_add ? ', start new topics' : '');
 
if ($goal == 'reply' && $dop_post_add)
{
        if ($req_topic == -1 && $dop_topic_add) // treba li novu temu?
        {
            $tfname = "topics.txt";
            if (check_file($tfname))
            {
                $numbers = explode("\n", trim(file_get_contents(DIR.$tfname)));
                $req_topic = $numbers[count($numbers) - 1] + 1; // iduci slobodni id
                $numbers[] = $req_topic;
                $fh = file_or_die($tfname, "w");
                fwrite($fh, implode("\n", $numbers));
                fclose($fh);

                // stvara datoteku za postove; ako nema dovoljno dopustenja dat ce gresku
                fclose(fopen("data/topic_$req_topic.txt", "x")); 
            }
        }
    
        $fname = "topic_".$req_topic.".txt";
        if (check_file($fname) && $req_topic >= 0) 
        {
            $oldcont = trim(file_get_contents(DIR.$fname));
            if ($oldcont === "") // je li ovo prvi post u temi? (a === jer je u pocetku samo id 0)
            {
                $numbers = array();
                $numbers[] = 0;
                $newid = 0;                
            }
            else
            {
                $numbers = explode("\n", $oldcont);
                $newid = $numbers[count($numbers) - 1] + 1; // iduci slobodni id
                $numbers[] = $newid;
            }
            $fh = fopen("data/post_$req_topic"."_$newid.txt", "x");
            
            fwrite($fh, ocisti_string($_POST['post_title'])."\n".$usr."\n".
                ocisti_string($_POST['post_content'])
                // redom onemogucavanje tagova (zbog js i sl.), \n => \n<br />, brisanje \n
            );
            fclose($fh);
            
            $fh = file_or_die($fname, "w");
            fwrite($fh, implode("\n", $numbers));
            fclose($fh);
        }
}

 
 // popis tema sa servera za lijevi stupac, te odmah izvlacenje postova za trenutnu temu
 $teme = array();  $teme_titles = array();
 
 // odgovori trenutne teme, formatirani
 $odgovori = array();
$fh = read_file_or_die("topics.txt");
while (($tema = fgets($fh, 4096)) !== false) 
{   
    $tema = trim($tema);
    if (!is_numeric($tema))
            break;
    
    $tema_title = '';
    
    $preostali = array(); // ako brisemo
    
    $fh2 = read_file_or_die('topic_'.$tema.'.txt');

    while (($post = fgets($fh2, 4096)) !== false) 
    {
        $post_id = trim($post);
        if (!is_numeric($post_id))
            break;
            
        if ($goal == 'del' && $dop_post_del && $req_topic == $tema && $req_post == $post_id)
        {
            if (!unlink("data/post_".$tema."_".$post_id.".txt" ))
                echo '<script>alert("Neuspjesno brisanje, provjerite dopustenja /data!");</script>';
            continue;
        }
        
        
        $preostali[] = $post_id;
        
        $fh3 = read_file_or_die("post_".$tema."_".$post_id.".txt" );
        
        $ime_posta = trim(fgets($fh3, 4096));
        $id_autor = trim(fgets($fh3, 4096));
        $post_content = trim(fgets($fh3, 4096));    
        fclose($fh3);

        if ($goal == 'edit' && $req_topic == $tema && $req_post == $post_id)
        {
           $edit_post_title = $ime_posta;
           $edit_post_content = vrati_nl($post_content);
        }
    
        if ($goal == 'post_edit' && ($dop_post_del || $id_autor == $usr) && $req_topic == $tema && $req_post == $post_id)
        {
            $fh3 = fopen("data/post_$tema"."_$post_id.txt", "w");
            $ime_posta = ocisti_string($_POST['post_title']); 
            $post_content = ocisti_string($_POST['post_content']);
            fwrite($fh3, $ime_posta."\n".$id_autor."\n".$post_content);
            fclose($fh3);
        }         
        
        if ($dop_post_del)
            $del_string = '[<a href="?del&tid='.$tema.'&pid='.$post_id.'">x</a>]';
        else
            $del_string = "";
            
        if ($id_autor == $usr || $dop_post_del)
            $edit_string = '[<a href="?edit&tid='.$tema.'&pid='.$post_id.'">edit</a>]';
        else
            $edit_string = '';
        
        if ($req_topic == $tema)
            $odgovori[] = "<b><i>$usrs_names[$id_autor]</i>: $ime_posta</b> $del_string $edit_string<br />$post_content";

        if ($tema_title == '') // nije jos postavljen naziv teme, ovo je prvi post 
        {
            $tema_title = $ime_posta;
            if ($req_topic != $tema)
            {            // ako nije trazena tema, ostali postovi nebitni:
                break;
            } else $tema_ttl = $ime_posta;

        }   
    }

    fclose($fh2);

    if(!empty($preostali)) // je li ostao ijedan post? (ako je, srediti popis postova, inace skroz obrisati file)
    {
        //echo $tema."*";
        $teme[] = $tema;
        $teme_titles[] = $tema_title;    
        
        if ($goal == 'del' &&  $req_topic == $tema ) // ponovno zapise postove, ako je bilo brisanja
        {
            $tfname = 'topic_'.$tema.'.txt';
            if (check_file($tfname)) 
            {
                //echo "ponovno pisem ".$tfname;
                $fh3 = file_or_die($tfname, "w");
                fwrite($fh3, implode("\n", $preostali));
                fclose($fh3);
            }
        }
    }
    else
    {
        unlink('data/topic_'.$tema.'.txt');
    }
}

fclose($fh);


if ($goal == 'del' ) // ponovno zapise teme, ako je bilo brisanja
{
    $tfname = "topics.txt";
    if (check_file($tfname)) 
    {
        //echo "ponovno pisem ".$tfname;
        $fh = file_or_die($tfname, "w");
        fwrite($fh, implode("\n", $teme));
        fclose($fh);
    }
    $req_topic = -1;
    $goal = 'reply';
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
          <td align="right" valign="top" width="150px"><b>topics:</b><br />
          <?php
            for ($i = 0; $i < count($teme); ++ $i)
            {
                echo '<a href="?tid='.$teme[$i].'">'.$teme_titles[$i].'</a><br />' ;
           }
        //readme<br />
          ?>
            
            
<?php if ($dop_topic_add) { ?>
            [<a href="?tid=-1">add</a>]<br />
<?php } ?>

            <br />
            
            <b>users:</b><br />
          <?php
            for ($i = 0; $i < count($usrs); ++ $i)
            {
                echo $usrs_names[$i].'<br />' ;
           }
        //readme<br />
          ?>
            
          </td>
          <td style="border-left: 2px solid gray; border-right: 2px
            solid gray;" align="left" valign="top">
            
            <?php
            foreach ($odgovori as $odgovor)
                {
                    echo $odgovor;
                    if ($odgovor != $odgovori[count($odgovori) - 1]) 
                        echo '<hr />'; 
                }
            
            if ($dop_post_add) {
            ?>
            <hr>
            
            <?php
            
            if ($goal == 'edit')
            {
                 echo '<form align="center" action="?post_edit&tid='.$req_topic.'&pid='.$req_post.'"'.' method="post">'; 
                 echo 'Edit post: <br /><input name="post_title" type="text" size="30" style="border-style: solid; border-width: 1px;" value = "'
                 .$edit_post_title.'" />'; 
            }
            else
            {            
                echo '<form align="center" action="?create&tid='.$req_topic.'"'.' method="post">'; 
                 if ($req_topic >= 0) echo 'Odgovor: '; else echo 'Start new topic:'; 
                 echo '<br /><input name="post_title" type="text" size="30" style="border-style: solid; border-width: 1px;" value = "';
                 if ($req_topic >= 0) echo 'RE: '.$tema_ttl; echo '" />'; 
            } 
             ?>
            
            <br />
            <textarea name="post_content" cols="40" rows="6"><?php echo $edit_post_content; ?></textarea> <br />
            [<button type="submit" style="margin:0;padding: 0;border: none;color: blue;background-color: transparent;">post</button>]
            </form>
            <?php } ?>
            
            <br />
          </td>
          <td align="left" valign="top" width="220px" ><b>session info:</b><br />
            user: <?php if ($usr == -1) echo 'guest'; else echo $usrs_names[$usr]; ?> 
            
            <span id="chng_btn"> [<a href="javascript:tog_usr_change()">switch user</a>]<br /></span>
            
            <form id="usr" style="display: none;"  action="?switch&tid=<?php echo $req_topic; ?>" method="post">
            <b>username:</b>  <br /> <input type="text" name="usr" size="15"> <br />
            <b>password:</b>  <br /><input type="password" name="psw" size="15"> <br />
            [<input type="submit" style="margin:0;padding: 0;border: none;color: blue;background-color: transparent;" name="login" value="login" >]
            [<a style="text-decoration:none;color: blue;font-size: small;" href="register.php">register</a>]
        
            </form>
            

             <?php if ($usr != -1) echo 'since: '.$_SESSION['stamp'].'<br /> '; ?>
            <b>user info:</b><br />
            permissions: <?php echo $perm_status; ?><br />
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
