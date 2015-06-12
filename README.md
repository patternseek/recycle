# Recycle

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
