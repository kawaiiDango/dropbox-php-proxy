<?php

if (!file_exists ('config.php')){

  session_start();  

  if ($_POST['app_key'] && $_POST['app_secret']){

    //$data = array('data' => 'this', 'data2' => 'that');
    //$data = http_build_query($data);
    $context =
        array('http'=>
          array(
            'method' => "post",
            'header' => 'Authorization: OAuth oauth_version="1.0", oauth_signature_method="PLAINTEXT", oauth_consumer_key="'.$_POST['app_key'].'", oauth_signature="'.$_POST['app_secret'].'&"\r\n' .
                        'Content-Length: 0\r\n' /**,
            'content' => $data **/
          )
        );
    $context = stream_context_create($context);
    $response = file_get_contents("https://api.dropbox.com/1/oauth/request_token", false, $context);
    parse_str($response,$response);

    $oauth_token_secret = $response['oauth_token_secret'];
    $oauth_token        = $response['oauth_token']; 

    $_SESSION['app_key']              = $_POST['app_key'];
    $_SESSION['app_secret']           = $_POST['app_secret'];
    $_SESSION['request_token_secret'] = $oauth_token_secret;
    $_SESSION['request_token']        = $oauth_token;
    $_SESSION['root']                 = $_POST['root'];
    $_SESSION['base']                 = $_SERVER['REQUEST_URI'];

    header('Location: https://www.dropbox.com/1/oauth/authorize?oauth_token='.$oauth_token.'&oauth_callback='.$_SERVER['HTTP_REFERER']);

  } else if ($_GET['uid'] && $_GET['oauth_token']){
    //$data = array('data' => 'this', 'data2' => 'that');
    //$data = http_build_query($data);
    $context =
        array('http'=>
          array(
            'method' => 'post',
            'header' >= 'Authorization: OAuth oauth_version="1.0", oauth_signature_method="PLAINTEXT", oauth_consumer_key="'.$_SESSION['app_key'].'", oauth_token="'.$_GET['oauth_token'].'", oauth_signature="'.$_SESSION['app_secret'].'&'.$_SESSION['request_token_secret'].'"\r\n' .
                        'Content-Length: 0\r\n' /**,
            'content' => $data **/
          )
        );
    $context = stream_context_create($context);
    $response = file_get_contents("https://api.dropbox.com/1/oauth/access_token", false, $context);
    parse_str($response,$response);

    file_put_contents('config.php',"<?php\n  \$app_key = \"".$_SESSION['app_key']."\";\n  \$app_secret = \"".$_SESSION['app_secret']."\";\n  \$access_token = \"".$response['oauth_token']."\";\n  \$access_token_secret = \"".$response['oauth_token_secret']."\";\n  \$root = \"".$_SESSION['root']."\";\n?>");
    print_r($_SERVER);
    header('Location: '.$_SESSION['base']);
      

  } else {
    include('setup.php');
  }

} else {
  
  include('config.php');

  //$uri = $_SERVER['PATH_INFO'];
  $uri = $_SERVER['REQUEST_URI'];
//print_r("path: ".$uri."<br />");
//print_r("path: ".$_ENV['PATH']."<br />");
//echo "<br />Server :";
//var_dump($_SERVER['REQUEST_URI']);
//echo "<br />";
  if ( substr( $uri, -1 ) == '/' ){
    $uri .= 'index.html';
  }

  # added in case no trailing slash, to make Dropbox work more like web server
  if ( $uri  == '' ){
    $uri .= '/index.html';
  }

  $url  = 'https://api-content.dropbox.com/1/files/'.$root.$uri;
//print_r($url."<br />");
//
//  $ch   = curl_init();
//  curl_setopt($ch, CURLOPT_URL, $url); 
//  curl_setopt($ch, CURLOPT_HEADER,true);
//  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//  curl_setopt($ch, CURLOPT_HTTPHEADER,array(
//    'Authorization: OAuth oauth_version="1.0", oauth_signature_method="PLAINTEXT", oauth_consumer_key="'.$app_key.'", oauth_token="'.$access_token.'", oauth_signature="'.$app_secret.'&'.$access_token_secret.'"',
//    )
//  );

//$data = array('data' => 'this', 'data2' => 'that');
//$data = http_build_query($data);

$context =
    array('http'=>
      array(
        'method' => 'get',
        'header' => 'Authorization: OAuth oauth_version="1.0", oauth_signature_method="PLAINTEXT", oauth_consumer_key="'.$app_key.'", oauth_token="'.$access_token.'", oauth_signature="'.$app_secret.'&'.$access_token_secret.'"' /**,
        'content' => $data **/
      )
      # comment out bracket on previous line and enable these lines if required
      //),
      //'ssl'=>
      //array(
      //  'allow_self_signed'=>true,
      //  'verify_peer'=>false
      //)
    );
$context = stream_context_create($context);

//$response = file_get_contents($url, false, $context);
$body = file_get_contents($url, false, $context);
//var_dump($response);
//print_r('<br />');
//  $response = curl_exec($ch);
//  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headers = parse_http_response_header($http_response_header);
//$body = $response;
$code = $headers[0]['status']['code']; // last status code
  if($code == 404) {
    header('HTTP/1.0 404 Not Found');
    echo "404 - Not found";
  } else {

//$parts = explode("\r\n\r\nHTTP/", $response);
//$parts = (count($parts) > 1 ? 'HTTP/' : '').array_pop($parts);
//list($header, $body) = explode("\r\n\r\n", $parts, 2);
//var_dump($response);
//$body = file_get_contents($url, NULL, $context);
//$header = parse_http_response_header($http_response_header);
//$header = get_headers();
//$headers = apache_response_header();
//$headers = parse_http_response_header($http_response_header);
    //list($header, $body) = explode("\r\n\r\n", $response, 2);
    //$headers = explode("\n",$header);
    foreach($headers[0]['fields'] as $label=>$h){
      # conditions inserted to avoid passing on Dropbox ascii charset in Content-Type in HTTP header
      # (improvement over following hack because it only touches charset and not MIME type
//print_r($h);
      if ( stripos( $h, 'charset=' ) ) {
        if (stripos( $h, 'ascii' ) ) {
          $h = str_replace( 'us-ascii', 'UTF-8' , $h );
          $h = str_replace( 'ascii', 'UTF-8' , $h );
          $h = str_replace( 'US-ASCII', 'UTF-8' , $h );
          $h = str_replace( 'ASCII', 'UTF-8' , $h );
        }
      }
//print_r($h);
      # New hack to circumvent the problem with Dropbox not passing on Last-Modified
      # by pulling it out of modified field in x-dropbox-metadata header instead
      # Note that although the header is actually called 'x-dropbox-metadata' this returns 0 because the 'x' is the first character
      if ( stripos( $label, 'dropbox-metadata' ) ) {
        $x_dropbox_metadata = explode('"', $h);
        $key = array_search('modified', $x_dropbox_metadata);
        $datemod = $x_dropbox_metadata[$key + 2];
//print_r('*Last-Modified: ' . $datemod . '<br />');
        header('Last-Modified: ' . $datemod);
      }
      header($label . ': ' . $h);
//print_r($label . ': ' . $h . '<br />');
    }
    # Hack to avoid passing on Dropbox ascii charset in Content-Type in HTTP header
    # (deprecated: see above replacement conditions)
    #header('Content-Type: text/html;charset=UTF-8');
  }
//var_dump(headers_sent());
//var_dump(headers_list());
  if ($code == 200) echo($body);
}

function parse_http_response_header(array $headers)
{
    $responses = array();
    $buffer = NULL;
    foreach ($headers as $header)
    {
        if ('HTTP/' === substr($header, 0, 5))
        {
            // add buffer on top of all responses
            if ($buffer) array_unshift($responses, $buffer);
            $buffer = array();

            list($version, $code, $phrase) = explode(' ', $header, 3) + array('', FALSE, '');

            $buffer['status'] = array(
                'line' => $header,
                'version' => $version,
                'code' => (int) $code,
                'phrase' => $phrase
            );
            $fields = &$buffer['fields'];
            $fields = array();
            continue;
        }
        list($name, $value) = explode(': ', $header, 2) + array('', '');
        // header-names are case insensitive
        $name = strtoupper($name);
        // values of multiple fields with the same name are normalized into
        // a comma separated list (HTTP/1.0+1.1)
        if (isset($fields[$name]))
        {
            $value = $fields[$name].','.$value;
        }
        $fields[$name] = $value;
    }
    unset($fields); // remove reference
    array_unshift($responses, $buffer);

    return $responses;
}

?>
