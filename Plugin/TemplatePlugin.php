<?php declare(strict_types=1);

namespace SchumacherFM\Twig\Plugin;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\File\Validator;

/**
 * Class TemplatePlugin
 */
class TemplatePlugin
{
    /**
     * @var Validator
     */
    protected $validator;

    /**
     * TemplatePlugin constructor.
     *
     * @param Validator $validator
     */
    public function __construct(Validator $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param Template $subject
     * @param callable $proceed
     * @param string $fileName
     * @return string
     */
    public function aroundFetchView(Template $subject, callable $proceed, string $fileName): string
    {
        $twigFile = $subject->getTemplateFile(str_replace('.phtml', '.html.twig', $subject->getTemplate()));

        if ($this->validator->isValid($twigFile)) {
            return $proceed($twigFile);
        }

        return $proceed($fileName);
    }
}
