<?php

namespace SchumacherFM\Twig\Framework\View\TemplateEngine;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\View\TemplateEngine\Php;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Config\ScopeConfigInterface;

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
     * @param DirectoryList          $directoryList
     * @param ScopeConfigInterface   $scopeConfig
     * @param ManagerInterface       $eventManager
     * @throws \Magento\Framework\Exception\FileSystemException
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
     * Initialises Twig with all Magento 2 necessary functions
     *
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    private function initTwig()
    {
        //removed Twig_Autoloader. Not needed for Twig v2.0 and up
        //\Twig_Autoloader::register();

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

        $this->twig->addExtension(new \Twig_Extension_Debug());

        foreach ($this->get_defined_functions_in_helpers_file() as $functionName) {
            $this->twig->addFunction(new \Twig_SimpleFunction($functionName, $functionName));
        }

        $this->eventManager->dispatch('twig_init', ['twig' => $this->twig]);
    }

    /**
     * @return \Twig_LoaderInterface
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    private function getLoader()
    {
        $loader = new \stdClass();
        $this->eventManager->dispatch('twig_loader', ['loader' => $loader]);

        if (false === ($loader instanceof \Twig_LoaderInterface)) {
            $loader = new \Twig_Loader_Filesystem($this->_directoryList->getPath(DirectoryList::ROOT));
        }

        return $loader;
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    private function getCachePath()
    {
        if (false === $this->_scopeConfig->isSetFlag('dev/twig/cache')) {
            return false;
        }

        return $this->_directoryList->getPath(DirectoryList::VAR_DIR) . DIRECTORY_SEPARATOR . self::TWIG_CACHE_DIR;
    }

    /**
     * @return mixed
     */
    public function catchGet()
    {
        $args = func_get_args();
        $name = array_shift($args);

        return $this->__call('get' . $name, $args);
    }

    /**
     * Render template
     * Render the named template in the context of a particular block and with
     * the data provided in $vars.
     *
     * @param BlockInterface $block
     * @param string         $fileName
     * @param array          $dictionary
     * @return string rendered template
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function render(BlockInterface $block, $fileName, array $dictionary = [])
    {
        $tmpBlock = $this->_currentBlock;
        $this->_currentBlock = $block;
        $result = $this->getTemplate($fileName)->render($dictionary);
        $this->_currentBlock = $tmpBlock;
        
        return $result;
    }

    /**
     * @param $fileName
     * @return \Twig_TemplateInterface
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    private function getTemplate($fileName)
    {
        $tf = str_replace($this->_directoryList->getPath(DirectoryList::ROOT) . DIRECTORY_SEPARATOR, '', $fileName);

        return $this->twig->loadTemplate($tf);
    }

    /**
     * Get helper singleton
     *
     * @param string $className
     * @return AbstractHelper
     * @throws \LogicException
     */
    public function helper($className)
    {
        $helper = $this->_helperFactory->get($className);

        if (false === $helper instanceof AbstractHelper) {
            throw new \LogicException($className . ' doesn\'t extends Magento\Framework\App\Helper\AbstractHelper');
        }

        return $helper;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    private function get_defined_functions_in_helpers_file()
    {
        $filepath = $this->_directoryList->getPath(DirectoryList::APP) . '/functions.php';

        $source = file_get_contents($filepath);
        $tokens = token_get_all($source);

        $functions = array();
        $nextStringIsFunc = false;
        $inClass = false;
        $bracesCount = 0;

        foreach($tokens as $token) {
            switch($token[0]) {
                case T_CLASS:
                    $inClass = true;
                    break;
                case T_FUNCTION:
                    if(!$inClass) $nextStringIsFunc = true;
                    break;

                case T_STRING:
                    if($nextStringIsFunc) {
                        $nextStringIsFunc = false;
                        $functions[] = $token[1];
                    }
                    break;

                // Anonymous functions
                case '(':
                case ';':
                    $nextStringIsFunc = false;
                    break;

                // Exclude Classes
                case '{':
                    if($inClass) $bracesCount++;
                    break;

                case '}':
                    if($inClass) {
                        $bracesCount--;
                        if($bracesCount === 0) $inClass = false;
                    }
                    break;
            }
        }

        return $functions;
    }
}
