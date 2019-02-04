<?php

namespace HAProxy\Config\Proxy;

class Defaults extends Proxy
{
    /**
     * Proxy constructor.
     *
     * @param string $name
     */
    public function __construct($name = null)
    {
        parent::__construct($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function getType()
    {
        return 'defaults';
    }

    /**
     * @throws \HAProxy\Config\Exception\InvalidParameterException
     */
    public function setPrintPriority($priority)
    {
        $this->throwInvalidParam(__FUNCTION__);
    }

    /**
     * Build the class with the proper constructor.
     *
     * @param array $line
     *
     * @return static
     */
    protected static function buildClass(array $line)
    {
        if (count($line) > 1) {
            return new static($line[1]);
        }
        return new static();
    }

    /**
     * @throws \HAProxy\Config\Exception\InvalidParameterException
     */
    public function bind($fqdnOrIp, $port, $options = [])
    {
        $this->throwInvalidParam(__FUNCTION__);
    }

    /**
     * @throws \HAProxy\Config\Exception\InvalidParameterException
     */
    public function hasBind($fqdnOrIp = '*')
    {
        $this->throwInvalidParam(__FUNCTION__);
    }

    /**
     * @throws \HAProxy\Config\Exception\InvalidParameterException
     */
    public function removeBind($fqdnOrIp = '*')
    {
        $this->throwInvalidParam(__FUNCTION__);
    }

    /**
     * @throws \HAProxy\Config\Exception\InvalidParameterException
     */
    public function addServer($name, $fqdnOrIp, $port = null, $options = [])
    {
        $this->throwInvalidParam('server');
    }

    /**
     * @throws \HAProxy\Config\Exception\InvalidParameterException
     */
    public function serverExists($name)
    {
        $this->throwInvalidParam('server');
    }

    /**
     * @throws \HAProxy\Config\Exception\InvalidParameterException
     */
    public function getServerDetails($name)
    {
        $this->throwInvalidParam('server');
    }

    /**
     * @throws \HAProxy\Config\Exception\InvalidParameterException
     */
    public function removeServer($name)
    {
        $this->throwInvalidParam('server');
    }

    /**
     * @throws \HAProxy\Config\Exception\InvalidParameterException
     */
    public function countServers()
    {
        $this->throwInvalidParam('server');
    }

    /**
     * @throws \HAProxy\Config\Exception\InvalidParameterException
     */
    public function addAcl($name, $options)
    {
        $this->throwInvalidParam('acl');
    }

    /**
     * @throws \HAProxy\Config\Exception\InvalidParameterException
     */
    public function aclExists($name)
    {
        $this->throwInvalidParam('acl');
    }

    /**
     * @throws \HAProxy\Config\Exception\InvalidParameterException
     */
    public function getAclDetails($name)
    {
        $this->throwInvalidParam('acl');
    }

    /**
     * @throws \HAProxy\Config\Exception\InvalidParameterException
     */
    public function removeAcl($name)
    {
        $this->throwInvalidParam('acl');
    }
}
