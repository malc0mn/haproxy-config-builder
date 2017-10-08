<?php

namespace HAProxy\Config\Tests;

use HAProxy\Config\Comment;
use PHPUnit\Framework\TestCase;

class CommentTest extends TestCase
{
    public function testConstructor()
    {
        $comment = new Comment('This is a comment!');

        $this->assertEquals('This is a comment!', $comment->getText());
    }

    public function testToString()
    {
        $comment = new Comment('This is a comment!');

        $this->assertEquals("# This is a comment!\n", (string) $comment);
    }
}
