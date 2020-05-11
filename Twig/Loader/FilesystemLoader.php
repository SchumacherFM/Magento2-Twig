<?php

namespace SchumacherFM\Twig\Twig\Loader;

class FilesystemLoader extends \Twig\Loader\FilesystemLoader
{
    public function __construct($paths = [], \Magento\Framework\Filesystem\DirectoryList $directoryList)
    {
        $paths[] = './';
        parent::__construct($paths, $directoryList->getRoot());
    }


}
