<?php
error_reporting(E_ALL & ~E_NOTICE);
$sound_name = end(explode("/",parse_url($_GET['url'], PHP_URL_PATH)));
$client_id = ""; // YOU CLIENT ID
$secret_key = ""; // YOU SECRET KEY
$access_token = getAccessToken($client_id, $secret_key);
$track_id = getTrack($sound_name, $access_token);
$stream_url = getStreamUrl($track_id, $secret_key, $access_token);
$outputFilename = $sound_name.'.mp3'; // Name of the output file

if (downloadSoundcloudTrack($stream_url, $outputFilename, $secret_key, $access_token)) {
  echo "The download is complete!";
} else {
  echo "An error occurred while downloading.";
}

function getAccessToken($client_id, $secret_key){
  $ch = curl_init('https://secure.soundcloud.com/oauth/token');
  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json, text/plain, */*',
                                                 'Content-length: '.strlen('grant_type=client_credentials'),
                                                 'Content-type: application/x-www-form-urlencoded',
                                                 'Authorization: Basic '.base64_encode("$client_id:$secret_key")));
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials'); 
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch, CURLOPT_HEADER, false);
  $res = curl_exec($ch);
  curl_close($ch);
  $res = json_decode($res, true);
  return $res['access_token'];
}

function getTrack($sound_name, $access_token){
  $ch = curl_init('https://api.soundcloud.com/tracks?q='.$sound_name.'&access=playable%2Cpreview%2Cblocked&limit=1&linked_partitioning=false');
  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json; charset=utf-8',
                                             'Authorization: Bearer '.$access_token));
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch, CURLOPT_HEADER, false);
  $html = curl_exec($ch);
  curl_close($ch);
  $array = json_decode($html, true);
  return $array['collection'][0]['id'];
}

function getStreamUrl($track_id, $secret_key, $access_token){
  $ch = curl_init('https://api.soundcloud.com/tracks/'.$track_id.'/streams?secret_token='.$secret_key);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array('accept: application/json; charset=utf-8',
                                             'Authorization: Bearer '.$access_token));
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch, CURLOPT_HEADER, false);
  $result = curl_exec($ch);
  curl_close($ch);
  $res = json_decode($result, true);
  return $res['http_mp3_128_url'];
}

function downloadSoundcloudTrack($trackUrl, $outputFile, $secret_key, $access_token) {
  $streamUrl = $trackUrl . '?secret_token='.$secret_key;
  $command = 'ffmpeg -headers "Authorization: Bearer '.$access_token.'" -i "' . $streamUrl . '" -vn -acodec libmp3lame -ab 192k -y "' . $outputFile . '" 2>&1';
  exec($command, $output, $returnCode);
  if ($returnCode !== 0) {
    echo "Error when downloading a track: " . PHP_EOL;
    foreach ($output as $line) {
      echo $line . PHP_EOL;
    }
    return false;
  }
  return true;
}
?>
