<?php
declare(strict_types=1);

namespace Int\Errortuner\PageErrorHandler;

use TYPO3\CMS\Frontend\Controller\ErrorController;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class PhpIncludeUserFunc extends AbstractPhpIncludeHandler
{
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
        $reasonText = $params['reasonText'];

        $this->handleSolrRequest($statusCode, $reasonText);

        return $this->includeErrorFile($statusCode, $reasonText);
    }
}
