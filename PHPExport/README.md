# hollodotme\Helpers

## PHPExport

This is a small collection of classes that enable you to export every class or 
even entire extension into PHP code or a PHP Archive (PHAR).

### Why this?

As I wrote a new class that used the PECL extension "pecl_http 2.0.x", I messed up,
because my development IDE (PphStorm) did not recognized the extension's namespace, classes, methods and so on.
And of course, no auto completion at all!
 
Unfortunately there was/is no PHP source code of that extension, so I started exporting it by using the Reflection classes of PHP.

In the end, I was able to export a PHAR file for the hole extension and could link that file into my IDE as an external library.

### How does this work?

There are 3 ways to export an entire extension and 2 ways to export a single class.

1: Export a PHAR for the whole extension

    <?php
    $extension = new \ReflectionExtension('http');
    $exporter = new \hollodotme\Helpers\PHPExport\ReflectionExtension($extension);
    $exporter->exportPHAR('pecl_http.phar', '/var/www/lib', true);

2: Export all files for the whole extension (including sub directories by namespace depth)

    <?php
    $extension = new \ReflectionExtension('http');
    $exporter = new \hollodotme\Helpers\PHPExport\ReflectionExtension($extension);
    $exporter->exportFiles('/var/www/lib/http');

3: Just print all classes/interfaces/traits from the extension

    <?php
    header('Content-type: text/plain');
    $extension = new \ReflectionExtension('http');
    $exporter = new \hollodotme\Helpers\PHPExport\ReflectionExtension($extension);
    echo $exporter->exportCode();

4: Export a single class/interface/trait to a file

    <?php
    $class = new \ReflectionClass('http\\Url');
    $exporter = new \hollodotme\Helpers\PHPExport\ReflectionClass($class);
    $exporter->exportFile('/var/www/lib/http/Url.php');

5: Just print a single class/interface/trait
 
    <?php
    header('Content-type: text/plain');
    $class = new \ReflectionClass('http\\Url');
    $exporter = new \hollodotme\Helpers\PHPExport\ReflectionClass($class);
    echo $exporter->exportCode();

Maybe this will help you out, too.
    