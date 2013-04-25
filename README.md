[![Build Status](https://travis-ci.org/peec/minibase-plugin-csrfprotection.png?branch=master)](https://travis-ci.org/peec/minibase-plugin-csrfprotection)

# CSRF Protection Plugin

CSRF Protection plugin for [Minibase](http://github.com/peec/minibase) applications.

Handle evil CSRF attacks for all your routes except GET.


## Install

```json
{
  "require":{
	     "pkj/minibase-plugin-csrfprotection": "dev-master"
	}
}

```


## Usage

Add the plugin to your app.

```php
$mb->initPlugins(array('Pkj\Minibase\Plugin\Csrf\CsrfPlugin' => null));
```

Echo `$csrfTokenInput` in the forms that does post requests. Note, also `$csrfToken` is available, it contains only the token.

```php

<form>
  <?php echo $csrfTokenInput ?>
</form>

```

You are now safe for CSRF protection.




## Configuration array:

- store: `cookie` or `session`. Note SESSION must be started if session is used. I recommend using `cookie`.
- token_name: the name of the token. Default is "csrfToken".



## Events

You may customize the error exception if a token is invalid by adding event handler.


```php
$mb->events->on("csrf:invalid", function ($request) {
	return function () {
		return $this->respond("html")->view("csrfinvalid.html.php");
	};
});
```

