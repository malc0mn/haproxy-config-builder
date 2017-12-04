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
     * Add 'use_backend' parameter.
     *
     * @param string $name
     * @param string|array $options
     *
     * @return static
     */
    public function addUseBackend($name, $options)
    {
        $this->addParameter("use_backend $name", $options);

        return $this;
    }

    /**
     * Remove 'use_backend' parameter.
     *
     * @param string $name
     *
     * @return static
     */
    public function removeUseBackend($name)
    {
        return $this->removeParameter("use_backend $name");
    }

    /**
     * Check if 'use_backend' parameter exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function useBackendExists($name)
    {
        return $this->parameterExists("use_backend $name");
    }

    /**
     * Get the details of the given 'use_backend'.
     *
     * @param string $name
     *
     * @return array|null
     */
    public function getUseBackendDetails($name)
    {
        return $this->useBackendExists($name) ? $this->getParameter("use_backend $name") : null;
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
}
