<?php

namespace HAProxy\Config\Proxy;

class Frontend extends Proxy
{
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
        return 'frontend';
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
}
