<?php

if (!file_exists ('config.php')){

  session_start();  

  if ($_POST['app_key'] && $_POST['app_secret']){
    $url  = 'https://api.dropbox.com/1/oauth/request_token';
    $ch   = curl_init();


    curl_setopt($ch, CURLOPT_URL, $url);  
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_HTTPHEADER,array(
        'Authorization: OAuth oauth_version="1.0", oauth_signature_method="PLAINTEXT", oauth_consumer_key="'.$_POST['app_key'].'", oauth_signature="'.$_POST['app_secret'].'&"',
        'Content-Length: 0'
      )
    );

    $response = curl_exec($ch);
    parse_str($response,$response);

    $oauth_token_secret = $response['oauth_token_secret'];
    $oauth_token        = $response['oauth_token']; 

    $_SESSION['app_key']              = $_POST['app_key'];
    $_SESSION['app_secret']           = $_POST['app_secret'];
    $_SESSION['request_token_secret'] = $oauth_token_secret;
    $_SESSION['request_token']        = $oauth_token;
    $_SESSION['root']                 = $_POST['root'];
    $_SESSION['base']                 = $_SERVER['REQUEST_URI'];

    curl_close($ch);

    header('Location: https://www.dropbox.com/1/oauth/authorize?oauth_token='.$oauth_token.'&oauth_callback='.$_SERVER['HTTP_REFERER']);

  } else if ($_GET['uid'] && $_GET['oauth_token']){
    $url  = 'https://api.dropbox.com/1/oauth/access_token';
    $ch   = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);  
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER,array(
        'Authorization: OAuth oauth_version="1.0", oauth_signature_method="PLAINTEXT", oauth_consumer_key="'.$_SESSION['app_key'].'", oauth_token="'.$_GET['oauth_token'].'", oauth_signature="'.$_SESSION['app_secret'].'&'.$_SESSION['request_token_secret'].'"',
        'Content-Length: 0'
      )
    );

    $response = curl_exec($ch);
    parse_str($response,$response);

    file_put_contents('config.php',"<?php\n  \$app_key = \"".$_SESSION['app_key']."\";\n  \$app_secret = \"".$_SESSION['app_secret']."\";\n  \$access_token = \"".$response['oauth_token']."\";\n  \$access_token_secret = \"".$response['oauth_token_secret']."\";\n  \$root = \"".$_SESSION['root']."\";\n?>");
    print_r($_SERVER);
    header('Location: '.$_SESSION['base']);
      

  } else {
    include('setup.php');
  }

} else {
  
  include('config.php');

  $uri = $_SERVER['PATH_INFO'];
  if ( substr( $uri, -1 ) == '/' ){
    $uri .= 'index.html';
  }

  # added in case no trailing slash, to make Dropbox work more like web server
  if ( $uri  == '' ){
    $uri .= '/index.html';
  }
    
  $url  = 'https://api-content.dropbox.com/1/files/'.$root.$uri;

  $ch   = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url); 
  curl_setopt($ch, CURLOPT_HEADER,true);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER,array(
    'Authorization: OAuth oauth_version="1.0", oauth_signature_method="PLAINTEXT", oauth_consumer_key="'.$app_key.'", oauth_token="'.$access_token.'", oauth_signature="'.$app_secret.'&'.$access_token_secret.'"',
    )
  );

  $response = curl_exec($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  if($code == 404) {
    header('HTTP/1.0 404 Not Found');
    echo "404 - Not found";
  } else {
    list($header, $body) = explode("\r\n\r\n", $response, 2);
    $headers = explode("\n",$header);
    foreach($headers as $h){
      # conditions inserted to avoid passing on Dropbox ascii charset in Content-Type in HTTP header
      # (improvement over following hack because it only touches charset and not MIME type
      if ( stripos( $h, 'charset=' ) ) {
        if (stripos( $h, 'ascii' ) ) {
            $h = str_replace( 'us-ascii', 'UTF-8' , $h );
            $h = str_replace( 'ascii', 'UTF-8' , $h );
            $h = str_replace( 'US-ASCII', 'UTF-8' , $h );
            $h = str_replace( 'ASCII', 'UTF-8' , $h );
        }
      }
      # New hack to circumvent the problem with Dropbox not passing on Last-Modified
      # by pulling it out of modified field in x-dropbox-metadata header instead
      # Note that although the header is actually called 'x-dropbox-metadata' this returns 0 because the 'x' is the first character
      if ( stripos( $label, 'dropbox-metadata' ) ) {
        $x_dropbox_metadata = explode('"', $h);
        $key = array_search('modified', $x_dropbox_metadata);
        $datemod = $x_dropbox_metadata[$key + 2];
        header('Last-Modified: ' . $datemod);
      }
      header($h);
    }
    # Hack to avoid passing on Dropbox ascii charset in Content-Type in HTTP header
    # (deprecated: see above replacement conditions)
    #header('Content-Type: text/html;charset=UTF-8');
  }
  echo($body);
}
?>
