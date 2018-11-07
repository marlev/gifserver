<?php

/*
1. Need webserver with php
2. Need ffmpeg installed, I recommend version 2+ ($brew install ffmpeg, if you have Homebrew installed)
3. Need ImageMagic installed with php module enbaled as well. ($brew reinstall php55-imagick, where php55 is your PHP version.)
4. Apace/webserver? Make sure it can run cmd: http://stackoverflow.com/questions/21610417/osx-apache-allow-execution-of-shell-command-for-php-script-include-path

Errors?
- Test the chell commands from php straight in your terminal
- Check permissions. On OSX you can let the "_www" user that belongs to group "staff" get access by running: $sudo chown _www:staff gifserver
*/



error_reporting(E_ERROR | E_PARSE); //turn off php error reporting
$testVideo = "http://levy.se/test.mp4"; //test video url
$baseUrl = strtok("http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'],'?'); //the url of this site
$ffmpeg = 'ffmpeg'; // where ffmpeg is located, such as /usr/sbin/ffmpeg
$video = null; //insert a path to video here
$html = null; //will hold the html output



$html .= '<a href="https://github.com/marlev/gifserver"><img style="position: absolute; top: 0; right: 0; border: 0;" src="https://camo.githubusercontent.com/e7bbb0521b397edbd5fe43e7f760759336b5e05f/68747470733a2f2f73332e616d617a6f6e6177732e636f6d2f6769746875622f726962626f6e732f666f726b6d655f72696768745f677265656e5f3030373230302e706e67" alt="Fork me on GitHub" data-canonical-src="https://s3.amazonaws.com/github/ribbons/forkme_right_green_007200.png"></a>';


$html .= "Hello. I convert videos to gif animations";
$html .= "<br>";
$html .= "<b>1. Give me a video</b> - Add your video url <a href='".$baseUrl."?video=".$testVideo."'>".$baseUrl."?video=".$testVideo."</a>";
$html .= "<br>";
$html .= "<b>2. json response</b> - Append &format=json <a href='".$baseUrl."?video=".$testVideo."&format=json'>".$baseUrl."?video=".$testVideo."&format=json</a>";
$html .= "<br>";
$html .= "<b>3. file response</b> - Append &format=file <a href='".$baseUrl."?video=".$testVideo."&format=file'>".$baseUrl."?video=".$testVideo."&format=file</a>";
$html .= "<br>";
$html .= "<small>* The animation will have 9 frames in a loop to reprecent the complete video</small>";
$html .= "<br>";
$html .= "<br>";


//create the filename from url
function fileName($url) {
  $url = preg_replace('#^https?://#', '', rtrim($url,'/')); //remove "http(s)://" from url
  //md5, base64, urlencode and then return first 8 chars
  $url = strtr(base64_encode($url), '+/=', '-_,');
  return substr( urlencode(md5($url)), 0,8);
}

//if we got a url to video
if($_GET['video']) {
  $video = $_GET['video']; //or request this with url?video=http://your-url-to-video
  $url=strtok($_GET[video],'?');
  $fileName = fileName($video); //retruns something like "e9edce95"
  $urlPath = parse_url($video);
  $origFileExt = explode(".", $urlPath['path']); //retruns something like "mp4"
  $origFileExt = $origFileExt[1];
  //If we already have the animation on disc
  if(file_exists($fileName.".gif")) $gifExist = $fileName.".gif";
}


/*
Creates a new directory with 744 permissions if it does not exist yet
Owner will be the user/group the PHP script is run under
The directory name will be "yymmdd_hhmmss-filename".
The video will download to this dir, then ffmpeg will extract 9 images and imagemagick will create the gif
*/
$imageTmpDir = date('ymd_His',time())."-".$fileName;
if ( !file_exists($imageTmpDir) ) {
  if( ($video) && !($gifExist) ) mkdir ($imageTmpDir, 0744); //only create if we got a video and the gif dosent already exist
 }

//If we get a url to a video, we should download and use it
if( ($_GET['video']) && !($gifExist) ) {
  file_put_contents($imageTmpDir.'/'.$fileName, fopen($_GET['video'], 'r'));
  $video = $imageTmpDir.'/'.$fileName;
}


/* gets the data from a URL */
function get_contents($url) {
  $ch = curl_init();
  $timeout = 5;
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch,CURLOPT_ENCODING , "gzip");
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
  $data = curl_exec($ch);
  curl_close($ch);
  return $data;
}


