<?php

namespace HAProxy\Config\Proxy;

use HAProxy\Config\Exception\TextException;
use HAProxy\Config\Parambag;

abstract class Proxy extends Parambag
{
    /**
     * @var string
     */
    protected $name;

    /**
     * Proxy constructor.
     *
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
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
            throw new TextException('Proxy blocks must have a name!');
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
                case 'acl':
                    $class->addAcl($line[1], array_slice($line, 2));
                    break;
                case 'bind':
                    $host = explode(':', $line[1]);
                    if (count($host) != 2) {
                        throw new TextException(sprintf(
                            'Invalid bind parameters for %s "%s"',
                            $class->getType(), $class->getName()
                        ));
                    }
                    $class->bind($host[0], $host[1]);
                    break;
                case 'server':
                    $host = explode(':', $line[2]);
                    if (count($host) != 2) {
                        throw new TextException(sprintf(
                            'Invalid server parameters for %s "%s"',
                            $class->getType(), $class->getName()
                        ));
                    }
                    $class->addServer($line[1], $host[0], $host[1], array_slice($line, 3));
                    break;
                default:
                    $class->addParameter($line[0], array_slice($line, 1));
            }
        }
    }

    /**
     * @param string $fqdnOrIp
     * @param int $port
     *
     * @return static
     */
    public function bind($fqdnOrIp, $port)
    {
        $this->addParameter('bind', "$fqdnOrIp:$port");

        return $this;
    }

    /**
     * @return static
     */
    public function removeBind()
    {
        return $this->removeParameter('bind');
    }

    /**
     * @return bool
     */
    public function hasBind()
    {
        return $this->parameterExists('bind');
    }

    /**
     * Get the details for the bind keyword.
     *
     * @return array|null
     */
    public function getBindDetails()
    {
        return $this->hasBind() ? $this->getParameter('bind') : null;
    }

    /**
     * @param string $name
     * @param string $fqdnOrIp
     * @param int|null $port
     * @param string|array $options
     *
     * @return static
     */
    public function addServer($name, $fqdnOrIp, $port = null, $options = [])
    {
        if ($port) {
            $fqdnOrIp = "$fqdnOrIp:$port";
        }
        $this->addParameter("server $name", array_merge(
            [$fqdnOrIp],
            $this->toArray($options)
        ));

        return $this;
    }

    /**
     * @param string $name
     *
     * @return static
     */
    public function removeServer($name)
    {
        return $this->removeParameter("server $name");
    }

    /**
     * Check if the given server exists.
     *
     * @param string$name
     *
     * @return bool
     */
    public function serverExists($name)
    {
        return $this->parameterExists("server $name");
    }

    /**
     * Get the details for the given server name.
     *
     * @param string$name
     *
     * @return array|null
     */
    public function getServerDetails($name)
    {
        return $this->serverExists($name) ? $this->getParameter("server $name") : null;
    }

    /**
     * @param string $name
     * @param string|array $options
     *
     * @return static
     */
    public function addAcl($name, $options)
    {
        $this->addParameter("acl $name", $options);

        return $this;
    }

    /**
     * @param string $name
     *
     * @return static
     */
    public function removeAcl($name)
    {
        return $this->removeParameter("acl $name");
    }

    /**
     * Check if the given acl exists.
     *
     * @param string$name
     *
     * @return bool
     */
    public function aclExists($name)
    {
        return $this->parameterExists("acl $name");
    }

    /**
     * Get the details for the given ACL name.
     *
     * @param string$name
     *
     * @return array|null
     */
    public function getAclDetails($name)
    {
        return $this->aclExists($name) ? $this->getParameter("acl $name") : null;
    }
}
