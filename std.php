<?php

 // funkcije
 
 const DIR = 'data/';
 
 function ocisti_string($w)
 {
     // redom onemogucavanje tagova (zbog JS i sl.), \n => \n<br />, brisanje \n
     return str_replace(array("\n", "\r"), '', trim(nl2br(strip_tags($w))));
}

 function vrati_nl($w)
 {
    $breaks = array("<br />","<br>","<br/>");  
     return str_ireplace($breaks, "\r\n", $w);  
 }

 function check_file_r($path)
 {
     $path = DIR.$path;
     if (file_exists($path))  if (is_readable($path))  return true;
    return false;
}

function check_file($path)
 {
     $path = DIR.$path;
     if (file_exists($path))  if (is_readable($path))  if ( is_writable($path))  return true;
    return false;
}

 function read_file_or_die($path) // za datoteke koje se ne mogu otvoriti, nema smisla dalje izvrsavat
 {
     if (check_file_r($path)) return fopen(DIR.$path, "r");
     echo "problem while loading forum data or corrupted data <br />";
     debug_print_backtrace();
     exit(-1); 
}

function file_or_die($path, $how) // za datoteke koje traze i citanje i pisanje
 {
     if (check_file($path)) return fopen(DIR.$path, $how);
     echo "problem while loading forum data or corrupted data <br />";
     debug_print_backtrace();
     exit(-1); 
}

// svaki korisnik je tuple (id, ime, pass, dopustenja)
function load_users(&$usrs , &$usrs_names, &$usrs_passes, &$usrs_perms)
{
    $fh = read_file_or_die("usr.txt");
    while (($usr = fgets($fh, 4096)) !== false) 
    {   
        $usr_id = trim($usr);
        if (!is_numeric($usr_id))
                break;
                
        $usr_name = trim(fgets($fh, 4096));
        $usr_pass = trim(fgets($fh, 4096)); // pass
        $usr_perms = explode(' ', trim(fgets($fh, 4096)));
        
        $usrs[] = $usr_id;
        $usrs_names[] = $usr_name;
        $usrs_passes[] = $usr_pass;
        $usrs_perms[] = $usr_perms;
    }
    fclose($fh);
}


// ucitavanje tema nije ovdje jer je potrebno samo u index.php, 
// i jer je efikasnije tom prilikom i uredivanja primijeniti koja drugdje nemaju smisla

?>