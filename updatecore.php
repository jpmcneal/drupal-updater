#!/usr/bin/env php

<?php
// 2019-05-21
// >= PHP 5.1.0
// Drupal core 8.7.1
// Drupal core 7.67

// This allows user to pass Drupal Version via CLI
// var_dump($argv[1]);

// die();

$version = $argv[1];

// Usage: echo $version['version'];
// Return: 7.79

// @todo Ask user for major version (7 or 8)
// @todo Ask user for latest or specific version



// Found this helper function on https://stackoverflow.com/questions/21439239/download-latest-github-release
// to fetch the tags from Drupal's Github mirror at https://github.com/drupal/drupal
// Once we have the tags, we can download specific version from there or ftp.drupal.org

// function getLatestTagUrl($repository, $default = 'master') {
//     $file = @json_decode(@file_get_contents("https://api.github.com/repos/$repository/tags", false,
//         stream_context_create(['http' => ['header' => "User-Agent: Vestibulum\r\n"]])
//     ));

//     return sprintf("https://github.com/$repository/archive/%s.zip", $file ? reset($file)->name : $default);
// }

// Usage: getLatestTagUrl('drupal/drupal');

//Setting
// @TODO: Get tarball
$latest_drupal8_url="https://ftp.drupal.org/files/projects/drupal-$version.zip";
$folders_to_copy_v8=[
  'sites/default/files',
  'core/themes',
  'modules'
];
$files_to_copy_v8=[
  'sites/default/settings.php',
  '.htaccess',
  'robots.txt',
  'web.config',
  //'composer.lock',
  'composer.json'
];
// @TODO: Get tarball
$latest_drupal7_url="https://ftp.drupal.org/files/projects/drupal-$version.zip";
$folders_to_copy_v7=[
  'sites/default/files',
  'sites/all',
];
$files_to_copy_v7=[
  'sites/default/settings.php',
  '.htaccess',
  'robots.txt',
  'web.config',
  //'composer.lock',
  'composer.json'
];

///////Auto detect version
$drupal_version=v_auto_detect();
$webrootname=basename(__DIR__);
$backup_filename='../'.$webrootname.'_'.date('Ymd_His');
if($drupal_version!=8 && $drupal_version!=7){
  echo 'Drupal version is not supported'.'<br/>';
  exit();
}

///////download drupal core
if($drupal_version==8)
  $latest_drupal_url=$latest_drupal8_url;
else if($drupal_version==7)
  $latest_drupal_url=$latest_drupal7_url;
$target_path='../latest_drupal_core.zip';
if(!downloadFile($latest_drupal_url,$target_path))
{
  echo 'Error while downloading Drupal core file'.'<br/>';
  exit();
}
if(!file_exists($target_path))
{
  echo 'Error while downloading Drupal core file. Please check whether the parent folder is writeable.'.'<br/>';
  exit();
}

///////unzip
$unzip_path='../latest_drupal_core';
if(!unzip($unzip_path,$target_path))
  exit();
@unlink($target_path);
$unzipped_path=$unzip_path.'/'.preg_replace('/\\.[^.\\s]{3,4}$/', '', basename($latest_drupal_url));

///////copy necessary files and folders
if($drupal_version==8)
{
  $files_to_copy=$files_to_copy_v8;
  $folders_to_copy=$folders_to_copy_v8;
}
elseif($drupal_version==7)
{
  $files_to_copy=$files_to_copy_v7;
  $folders_to_copy=$folders_to_copy_v7;
}

//////find another files
$sourceDirList=[];
if ($handle = opendir($unzipped_path)) {
    while (false !== ($entry = readdir($handle))) {
        if ($entry != "." && $entry != "..") {
            $sourceDirList[]=$entry;
        }
    }
    closedir($handle);
}
if ($handle = opendir('.')) {
    while (false !== ($entry = readdir($handle))) {
        if ($entry != "." && $entry != "..") {
            if(!in_array($entry,$sourceDirList) && $entry!=basename(__FILE__) && pathinfo($entry, PATHINFO_EXTENSION)!='swp')
            {
              if(is_dir($entry))
              {
                $folders_to_copy[]=$entry;
              }
              else{
                $files_to_copy[]=$entry;
              }
            }
        }
    }
    closedir($handle);
}


foreach ($files_to_copy as $file) {
  if(file_exists($file) && !copy($file,$unzipped_path.'/'.$file))
  {
    echo 'There was a problem while copying '.$file.' to '.$unzipped_path.'/'.$file.'<br/>';
    exit();
  }
}
foreach ($folders_to_copy as $folder) {
  full_copy($folder,$unzipped_path.'/'.$folder);
}

///////// make a backup file
if(!rename('../'.$webrootname,$backup_filename))
{
  echo 'Error: Cannot create backup file'.'<br/>';
  exit();
}

///////// update completed, move new version to webroot
if(!rename($unzipped_path,'../'.$webrootname))
{
  //rollback
  if(!rename($backup_filename,'../'.$webrootname))
  {
    echo 'Error: Cannot move files to webroot'.'<br/>';
  }
  else
    echo 'Critical error!!! Cannot move files to webroot, please recover the webroot folder manually.'.'<br/>';
  exit();
}

//////////End
rmdir($unzip_path);
echo 'Update core finished'.'<br/>';
echo 'Go to <a href="/update.php">update.php</a><br/>';
echo 'You may also need to run "composer update --with-dependencies" in the terminal.<br/>';
exit();

////////////////////////////////////////////////////////////////////////////////////////////////
function v_auto_detect(){
  if(file_exists('sites/all/themes'))
  {
    echo 'Detected Version: 7<br/>';
    return 7;
  }
  else if(file_exists('core'))
  {
    echo 'Detected Version: 8<br/>';
    return 8;
  }

  return false;
}

function unzip($outpath,$zippath)
{
  $unzip = new ZipArchive;
  $out = $unzip->open($zippath);
  if ($out === TRUE) {
    $unzip->extractTo($outpath);
    $unzip->close();
    return true;
  } else {
    echo 'Error while unzipping the file.'.PHP_EOL;
    return false;
  }
}
function downloadFile($url, $path)
{
    $newfname = $path;
    $file = fopen ($url, 'rb');
    if ($file) {
        @unlink($newfname);
        $newf = fopen ($newfname, 'wb');
        if ($newf) {
            while(!feof($file)) {
                if(!fwrite($newf, fread($file, 1024 * 8), 1024 * 8))
                {
                  echo 'Cannot write to the path '.$path.'<br/>';
                }
            }
        }
        else {
          echo 'Cannot create the path '.$path.'<br/>';
        }
    }
    else{
      return false;
    }

    if ($file) {
        fclose($file);
    }
    if ($newf) {
        fclose($newf);
    }
    return true;
}
function full_copy( $source, $target ) {
    if ( is_dir( $source ) ) {
        @mkdir( $target );
        $d = dir( $source );
        while ( FALSE !== ( $entry = $d->read() ) ) {
            if ( $entry == '.' || $entry == '..' ) {
                continue;
            }
            $Entry = $source . '/' . $entry;
            if ( is_dir( $Entry ) ) {
                full_copy( $Entry, $target . '/' . $entry );
                continue;
            }
            copy( $Entry, $target . '/' . $entry );
        }

        $d->close();
    }else {
        copy( $source, $target );
    }
}
?>
