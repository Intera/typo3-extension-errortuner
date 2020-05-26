<?php
declare(strict_types=1);

namespace Int\Errortuner\PageErrorHandler;

use InvalidArgumentException;
use RuntimeException;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Error\Http\PageNotFoundException;
use TYPO3\CMS\Core\Error\Http\ServiceUnavailableException;
use TYPO3\CMS\Core\Error\Http\StatusException;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\ArrayUtility;
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
            throw $this->getDefaultException($statusCode, $message);
        }
    }

    protected function includeErrorFile(int $statusCode, string $reasonText): string
    {
        if (!$this->isAdministratorLoggedIn()) {
            /** @noinspection PhpUnusedLocalVariableInspection Is used in included PHP! */
            $reasonText = '';
        }

        $errortunerConfig = $this->getErrorTunerConfiguration();
        $includePath = $errortunerConfig['errorIncludes'][$statusCode] ?? '';
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

    private function getErrorTunerConfiguration(): array
    {
        $globalConfiguration = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['errortuner'] ?? [];
        $siteConfiguration = $this->getErrorTunerConfigurationFromSite();
        ArrayUtility::mergeRecursiveWithOverrule($globalConfiguration, $siteConfiguration);
        return $globalConfiguration;
    }

    private function getErrorTunerConfigurationFromSite(): array
    {
        $config = [];
        if (empty($GLOBALS['TYPO3_REQUEST'])) {
            return $config;
        }

        $request = $GLOBALS['TYPO3_REQUEST'];
        $site = $request->getAttribute('site');
        if (!$site instanceof Site) {
            return $config;
        }

        $configuration = $site->getConfiguration();
        return $configuration['errortuner'] ?? [];
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
