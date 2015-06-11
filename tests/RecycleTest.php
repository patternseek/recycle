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

namespace PatternSeek\Recycle\Test;

use PatternSeek\Recycle\Recycle;

class RecycleTest extends \PHPUnit_Framework_TestCase
{

    private $storageDir = "/tmp/recycle_test";

    public function testDirectoryCreation(){
        $toMove = "/tmp/moveme";
        mkdir( $toMove );
        $toMoveChild = $toMove."/child";
        file_put_contents( $toMoveChild, "MyData" );

        $r = new Recycle( $this->storageDir );
        $movedTo = $r->moveToBin($toMove);

        static::assertStringEqualsFile( $movedTo."/child", "MyData" );
        
        $r->emptyBin( 0 );
        static::assertFalse( file_exists( $toMove ) );

        rmdir( $this->storageDir );
        static::assertFalse( file_exists( $this->storageDir) );
        
    }

    public function testBinEmptyDeletionCriteria(){
        $todayOb = new \DateTimeImmutable( "today" );
        $today = $todayOb->format("c");
        $yesterday = $todayOb->modify( "-1 days" )->format("c");
        $dayBefore = $todayOb->modify( "-2 days" )->format("c");
        $tomorrow = $todayOb->modify( "+1 days" )->format("c");
        
        $r = new Recycle( $this->storageDir );
        $inputItems = [
            $tomorrow,
            $today,
            $yesterday,
            $dayBefore,
            "somerandomfile"
        ];
        
        $toDelete = $this->invokeMethod( $r, "generateDeletionList",
            [
                "keepDays"=>0,
                "items"=>$inputItems
            ]
        );
        static::assertEquals(
            [
                $tomorrow,
                $today,
                $yesterday,
                $dayBefore
            ],
            $toDelete
        );

        $toDelete = $this->invokeMethod( $r, "generateDeletionList",
            [
                "keepDays"=>1,
                "items"=>$inputItems
            ]
        );
        static::assertEquals(
            [
                $tomorrow,
                $yesterday,
                $dayBefore
            ],
            $toDelete
        );

        $toDelete = $this->invokeMethod( $r, "generateDeletionList",
            [
                "keepDays"=>2,
                "items"=>$inputItems
            ]
        );
        static::assertEquals(
            [
                $tomorrow,
                $dayBefore
            ],
            $toDelete
        );

        $toDelete = $this->invokeMethod( $r, "generateDeletionList",
            [
                "keepDays"=>3,
                "items"=>$inputItems
            ]
        );
        static::assertEquals(
            [
                $tomorrow,
            ],
            $toDelete
        );
    }

    /**
     * @expectedException \Exception
     */
    public function testPathSafetyForNonExistent(){
        $r = new Recycle( $this->storageDir );
        $this->invokeMethod( $r, "pathSafetyCheck", ["/bloop/blerp/"] );
    }

    /**
     * @expectedException \Exception
     */
    public function testPathSafetyForRootDirs(){
        $r = new Recycle( $this->storageDir );
        $this->invokeMethod( $r, "pathSafetyCheck", ["/var"] );
    }

    /**
     * @expectedException \Exception
     */
    public function testPathSafetyForRootDirs2(){
        $r = new Recycle( $this->storageDir );
        $this->invokeMethod( $r, "pathSafetyCheck", ["/var/"] );
    }
    
    public function testPathSafetyAllowsSecondLevel(){
        $r = new Recycle( $this->storageDir );
        $testDir = "/tmp/recycle_path_test";
        mkdir( $testDir );
        $this->invokeMethod( $r, "pathSafetyCheck", [ $testDir ] );
        rmdir( $testDir );
    }

    public function invokeMethod(&$object, $methodName, array $parameters = array())
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
    
}
