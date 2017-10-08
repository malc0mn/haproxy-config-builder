<?php

namespace HAProxy\Config;

use HAProxy\Config\Exception\FileException;
use HAProxy\Config\Exception\InvalidParameterException;
use HAProxy\Config\Exception\TextException;

/**
 * Class Userlist
 *
 * @package HAProxy\Config
 */
class Userlist extends Parambag
{
    /**
     * @var string
     */
    private $name;

    /**
     * Userlist constructor.
     *
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Allow fluid code.
     *
     * @param string $name
     *
     * @return self
     */
    public static function create($name) {
        return new self($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function getType()
    {
        return 'userlist';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    protected static function buildClass(array $line)
    {
        if (count($line) < 2) {
            throw new TextException('Userlists must have a name!');
        }
        return new static($line[1]);
    }

    /**
     * {@inheritdoc}
     */
    protected static function handleLine($class, array $line)
    {
        if (!empty($line[0])) {
            switch ($line[0]) {
                case 'user':
                    // 2 and 4 are indeed omitted.
                    $groups = array_slice($line, 5);
                    if (isset($groups[0])) {
                        $groups = explode(',', $groups[0]);
                    }
                    $class->addUser($line[1], $line[3], $groups);
                    break;
                case 'group':
                    // 2 is indeed omitted
                    $users = array_slice($line, 3);
                    if (isset($users[0])) {
                        $users = explode(',', $users[0]);
                    }
                    $class->addGroup($line[1], $users);
                    break;
                default:
                    throw new FileException(sprintf(
                        'Unable to parse user or group parameters for %s "%s".',
                        $class->getType(), $class->getName()
                    ));
            }
        }
    }

    /**
     * @param string $name
     * @param string|array $users
     *
     * @return self
     */
    public function addGroup($name, $users)
    {
        $this->parameters['groups'][$name] = $this->toArray($users);

        return $this;
    }

    /**
     * Return the data for the given group name.
     *
     * @param string $name
     *
     * @return null|array
     */
    public function getGroupUsers($name)
    {
        return $this->groupExists($name) ? $this->parameters['groups'][$name] : null;
    }

    /**
     * Checks if the given group exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function groupExists($name)
    {
        return isset($this->parameters['groups'][$name]);
    }

    /**
     * @param string $name
     *
     * @return self
     */
    public function removeGroup($name)
    {
        unset($this->parameters['groups'][$name]);

        return $this;
    }

    /**
     * @param string $user
     * @param string $group
     *
     * @return self
     */
    public function addUserToGroup($user, $group)
    {
        if (!in_array($user, $this->parameters['groups'][$group])) {
            $this->parameters['groups'][$group][] = $user;
        }

        return $this;
    }

    /**
     * @param string $group
     * @param string $user
     *
     * @return self
     */
    public function removeUserFromGroup($user, $group)
    {
        $key = array_search($user, $this->parameters['groups'][$group]);
        if ($key !== false) {
            unset($this->parameters['groups'][$group][$key]);
        }

        return $this;
    }

    /**
     * @param string $name
     * @param string $password you should run this through the crypt() function
     *                         using SHA-512 ($6$)!
     *                         Insecure passwords are NOT supported!
     * @param string|array $groups
     *
     * @return self
     */
    public function addUser($name, $password, $groups = [])
    {
        $this->parameters['users'][$name] = [
            'password' => $password,
            'groups' => $this->toArray($groups),
        ];

        return $this;
    }

    /**
     * Return the data for the given user name.
     *
     * @param string $name
     *
     * @return null|array
     */
    public function getUser($name)
    {
        return $this->userExists($name) ? $this->parameters['users'][$name] : null;
    }

    /**
     * Check if the given user exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function userExists($name)
    {
        return isset($this->parameters['users'][$name]);
    }

    /**
     * Get the password for the given user name.
     *
     * @param string $name
     *
     * @return null|array
     */
    public function getUserPassword($name)
    {
        return $this->getUser($name) ? $this->getUser($name)['password'] : null;
    }

    /**
     * @param string $name
     *
     * @return null|array
     */
    public function getUserGroups($name)
    {
        return $this->getUser($name) ? $this->getUser($name)['groups'] : null;
    }

    /**
     * @param string $name
     *
     * @return self
     */
    public function removeUser($name)
    {
        unset($this->parameters['users'][$name]);

        return $this;
    }

    /**
     * @param string $group
     * @param string $user
     *
     * @return self
     */
    public function addGroupToUser($group, $user)
    {
        if (!in_array($group, $this->parameters['users'][$user]['groups'])) {
            $this->parameters['users'][$user]['groups'][] = $group;
        }

        return $this;
    }

    /**
     * @param string $group
     * @param string $user
     *
     * @return self
     */
    public function removeGroupFromUser($group, $user)
    {
        $key = array_search(
            $group,
            $this->parameters['users'][$user]['groups']
        );

        if ($key !== false) {
            unset($this->parameters['users'][$user]['groups'][$key]);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addParameter($keyword, $params = [])
    {
        throw new InvalidParameterException(
            'Adding separate parameters on a user list is not allowed!'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function prettyPrint($indentLevel, $spacesPerIndent = 4)
    {
        $text = '';
        $indent = $this->indent($indentLevel, $spacesPerIndent);

        foreach ($this->parameters['groups'] as $name => $users) {
            $text .= $indent . 'group ' . $name;
            if ($users) {
                $text .= ' users ' . implode(',', $users);
            }
            $text .= "\n";
        }
        // Add empty line for readability.
        if (!empty($text)) {
            $text .= "\n";
        }
        foreach ($this->parameters['users'] as $name => $data) {
            $text .= $indent . 'user ' . $name;
            if ($data['password']) {
                $text .= ' password ' . $data['password'];
            }
            if ($data['groups']) {
                $text .= ' groups ' . implode(',', $data['groups']);
            }
            $text .= "\n";
        }

        if (!empty($text)) {
            // No indent here.
            $text = $this->getType() . ' ' . $this->getName() . "\n" . $text . "\n";
        }

        return $text;
    }
}
