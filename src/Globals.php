<?php

namespace HAProxy\Config;

use HAProxy\Config\Exception\TextException;

/**
 * Class Globals
 *
 * Ideally this class would have been named 'Global' to match the HAProxy
 * section name, but alas...
 *
 * @package HAProxy\Config
 */
class Globals extends Parambag
{
    /**
     * {@inheritdoc}
     */
    protected function getType()
    {
        return 'global';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected static function buildClass(array $line)
    {
        if (count($line) > 1) {
            throw new TextException('Global does not have a name!');
        }
        return new static();
    }

    /**
     * {@inheritdoc}
     */
    protected static function handleLine($class, array $line)
    {
        if (in_array($line[0], ['debug', 'quiet', 'daemon'])) {
            $class->{$line[0]}(true);
        } elseif (!empty($line[0])) {
            parent::handleLine($class, $line);
        }
    }

    /**
     * Add special debug option.
     *
     * @param bool $bool
     */
    public function debug($bool = true)
    {
        if ($bool) {
            $this->addParameter('debug');
        } else {
            $this->removeParameter('debug');
        }
    }

    /**
     * Check if debug flag is set.
     *
     * @return bool
     */
    public function isDebug()
    {
        return $this->parameterExists('debug');
    }

    /**
     * Add special quiet option.
     *
     * @param bool $bool
     */
    public function quiet($bool = true)
    {
        if ($bool) {
            $this->addParameter('quiet');
        } else {
            $this->removeParameter('quiet');
        }
    }

    /**
     * Check if quiet flag is set.
     *
     * @return bool
     */
    public function isQuiet()
    {
        return $this->parameterExists('quiet');
    }

    /**
     * Add special debug option.
     *
     * @param bool $bool
     */
    public function daemon($bool = true)
    {
        if ($bool) {
            $this->addParameter('daemon');
        } else {
            $this->removeParameter('daemon');
        }
    }

    /**
     * Check if daemon flag is set.
     *
     * @return bool
     */
    public function isDaemon()
    {
        return $this->parameterExists('daemon');
    }
}
