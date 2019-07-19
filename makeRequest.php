<?php

require_once __DIR__ . '/vendor/autoload.php';

use Http\Adapter\Guzzle6\Client as GuzzleClient;
use Github\HttpClient\Builder;
use Github\HttpClient\Message\ResponseMediator;

$TOKEN="4647dca4665aa4f3b3c557c7d5f2313e4c92eb02";

# Parameters
$DRONE_SERVER="https://drone.fpfis.eu";
$OWNER="ec-europa";
$REPO="digit-housing7test-reference";
$COMMIT="d8050c27a2db67baad9d2871d390d59b26160b8d";
$BRANCH_REF = "refs/heads/master";

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

$hooks = $github->api('repo')->hooks()->all($OWNER, $REPO);

$b=new Builder();
$DATAS = array();
foreach($hooks as $hook){
  
  if (strpos($hook['config']["url"], $DRONE_SERVER) === 0){
    
    # Set sender information
    $response = $github->getHttpClient()->get("user");
    $resp = ResponseMediator::getContent($response);
    $sender = array_intersect_key ($resp, array_flip($USER_INFO));
    $DATAS['sender'] = $sender ;
    
    # Set Commit information
    $commit = $github->api('repo')->commits()->show($OWNER, $REPO, $COMMIT);
    //$response = $github->getHttpClient()->get("/repos/$OWNER/$REPO/commits/$COMMIT");
    //$resp = ResponseMediator::getContent($response);
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
    $repo['organization'] = (isset($repo['organization']['login'])) ? $repo['organization']['login'] : "";
    $DATAS['repository'] = $repo;
    
    # Set default information
    $DATAS['ref'] = $BRANCH_REF;
    $DATAS['after'] = $COMMIT;
    $DATAS['created'] = $DATAS['deleted'] = $DATAS['forced'] = false;
    $DATAS['base_ref'] = NULL;
    
    # Send to drone
    $response = $b->getHttpClient()->post($hook['config']["url"], $REQUEST_HEADER, json_encode($DATAS));
    $resp = ResponseMediator::getContent($response);
    print_r($resp);
    
  }
}






