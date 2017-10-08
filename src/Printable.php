<?php

namespace HAProxy\Config;

/**
 * Class Printable
 *
 * @package HAProxy\Config
 */
abstract class Printable
{
    /**
     * Prints out config in plain text.
     *
     * @param int $indentLevel
     * @param int $spacesPerIndent
     *
     * @return string
     */
    abstract public function prettyPrint($indentLevel, $spacesPerIndent = 4);

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->prettyPrint(0);
    }

    /**
     * Helper to return indent to prepend to a string.
     *
     * @param int $indentLevel
     * @param int $spacesPerIndent
     *
     * @return string
     */
    protected function indent($indentLevel, $spacesPerIndent)
    {
        return str_repeat(str_repeat(' ', $spacesPerIndent), $indentLevel);
    }
}
