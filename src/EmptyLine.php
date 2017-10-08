<?php

namespace HAProxy\Config;

class EmptyLine extends Printable
{
    public static function fromString(Text $configString)
    {
        $configString->gotoNextEol();
        return new self;
    }

    public function prettyPrint($indentLevel, $spacesPerIndent = 4)
    {
        return "\n";
    }
}
