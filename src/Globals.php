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
    const OPTION_DAEMON = 'daemon';
    const OPTION_DEBUG = 'debug';
    const OPTION_QUIET = 'quiet';

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
     *
     * @throws TextException
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
        if (in_array($line[0], [self::OPTION_DAEMON, self::OPTION_DEBUG, self::OPTION_QUIET], true)) {
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
            $this->addParameter(self::OPTION_DEBUG);
        } else {
            $this->removeParameter(self::OPTION_DEBUG);
        }
    }

    /**
     * Check if debug flag is set.
     *
     * @return bool
     */
    public function isDebug()
    {
        return $this->parameterExists(self::OPTION_DEBUG);
    }

    /**
     * Add special quiet option.
     *
     * @param bool $bool
     */
    public function quiet($bool = true)
    {
        if ($bool) {
            $this->addParameter(self::OPTION_QUIET);
        } else {
            $this->removeParameter(self::OPTION_QUIET);
        }
    }

    /**
     * Check if quiet flag is set.
     *
     * @return bool
     */
    public function isQuiet()
    {
        return $this->parameterExists(self::OPTION_QUIET);
    }

    /**
     * Add special daemon option.
     *
     * @param bool $bool
     */
    public function daemon($bool = true)
    {
        if ($bool) {
            $this->addParameter(self::OPTION_DAEMON);
        } else {
            $this->removeParameter(self::OPTION_DAEMON);
        }
    }

    /**
     * Check if daemon flag is set.
     *
     * @return bool
     */
    public function isDaemon()
    {
        return $this->parameterExists(self::OPTION_DAEMON);
    }
}
