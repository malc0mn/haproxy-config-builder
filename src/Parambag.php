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
    const EMPTY_LINE_KEY = '$emptyLine$';

    /**
     * @var array
     */
    protected $parameters;

    /**
     * @var Comment
     */
    protected $comment;

    /**
     * @var array
     */
    protected $order;

    /**
     * @var bool
     */
    protected $orderGroup = false;

    /**
     * @var array
     */
    protected $allowDuplicate = ['timeout', 'reqrep'];

    /**
     * @var int
     */
    protected $emptyLineCounter = 0;

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
            $class->addParameter($line[0], array_slice($line, 1));
        } else {
            $class->addEmptyLine();
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
        if (in_array($keyword, $this->allowDuplicate)) {
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
     * Adds a placeholder to render an empty line.
     *
     * @return $this
     */
    public function addEmptyLine()
    {
        $this->addParameter(self::EMPTY_LINE_KEY . $this->emptyLineCounter++);
        return $this;
    }

    /**
     * Set ordering of parameters.
     *
     * @param array $order
     * @param bool $group Whether or not to 'group' the output by separating
     *                    groups of parameters that start with the same keyword
     *                    with an empty line
     *
     * @return $this
     */
    public function setParameterOrder(array $order, $group = false)
    {
        $this->order = array_fill_keys($order, null);
        $this->orderGroup = $group;
        return $this;
    }

    /**
     * Get parameter ordering.
     *
     * @return array
     */
    public function getParameterOrder()
    {
        return $this->order;
    }

    /**
     * Order the parameters as requested.
     *
     * @return array
     */
    public function getOrderedParameters() {
        if (!empty($this->order)) {
            $sorted = [];
            // We need to work on a copy since we will be unsetting stuff...
            $paramsCopy = $this->parameters;

            $i = 0;
            $len = count($this->order);
            // $_ as a means to indicate we won't be using this variable!
            foreach ($this->order as $key => $_) {
                $i++;
                foreach ($paramsCopy as $parameter => $options) {
                    // The stripos() approach is used as certain parameters can
                    // occur multiple times. For example:
                    //   the user requests order ['acl', 'use_backend'], so we
                    //   need to look for ALL ACLs whose keys will be
                    //   'acl [name]' which is why we cannot just compare the
                    //   keys alone.
                    if ($key === $parameter || stripos($parameter, "$key ") === 0) {
                        $sorted[$parameter] = $paramsCopy[$parameter];
                        unset($paramsCopy[$parameter]);
                    }
                }
                // Add empty line after keyword 'group' when requested, except
                // for the last one.
                if ($i < $len && $this->orderGroup) {
                    $sorted[self::EMPTY_LINE_KEY . $this->emptyLineCounter++] = [];
                }
            }

            return array_merge($sorted, $paramsCopy);
        }
        return $this->parameters;
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

        foreach ($this->getOrderedParameters() as $keyword => $params) {
            if (stripos($keyword, self::EMPTY_LINE_KEY) === 0) {
                $text .= "\n";
                continue;
            }

            $glue = ' ';
            if (stripos($keyword, 'bind ') === 0) {
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
