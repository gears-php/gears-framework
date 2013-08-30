Gears Framework (Gears\Framework)
=========

A web development PHP framework whish aims to be simple, solid and fast. **Do more, write less**


## Basics
The minimum you need to run your app

```yaml
# app/config/routes.yml
- route: /*
  to: /
```
Above is the default routing rule. The routing principles will be explained later in the corresponding section

```php
# index.php
<?php

use Gears\Framework\App\App;

// framework
require_once 'path/to/vendor/gears-php/framework/bootstrap.php';

try {
	(new App)->run();
} catch (ResourceNotFound $e) {
	throw $e; // you can show 404 page instead if working at production env
}
```
