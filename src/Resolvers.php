<?php

namespace HAProxy\Config;

use HAProxy\Config\Exception\InvalidParameterException;
use HAProxy\Config\Exception\TextException;

/**
 * Class Resolvers
 *
 * @package HAProxy\Config
 */
class Resolvers extends Parambag
{
    /**
     * @var string
     */
    private $name;

    /**
     * Resolvers constructor.
     *
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

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
        return 'resolvers';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    protected static function buildClass(array $line)
    {
        if (count($line) < 2) {
            throw new TextException('Resolvers must have a name!');
        }
        return new static($line[1]);
    }

    /**
     * {@inheritdoc}
     */
    protected static function handleLine($class, array $line)
    {
        if (!empty($line[0])) {
            switch ($line[0]) {
                case 'nameserver':
                    $server = explode(':', $line[2]);
                    $class->addNameserver($line[1], $server[0], $server[1]);
                    break;
                default:
                    parent::handleLine($class, $line);
            }
        }
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidParameterException
     */
    public function addParameter($keyword, $params = [], $prio = null)
    {
        if ($keyword === 'nameserver') {
            throw new InvalidParameterException('Please use the addNameserver() method to add nameservers!');
        }
        return parent::addParameter($keyword, $params, $prio);
    }

    /**
     * @param string $name
     * @param string $ip the name server IP address
     * @param int    $port the name server IP address
     *
     * @return self
     */
    public function addNameserver($name, $ip, $port = 53)
    {
        $this->parameters['nameservers'][$name] = [
            'ip' => $ip,
            'port' => $port,
        ];

        return $this;
    }

    /**
     * Return the data for the given nameserver.
     *
     * @param string $name
     *
     * @return null|array
     */
    public function getNameserver($name)
    {
        return $this->nameserverExists($name) ? $this->parameters['nameservers'][$name] : null;
    }

    /**
     * Check if the given nameserver exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function nameserverExists($name)
    {
        return isset($this->parameters['nameservers'][$name]);
    }

    /**
     * @param string $name
     *
     * @return self
     */
    public function removeNameserver($name)
    {
        unset($this->parameters['nameservers'][$name]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function prettyPrint($indentLevel, $spacesPerIndent = 4)
    {
        $text = '';
        $indent = $this->indent($indentLevel, $spacesPerIndent);
        $maxKeyLength = $this->getLongestKeywordSize();

        if (isset($this->parameters['nameservers'])) {
            foreach ($this->parameters['nameservers'] as $name => $nameserver) {
                $text .= $indent . str_pad('nameserver', $maxKeyLength) . ' ' . $name;
                if ($nameserver) {
                    $text .= ' ' . $nameserver['ip'] . ':' . $nameserver['port'];
                }
                $text .= "\n";
            }
        }

        foreach ($this->parameters as $keyword => $params) {
            if ($keyword === 'nameservers') {
                continue;
            }
            $text .= $this->printLine($keyword, $params, $maxKeyLength, $indent);
        }

        if (!empty($text)) {
            // No indent here.
            $text = $this->getType() . ' ' . $this->getName() . "\n" . $text . "\n";
        }

        return $text;
    }
}
