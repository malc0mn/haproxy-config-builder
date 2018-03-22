<?php

namespace HAProxy\Config\Proxy;

use HAProxy\Config\Exception\InvalidParameterException;

class Frontend extends Proxy
{
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
    public function addParameter($keyword, $params = [])
    {
        if (!empty($keyword)) {
            switch ($keyword) {
                case 'use_backend':
                    $params = $this->toArray($params, $keyword);

                    if (empty($params)) {
                        throw new InvalidParameterException("The 'use_backend' keyword expects at least one parameter!");
                    }

                    $this->addUseBackend($params[0]);

                    // If this 'use_backend' call has conditions, parse them as
                    // well.
                    if (isset($params[1]) && in_array($params[1], ['if', 'unless'])) {
                        $this->addUseBackendWithConditions(
                            $params[0], array_slice($params, 2), $params[1]
                        );
                    }

                    break;
                default:
                    parent::addParameter($keyword, $params);
            }
        }

        return $this;
    }

    /**
     * Add 'use_backend' parameter.
     *
     * @param string $name
     *
     * @return static
     */
    public function addUseBackend($name)
    {
        parent::addParameter("use_backend $name");

        return $this;
    }

    /**
     * Add 'use_backend' parameter with conditions OR add conditions to an
     * existing 'use_backend' parameter.
     *
     * @param string $name
     * @param array $conditions
     * @param string $test
     *
     * @return static
     */
    public function addUseBackendWithConditions($name, array $conditions, $test = 'if')
    {
        $or = '||';

        if (!$this->useBackendExists($name)) {
            $this->addUseBackend($name);
        }

        $grouped = [$conditions];
        if (in_array($or, $conditions)) {
            $grouped = [];
            foreach ($conditions as $condition) {
                if ($condition != $or) {
                    $parts[] = $condition;
                    continue;
                }
                $grouped[] = $parts;
                $parts = [];
            }
            $grouped[] = $parts;
        }

        if (!empty($conditions)) {
            if (!isset($this->useBackendConditions[$name]['conditions'])) {
                $this->useBackendConditions[$name]['conditions'] = [];
            }
            $this->useBackendConditions[$name]['test'] = $test;
            $this->useBackendConditions[$name]['conditions'] = array_merge(
                $this->useBackendConditions[$name]['conditions'],
                $grouped
            );
        }

        return $this;
    }

    /**
     * Check if a given 'use_backend' statement has conditions.
     *
     * @param string $name
     *
     * @return bool
     */
    public function useBackendHasConditions($name)
    {
        return $this->useBackendExists($name) && !empty($this->useBackendConditions[$name]['conditions']);
    }

    /**
     * Check if a given 'use_backend' statement has conditions.
     *
     * @param string $name
     *
     * @return array|null
     */
    public function useBackendGetConditions($name)
    {
        return $this->useBackendHasConditions($name) ? $this->useBackendConditions[$name] : null;
    }

    /**
     * Remove 'use_backend' parameter.
     *
     * @param string $name
     *
     * @return static
     */
    public function removeUseBackend($name)
    {
        return $this->removeParameter("use_backend $name");
    }

    /**
     * Check if 'use_backend' parameter exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function useBackendExists($name)
    {
        return $this->parameterExists("use_backend $name");
    }

    /**
     * Get the details of the given 'use_backend'.
     *
     * @param string $name
     *
     * @return array|null
     */
    public function getUseBackendDetails($name)
    {
        return $this->useBackendGetConditions($name);
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
            if (stripos($keyword, 'use_backend') === 0) {
                $name = explode(' ', $keyword)[1];

                if ($conditions = $this->useBackendGetConditions($name)) {
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
                            $argSize = 0;
                            $print = [];
                        }
                        $print[] = implode(' ', $condition);
                    }
                    $text .= $this->printLine($keyword, [implode(' || ', $print)], $maxKeyLength, $indent);
                    continue;
                }
            }
            $text .= $this->printLine($keyword, $params, $maxKeyLength, $indent);
        }

        if (!empty($text)) {
            // No indent here.
            $text = $comment . $this->getType() . ($this->getName() ? ' ' . $this->getName() : '') . "\n" . $text . "\n";
        }

        return $text;
    }
}
