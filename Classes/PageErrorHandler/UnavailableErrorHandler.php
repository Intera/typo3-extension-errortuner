<?php
declare(strict_types=1);

namespace Int\Errortuner\PageErrorHandler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Error\PageErrorHandler\PageErrorHandlerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class UnavailableErrorHandler implements PageErrorHandlerInterface
{
    public function handlePageError(
        ServerRequestInterface $request,
        string $message,
        array $reasons = []
    ): ResponseInterface {
        $includeErrorHandler = GeneralUtility::makeInstance(PhpIncludeErrorHandler::class);
        return $includeErrorHandler->handlePageError($request, 500, $message, $reasons);
    }
}
