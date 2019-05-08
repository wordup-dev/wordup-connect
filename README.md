Wordup Connect 
==============

Wordup Connect is a WordPress plugin which connects a remote WordPress installation with your local wordup CLI.

After you have installed this plugin on your server. Run locally:

```
$ wordup install --connect=[url-of-your-wp-website]
```

## How it works

Wordup looks for an Updraftplus backup on your remote WordPress installation. If found, it will download the files to your wordup project and will install all components to run your development stack.

## Requirements

* Install [wordup-cli](https://github.com/wordup-dev/wordup-cli) on your local machine
* Install wordup-connect and Updraftplus on your remote WordPress installation. 

## Installation

Download the WordPress plugin zip from the /dist folder.

In your WordPress admin go to Tools -> Wordup connect and follow the instructions. 

## Note

This plugin should **not** be used in a production environment. 
It's currently in an early beta stadium.

## Wordup CLI

This source code is also a good example of what a wordup development stack looks like. Just clone this project and run wordup install:

```
$ git clone https://github.com/wordup-dev/wordup-connect

$ wordup install
```


## FAQ

-  [Why is only Updraftplus supported?](#why-is-only-updraftplus-supported)

### Why is only Updraftplus supported?

It is planned to support other backup methods in future releases, but for now on Updraftplus as the most popular backup plugin is required. 

