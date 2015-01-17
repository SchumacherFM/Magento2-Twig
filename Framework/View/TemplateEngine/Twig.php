<?php

namespace SchumacherFM\Twig\Framework\View\TemplateEngine;

use Magento\Framework\View\TemplateEngine\Php,
    Magento\Framework\App\Filesystem\DirectoryList,
    Magento\Framework\ObjectManagerInterface,
    Magento\Framework\App\Config\ScopeConfigInterface,
    Magento\Framework\Event\ManagerInterface;

class Twig extends Php
{
    const TWIG_CACHE_DIR = 'twig';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Twig_Environment
     */
    private $twig = null;

    /**
     * @var DirectoryList
     */
    protected $_directoryList;

    /**
     * Event manager
     *
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @param ObjectManagerInterface $helperFactory
     * @param DirectoryList $directoryList
     * @param ScopeConfigInterface $scopeConfig
     * @param ManagerInterface $eventManager
     */
    public function __construct(
        ObjectManagerInterface $helperFactory,
        DirectoryList $directoryList,
        ScopeConfigInterface $scopeConfig,
        ManagerInterface $eventManager
    ) {
        parent::__construct($helperFactory);
        $this->_directoryList = $directoryList;
        $this->_scopeConfig = $scopeConfig;
        $this->eventManager = $eventManager;
        $this->initTwig();
    }

    /**
     * Inits Twig with all Magento2 necessary functions
     */
    private function initTwig() {
        \Twig_Autoloader::register();

        $this->twig = new \Twig_Environment($this->getLoader(), [
            // make it configurable http://twig.sensiolabs.org/doc/api.html#environment-options
            'cache' => $this->getCachePath(),
            'debug' => $this->_scopeConfig->isSetFlag('dev/twig/debug'),
            'auto_reload' => $this->_scopeConfig->isSetFlag('dev/twig/auto_reload'),
            'strict_variables' => $this->_scopeConfig->isSetFlag('dev/twig/strict_variables'),
            'charset' => $this->_scopeConfig->getValue('dev/twig/charset'),
        ]);

        $this->twig->addFunction(new \Twig_SimpleFunction('helper', [$this, 'helper']));
        $this->twig->addFunction(new \Twig_SimpleFunction('block', [$this, '__call']));
        $this->twig->addFunction(new \Twig_SimpleFunction('get*', [$this, 'catchGet']));
        $this->twig->addFunction(new \Twig_SimpleFunction('isset', [$this, '__isset']));

        $this->eventManager->dispatch('twig_init', ['twig' => $this->twig]);
    }

    /**
     * @return \Twig_LoaderInterface
     */
    private function getLoader() {
        $loader = new \stdClass();
        $this->eventManager->dispatch('twig_loader', ['loader' => $loader]);
        if (false === ($loader instanceof \Twig_LoaderInterface)) {
            $loader = new \Twig_Loader_Filesystem($this->_directoryList->getPath(DirectoryList::ROOT));
        }
        return $loader;
    }

    /**
     * @return string
     */
    private function getCachePath() {
        if (false === $this->_scopeConfig->isSetFlag('dev/twig/cache')) {
            return false;
        }
        return $this->_directoryList->getPath(DirectoryList::VAR_DIR) . DIRECTORY_SEPARATOR . self::TWIG_CACHE_DIR;
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
    public function render(\Magento\Framework\View\Element\BlockInterface $block, $fileName, array $dictionary = []) {
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
