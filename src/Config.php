<?php

namespace HAProxy\Config;

use HAProxy\Config\Exception\FileException;
use HAProxy\Config\Proxy\Defaults;
use HAProxy\Config\Proxy\Backend;
use HAProxy\Config\Proxy\Frontend;
use HAProxy\Config\Proxy\Listen;
use HAProxy\Config\Proxy\Proxy;

class Config extends Printable
{
    /**
     * @var Globals
     */
    private $global;

    /**
     * @var Defaults
     */
    private $defaults;

    /**
     * @var Userlist[]
     */
    private $userlists;

    /**
     * @var Proxy[]
     */
    private $proxies;

    /**
     * @var Printable[]
     */
    private $printables;


    /**
     * Config constructor.
     */
    public function __construct()
    {
        $this->global = new Globals();
        $this->defaults = new Defaults();
        $this->userlists = [];
        $this->proxies = [];
        $this->printables = [];
    }

    /**
     * Write this Config into a file.
     *
     * @param string $filePath
     *
     * @throws FileException
     */
    public function saveToFile($filePath)
    {
        $handle = @fopen($filePath, 'w');
        if (false === $handle) {
            throw new FileException(sprintf(
                'Cannot open file "%s" for writing.',
                $filePath
            ));
        }

        $bytesWritten = @fwrite($handle, (string)$this);
        if (false === $bytesWritten) {
            fclose($handle);
            throw new FileException(sprintf(
                'Cannot write to file "%s".',
                $filePath
            ));
        }

        $closed = @fclose($handle);
        if (false === $closed) {
            throw new FileException(sprintf(
                'Cannot close file handle for "%s".',
                $filePath
            ));
        }
    }

    /**
     * Allow fluid code.
     *
     * @return Config
     */
    public static function create()
    {
        return new self();
    }

    /**
     * Create new Config from the configuration string.
     *
     * @param Text $configString
     *
     * @return Config
     */
    public static function fromString(Text $configString)
    {
        $config = new Config();
        while ($configString->eof() === false) {

            if ($configString->isEmptyLine() === true) {
                $config->addPrintable(EmptyLine::fromString($configString));
            }

            $char = $configString->getChar();

            if ($char === '#') {
                $config->addPrintable(Comment::fromString($configString));
                continue;
            }

            if (($char >= 'a') && ($char <= 'z')) {
                switch (true) {
                    case $configString->firstWordMatches('global'):
                        $config->setGlobal(Globals::fromString($configString));
                        break;

                    case $configString->firstWordMatches('defaults'):
                        $config->setDefaults(Defaults::fromString($configString));
                        break;

                    case $configString->firstWordMatches('userlist'):
                        $config->addUserlist(Userlist::fromString($configString));
                        break;

                    case $configString->firstWordMatches('frontend'):
                        $config->addFrontend(Frontend::fromString($configString));
                        break;

                    case $configString->firstWordMatches('backend'):
                        $config->addBackend(Backend::fromString($configString));
                        break;

                    case $configString->firstWordMatches('listen'):
                        $config->addListen(Listen::fromString($configString));
                        break;
                }
                continue;
            }

            $configString->inc();
        }
        return $config;
    }

    /**
     * Create new Config from a file.
     *
     * @param $filePath
     *
     * @return self
     */
    public static function fromFile($filePath)
    {
        return self::fromString(new File($filePath));
    }

    /**
     * Overwrite the global section with the given object.
     *
     * @param Globals $global
     *
     * @return self
     */
    public function setGlobal(Globals $global)
    {
        $this->global = $global;

        return $this;
    }

    /**
     * Add global configuration parameter.
     *
     * @param string $keyword
     * @param string|array $params
     *
     * @return self
     */
    public function addGlobal($keyword, $params)
    {
        $this->global->addParameter($keyword, $params);

        return $this;
    }

    /**
     * Get global settings.
     *
     * @return Globals
     */
    public function getGlobal()
    {
        return $this->global;
    }

    /**
     * @param bool $bool
     *
     * @return self
     */
    public function setDebug($bool = true)
    {
        $this->global->debug($bool);

        return $this;
    }

    /**
     * @param bool $bool
     *
     * @return self
     */
    public function setQuiet($bool = true)
    {
        $this->global->quiet($bool);

        return $this;
    }

    /**
     * @param bool $bool
     *
     * @return self
     */
    public function setDaemon($bool = true)
    {
        $this->global->daemon($bool);

        return $this;
    }

    /**
     * Overwrite the defaults section with the given object.
     *
     * @param Defaults $defaults
     *
     * @return self
     */
    public function setDefaults(Defaults $defaults)
    {
        $this->defaults = $defaults;

        return $this;
    }

    /**
     * Add default configuration parameter.
     *
     * @param string $keyword
     * @param string|array $params
     *
     * @return self
     */
    public function addDefaults($keyword, $params)
    {
        $this->defaults->addParameter($keyword, $params);

        return $this;
    }

    /**
     * Get defaults.
     *
     * @return Defaults
     */
    public function getDefaults()
    {
        return $this->defaults;
    }

    /**
     * Add userlist.
     *
     * @param Userlist $userlist
     *
     * @return self
     */
    public function addUserlist(Userlist $userlist)
    {
        $this->userlists[$userlist->getName()] = $userlist;

        return $this;
    }

