<?php

namespace HAProxy\Config;

use HAProxy\Config\Exception\FileException;

class File extends Text
{
    /**
     * @var string
     * */
    private $inFilePath;

    /**
     * @param $filePath string Name of the conf file (or full path).
     *
     * @throws FileException
     */
    public function __construct($filePath)
    {
        $this->inFilePath = $filePath;

        $contents = @file_get_contents($this->inFilePath);

        if ($contents === false) {
            throw new FileException(sprintf(
                'Cannot read file "%s".', $this->inFilePath
            ));
        }

        parent::__construct($contents);
    }
}
