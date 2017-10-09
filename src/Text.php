<?php

/**
 * @file
 *
 * This file was taken from the the Nginx Config Processor package by Roman
 * Piták <roman@pitak.net> and modified to suit this library.
 */

namespace HAProxy\Config;

use HAProxy\Config\Exception\TextException;

class Text
{
    const CURRENT_POSITION = -1;

    /**
     * @var string
     */
    private $data;

    /**
     * @var int
     */
    private $position;

    /**
     * @param string $data
     */
    public function __construct($data)
    {
        $this->position = 0;
        $this->data = $data;
    }

    /**
     * Is this the end of file (string) or beyond?
     *
     * @param int $position
     *
     * @return bool
     */
    public function eof($position = self::CURRENT_POSITION)
    {
        if ($position === self::CURRENT_POSITION) {
            $position = $this->position;
        }

        return !isset($this->data[$position]);
    }

    /**
     * Is this the end of line?
     *
     * @param int $position
     *
     * @return bool
     *
     * @throws TextException
     */
    public function eol($position = self::CURRENT_POSITION)
    {
        return $this->getChar($position) === "\r" || $this->getChar($position) === "\n";
    }

    /**
     * Move string pointer.
     *
     * @param int $inc
     */
    public function inc($inc = 1)
    {
        $this->position += $inc;
    }

    /**
     * Returns one character of the string.
     *
     * Does not move the string pointer. Use inc() to move the pointer after
     * getChar().
     *
     * @param int $position If not specified, current character is returned.
     *
     * @return string The current character (under the pointer).
     *
     * @throws TextException
     */
    public function getChar($position = self::CURRENT_POSITION)
    {
        if ($position === self::CURRENT_POSITION) {
            $position = $this->position;
        }

        if (!is_int($position)) {
            throw new TextException(sprintf(
                'Expected position to be integer, got %s!',
                gettype($position)
            ));
        }

        if ($this->eof($position)) {
            throw new TextException(sprintf(
                'Index out of range. Position: %d.',
                $position
            ));
        }

        return $this->data[$position];
    }

    /**
     * Get the text from $position to the next end of line.
     *
     * Does not move the string pointer.
     *
     * @param int $position
     *
     * @return string
     */
    public function getRestOfTheLine($position = self::CURRENT_POSITION)
    {
        if ($position === self::CURRENT_POSITION) {
            $position = $this->position;
        }
        $text = '';
        while ($this->eof($position) === false && $this->eol($position) === false) {
            $text .= $this->getChar($position);
            $position++;
        }

        return $text;
    }

    /**
     * Determine if the first word matches the given string.
     *
     * @param string $string
     *
     * @return bool
     */
    public function firstWordMatches($string)
    {
        return explode(' ', $this->getRestOfTheLine(), 2)[0] === $string;
    }

    /**
     * Is this line empty?
     *
     * @param int $position
     *
     * @return bool
     */
    public function isEmptyLine($position = self::CURRENT_POSITION)
    {
        $line = $this->getCurrentLine($position);

        return strlen(trim($line)) === 0;
    }

    /**
     * Get the current line.
     *
     * @param int $position
     *
     * @return string
     */
    public function getCurrentLine($position = self::CURRENT_POSITION)
    {
        if ($position === self::CURRENT_POSITION) {
            $position = $this->position;
        }

        $offset = $this->getPreviousEol($position);
        $length = $this->getNextEol($position) - $offset;

        return substr($this->data, $offset, $length);
    }

    /**
     * Get the position of the previous EOL.
     *
     * @param int $position
     *
     * @return int
     */
    public function getPreviousEol($position = self::CURRENT_POSITION)
    {
        if ($position === self::CURRENT_POSITION) {
            $position = $this->position;
        }

        return strrpos(substr($this->data, 0, $position), "\n", 0);
    }

    /**
     * Get the position of the next EOL.
     *
     * @param int $position
     *
     * @return int
     */
    public function getNextEol($position = self::CURRENT_POSITION)
    {
        if ($position === self::CURRENT_POSITION) {
            $position = $this->position;
        }

        $eolPosition = strpos($this->data, "\n", $position);
        if ($eolPosition === false) {
            $eolPosition = strlen($this->data) - 1;
        }

        return $eolPosition;
    }

    /**
     * Move pointer (position) to the next EOL.
     *
     * @param int $position
     */
    public function gotoNextEol($position = self::CURRENT_POSITION)
    {
        if ($position === self::CURRENT_POSITION) {
            $position = $this->position;
        }
        $this->position = $this->getNextEol($position);
    }
}
