<?php

namespace HAProxy\Config;

use HAProxy\Config\Exception\InvalidParameterException;
use HAProxy\Config\Exception\TextException;

/**
 * Class Parambag
 *
 * Just a bag to hold parameters which is the base for all things HAProxy.
 *
 * @package HAProxy\Config
 */
abstract class Parambag extends Printable
{
    /**
     * @var array
     */
    protected $parameters;

    /**
     * Returns the type of the parameter bag.
     *
     * @return string
     */
    abstract protected function getType();

    /**
     * Returns the name of the parameter bag.
     *
     * @return string
     */
    abstract public function getName();

    /**
     * Create a parameter bag from a Text object containing the HAProxy config
     * to be converted.
     *
     * @param Text $configString
     *
     * @return static
     *
     * @throws TextException
     */
    public static function fromString(Text $configString)
    {
        $line = explode(' ', trim($configString->getRestOfTheLine()), 2);

        $class = static::buildclass($line);

        $configString->gotoNextEol();
        $configString->inc();

        while ($configString->eof() === false) {
            // Split after the first space encountered.
            $line = explode(' ', trim($configString->getRestOfTheLine()));

            // Return the new class if we encounter a new section.
            if (self::isSection($line[0])) {
                return $class;
            }

            // Use static here, NOT self!!
            static::handleLine($class, $line);

            $configString->gotoNextEol();
            $configString->inc();

            // Return the new class if we are at the end of the file!
            if ($configString->eof()) {
                return $class;
            }
        }
        throw new TextException('Could not parse parameters.');
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
     * Parse the given line to parameters for the given class.
     *
     * @param object $class
     * @param array $line
     */
    protected static function handleLine($class, array $line)
    {
        if (!empty($line[0])) {
            $class->addParameter($line[0], isset($line[1]) ? $line[1] : []);
        }
    }

    /**
     * Determine if we encountered a new section.
     *
     * @param $string
     *
     * @return bool
     */
    protected static function isSection($string)
    {
        return in_array($string, ['global', 'defaults', 'userlist', 'frontend', 'backend', 'listen']);
    }

    /**
     * Helper to make sure we have an array.
     *
     * @param string|array $params
     *
     * @return array
     */
    protected function toArray($params)
    {
        if (!is_array($params)) {
            $params = [$params];
        }

        return $params;
    }

    /**
     * Add a parameter to the bag.
     *
     * @param string $keyword
     * @param string|array $params
     *
     * @return static
     */
    public function addParameter($keyword, $params = [])
    {
        $params = $this->toArray($params);
        // This is a bit of an odd exception. If you have a better way to handle
        // this one, create a pull request ;-)
        if ($keyword == 'timeout') {
            $keyword = $keyword . ' ' . array_shift($params);
        }
        $this->parameters[$keyword] = $params;

        return $this;
    }

    protected function throwInvalidParam($text)
    {
        throw new InvalidParameterException(sprintf(
            '%s is not allowed on a %s proxy block!',
            ucfirst($text), $this->getType()
        ));
    }

    /**
     * Get the data for the given parameter.
     *
     * @return array
     */
    public function getParameter($param)
    {
        return $this->parameterExists($param) ? $this->parameters[$param] : null;
    }

    /**
     * Check if the given parameter exists.
     *
     * @param string $param
     *
     * @return bool
     */
    public function parameterExists($param)
    {
        return isset($this->parameters[$param]);
    }

    /**
     * {@inheritdoc}
     */
    public function prettyPrint($indentLevel, $spacesPerIndent = 4)
    {
        $text = '';
        $indent = $this->indent($indentLevel, $spacesPerIndent);

        foreach ($this->parameters as $keyword => $params) {
            $text .= $indent . $keyword . ' ' . implode(' ', $params) . "\n";
        }

        if (!empty($text)) {
            // No indent here.
            $text = $this->getType() . ($this->getName() ? ' ' . $this->getName() : '') . "\n" . $text . "\n";
        }

        return $text;
    }
}
