<?php
/**
 *
 * © 2015 Tolan Blundell.  All rights reserved.
 * <tolan@patternseek.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace PatternSeek\Recycle;

use Webmozart\PathUtil\Path;

/**
 * Class Recycle
 * @package PatternSeek\Recycle
 */
class Recycle
{

    /**
     * @var string
     */
    private $storageDirectory;

    /**
     * @param $storageDirectory
     */
    public function __construct( $storageDirectory ){
        if( $storageDirectory{-1} !== '/' ){
            $storageDirectory .= '/';
        }
        $this->storageDirectory = $storageDirectory;
    }

    /**
     * Move a file or directory to the recycle bin.
     *
     * @param $path
     * @return string The path that $path was renamed to.
     * @throws \Exception
     */
    public function moveToBin( $path ){

        $path = Path::canonicalize( $path );

        $this->pathSafetyCheck( $path );
        
        $date = new \DateTimeImmutable("today");
        $dateStr = $date->format("c");
        $unique = ( (string) microtime(true) ) .'-'. ( (string) rand(0, 100000 ) );
        $basename = basename( $path );
        
        $todayDir = $this->storageDirectory . $dateStr;
        $finalRestingPlaceDir = "{$todayDir}/{$unique}";
        $finalRestingPlace = "{$finalRestingPlaceDir}/{$basename}";

        $this->ensureDirUsable( $this->storageDirectory );
        $this->ensureDirUsable( $todayDir );
        $this->ensureDirUsable( $finalRestingPlaceDir );
        
        // Would use PHP's rename but... it doesn't always work
        // when moving a directory to another device.
        exec( "mv {$path} {$finalRestingPlace}" );
        return $finalRestingPlace;
    }

    /**
     * Check that directory exists or is creatable and is writable.
     * @param $dir
     * @throws \Exception
     */
    private function ensureDirUsable( $dir )
    {
        if (!file_exists( $dir )) {
            mkdir( $dir );
            if (!file_exists( $dir )) {
                throw new \Exception( "{$dir} does not exist and could not be created." );
            }
        }
        
        $this->pathSafetyCheck( $dir );
        
        $tmpFilename = tempnam( $dir, "___" );
        if (!file_exists( $tmpFilename )) {
            throw new \Exception( "{$dir} does not appear to be writable." );
        }
        unlink( $tmpFilename );
    }

    /**
     * Empty the bin keeping $keepDays worth of items.
     * Note that $keepDays = 1 means that files recycled by scripts which moved
     * files after 00:00 (default timezone) today will be kept. It does not keep 24 hours of files.
     * 
     * Note that items from the future will also be deleted. Only items matching the specific days
     * from today into the past will be kept.
     * 
     * @param int $keepDays 1 = today (back to 00:00 from now).
     */
    public function emptyBin( $keepDays = 1 ){

        $items = $this->readDir( $this->storageDirectory );
        // Items exclude parent directory!
        $toDelete = $this->generateDeletionList( $keepDays, $items );

        if( count( $toDelete ) > 0 ){
            foreach( $toDelete as $itemToDelete){
                $fullPathToDelete = "{$this->storageDirectory}/{$itemToDelete}";
                $this->pathSafetyCheck( $fullPathToDelete );
                exec( "rm -rf {$fullPathToDelete}" );
            }
        }
    }

    /**
     * Return all the filenames in a directory, excluding . and ..
     * 
     * @param $directoryPath
     * @return array
     * @throws \Exception
     */
    private function readDir( $directoryPath )
    {
        $ret = [];
        $this->ensureDirUsable( $directoryPath );
        $d = dir( $directoryPath );
        while( false !== ( $entry = $d->read() ) ){
            if( $entry === '.' || $entry === '..' ){
                continue;
            }
            $ret[] = $entry;
        }
        $d->close();
        return $ret;
    }

    /**
     * Given a set of filenames, not including their parent directory, and $keepDays which works
     * the same as the $keepDays argument for emptyBin(), determine which entries should be
     * deleted. 
     * @param $keepDays
     * @param $items
     * @return array
     */
    private function generateDeletionList( $keepDays, $items )
    {
        $keepStrings = [];
        if( $keepDays > 0 ){
            for( $day = 0; $day < $keepDays; $day++ ){
                $dateTmp = new \DateTimeImmutable("today");
                $dateTmp = $dateTmp->modify( "-{$day} days" );
                $keepStrings[] = $dateTmp->format("c");
            }
        }
        
        $toDelete = [];
        foreach( $items as $entry ){
            // In exclusion list?
            if( in_array( $entry, $keepStrings, true ) ){
                continue;
            }
            // Is a valid ISO 8601 date?
            if( preg_match(
                    '/^([\+-]?\d{4}(?!\d{2}\b))((-?)((0[1-9]|1[0-2])(\3([12]\d|0[1-9]|3[01]))?|W([0-4]\d|5[0-2])(-?[1-7])?|(00[1-9]|0[1-9]\d|[12]\d{2}|3([0-5]\d|6[1-6])))([T\s]((([01]\d|2[0-3])((:?)[0-5]\d)?|24\:?00)([\.,]\d+(?!:))?)?(\17[0-5]\d([\.,]\d+)?)?([zZ]|([\+-])([01]\d|2[0-3]):?([0-5]\d)?)?)?)?$/',
                    $entry) 
            ){
                $toDelete[] = $entry;
            }
        }
        return $toDelete;
    }

    /**
     * Do some basic sanity checks on a path
     * 
     * @param $path
     * @throws \Exception
     */
    private function pathSafetyCheck( $path )
    {
        // Duplication is intentional for belt-and-braces approach
        $path = Path::canonicalize( $path );
        if( ! file_exists( $path ) ){
            throw new \Exception( "{$path} doesn't exist." );
        }
        if( is_dir( $path ) ){
            // Path::canonicalize() leaves no trailing slash.
            $path .= '/';
            if( mb_substr_count( $path, '/', "UTF-8" ) < 3 ){
                throw new \Exception("Can't use root level directories as recycle area or move or delete them: {$path}");
            }
        }
    }

}
