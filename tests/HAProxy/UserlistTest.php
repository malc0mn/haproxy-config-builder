<?php

namespace HAProxy\Config\Tests;

use HAProxy\Config\Comment;
use HAProxy\Config\Userlist;
use PHPUnit\Framework\TestCase;

class UserlistTest extends TestCase
{
    /**
     * @var Userlist
     */
    private $userlist;

    protected function setUp(): void
    {
        $this->userlist = new Userlist('test');
    }

    public function testConstruct()
    {
        $userlist = new Userlist('test');
        $this->assertInstanceOf('HAProxy\Config\Userlist', $userlist);
    }

    public function testFactory()
    {
        $userlist = Userlist::create('test');
        $this->assertInstanceOf('HAProxy\Config\Userlist', $userlist);
    }

    public function testName()
    {
        $userlist = new Userlist('test');

        $this->assertEquals(
            'test',
            $userlist->getName()
        );
    }

    public function testAddGroup()
    {
        $this->userlist
            ->addGroup('developers', ['henry', 'jules'])
        ;

        $this->assertTrue(
            $this->userlist->groupExists('developers')
        );

        $this->assertEquals(
            ['henry', 'jules'],
            $this->userlist->getGroupUsers('developers')
        );
    }

    public function testRemoveGroup()
    {
        $this->userlist
            ->addGroup('developers', ['henry', 'jules'])
            ->addGroup('noobs', ['willy', 'vernon'])
        ;

        $this->userlist
            ->removeGroup('developers')
        ;

        $this->assertFalse(
            $this->userlist->groupExists('developers')
        );
        $this->assertTrue(
            $this->userlist->groupExists('noobs')
        );
    }

    public function testAddUserToGroup()
    {
        $this->userlist
            ->addGroup('developers', ['henry', 'jules'])
            ->addGroup('noobs', ['willy', 'vernon'])
        ;

        $this->userlist->addUserToGroup('johny', 'noobs');

        $this->assertNotContains(
            'johny',
            $this->userlist->getGroupUsers('developers')
        );
        $this->assertContains(
            'johny',
            $this->userlist->getGroupUsers('noobs')
        );
    }

    public function testRemoveUserFromGroup()
    {
        $this->userlist
            ->addGroup('developers', ['henry', 'jules', 'vernon'])
            ->addGroup('noobs', ['willy', 'vernon'])
        ;

        $this->userlist->removeUserFromGroup('vernon', 'noobs');

        $this->assertNotContains(
            'vernon',
            $this->userlist->getGroupUsers('noobs')
        );
        $this->assertContains(
            'vernon',
            $this->userlist->getGroupUsers('developers')
        );
    }

    public function testAddUser()
    {
        $this->userlist
            ->addUser('henry', 'somepass', ['noobs'])
        ;

        $this->assertTrue(
            $this->userlist->userExists('henry')
        );
        $this->assertEquals(
            'somepass',
            $this->userlist->getUserPassword('henry')
        );
        $this->assertEquals(
           ['noobs'],
            $this->userlist->getUserGroups('henry')
        );
    }

    public function testRemoveUser()
    {
        $this->userlist
            ->addUser('henry', 'somepass', ['noobs'])
            ->addUser('jules', 'somepass', ['noobs'])
        ;
        $this->userlist
            ->removeUser('henry')
        ;

        $this->assertFalse(
            $this->userlist->userExists('henry')
        );
        $this->assertTrue(
            $this->userlist->userExists('jules')
        );
    }

    public function testAddGroupToUser()
    {
        $this->userlist
            ->addUser('henry', 'somepass', ['noobs'])
            ->addUser('jules', 'somepass', ['noobs', 'developers'])
        ;

        $this->userlist->addGroupToUser('hipsters', 'jules');

        $this->assertNotContains(
            'hipsters',
            $this->userlist->getUserGroups('henry')
        );
        $this->assertContains(
            'hipsters',
            $this->userlist->getUserGroups('jules')
        );
    }

    public function testRemoveGroupFromUser()
    {
        $this->userlist
            ->addUser('henry', 'somepass', ['noobs'])
            ->addUser('jules', 'somepass', ['noobs', 'developers'])
        ;

        $this->userlist->removeGroupFromUser('noobs', 'jules');

        $this->assertNotContains(
            'noobs',
            $this->userlist->getUserGroups('jules')
        );
        $this->assertContains(
            'developers',
            $this->userlist->getUserGroups('jules')
        );
    }

    public function testAddParameter()
    {
        $this->expectException('HAProxy\Config\Exception\InvalidParameterException');
        $this->expectExceptionMessage('Adding separate parameters on a user list is not allowed!');

        $this->userlist->addParameter('mode', 'http');
    }

    public function testSetComment()
    {
        $this->assertFalse(
            $this->userlist->hasComment()
        );

        $this->userlist->setComment(new Comment("Hello world, I'm a comment!"));

        $this->assertTrue(
            $this->userlist->hasComment()
        );

        $commend = $this->userlist->getComment();

        $this->assertEquals(new Comment("Hello world, I'm a comment!"), $commend);
    }

    public function testRemoveComment()
    {
        $this->userlist->setComment(new Comment("Hello world, I'm a comment!"))
        ;

        $this->assertTrue(
            $this->userlist->hasComment()
        );

        $this->userlist->removeComment();

        $this->assertFalse(
            $this->userlist->hasComment()
        );
    }
}
