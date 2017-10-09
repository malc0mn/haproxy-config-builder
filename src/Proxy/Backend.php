<?php

namespace HAProxy\Config\Proxy;

class Backend extends Proxy
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
        return 'backend';
    }

    /**
     * @throws \HAProxy\Config\Exception\InvalidParameterException
     */
    public function bind($fqdnOrIp, $port)
    {
        $this->throwInvalidParam(__FUNCTION__);
    }

    /**
     * @throws \HAProxy\Config\Exception\InvalidParameterException
     */
    public function hasBind()
    {
        $this->throwInvalidParam(__FUNCTION__);
    }

    /**
     * @throws \HAProxy\Config\Exception\InvalidParameterException
     */
    public function removeBind()
    {
        $this->throwInvalidParam(__FUNCTION__);
    }
}
