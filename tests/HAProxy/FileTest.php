<?php

namespace HAProxy\Config\Tests;

use HAProxy\Config\File;
use PHPUnit\Framework\TestCase;

class FileTest extends TestCase
{
    public function testCannotRead()
    {
        $this->expectException('\HAProxy\Config\Exception\FileException');
        $this->expectExceptionMessage('Cannot read file "path/to/unknown/config.cfg".');

        new File('path/to/unknown/config.cfg');
    }
}