function durationTimeStamp($seconds) {
  $t = round($seconds);
  return sprintf('%02d:%02d:%02d', ($t/3600),($t/60%60), $t%60);
}


function deleteDir($path) {
    return !empty($path) && is_file($path) ?
        @unlink($path) :
        (array_reduce(glob($path.'/*'), function ($r, $i) { return $r && deleteDir($i); }, TRUE)) && @rmdir($path);
}

if( ($video) && !($gifExist) ) {
  // get duration from ffmpeg/video
  $ffmpegCmdDuration = $ffmpeg." -i ".$video." 2>&1 | grep 'Duration' | cut -d ' ' -f 4 | sed s/,//";
  $duration = explode(":",shell_exec($ffmpegCmdDuration));
  $durationInSeconds = $duration[0]*3600 + $duration[1]*60+ round($duration[2]);

  $html = "<b>creating tmp dir: </b>";
  $html .= date('ymd_His',time())."-".time();
  $html .= "<br>";

  //getting 9 images upto 90%
  for ($i=1; $i < 10; $i++) {
    $timeStamp = durationTimeStamp($durationInSeconds*($i/10));
    $ffmpegCmdExtract = $ffmpeg." -ss ".$timeStamp." -i ".$video." -vframes 1 ".$imageTmpDir."/".$i.".png 2>&1";
    shell_exec($ffmpegCmdExtract);

    if($i == 1) $html .= "<b>capturing frames:</b> ";
    $html .= $i."0% ";
  }

  //Creating gif animation
  $gifFileName = ($fileName) ? $fileName.".gif" : $imageTmpDir.".gif";
  $convertCmdCreate = "convert -delay 50 -loop 0 -dither none -matte -depth 8 -deconstruct -layers optimizePlus -resize 50% $imageTmpDir/*.png $gifFileName 2>&1";
  shell_exec($convertCmdCreate);
  $html .= "<br>";
  $html .= "<b>creating animation: </b> <a href='".$gifFileName."'>".$gifFileName."</a>";
  $html .= " <b>".round((filesize($gifFileName) / 1024), 0)." KB</b>";


  //removing imageTmpDir
  deleteDir($imageTmpDir);
  $html .= "<br>";
  $html .= "<b>removing directory/files: </b> ".$imageTmpDir;



} //end if($video)

if( ($video) || ($gifExist) ) {
 //Presenting our animation
  if($gifExist) {
    $html .= "The animation already existed on disc: <a href='".$gifExist."'>".$gifExist."</a>";
    $html .= " <b>".round((filesize($gifExist) / 1024), 0)." KB</b>";
    $gifFileName = $gifExist;
  }
  $html .= "<br>";
  $html .= "<br>";
  $html .= "<img src='".$gifFileName."' width='50%'>";
  $html .= "<br>";
  $html .= "<br>";

}

//HTML or JSON or FILE
if($_GET['format'] === "file") {
  $completeUrl = "http://".$_SERVER[HTTP_HOST].strtok($_SERVER['REQUEST_URI'], '?').$gifFileName;
  //echo $completeUrl;
  //echo strtok($_SERVER['REQUEST_URI'], '?');
  //header($completeUrl); /* Redirect browser */
  header("Location: $completeUrl");
  exit();
}

if($_GET['format'] === "json") {
  $completeUrl = strtok("http://".$_SERVER[HTTP_HOST].$_SERVER[REQUEST_URI],'?').$gifFileName;
  if( ($_GET['client']) && ($_GET['project']) ) {
    $completeUrl .= "?project_id=".$_GET['client']."/".$_GET['project'];
  }

  $json = json_encode(array("url" => $completeUrl));
  header('Content-Type: text/javascript');
  header('Cache-Control: no-cache');
  header('Age: 0');
  print($json);
}


else {
  echo $html;
}


?>