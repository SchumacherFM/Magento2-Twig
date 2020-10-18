<?php declare(strict_types=1);

namespace SchumacherFM\Twig\Twig\Loader;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\View\DesignInterface;
use Magento\Framework\View\Element\Template\File\Resolver;
use Magento\Framework\View\File\Collector\Base;
use Magento\Framework\View\File\Collector\ThemeModular;

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
     * @var ThemeModular
     */
    protected $themeFilesCollector;

    /**
     * @var Base
     */
    protected $moduleFilesCollector;

    /**
     * @var DesignInterface
     */
    protected $design;

    /**
     * FilesystemLoader constructor.
     *
     * @param DirectoryList   $directoryList
     * @param Resolver        $resolver
     * @param DesignInterface $design
     * @param ThemeModular    $themeFilesCollector
     * @param Base            $moduleFilesCollector
     * @param array           $paths
     *
     * @throws \Twig\Error\LoaderError
     */
    public function __construct(
        DirectoryList $directoryList,
        Resolver $resolver,
        DesignInterface $design,
        ThemeModular $themeFilesCollector,
        Base $moduleFilesCollector,
        $paths = []
    ) {
        $this->directoryList = $directoryList;
        $this->resolver = $resolver;
        $this->themeFilesCollector = $themeFilesCollector;
        $this->moduleFilesCollector = $moduleFilesCollector;
        $this->design = $design;
        $paths[] = './';

        parent::__construct($paths, $directoryList->getRoot());
        $this->initTemplateNamespaces();
    }

    /**
     * @param string $name
     * @param bool   $throw
     *
     * @return false|null|string
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Twig\Error\LoaderError
     */
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

    /**
     * @throws \Twig\Error\LoaderError
     */
    protected function initTemplateNamespaces()
    {
        // Module Namespaces
        $theme = $this->design->getDesignTheme();
        $moduleFiles = $this->moduleFilesCollector->getFiles($theme, 'templates');
        foreach ($moduleFiles as $moduleFile) {
            // This adds the namespace Vendor_Module[module] that can be later used in the theme to extend
            $moduleNamespace = $moduleFile->getModule().'[module]';
            $this->prependPath($moduleFile->getFilename(), $moduleNamespace);
            // This adds the namespace Vendor_Module that later can be overridden in the theme
            $defaultNamespace = $moduleFile->getModule();
            $this->prependPath($moduleFile->getFilename(), $defaultNamespace);
        }

        // Add Theme Namespaces
        $inheritedThemes = $theme->getInheritedThemes();
        foreach ($inheritedThemes as $inheritanceLevel => $currentTheme) {
            $themeFiles = $this->themeFilesCollector->getFiles($currentTheme, 'templates');
            $code = str_replace('/', '|', $currentTheme->getCode());
            foreach ($themeFiles as $file) {
                // This adds the namespace Vendor_Module[Vendor|theme] if we want to extend a certain parent theme twig file
                $themeNamespace = $file->getModule().'['.$code.']';
                $this->prependPath($file->getFilename(), $themeNamespace);
                // This adds files to the namespace Vendor_Module for the current theme
                $defaultNamespace = $file->getModule();
                $this->prependPath($file->getFilename(), $defaultNamespace);
            }
        }

    }

}
