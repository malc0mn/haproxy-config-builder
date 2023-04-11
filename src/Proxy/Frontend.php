<?php

namespace HAProxy\Config\Proxy;

use HAProxy\Config\Exception\InvalidParameterException;

class Frontend extends Proxy
{
    const TAG_DELIMITER = '|';

    /**
     * @var array
     */
    protected $useBackendConditions;

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
     * {@inheritdoc}
     *
     * @throws InvalidParameterException
     */
    public function addParameter($keyword, $params = [], $prio = null)
    {
        if (!empty($keyword)) {
            switch ($keyword) {
                case 'use_backend':
                    $params = $this->toArray($params, $keyword);

                    if (empty($params)) {
                        throw new InvalidParameterException("The 'use_backend' keyword expects at least one parameter!");
                    }

                    $this->addUseBackend($params[0], null, $prio);

                    // If this 'use_backend' call has conditions, parse them as
                    // well.
                    if (isset($params[1]) && in_array($params[1], ['if', 'unless'])) {
                        $this->addUseBackendWithConditions(
                            $params[0], array_slice($params, 2), $params[1]
                        );
                    }

                    break;
                default:
                    parent::addParameter($keyword, $params, $prio);
            }
        }

        return $this;
    }

    /**
     * For internal use only.
     *
     * @param string|null $tag
     *
     * @return string
     */
    protected function addTag($tag = null)
    {
        if ($tag !== null) {
            return self::TAG_DELIMITER . $tag;
        }
        return '';
    }

    /**
     * For internal use only.
     *
     * @param string $name
     *
     * @return string
     */
    protected function stripTag($name)
    {
        return explode(self::TAG_DELIMITER, $name)[0];
    }

    /**
     * Add 'use_backend' parameter.
     *
     * @param string $name
     * @param string|null $tag
     * @param int|null $prio
     *
     * @return static
     */
    public function addUseBackend($name, $tag = null, $prio = null)
    {
        parent::addParameter("use_backend $name" . $this->addTag($tag), [], $prio);

        return $this;
    }

    /**
     * Add 'use_backend' parameter with conditions OR add conditions to an
     * existing 'use_backend' parameter.
     *
     * @param string $name
     * @param array $conditions
     * @param string $test
     * @param string|null $tag
     * @param int|null $prio
     *
     * @return static
     */
    public function addUseBackendWithConditions(
        $name,
        array $conditions,
        $test = 'if',
        $tag = null,
        $prio = null
    ) {
        $or = '||';

        if (!$this->useBackendExists($name, $tag)) {
            $this->addUseBackend($name, $tag, $prio);
        }

        $backend = $name . $this->addTag($tag);

        $conditions = $this->toFullFlatArray($conditions);

        $grouped = [$conditions];
        if (in_array($or, $conditions, true)) {
            $grouped = [];
            foreach ($conditions as $condition) {
                if ($condition !== $or) {
                    $parts[] = $condition;
                    continue;
                }
                $grouped[] = $parts;
                $parts = [];
            }
            $grouped[] = $parts;
        }

        if (!empty($conditions)) {
            if (!isset($this->useBackendConditions[$backend]['conditions'])) {
                $this->useBackendConditions[$backend]['conditions'] = [];
            }
            $this->useBackendConditions[$backend]['test'] = $test;
            // The array_unique here is to filter out any double condition
            // groups that might be present.
            $this->useBackendConditions[$backend]['conditions'] = array_unique(array_merge(
                $this->useBackendConditions[$backend]['conditions'],
                $grouped
            ), SORT_REGULAR);
        }

        return $this;
    }

    /**
     * Check if a given 'use_backend' statement has conditions.
     *
     * @param string $name
     * @param string|null $tag
     *
     * @return bool
     */
    public function useBackendHasConditions($name, $tag = null)
    {
        $backend = $name . $this->addTag($tag);
        return $this->useBackendExists($name, $tag) && !empty($this->useBackendConditions[$backend]['conditions']);
    }

    /**
     * Check if a given 'use_backend' statement has conditions.
     *
     * @param string $name
     * @param string|null $tag
     *
     * @return array|null
     */
    public function useBackendGetConditions($name, $tag = null)
    {
        $backend = $name . $this->addTag($tag);
        return $this->useBackendHasConditions($name, $tag) ? $this->useBackendConditions[$backend] : null;
    }

    /**
     * Remove 'use_backend' parameter.
     *
     * @param string $name
     * @param string|null $tag
     *
     * @return static
     */
    public function removeUseBackend($name, $tag = null)
    {
        return $this->removeParameter("use_backend $name" . $this->addTag($tag));
    }

    /**
     * Check if 'use_backend' parameter exists.
     *
     * @param string $name
     * @param string|null $tag
     *
     * @return bool
     */
    public function useBackendExists($name, $tag = null)
    {
        return $this->parameterExists("use_backend $name" . $this->addTag($tag));
    }

    /**
     * Get the details of the given 'use_backend'.
     *
     * @param string $name
     * @param string|null $tag
     *
     * @return array|null
     */
    public function getUseBackendDetails($name, $tag = null)
    {
        return $this->useBackendGetConditions($name . $this->addTag($tag));
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
            // This is not a good solution, it should be something universal but
            // this requires a thorough rewrite of this ancient and not so good
            // library.
            if (stripos($keyword, 'use_backend') === 0) {
                $name = explode(' ', $keyword)[1];

                if ($conditions = $this->useBackendGetConditions($name)) {
                    $keyword = $this->stripTag($keyword);
                    $keyword .= ' ' . $conditions['test'];
                    // HAProxy has an argument limit of 64 (MAX_LINE_ARGS)! We
                    // take some margin here to be sure.
                    $orSize = -1;
                    $argSize = 0;
                    $print = [];
                    foreach ($conditions['conditions'] as $condition) {
                        // This loop will print multiple use_backend $name lines
                        // with respect to HAPRoxy's argument limit.
                        $argSize += count($condition);
                        $orSize++;
                        if ($argSize + $orSize > 60) {
                            $text .= $this->printLine($keyword, [implode(' || ', $print)], $maxKeyLength, $indent);
                            $orSize = -1;
                            // Do not reset to 0 here!
                            $argSize = count($condition);
                            $print = [];
                        }
                        $print[] = implode(' ', $condition);
                    }
                    $text .= $this->printLine($keyword, [implode(' || ', $print)], $maxKeyLength, $indent);
                    continue;
                }
            }

            // Another bad solution, but it will work :/
            if (stripos($keyword, 'acl') === 0) {
                if (count($params) > 60) {
                    $offset = 1;
                    if (stripos($params[1], '-') === 0) {
                        $offset = 2;
                        if (in_array($params[1], ['-f', '-m'])) {
                            $offset = 3;
                        }
                    }
                    $base = array_slice($params, 0, $offset);
                    $parts = array_chunk(array_slice($params, $offset), 59);
                    foreach ($parts as $i => $part) {
                        $text .= $this->printLine($this->stripTag($keyword), array_merge($base, $part), $maxKeyLength, $indent);
                    }
                    continue;
                }
            }

            $text .= $this->printLine($this->stripTag($keyword), $params, $maxKeyLength, $indent);
        }

        if (!empty($text)) {
            // No indent here.
            $text = $comment . $this->getType() . ($this->getName() ? ' ' . $this->getName() : '') . "\n" . $text . "\n";
        }

        return $text;
    }
}
