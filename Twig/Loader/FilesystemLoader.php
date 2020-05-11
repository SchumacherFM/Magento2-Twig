<?php

namespace SchumacherFM\Twig\Twig\Loader;

class FilesystemLoader extends \Twig\Loader\FilesystemLoader
{

    protected $directoryList;

    protected $resolver;

    public function __construct(
        \Magento\Framework\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\View\Element\Template\File\Resolver $resolver,
        $paths = [])
    {
        $this->directoryList = $directoryList;
        $this->resolver = $resolver;
        $paths[] = './';
        parent::__construct($paths, $directoryList->getRoot());
    }

    protected function findTemplate($name, $throw = true)
    {
        if(stristr($name, "::") !== false) {
            $t = $this->resolver->getTemplateFileName($name);
            $t = str_replace($this->directoryList->getPath(DirectoryList::ROOT) . DIRECTORY_SEPARATOR, '', $t);
            return parent::findTemplate($t, $throw);
        } else {
            return parent::findTemplate($name, $throw);
        }
    }

}

