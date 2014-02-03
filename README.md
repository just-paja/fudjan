# Fudjan LMCV web framework

Yet another web framework that tries to be open, lightweight and simple.

Sadly, I didn't have time to create any usable docs, just some inlined javadoc style comments.

## Installation

1. Get [Composer](https://github.com/composer/composer) if you don't have it already
2. Create project via composer.
	Package [fudjan-project](https://github.com/just-paja/fudjan-project) is a very simple wrapper for downloading composer dependencies and keeping clean ```BASE_DIR```

	```bash
	$ composer create-project just-paja/fudjan-project
	```

## Features

### Multi level JSON config

You keep your config on path ```etc/conf.d/{env}``` using simple and readable JSON. Lookup default config on ```etc/default/conf.d``` for list of options. Fudjan reads all .json files begining in ```{ROOT}/etc/default/conf.d```, going trough ```{BASE_DIR}/lib/vendor/**/etc/default/conf.d``` and finishing in ```{BASE_DIR}/etc/conf.d/{env}```. So you can simply overwrite settings just by writing them inside a json file.

### Domain config

Do you use subdomains, vhosts or just run multiple domains? Just define rules how they're recognized and connect it with your routes. You might want to keep this config in ```etc/conf.d/global/domains.json```

```json
{
	"global":{
		"rules":["^(www\\.)?mydomain.net$", "^(www\\.)?mydomain.net.local$"],
		"init":["www", "godmode"]
	},
	"static":{
		"rules":["static.mydomain.net.net$", "static.mydomain.net.local$"]
	}
}
```

Key ```rules``` is regexp that is matched agains host header. Key ```init``` is list of scripts to run before page load. Scripts are taken from ```etc/init.d```


### Binary helpers

If your installation went well, you'll find executable in ```bin/manage```. It contains modules and context help. Running it without arguments displays some basic info about your config. Syncing or migrating database, updating assets and deploying your project will be like walking trough the garden of marshmallow.


### Regexp URLs (routes)

This is pretty standard feature I guess. Routes are also in JSON on path ```etc/routes.d/{domain}.json```

```json
[
	["^/$", {
		"layout":["abstract/skeleton", "home"],
		"modules":[
			["example/mod_name", {"slot":"top", "mod_opts_example":"test"}, "name"]
		]
	}, "home"]
]
```

### Advanced resource handling

Images, icon, styles and scripts are accessed using serial numbers. When you change your website style, you change the serial number and frontend client is forced to re-download whole style.

Javascript and css files are minified when 'dev.debug.frontend' is not falsy.

#### Bower integration

Basic functions of bower are integrated inside fudjan. Packages are saved inside ```share/bower```. If you want to add dependency, set config ```assets.dependencies```. For syntax, see default settings.

Fudjan has CLI module for listing and updating frontend dependencies
```bash 
$ ./bin/manage assets list
```

#### Resource packs

Resources are gathered during rendering process of http response. Right before sending it, they're concatenated.

```php
$ren->content_for('scripts', 'bower/async/lib/async.js');
$ren->content_for('scripts', 'script/example.js');
$ren->content_for('styles', 'styles/layout.js');
```

#### Resource tags

You can tag images, icons, whatever and create your own tags inside styles and scripts.

```css
.example {
	background-image:<pixmap(layout/example-bg.png)>;
}
```

```js
console.log('<pixmap(layout/example-bg.png)>');
```

## Requirements

Fudjan will run with php >= 5.3. Extension for database connections will be required, most likely PDO, but applications without DB can be made with fudjan as well.

## LMCV?

Layout, model, controller, view. You define what and how many modules are run when URL pattern is triggered.

## Why fudjan?

The name is derived from Fuck Django.
