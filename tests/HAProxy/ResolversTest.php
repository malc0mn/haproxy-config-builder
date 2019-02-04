<?php

namespace HAProxy\Config\Tests;

use HAProxy\Config\Comment;
use HAProxy\Config\Resolvers;
use PHPUnit\Framework\TestCase;

class ResolversTest extends TestCase
{
    /**
     * @var Resolvers
     */
    private $resolvers;

    protected function setUp(): void
    {
        $this->resolvers = new Resolvers('test');
    }

    public function testConstruct()
    {
        $resolvers = new Resolvers('test');
        $this->assertInstanceOf('HAProxy\Config\Resolvers', $resolvers);
    }

    public function testFactory()
    {
        $resolvers = Resolvers::create('test');
        $this->assertInstanceOf('HAProxy\Config\Resolvers', $resolvers);
    }

    public function testName()
    {
        $resolvers = new Resolvers('test');

        $this->assertEquals(
            'test',
            $resolvers->getName()
        );
    }

    public function testAddNameserver()
    {
        $this->resolvers
            ->addNameserver('google', '8.8.8.8', 53)
        ;

        $this->assertTrue(
            $this->resolvers->nameserverExists('google')
        );

        $this->assertEquals(
            ['ip' => '8.8.8.8', 'port' => 53],
            $this->resolvers->getNameserver('google')
        );
    }

    public function testRemoveNameserver()
    {
        $this->resolvers
            ->addNameserver('google', '8.8.8.8', 53)
            ->addNameserver('cloudflare', '1.1.1.1', 53)
        ;

        $this->resolvers
            ->removeNameserver('google')
        ;

        $this->assertFalse(
            $this->resolvers->nameserverExists('google')
        );
        $this->assertTrue(
            $this->resolvers->nameserverExists('cloudflare')
        );
    }

    public function testAddNameserverThroughAddParameter()
    {
        $this->expectException('HAProxy\Config\Exception\InvalidParameterException');
        $this->expectExceptionMessage('Please use the addNameserver() method to add nameservers!');

        $this->resolvers->addParameter('nameserver', 'google 8.8.8.8:53');
    }

    public function testAddParameter()
    {
        $this->resolvers->addParameter('resolve_retries', 3);
        $this->resolvers->addParameter('timeout', 'retry 1s');
        $this->resolvers->addParameter('hold', ['valid', '60s']);

        $this->assertTrue($this->resolvers->parameterExists('resolve_retries'));
        $this->assertEquals([3], $this->resolvers->getParameter('resolve_retries'));

        $this->assertTrue($this->resolvers->parameterExists('timeout retry'));
        $this->assertEquals(['1s'], $this->resolvers->getParameter('timeout retry'));

        $this->assertTrue($this->resolvers->parameterExists('hold'));
        $this->assertEquals(['valid', '60s'], $this->resolvers->getParameter('hold'));
    }

    public function testSetComment()
    {
        $this->assertFalse(
            $this->resolvers->hasComment()
        );

        $this->resolvers->setComment(new Comment("Hello world, I'm a comment!"));

        $this->assertTrue(
            $this->resolvers->hasComment()
        );

        $commend = $this->resolvers->getComment();

        $this->assertEquals(new Comment("Hello world, I'm a comment!"), $commend);
    }

    public function testRemoveComment()
    {
        $this->resolvers->setComment(new Comment("Hello world, I'm a comment!"))
        ;

        $this->assertTrue(
            $this->resolvers->hasComment()
        );

        $this->resolvers->removeComment();

        $this->assertFalse(
            $this->resolvers->hasComment()
        );
    }
}
