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
     * @var bool
     */
    private $debug;

    /**
     * @var bool
     */
    private $quiet;

    /**
     * @var bool
     */
    private $daemon;

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
        $this->debug = $bool;
    }

    /**
     * Check if debug flag is set.
     *
     * @return bool
     */
    public function isDebug()
    {
        return (bool)$this->debug;
    }

    /**
     * Add special quiet option.
     *
     * @param bool $bool
     */
    public function quiet($bool = true)
    {
        $this->quiet = $bool;
    }

    /**
     * Check if quiet flag is set.
     *
     * @return bool
     */
    public function isQuiet()
    {
        return (bool)$this->quiet;
    }

    /**
     * Add special debug option.
     *
     * @param bool $bool
     */
    public function daemon($bool = true)
    {
        $this->daemon = $bool;
    }

    /**
     * Check if daemon flag is set.
     *
     * @return bool
     */
    public function isDaemon()
    {
        return (bool)$this->daemon;
    }

    /**
     * {@inheritdoc}
     */
    public function prettyPrint($indentLevel, $spacesPerIndent = 4)
    {
        $text = parent::prettyPrint($indentLevel, $spacesPerIndent);

        if ($text) {
            // Remove blank line.
            $text = trim($text) . "\n";

            $indent = $this->indent($indentLevel, $spacesPerIndent);

            if ($this->quiet) {
                $text .= $indent . "quiet\n";
            }

            if ($this->debug) {
                $text .= $indent . "debug\n";
            }

            if ($this->daemon) {
                $text .= $indent . "daemon\n";
            }
            $text .= "\n";
        }

        return $text;
    }
}
