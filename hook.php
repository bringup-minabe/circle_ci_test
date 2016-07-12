<?php

$hookSecret = $_SERVER['SECRET'];
$mode = $_SERVER['MODE'];
$pull_branch = $_SERVER['BRANCH_NAME'];

function pullMaster($payload){
  $branch = isset($payload['payload']['branch'])? $payload['payload']['branch'] : null;
  $subject = isset($payload['payload']['all_commit_details'][0]['subject'])? $payload['payload']['all_commit_details'][0]['subject'] : null;
  if ($branch === 'master'){
      `sudo -u deploy sh /home/deploy/pull.sh`;
      file_put_contents('hook.log', date("[Y-m-d H:i:s]")." ".$_SERVER['REMOTE_ADDR']." git pulled: ".$subject."\n", FILE_APPEND|LOCK_EX);
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
  if ($hookSecret !== NULL) {
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
  } elseif (!isset($_SERVER['HTTP_X_GITHUB_EVENT'])) {
    throw new \Exception("Missing HTTP 'X-Github-Event' header.");
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
    file_put_contents(dirname(__FILE__).'/hook.log', date("[Y-m-d H:i:s]")." ".$_SERVER['REMOTE_ADDR']." git pulled: debug mode\n", FILE_APPEND|LOCK_EX);
  break;

  default:
    checkRequest();
    checkSecret($hookSecret);
    // checkContentType();
    $payload = json_decode(getJson(), true);
    triggerEvent($payload);
}