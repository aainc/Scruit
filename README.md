# Scruit

## description

Scruit is a Generator and Compressor for Hoimi and Mahotora.
The features are them.

- Generate source codes by Database Scheme.
    - directory structures
    - app/resources/config.php
    - app/bootstrap.php
    - app/classes/dao/[table names].php
    - app/actions/[table names].php
- Compression and concat Hoimi and Mahotora source codes to one file(app/hoimi-all.php)

## manual
### Generate Application

```php
php src/lib/Scruit/src/Scruit/Runner.php  -n=init \
    --optional="app='[appName]' host='[DatabaseHost]' user='[DatabaseUser]' pass='[DatabasePassword]' db='[DatabaseName]'"
```

### Compression Libraries

```php
php src/lib/Scruit/src/Scruit/Runner.php  -n=optimize
```

### "scruit" and ".scruit"

"${ROOT}/scruit" appears on generated the application.
this file is shortcut the Scruit/Runner.php.
this script is extendable by ".scruit".
".scruit" is php hash like this.

```
<?php
return array(
  'subCommandName' =>  'Namespace\to\ClassName',
  ...
  ...
  ...
);
```

"scruit" read ".scruit" at first.
if a subcommand's name defined by ".scruit" duplicate name of subcommand as subsets then
scruits is prior a subcommand defined by ".scruit" to all other subcommands as subsets

the way to call subcommand.

```
php scruit [subcommand_name] "argKey=argValue argKey2=argValue2..."
```

the way implementation the sub command.
the class need implement "\Scruit\Runnable" interface.

```
<?php

namespace \Scruit\Sample;

class SubCommand implement \Scruit\Runnable
{
  public function getName ()
  {
    return "subCommandName";
  }

  public function run ($argv)
  {
    var_dump($argv);
  }
}
```


## See Also

- Hoimi(micro mvc framework)
- Mahotora(micro database library)

## Licens

The MIT License

Copyright (c) 2005-2015 Allied Architects Co.,Ltd.

Permission is hereby granted, free of charge, to any person obtaining a copy of
this software and associated documentation files (the "Software"), to deal in
the Software without restriction, including without limitation the rights to
use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
the Software, and to permit persons to whom the Software is furnished to do so,
subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
