# Recycle

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/patternseek/recycle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/patternseek/recycle/?branch=master) [![SensioLabsInsight](https://insight.sensiolabs.com/projects/e125c475-0221-419d-90a7-7f6e6db2ed48/mini.png)](https://insight.sensiolabs.com/projects/e125c475-0221-419d-90a7-7f6e6db2ed48)

A utility library implementing a recycle/trash bin for scripts to recursively 'delete' directories more safely.

## Usage example

```php
// Recycle will attempt to create the directory, but not its parent.
$r = new Recycle( "/tmp/my_apps_recycle_bin/" );

// moveToBin tells you the new filepath/name for the moved
// file or directory. This is mostly useful for testing.
$movedTo = $r->moveToBin("/var/tmp/somefile_or_dir");

// Remove entries before last midnight
$r->emptyBin( $daysToKeep = 1 );

```
