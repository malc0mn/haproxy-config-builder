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
     * @var Comment
     */
    protected $comment;

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
        $line = explode(' ', self::cleanLine($configString->getRestOfTheLine()), 2);

        $class = static::buildclass($line);

        $configString->gotoNextEol();
        $configString->inc();

        while ($configString->eof() === false) {
            // Split after the first space encountered.
            $line = explode(' ', self::cleanLine($configString->getRestOfTheLine()));

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
     * Helper function to reduce all spaces to a single space.
     *
     * @param string $line
     *
     * @return mixed
     */
    protected static function cleanLine($line)
    {
        return preg_replace('!\s+!', ' ', trim($line));
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

    /**
     * Remove a parameter by keyword.
     *
     * @param string $keyword
     *
     * @return $this
     */
    public function removeParameter($keyword)
    {
        unset($this->parameters[$keyword]);

        return $this;
    }

    /**
     * Throw an invalid parameter exception.
     *
     * @param string $text
     *
     * @throws InvalidParameterException
     */
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
     * Add comment to the top of a proxy block.
     *
     * @param Comment $comment
     *
     * @return $this
     */
    public function setComment(Comment $comment)
    {
        $this->comment = $comment;
        return $this;
    }

    /**
     * Get comment from proxy block.
     *
     * @return Comment
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Check if the proxy block has a comment.
     *
     * @return bool
     */
    public function hasComment()
    {
        return $this->comment !== null;
    }

    /**
     * Remove comment from proxy block.
     *
     * @return $this
     */
    public function removeComment()
    {
        $this->comment = null;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function prettyPrint($indentLevel, $spacesPerIndent = 4)
    {
        $text = '';
        $comment = '';
        $indent = $this->indent($indentLevel, $spacesPerIndent);

        if ($this->hasComment()) {
            $comment = $this->comment->prettyPrint($indentLevel-1, $spacesPerIndent);
        }

        foreach ($this->parameters as $keyword => $params) {
            $glue = ' ';
            if (stripos($keyword, 'bind') === 0) {
                $glue = '';
            }
            $text .= $indent . trim($keyword . $glue . implode(' ', $params)) . "\n";
        }

        if (!empty($text)) {
            // No indent here.
            $text = $comment . $this->getType() . ($this->getName() ? ' ' . $this->getName() : '') . "\n" . $text . "\n";
        }

        return $text;
    }
}
