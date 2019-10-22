<?php declare(strict_types=1);

namespace SchumacherFM\Twig\Plugin;

/**
 * Class TemplatePlugin
 */
class TemplatePlugin
{
    /**
     * @var \Magento\Framework\View\Element\Template\File\Validator
     */
    protected $validator;

    /**
     * TemplatePlugin constructor.
     *
     * @param \Magento\Framework\View\Element\Template\File\Validator $validator
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\File\Validator $validator
    )
    {
        $this->validator = $validator;
    }

    /**
     * @param \Magento\Framework\View\Element\Template $subject
     * @param callable $proceed
     * @param string $fileName
     * @return string
     */
    public function aroundFetchView(
        \Magento\Framework\View\Element\Template $subject,
        callable $proceed,
        string $fileName
    ): string
    {
        $twigFile = $subject->getTemplateFile(str_replace('.phtml', '.html.twig', $subject->getTemplate()));

        if ($this->validator->isValid($twigFile)) {
            return $proceed($twigFile);
        }

        return $proceed($fileName);
    }
}
