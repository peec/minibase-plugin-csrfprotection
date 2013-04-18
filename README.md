[![Build Status](https://travis-ci.org/peec/minibase-plugin-csrfprotection.png?branch=master)](https://travis-ci.org/peec/minibase-plugin-csrfprotection)

# CSRF Protection Plugin

CSRF Protection plugin for [Minibase](http://github.com/peec/minibase) applications.


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

Echo `$csrfTokenInput` in the forms that does post requests.

```php

<form>
  <?php echo $csrfTokenInput ?>
</form>

```

You are now safe for CSRF protection.
