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
     * @var array
     */
    protected $parameterPrios;

    /**
     * @var Comment
     */
    protected $comment;

    /**
     * @var array
     */
    protected $order;

    /**
     * @var array
     */
    protected $allowDuplicate = [
        'errorfile' => 1,
        'http-request' => 3,
        'http-response' => 3,
        'option' => 1,
        'redirect' => 5,
        'reqrep' => 1,
        'reqirep' => 1,
        'stats' => 1,
        'tcp-check' => 2,
        'timeout' => 1,
        'filter' => 1,
        'compression' => 1,
    ];

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

        // Use static here, so that we call the buildClass() method from
        // the class that extends from us!
        $class = static::buildClass($line);

        $configString->gotoNextEol();
        $configString->inc();

        while ($configString->eof() === false) {
            // Split after the first space encountered.
            $line = explode(' ', self::cleanLine($configString->getRestOfTheLine()));

            // Return the new class if we encounter a new section.
            if (self::isSection($line[0])) {
                return $class;
            }

            // Use static here, so that we call the handleLine() method from
            // the class that extends from us!
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
        return in_array($string, ['global', 'defaults', 'resolvers', 'userlist', 'frontend', 'backend', 'listen']);
    }

    /**
     * Helper to make sure we have an array.
     *
     * @param string|array $params
     * @param string $keyword
     *
     * @return array
     */
    protected function toArray($params, $keyword = null)
    {
        if (!is_array($params)) {
            if ($keyword && stripos($keyword, 'req') === 0) {
                return [$params];
            }

            $params = explode(' ', $params);
        }

        return $params;
    }

    /**
     * What this method will do is convert something like this:
     *     [
     *         'is_host',
     *         '||',
     *         'is_path is_host',   // Note the space here!!
     *         '||',
     *         'is_path1',
     *         'is_host2 is_path2', // Note the space here!!
     *     ]
     *
     * To a 'real' flat array like this:
     *     [
     *         'is_host',
     *         '||',
     *         'is_path',
     *         'is_host',
     *         '||',
     *         'is_path1',
     *         'is_host2',
     *         'is_path2',
     *     ]
     *
     * @param array $params
     */
    protected function toFullFlatArray(array $params)
    {
        if (!empty($params)) {
            // Explode all elements in the array on spaces.
            array_walk(
                $params,
                function (&$item) {
                    $item = explode(' ', $item);
                }
            );

            // Flatten the array back to a single dimensional array.
            return call_user_func_array('array_merge', $params);
        }
        return $params;
    }

    /**
     * Add a parameter to the bag.
     *
     * @param string $keyword
     * @param string|array $params
     * @param int|null $prio
     *
     * @return static
     */
    public function addParameter($keyword, $params = [], $prio = null)
    {
        $params = $this->toArray($params, $keyword);
        // Handle keywords that can occur multiple times.
        if (array_key_exists($keyword, $this->allowDuplicate)) {
            $oldKey = $keyword;
            $keyword = $keyword . ' ' . implode(' ', array_slice($params, 0, $this->allowDuplicate[$oldKey]));
            $params = array_slice($params, $this->allowDuplicate[$oldKey]);
        }
        $this->parameters[$keyword] = $params;

        if (is_int($prio)) {
            $this->parameterPrios[$keyword] = $prio;
        }

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
        unset(
            $this->parameters[$keyword],
            $this->parameterPrios[$keyword]
        );

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
     * @param string $param
     *
     * @return array|null
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
     * Get the data for the given parameter.
     *
     * @param string $param
     *
     * @return array|null
     */
    public function getParameterPrio($param)
    {
        return $this->parameterExists($param) ? $this->parameterPrios[$param] : null;
    }

    /**
     * Get the data for the given parameter.
     *
     * @param string $param
     * @param int $prio
     *
     * @return bool true on success, false on failure
     */
    public function setParameterPrio($param, $prio)
    {
        if ($this->parameterExists($param) && is_int($prio)) {
            $this->parameterPrios[$param] = $prio;
            return true;
        }
        return false;
    }

    /**
     * Check if the given parameter has a priority.
     *
     * @param string $param
     *
     * @return bool
     */
    public function parameterHasPrio($param)
    {
        return $this->parameterExists($param) && isset($this->parameterPrios[$param]);
    }

    /**
     * Get the size of the longest keyword in the bag.
     *
     * @return int
     */
    public function getLongestKeywordSize()
    {
        return strlen(array_reduce(array_keys($this->parameters), function ($a, $b) {
            $a = explode(' ', $a)[0];
            $b = explode(' ', $b)[0];
            return strlen($a) > strlen($b) ? $a : $b;
        }));
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
     * @param array $order A single dimensional array containing the keywords in
     *                     the order you want them to be rendered.
     *                       OR
     *                     A multi dimensional array containing the keywords in
     *                     the order you want them to be rendered AS KEY and the
     *                     value set to TRUE or FALSE indicating to add a
     *                     a trailing empty line after the 'block' of keywords.
     *
     * @return $this
     */
    public function setParameterOrder(array $order)
    {
        $this->order = $order;

        $grouping = array_filter($order,'is_bool');
        if (count($grouping) === 0) {
            $this->order = array_fill_keys($order, false);
        }

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
        // First sort on the requested parameter prios.
        if (!empty($this->parameterPrios) && is_array($this->parameterPrios)) {
            asort($this->parameterPrios);
            $this->parameters = array_merge($this->parameterPrios, $this->parameters);
        }

        // Now we make sure we have the right output ORDER.
        if (!empty($this->order)) {
            $sorted = [];
            // We need to work on a copy since we will be unsetting stuff...
            $paramsCopy = $this->parameters;

            $i = 0;
            $emptyLines = 0;
            $len = count($this->order);
            foreach ($this->order as $key => $trailingEmptyLine) {
                $i++;
                $found = false;
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
                        $found = true;
                    }
                }
                // Add empty line after keyword 'group' when requested, except
                // for the last one.
                if ($found && $i < $len && $trailingEmptyLine) {
                    $sorted[self::EMPTY_LINE_KEY . $emptyLines++] = [];
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
        $maxKeyLength = $this->getLongestKeywordSize();

        if ($this->hasComment()) {
            $comment = $this->comment->prettyPrint($indentLevel-1, $spacesPerIndent);
        }

        foreach ($this->getOrderedParameters() as $keyword => $params) {
            $text .= $this->printLine($keyword, $params, $maxKeyLength, $indent);
        }

        if (!empty($text)) {
            // No indent here.
            $text = $comment . $this->getType() . ($this->getName() ? ' ' . $this->getName() : '') . "\n" . $text . "\n";
        }

        return $text;
    }

    /**
     * Helper to print a single keyword/params line.
     *
     * @param string $keyword
     * @param array $params
     * @param int $maxKeyLength
     * @param int $indent
     *
     * @return string
     */
    protected function printLine($keyword, $params, $maxKeyLength, $indent)
    {
        if (stripos($keyword, self::EMPTY_LINE_KEY) === 0) {
            return "\n";
        }

        $glue = ' ';
        if (stripos($keyword, 'bind ') === 0) {
            $glue = '';
        }
        // TODO: properly handle comments!!!
        if (strpos($keyword, '#') !== 0) {
            $keyword = explode(' ', $keyword);
            $keyword = str_pad($keyword[0], $maxKeyLength) . (isset($keyword[1]) ? ' ' . implode(' ', array_slice($keyword, 1)) : '');
        }
        return $indent . trim($keyword . $glue . implode(' ', $params)) . "\n";
    }
}
