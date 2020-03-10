<?php

require_once __DIR__ . '/vendor/autoload.php';

use Http\Adapter\Guzzle6\Client as GuzzleClient;
use Github\HttpClient\Builder;
use Github\HttpClient\Message\ResponseMediator;

$TOKEN=getenv( "GITHUB_TOKEN");

if ($TOKEN == ""){
  print ("[ERROR] env var GITHUB_TOKEN is empty\n");
  exit(1);
}

# Parameters
$DRONE_SERVER="https://drone.fpfis.eu";

# Repository to trigger drone
$REPOS = [
  "owner/repository" => ["commit" => "", "branch" => "master"]
];

$USER_INFO = ["login", "id", "avatar_url", "site_admin"];
$COMMIT_INFO = ["author", "committer", "message", "url"];

#$DATAS=file_get_contents ("./data.json");
#$DATAS=json_decode($DATAS, TRUE);

$REQUEST_HEADER = [
  "X-GitHub-Event" => "push",
  "User-Agent" => "GitHub-Hookshot/f221634",
  "Content-Type" => "application/json",
];

$builder = new Github\HttpClient\Builder(new GuzzleClient());
$github = new Github\Client($builder, 'machine-man-preview');

$github->authenticate($TOKEN, null, Github\Client::AUTH_HTTP_TOKEN);

foreach ($REPOS as $ID => $VALUE){
  list($OWNER, $REPO) = explode('/', $ID);


  $hooks = $github->api('repo')->hooks()->all($OWNER, $REPO);

  $b=new Builder();
  $DATAS = array();
  foreach($hooks as $hook){
    
    if (strpos($hook['config']["url"], $DRONE_SERVER) === 0){
      $COMMIT = $VALUE["commit"];
      $BRANCH_REF = $VALUE["branch"];
      $COMMIT = $VALUE["commit"];

      if ($COMMIT == "last" || $COMMIT == ""){
        $commits = $github->api('repo')->commits()->all($OWNER, $REPO, array('sha' => $BRANCH_REF));
        if (isset($commits[0]) && isset($commits[0]['sha'])){
          $COMMIT = $commits[0]['sha'];
        }
      }

      # Set sender information
      $response = $github->getHttpClient()->get("user");
      $resp = ResponseMediator::getContent($response);
      $sender = array_intersect_key ($resp, array_flip($USER_INFO));
      $DATAS['sender'] = $sender ;

      # Set Commit information
      $commit = $github->api('repo')->commits()->show($OWNER, $REPO, $COMMIT);

      unset($commit['files']);
      $DATAS['commits'] = $DATAS['head_commit'] = array();
      $DATAS['commits'] = $DATAS['head_commit'] = array_intersect_key ($commit['commit'], array_flip($COMMIT_INFO));
      $DATAS['commits']['id'] = $DATAS['head_commit']['id'] = $commit['sha'];
      $DATAS['commits']['id'] = $DATAS['head_commit']['id'] = $commit['sha'];

      # Set repository information
      //$response = $github->getHttpClient()->get("/repos/$OWNER/$REPO");
      //$resp = ResponseMediator::getContent($response);
      $repo = $github->api('repo')->show($OWNER, $REPO);
      unset ($repo['permissions']);
      #$repo["default_branch"] = "master";
      #$repo["master_branch"] = "master";
      $repo['organization'] = (isset($repo['organization']['login'])) ? $repo['organization']['login'] : "";
      $DATAS['repository'] = $repo;

      # Set default information
      $DATAS['ref'] = $BRANCH_REF;
      $DATAS['after'] = $COMMIT;
      $DATAS['created'] = $DATAS['deleted'] = $DATAS['forced'] = false;

      if (strpos($BRANCH_REF, "tags") !== false){
        $response = $github->getHttpClient()->get("https://api.github.com/repos/$OWNER/$REPO/commits/$COMMIT/branches-where-head", array ('Accept' => 'application/vnd.github.groot-preview+json'));
        $resp = ResponseMediator::getContent($response);
        if (empty($resp)){
          $DATAS['base_ref'] = "refs/heads/master";
        }else{
          $DATAS['base_ref'] = $resp[0]["name"];
        }
      }else{
        $DATAS['base_ref'] = null;
      }

      # Send to drone
      print_r("Sent request for $REPO...\n");
      $response = $b->getHttpClient()->post($hook['config']["url"], $REQUEST_HEADER, json_encode($DATAS));
      $resp = ResponseMediator::getContent($response);
      print_r($resp);
    }
  }
}
