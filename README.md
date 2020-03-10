# POC github-drone

trigger drone build using github webhook.

Can be used on tags or branch.

## Installation

```
composer install
```



## Usage

Define github token

```
read -s GITHUB_TOKEN
export GITHUB_TOKEN
```

Edit variable $REPOS in makeRequest.php and execute the script:

```
php makeRequest.php
```

## Examples

Trigger build on a tag, commit is retrieved automatically:
```
$REPOS = [
  "owner/repo" => ["commit" => "", "branch" => "refs/tags/2.5.13"]
];
```

Trigger latest commit on master branch:
```
$REPOS = [
  "owner/repo" => ["commit" => "", "branch" => "master"]
];
```

Trigger specific commit on master branch:
```
$REPOS = [
  "owner/repo" => ["commit" => "aa218f56b14c9653891f9e74264a383fa43fefbd", "branch" => "master"]
];
```
