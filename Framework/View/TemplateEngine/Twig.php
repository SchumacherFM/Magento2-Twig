<?php
//require_once '../../../../../../../twig/twig/lib/Twig/Autoloader.php';

namespace SchumacherFM\Twig\Framework\View\TemplateEngine;

use Magento\Framework\View\TemplateEngineInterface;
use Magento\Framework\App\Filesystem\DirectoryList;

class Twig implements TemplateEngineInterface
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
     * @param \Magento\Framework\Filesystem\DirectoryList $directoryList
     */
    public function __construct(\Magento\Framework\Filesystem\DirectoryList $directoryList)
    {
        $this->_directoryList = $directoryList;
        \Twig_Autoloader::register();
        // @todo let users configure the loader. Redis! clear twig cache
        $loader = new \Twig_Loader_Filesystem($this->_directoryList->getPath(DirectoryList::ROOT));
        $this->twig = new \Twig_Environment($loader, [
            // make it configurable http://twig.sensiolabs.org/doc/api.html#environment-options
            'cache' => $this->_directoryList->getPath(DirectoryList::VAR_DIR) . DIRECTORY_SEPARATOR . self::TWIG_CACHE_DIR,
        ]);
    }

    /**
     * Render template
     *
     * Render the named template in the context of a particular block and with
     * the data provided in $vars.
     *
     * @param \Magento\Framework\View\Element\BlockInterface $block
     * @param string $templateFile
     * @param array $dictionary
     * @return string rendered template
     */
    public function render(
        \Magento\Framework\View\Element\BlockInterface $block,
        $templateFile,
        array $dictionary = []
    )
    {
        // $this->twig->addFilter();
        // use reflection :-( to figure out all getters ...
        // $filter = new Twig_SimpleFilter('rot13', array('SomeClass', 'rot13Filter'));
        // remove block specific filters after rendering
        return $this->getTemplate($templateFile)->render([
            'data' => $block->getData(), // temp added
        ]);
    }

    /**
     * @param $templateFile
     * @return \Twig_TemplateInterface
     */
    private function getTemplate($templateFile)
    {
        $tf = str_replace($this->_directoryList->getPath(DirectoryList::ROOT) . DIRECTORY_SEPARATOR, '', $templateFile);
        return $this->twig->loadTemplate($tf);
    }
}
