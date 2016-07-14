<?php

$hookSecret = getenv('CI_SECRET');
$mode = getenv('CI_MODE');

$log_path = (getenv('CI_LOG_PATH') !== false)? getenv('CI_LOG_PATH') : null;
define('LOG_PATH', getenv('CI_LOG_PATH'));

function pullMaster($payload){
  
  //get env
  $pull_branch = getenv('CI_BRANCH_NAME');

  //set payload
  $branch = isset($payload['payload']['branch'])? $payload['payload']['branch'] : null;
  $subject = isset($payload['payload']['all_commit_details'][0]['subject'])? $payload['payload']['all_commit_details'][0]['subject'] : null;
  
  if ($branch === $pull_branch){
      `sudo -u deploy sh /home/deploy/pull.sh`;
      file_put_contents(LOG_PATH.'hook.log', date("[Y-m-d H:i:s]")." ".$_SERVER['REMOTE_ADDR']." git pulled: ".$subject."\n", FILE_APPEND|LOCK_EX);
  }
}

set_error_handler(function($severity, $message, $file, $line) {
  throw new \ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(function($e) {
  header('HTTP/1.1 500 Internal Server Error');
  echo "Error on line {$e->getLine()}: " . htmlSpecialChars($e->getMessage());
  die();
});

function checkRequest(){
  if($_SERVER['REQUEST_METHOD'] !== 'POST'){
      throw new \Exception("Method Not Allowed");
  }
}

function checkSecret($hookSecret){
  $secret = filter_input(INPUT_GET, 'secret');
  if ($hookSecret !== null) {
    if (empty($secret)) {
      throw new \Exception('secret none.');
    }
    if ($secret !== $hookSecret) {
      throw new \Exception('Hook secret does not match.');
    }
  };
}

function checkContentType() {
  if (!isset($_SERVER['CONTENT_TYPE'])) {
    throw new \Exception("Missing HTTP 'Content-Type' header.");
  }
}

function getJson(){
  $json = null;
  switch ($_SERVER['CONTENT_TYPE']) {
  case 'application/json':
    $json = file_get_contents('php://input');
    break;
  case 'application/x-www-form-urlencoded':
    $json = $_POST['payload'];
    break;
  default:
    throw new \Exception("Unsupported content type: $_SERVER[CONTENT_TYPE]");
  }
  return $json;
}

function triggerEvent($payload){
  $status = isset($payload['payload']['status'])? $payload['payload']['status'] : null;
  switch ($status) {
    case 'success':
      pullMaster($payload);
      break;
    case 'failed':
      break;
    default:
      header('HTTP/1.0 404 Not Found');
      die();
  }
}

switch($mode){
  case 'debug':
    echo "debug mode";
    `sudo -u deploy sh /home/deploy/test1.sh`;
    file_put_contents(LOG_PATH.'hook.log', date("[Y-m-d H:i:s]")." ".$_SERVER['REMOTE_ADDR']." git pulled: debug mode\n", FILE_APPEND|LOCK_EX);
  break;

  default:
    checkRequest();
    checkSecret($hookSecret);
    checkContentType();
    $payload = json_decode(getJson(), true);
    triggerEvent($payload);
}