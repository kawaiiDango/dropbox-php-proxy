<?php
error_reporting( E_ALL );
ini_set( 'display_errors', TRUE );
ini_set( 'log_errors',     TRUE );

if ( !isset($_COOKIE['a']) || $_COOKIE['a'] != '741852963')
	die('lol who do u think u are, huh?');

date_default_timezone_set ('Asia/Kolkata');

$baseURI = dirname($_SERVER['SCRIPT_NAME']);
require('config.php');

function human_filesize($bytes, $decimals = 2) {
    $size = array('B','K','M','G','TB','PB','EB','ZB','YB');
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
}

function human_time($timeStr) {
    $time = strtotime($timeStr);
    return date('H:i:s, d-m-y', $time);
}

function curlit($url, $postData=false){
  global $code, $authHeaders;
  $headers = $authHeaders;
  //echo $url."<br>";
  $ch   = curl_init();

  if($postData){

  	if (strpos($postData, '/') === 0){
  		$fp = fopen($postData, 'rb');
		curl_setopt($ch, CURLOPT_INFILE, $fp);
		curl_setopt($ch, CURLOPT_INFILESIZE, filesize($postData));

  		array_push($headers, "Content-Type: application/octet-stream");
  		curl_setopt($ch, CURLOPT_PUT, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
  	}
  	else{
  		curl_setopt($ch, CURLOPT_POST, 1);
	  	array_push($headers, "Content-Type: application/json");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
  	}
  }

  curl_setopt($ch, CURLOPT_URL, $url); 
  curl_setopt($ch, CURLOPT_HEADER,true);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  $response = curl_exec($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  return $response;
}

if (!file_exists ('config.php')){

  die("config.php doesnt exist");

} else if(isset($_GET['setup'])){
	$url = "https://www.dropbox.com/oauth2/authorize?client_id={$app_key}&response_type=token&redirect_uri=https://a3264193.000webhostapp.com{$baseURI}";
	header('Location: '.$url);
} else {
  $uri = "";
  if (isset($_SERVER['PATH_INFO']))
    $uri = $_SERVER['PATH_INFO'];
  if ( substr( $uri, -1 ) == '/' ){
    $uri .= 'index.html';
  }

$authHeaders = array(
    "Authorization: Bearer {$access_token}",
    );

 if(isset($_FILES['f'])){
 	print_r( $_FILES['f']['name'][0]);
 	for ($i=0; $i < count($_FILES['f']['error']); $i++){
 		if ($_FILES['f']['error'][$i] === 0){

 			$url  = "https://content.dropboxapi.com/2/files/upload?arg=".
  			urlencode('{"path": "'.$root.$uri.'/'.$_FILES['f']['name'][$i].'",
"mode": "overwrite",
"autorename": true,
"mute": false}');
  			$postData = $_FILES['f']['tmp_name'][$i];
 //'extra_info' => 'abcd');//, 'name' => '123456',
  			$response = curlit($url, $postData);
  			if ($code == 200)
  				echo "uploaded ".$_FILES['f']['name'][$i]. "<BR>";
  			else
	  			print_r($response);
  			
 		}
	}
	echo "<hr>";
  
	}
  $url  = 'https://content.dropboxapi.com/2/files/download?arg='.
  	urlencode("{\"path\": \"".$root.$uri."\"}");
  $response = curlit($url);



  list($header, $body) = explode("\r\n\r\n", $response, 2);

  if($code == 409 || $code == 400) {
  	// path not found
  	$err = json_decode( $body, true )['error_summary'];
  	if ($err && $err == "path/not_found/"){
  		header('HTTP/1.0 404 Not Found');
      	die( "404 - Not found");
  	}


    $url  = 'https://api.dropboxapi.com/2/files/list_folder';
    $postData = '{
	    "path": "'.$root.$uri.'",
	    "recursive": false,
	    "include_media_info": false,
	    "include_deleted": false,
	    "include_has_explicit_shared_members": false
	}';
    $response = curlit($url, $postData);
    list($header, $body) = explode("\r\n\r\n", $response, 2);
    $files = json_decode( $body, true )['entries'];
    ?>
    <html>
    	<body bgcolor=black text=lime link=yellow alink=white vlink=orange>
	    	<script>
	            function more(){
	                var ele= document.createElement('input');
	            ele.type="file";
	            ele.name="f[]";
	            ele.setAttribute("onchange","more(this.value)");
	            
	document.getElementById('fl').prepend(ele);
	//document.getElementById('fl').appendChild(document.createElement('br'));

	            }
	        </script>
	        <form method="POST" enctype="multipart/form-data">
	            <div id="fl">
	                <input type="file" name="f[]" onchange="more()" > 
	                <noscript>
                       <BR> <input type="file" name="f[]" >
                       <BR>  <input type="file" name="f[]" >
                       <BR> <input type="file" name="f[]" > <BR>
	                </noscript>
	                <input type="submit" value=" ^|^ " style="width:100px" >
	        </div>
	        </form>
    		<table border=1 cellpadding=10>
<?php
    foreach ($files as $key => $val) { 
      echo "<tr> ";
      $path = substr ($val['path_display'], strpos($val['path_display'], '/', 1));
      $path = $baseURI. $path;

      echo "<td><a href =\"{$path}\" > ";
      if ($val['.tag'] == "file")
      	echo $val['name'];
      else
      	echo "<b>{$val['name']}</b>";

      echo "</a></td>";
      
      if ($val['.tag'] == "file"){
      	echo "<td> <i>";
        echo human_filesize($val['size']);
      	echo "</i></td> ";
	  	echo "<td> <small>" . human_time($val['client_modified']) . "</small></td> ";
	  }
      echo "</tr> \n";
      
    }
    echo "</table>";
    
  } else {
    list($header, $body) = explode("\r\n\r\n", $response, 2);
    $headers = explode("\n",$header);
    foreach($headers as $h){
		header($h);
    }
    header('Content-Disposition: attachment;');
    echo($body);
  }
  
  echo "<br> Upload limit: " .ini_get('upload_max_filesize');
}
?>