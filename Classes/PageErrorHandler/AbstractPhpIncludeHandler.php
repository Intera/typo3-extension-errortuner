<?php
declare(strict_types=1);

namespace Int\Errortuner\PageErrorHandler;

use InvalidArgumentException;
use RuntimeException;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Error\Http\PageNotFoundException;
use TYPO3\CMS\Core\Error\Http\ServiceUnavailableException;
use TYPO3\CMS\Core\Error\Http\StatusException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class AbstractPhpIncludeHandler
{
    protected function getDefaultException(int $statusCode, string $message): StatusException
    {
        switch ($statusCode) {
            case 403:
            case 404:
                return new PageNotFoundException($message, 1518472189);
                break;
            case 500:
                return new ServiceUnavailableException($message, 1518472181);
            default:
                throw new InvalidArgumentException('Unknown error code: ' . $statusCode);
        }
    }

    protected function handleSolrRequest(int $statusCode, string $message): void
    {
        if (!empty($_SERVER['HTTP_X_TX_SOLR_IQ'])) {
            $exception = $this->getDefaultException($statusCode, $message);
            throw $exception;
        }
    }

    protected function includeErrorFile(int $statusCode, string $reasonText): string
    {
        if (!$this->isAdministratorLoggedIn()) {
            $reasonText = '';
        }

        $includePath = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['errortuner']['errorIncludes'][$statusCode] ?? '';
        if (!$includePath) {
            throw new RuntimeException('Include path is not configured for status ' . $statusCode);
        }

        $resolvedIncludePath = GeneralUtility::getFileAbsFileName($includePath);
        if (empty($resolvedIncludePath)) {
            throw new RuntimeException('Could not resolve include path: ' . $includePath);
        }

        ob_start();
        /** @noinspection PhpIncludeInspection */
        include $resolvedIncludePath;
        return ob_get_clean();
    }

    private function isAdministratorLoggedIn()
    {
        $context = GeneralUtility::makeInstance(Context::class);
        if (!$context->getPropertyFromAspect('backend.user', 'isLoggedIn', false)) {
            return false;
        }

        return $context->getPropertyFromAspect('backend.user', 'isAdmin', false);
    }
}
