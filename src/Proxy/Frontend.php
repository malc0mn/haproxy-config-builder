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
}
