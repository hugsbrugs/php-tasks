<?php

# For PHP7
declare(strict_types=1);

// namespace Hug\Tests\Sftp;

use PHPUnit\Framework\TestCase;

use Hug\Tasks\Tasks as Tasks;

/**
 *
 */
final class TasksTest extends TestCase
{    

    /* ************************************************* */
    /* ******************* Tasks::test ****************** */
    /* ************************************************* */

    /**
     *
     */
    public function testCanTest()
    {
        $test = Tasks::test($server, $user, $password, $port);
        $this->assertTrue($test);
    }
}
