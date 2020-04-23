Wordup Connect 
==============

Wordup Connect give you access to your private WordPress themes and plugins hosted on https://wordup.dev.

After you have installed this plugin on your server you are able to download all your themes and plugins from the wordup registry, furthermore your wordup hosted plugins and themes will be available for automatic updates whenever there is a new release.

You can provide a new release with the wordup CLI:

```
$ wordup cloud:publish --env=release
```

## How it works

Wordup-connect extends the default WordPress update functionality, so that we can provide you the same update workflow for your private themes and plugins than WordPress for there hosted plugins and themes.

After you have released a new version of your plugin or theme on wordup.dev, all connected WordPress installation will be able to automatically update to the new version.

## Requirements

* Install [wordup-cli](https://github.com/wordup-dev/wordup-cli) on your local machine or use the wordup [web interface](https://console.wordup.dev)
* Install wordup-connect on your remote WordPress installation.

## Installation

Download the WordPress plugin from https://api.wordup.dev/release_dl/wordup-connect/latest/wordup-connect.zip

In your WordPress admin go to Tools -> Wordup connect and follow the instructions.


