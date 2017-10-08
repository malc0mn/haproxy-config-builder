<?php

namespace HAProxy\Config\Proxy;

class Listen extends Proxy
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
        return 'listen';
    }
}
