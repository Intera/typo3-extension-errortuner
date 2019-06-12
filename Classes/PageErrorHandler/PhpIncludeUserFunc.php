<?php
declare(strict_types=1);

namespace Int\Errortuner\PageErrorHandler;

use TYPO3\CMS\Frontend\Controller\ErrorController;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class PhpIncludeUserFunc extends AbstractPhpIncludeHandler
{
    /**
     * @var string
     */
    private $currentUrl = '';

    /**
     * @var array
     */
    private $pageAccessFailureReasons = [];

    /**
     * @var ErrorController|TypoScriptFrontendController
     */
    private $parent;

    /**
     * @var string
     */
    private $reasonText = '';

    public function handleError404(array $params, $parent): string
    {
        return $this->handleError(404, $params, $parent);
    }

    public function handleError503(array $params, $parent): string
    {
        return $this->handleError(503, $params, $parent);
    }

    private function handleError(int $statusCode, array $params, $parent): string
    {
        $this->currentUrl = $params['currentUrl'];
        $this->reasonText = $params['reasonText'];
        $this->pageAccessFailureReasons = $params['pageAccessFailureReasons'];
        $this->parent = $parent;

        $this->handleSolrRequest($statusCode, $this->reasonText);

        $errorContent = $this->includeErrorFile($statusCode, $this->reasonText);
        return $errorContent;
    }
}