    /**
     * Get userlist by name.
     *
     * @param string $name
     *
     * @return Userlist|null
     */
    public function getUserlist($name)
    {
        return isset($this->userlists[$name]) ? $this->userlists[$name] : null;
    }

    /**
     * Checks if the given userlist exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function userlistExists($name)
    {
        return isset($this->userlists[$name]);
    }

    /**
     * Get all userlists.
     *
     * @return Userlist[]
     */
    public function getUserlists()
    {
        return $this->userlists;
    }

    /**
     * Add listen block.
     *
     * @param Listen $listen
     *
     * @return self
     */
    public function addListen(Listen $listen)
    {
        $this->proxies['listen ' . $listen->getName()] = $listen;

        return $this;
    }

    /**
     * Remove listen block by name.
     *
     * @param string $name
     *
     * @return self
     */
    public function removeListen($name) {
        unset($this->proxies["listen $name"]);

        return $this;
    }

    /**
     * Get listen block by name.
     *
     * @param string $name
     *
     * @return Listen|null
     */
    public function getListen($name) {
        return isset($this->proxies["listen $name"]) ? $this->proxies["listen $name"] : null;
    }

    /**
     * Checks if the given listen block exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function listenExists($name)
    {
        return isset($this->proxies["listen $name"]);
    }

    /**
     * Add frontend.
     *
     * @param Frontend $frontend
     *
     * @return self
     */
    public function addFrontend(Frontend $frontend)
    {
        $this->proxies['frontend ' . $frontend->getName()] = $frontend;

        return $this;
    }

    /**
     * Remove frontend block by name.
     *
     * @param string $name
     *
     * @return self
     */
    public function removeFrontend($name) {
        unset($this->proxies["frontend $name"]);

        return $this;
    }

    /**
     * Get frontend by name.
     *
     * @param string $name
     *
     * @return Frontend|null
     */
    public function getFrontend($name) {
        return isset($this->proxies["frontend $name"]) ? $this->proxies["frontend $name"] : null;
    }

    /**
     * Checks if the given frontend exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function frontendExists($name)
    {
        return isset($this->proxies["frontend $name"]);
    }

    /**
     * Add backend.
     *
     * @param Backend $backend
     *
     * @return self
     */
    public function addBackend(Backend $backend)
    {
        $this->proxies['backend ' . $backend->getName()] = $backend;

        return $this;
    }

    /**
     * Remove backend block by name.
     *
     * @param string $name
     *
     * @return self
     */
    public function removeBackend($name) {
        unset($this->proxies["backend $name"]);

        return $this;
    }

    /**
     * Get backend by name.
     *
     * @param string $name
     *
     * @return Backend|null
     */
    public function getBackend($name) {
        return isset($this->proxies["backend $name"]) ? $this->proxies["backend $name"] : null;
    }

    /**
     * Checks if the given backend exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function backendExists($name)
    {
        return isset($this->proxies["backend $name" ]);
    }

    /**
     * Get all frontends, backends and listen blocks.
     *
     * @return Proxy[]
     */
    public function getProxies()
    {
        return $this->proxies;
    }

    /**
     * Get the proxies ordered by their priority.
     *
     * @return Proxy[]
     */
    public function getProxiesByPriority()
    {
        // We work on a copy of the data since usort() modifies the original
        // array which is not what we want.
        $prioritised = $this->proxies;

        usort($prioritised, function(Proxy $a, Proxy $b) {
            // This might seem a bit strange, but it will ensure a consistent
            // sorting order all the way from PHP 4.x up to 7.x!
            if ($a->getPrintPriority() > $b->getPrintPriority()) {
                return 1;
            }
            if ($a->getPrintPriority() < $b->getPrintPriority()) {
                return -1;
            }
            return 0;
        });

        return $prioritised;
    }

    /**
     * Add a comment at the top of the config file.
     *
     * @param Comment $comment
     *
     * @return self
     */
    public function addComment(Comment $comment)
    {
        $this->addPrintable($comment);

        return $this;
    }

    /**
     * Add printable element.
     *
     * @param Printable $printable
     */
    private function addPrintable(Printable $printable)
    {
        $this->printables[] = $printable;
    }

    /**
     * Pretty print with indentation.
     *
     * @param int $indentLevel
     * @param int $spacesPerIndent
     *
     * @return string
     */
    public function prettyPrint($indentLevel, $spacesPerIndent = 4)
    {
        $text = "";

        foreach ($this->printables as $printable) {
            $text .= $printable->prettyPrint(0, $spacesPerIndent);
        }

        $text .= $this->global->prettyPrint($indentLevel+1, $spacesPerIndent);
        $text .= $this->defaults->prettyPrint($indentLevel+1, $spacesPerIndent);

        foreach ($this->userlists as $userlist) {
            $text .= $userlist->prettyPrint($indentLevel+1, $spacesPerIndent);
        }

        foreach ($this->getProxiesByPriority() as $proxy) {
            $text .= $proxy->prettyPrint($indentLevel+1, $spacesPerIndent);
        }

        // Remove trailing empty line.
        return trim($text) . "\n";
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->prettyPrint(0);
    }
}
