<?php

namespace HAProxy\Config;

class Comment extends Printable
{
    /**
     * @var string
     */
    private $text;

    /**
     * Comment constructor.
     *
     * @param string $text
     */
    public function __construct($text = null)
    {
        $this->text = $text;
    }

    public static function fromString(Text $configString)
    {
        $text = '';
        while ($configString->eof() === false && $configString->eol() === false) {
            $text .= $configString->getChar();
            $configString->inc();
        }
        return new Comment(ltrim($text, '# '));
    }

    /**
     * Get comment text
     *
     * @return string|null
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Is this an empty (no text) comment?
     *
     * @return bool
     */
    public function isEmpty()
    {
        return is_null($this->text) || $this->text === '';
    }

    /**
     * Is this comment multi-line?
     *
     * @return bool
     */
    public function isMultiline()
    {
        return strpos(rtrim($this->text), "\n") !== false;
    }

    /**
     * Set the comment text
     *
     * If you set the comment text to null or empty string,
     * the comment will not print.
     *
     * @param string|null $text
     */
    public function setText($text)
    {
        $this->text = $text;
    }

    /**
     * {@inheritdoc}
     */
    public function prettyPrint($indentLevel, $spacesPerIndent = 4)
    {
        if ($this->isEmpty() === true) {
            return '';
        }

        $indent = $this->indent($indentLevel, $spacesPerIndent);
        $text = $indent . '# ' . rtrim($this->text);

        if ($this->isMultiline() === true) {
            $text = preg_replace("#\r{0,1}\n#", PHP_EOL . $indent . '# ', $text);
        }

        return $text . "\n";
    }
}
