<?php

namespace SchumacherFM\Twig\Twig\Loader;

use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\View\Element\Template\File\Resolver;

class FilesystemLoader extends \Twig\Loader\FilesystemLoader
{

    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * @var Resolver
     */
    protected $resolver;

    /**
     * FilesystemLoader constructor.
     *
     * @param DirectoryList $directoryList
     * @param Resolver      $resolver
     * @param array         $paths
     */
    public function __construct(
        DirectoryList $directoryList,
        Resolver $resolver,
        $paths = []
    ) {
        $this->directoryList = $directoryList;
        $this->resolver = $resolver;
        $paths[] = './';
        parent::__construct($paths, $directoryList->getRoot());
    }

    protected function findTemplate($name, $throw = true)
    {
        if(stristr($name, '::') !== false) {
            $templateName = $this->resolver->getTemplateFileName($name);
            $templateName = str_replace($this->directoryList->getPath(DirectoryList::ROOT) . DIRECTORY_SEPARATOR, '', $templateName);
            return parent::findTemplate($templateName, $throw);
        } else {
            return parent::findTemplate($name, $throw);
        }
    }

}
