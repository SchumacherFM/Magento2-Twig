<?php

namespace SchumacherFM\Twig\Framework\View\TemplateEngine;

use Magento\Framework\View\TemplateEngine\Php,
    Magento\Framework\App\Filesystem\DirectoryList,
    \Magento\Framework\ObjectManagerInterface;

class Twig extends Php
{
    const TWIG_CACHE_DIR = 'twig';

    /**
     * @var \Twig_Environment
     */
    private $twig = null;

    /**
     * @var DirectoryList
     */
    protected $_directoryList;

    /**
     * @param ObjectManagerInterface $helperFactory
     * @param DirectoryList $directoryList
     */
    public function __construct(ObjectManagerInterface $helperFactory, DirectoryList $directoryList) {
        parent::__construct($helperFactory);
        $this->_directoryList = $directoryList;
        $this->initTwig();
    }

    /**
     * Inits Twig with all Magento2 necessary functions
     */
    private function initTwig() {
        \Twig_Autoloader::register();
        // @todo let users configure the loader. Redis! clear twig cache

        $loader = new \Twig_Loader_Filesystem($this->_directoryList->getPath(DirectoryList::ROOT));

        $this->twig = new \Twig_Environment($loader, [
            // make it configurable http://twig.sensiolabs.org/doc/api.html#environment-options
            'cache' => $this->_directoryList->getPath(DirectoryList::VAR_DIR) . DIRECTORY_SEPARATOR . self::TWIG_CACHE_DIR,
            'debug' => true,
        ]);

        $this->twig->addFunction(new \Twig_SimpleFunction('helper', [$this, 'helper']));
        $this->twig->addFunction(new \Twig_SimpleFunction('block', [$this, '__call']));
        $this->twig->addFunction(new \Twig_SimpleFunction('get*', [$this, 'catchGet']));
        $this->twig->addFunction(new \Twig_SimpleFunction('isset', [$this, '__isset']));
    }

    /**
     * @return mixed
     */
    public function catchGet() {
        $args = func_get_args();
        $name = array_shift($args);
        return $this->__call('get' . $name, $args);
    }

    /**
     * Render template
     *
     * Render the named template in the context of a particular block and with
     * the data provided in $vars.
     *
     * @param \Magento\Framework\View\Element\BlockInterface $block
     * @param string $fileName
     * @param array $dictionary
     * @return string rendered template
     */
    public function render(
        \Magento\Framework\View\Element\BlockInterface $block,
        $fileName,
        array $dictionary = []
    ) {
        $this->_currentBlock = $block;
        return $this->getTemplate($fileName)->render($dictionary);
    }

    /**
     * @param $fileName
     * @return \Twig_TemplateInterface
     */
    private function getTemplate($fileName) {
        $tf = str_replace($this->_directoryList->getPath(DirectoryList::ROOT) . DIRECTORY_SEPARATOR, '', $fileName);
        return $this->twig->loadTemplate($tf);
    }

    /**
     * Get helper singleton
     *
     * @param string $className
     * @return \Magento\Framework\App\Helper\AbstractHelper
     * @throws \LogicException
     */
    public function helper($className) {
        $helper = $this->_helperFactory->get($className);
        if (false === $helper instanceof \Magento\Framework\App\Helper\AbstractHelper) {
            throw new \LogicException($className . ' doesn\'t extends Magento\Framework\App\Helper\AbstractHelper');
        }
        return $helper;
    }
}
