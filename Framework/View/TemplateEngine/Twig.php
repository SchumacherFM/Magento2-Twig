<?php

namespace SchumacherFM\Twig\Framework\View\TemplateEngine;

use LogicException;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\TemplateEngine\Php;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\DebugExtension;
use Twig\TemplateWrapper;
use Twig\TwigFilter;
use Twig\TwigFunction;

class Twig extends Php
{
    const TWIG_CACHE_DIR = 'twig';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Environment
     */
    protected $twig = null;

    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * Event manager
     *
     * @var ManagerInterface
     */
    protected $eventManager;

    /**
     * @param ObjectManagerInterface $helperFactory
     * @param DirectoryList          $directoryList
     * @param ScopeConfigInterface   $scopeConfig
     * @param ManagerInterface       $eventManager
     * @param Environment      $twig
     *
     * @throws FileSystemException
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
        $this->directoryList = $directoryList;
        $this->scopeConfig = $scopeConfig;
        $this->eventManager = $eventManager;
        $this->twig = $twig;
        $this->initTwig();
    }

    /**
     * Initialises Twig with all Magento 2 necessary functions
     *
     * @throws FileSystemException
     */
    private function initTwig()
    {
        $this->twig->setCache($this->getCachePath());
        if ($this->scopeConfig->isSetFlag('dev/twig/debug')) {
            $this->twig->enableDebug();
        } else {
            $this->twig->disableDebug();
        }
        if ($this->scopeConfig->isSetFlag('dev/twig/auto_reload')) {
            $this->twig->enableAutoReload();
        } else {
            $this->twig->disableAutoReload();
        }
        if ($this->scopeConfig->isSetFlag('dev/twig/strict_variables')) {
            $this->twig->enableStrictVariables();
        } else {
            $this->twig->disableStrictVariables();
        }
        $this->twig->setCharset($this->scopeConfig->getValue('dev/twig/charset'));
        $this->twig->addFunction(new TwigFunction('helper', [$this, 'helper']));
        $this->twig->addFunction(new TwigFunction('get*', [$this, 'catchGet']));
        $this->twig->addFunction(new TwigFunction('isset', [$this, '__isset']));
        $this->twig->addFunction(new TwigFunction('child_html', [$this, 'getChildHtml'], [
            'needs_context' => true,
            'is_safe' => ['html']
        ]));

        $this->twig->addFilter(new TwigFilter('trans', '__'));
        $this->twig->addExtension(new DebugExtension());
        $this->eventManager->dispatch('twig_init', ['twig' => $this->twig]);
    }

    /**
     * @return string
     * @throws FileSystemException
     */
    private function getCachePath()
    {
        if (false === $this->scopeConfig->isSetFlag('dev/twig/cache')) {
            return false;
        }
        return $this->directoryList->getPath(DirectoryList::VAR_DIR) . DIRECTORY_SEPARATOR . self::TWIG_CACHE_DIR;
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
     * Render template
     * Render the named template in the context of a particular block and with
     * the data provided in $vars.
     *
     * @param BlockInterface $block
     * @param string $fileName
     * @param array $dictionary
     * @return string rendered template
     * @throws FileSystemException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
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
     * @return TemplateWrapper
     * @throws FileSystemException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    private function getTemplate($fileName)
    {
        $tf = str_replace($this->directoryList->getPath(DirectoryList::ROOT) . DIRECTORY_SEPARATOR, '', $fileName);
        return $this->twig->load($tf);
    }

    /**
     * Get helper singleton
     *
     * @param string $className
     * @return AbstractHelper
     * @throws LogicException
     */
    public function helper($className)
    {
        $helper = $this->_helperFactory->get($className);
        if (false === $helper instanceof AbstractHelper) {
            throw new LogicException($className . ' doesn\'t extends Magento\Framework\App\Helper\AbstractHelper');
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

}
