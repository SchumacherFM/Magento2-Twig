<?php

namespace SchumacherFM\Twig\Framework\View\TemplateEngine;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\TemplateEngine\Php;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Twig\Environment;

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
     * @param Environment      $twig
     *
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        ObjectManagerInterface $helperFactory,
        DirectoryList $directoryList,
        ScopeConfigInterface $scopeConfig,
        ManagerInterface $eventManager,
        Environment $twig
    )
    {
        parent::__construct($helperFactory);
        $this->_directoryList = $directoryList;
        $this->_scopeConfig = $scopeConfig;
        $this->eventManager = $eventManager;
        $this->twig = $twig;
        $this->initTwig();
    }

    /**
     * Initialises Twig with all Magento 2 necessary functions
     *
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    private function initTwig()
    {
        $this->twig->setCache($this->getCachePath());
        if ($this->_scopeConfig->isSetFlag('dev/twig/debug')) {
            $this->twig->enableDebug();
        } else {
            $this->twig->disableDebug();
        }
        if ($this->_scopeConfig->isSetFlag('dev/twig/auto_reload')) {
            $this->twig->enableAutoReload();
        } else {
            $this->twig->disableAutoReload();
        }
        if ($this->_scopeConfig->isSetFlag('dev/twig/strict_variables')) {
            $this->twig->enableStrictVariables();
        } else {
            $this->twig->disableStrictVariables();
        }
        $this->twig->setCharset($this->_scopeConfig->getValue('dev/twig/charset'));
        $this->twig->addFunction(new \Twig\TwigFunction('helper', [$this, 'helper']));
        $this->twig->addFunction(new \Twig\TwigFunction('layoutBlock', [$this, 'layoutBlock']));
        $this->twig->addFunction(new \Twig\TwigFunction('get*', [$this, 'catchGet']));
        $this->twig->addFunction(new \Twig\TwigFunction('isset', [$this, '__isset']));
        $this->twig->addFunction(new \Twig\TwigFunction('child_html', [$this, 'getChildHtml'], [
            'needs_context' => true,
            'is_safe' => ['html']
        ]));

        $this->twig->addFilter(new \Twig\TwigFilter('trans', '__'));
        $this->twig->addExtension(new \Twig\Extension\DebugExtension());
        foreach ($this->getDefinedFunctionsInHelpersFile() as $functionName) {
            $this->twig->addFunction(new \Twig\TwigFunction($functionName, $functionName));
        }
        $this->eventManager->dispatch('twig_init', ['twig' => $this->twig]);
    }

    /**
     * @return \Twig\Loader\LoaderInterface
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    private function getLoader()
    {
        $loader = new \stdClass();
        $this->eventManager->dispatch('twig_loader', ['loader' => $loader]);
        if (false === ($loader instanceof \Twig\Loader\LoaderInterface)) {
            $loader = new \Twig\Loader\FilesystemLoader($this->_directoryList->getPath(DirectoryList::ROOT));
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
    public function catchGet(...$args)
    {
        $name = array_shift($args);
        return $this->__call('get' . $name, $args);
    }

    /**
     * @param $method
     * @param $args
     *
     * @deprecated since 1.7
     * @return mixed
     */
    public function layoutBlock($method, $args)
    {
        @trigger_error(sprintf('Using the "layoutBlock" function in twig is deprecated since version 1.7, use the "block" variable instead.'), E_USER_DEPRECATED);
        return $this->__call($method, $args);
    }

    /**
     * Render template
     * Render the named template in the context of a particular block and with
     * the data provided in $vars.
     *
     * @param BlockInterface $block
     * @param string $fileName
     * @param array $dictionary
     * @return string rendered template
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function render(BlockInterface $block, $fileName, array $dictionary = [])
    {
        $tmpBlock = $this->_currentBlock;
        $this->_currentBlock = $block;
        $this->twig->addGlobal('block', $block);
        $result = $this->getTemplate($fileName)->render($dictionary);
        $this->_currentBlock = $tmpBlock;
        return $result;
    }

    /**
     * @param $fileName
     *
     * @return \Twig\TemplateWrapper
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    private function getTemplate($fileName)
    {
        $tf = str_replace($this->_directoryList->getPath(DirectoryList::ROOT) . DIRECTORY_SEPARATOR, '', $fileName);
        return $this->twig->load($tf);
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

    public function getChildHtml(array $context, $alias = '', $useCache = true)
    {
        if (!isset($context['block'])) {
            return null;
        }
        $block = $context['block'];
        if (!$block instanceof AbstractBlock) {
            return null;
        }
        return $block->getChildHtml($alias, $useCache);
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    private function getDefinedFunctionsInHelpersFile()
    {
        $filepath = $this->_directoryList->getPath(DirectoryList::APP) . '/functions.php';
        $source = file_get_contents($filepath);
        $tokens = token_get_all($source);
        $functions = array();
        $nextStringIsFunc = false;
        $inClass = false;
        $bracesCount = 0;
        foreach ($tokens as $token) {
            switch ($token[0]) {
                case T_CLASS:
                    $inClass = true;
                    break;
                case T_FUNCTION:
                    if (!$inClass) {
                        $nextStringIsFunc = true;
                    }
                    break;
                case T_STRING:
                    if ($nextStringIsFunc) {
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
                    if ($inClass) $bracesCount++;
                    break;
                case '}':
                    if ($inClass) {
                        $bracesCount--;
                        if ($bracesCount === 0) {
                            $inClass = false;
                        }
                    }
                    break;
            }
        }
        return $functions;
    }
}
