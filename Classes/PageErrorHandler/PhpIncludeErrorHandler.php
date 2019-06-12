<?php
declare(strict_types=1);

namespace Int\Errortuner\PageErrorHandler;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use TYPO3\CMS\Core\Error\Http\PageNotFoundException;
use TYPO3\CMS\Core\Error\Http\ServiceUnavailableException;
use TYPO3\CMS\Core\Error\Http\StatusException;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PhpIncludeErrorHandler
{
    public function handlePageError(
        ServerRequestInterface $request,
        int $errorCode,
        string $message,
        array $reasons = []
    ): ResponseInterface {
        switch ($errorCode) {
            case 403:
                $configKey = 'pageNotFound_handling_accessdenied';
                $headerConfigKey = 'pageNotFound_handling_accessdeniedheader';
                break;
            case 404:
                $configKey = 'pageNotFound_handling';
                $headerConfigKey = 'pageNotFound_handling_statheader';
                break;
            case 500:
                $configKey = 'pageUnavailable_handling';
                $headerConfigKey = 'pageUnavailable_handling_statheader';
                break;
            default:
                throw new InvalidArgumentException('Can not handle erorr code ' . $errorCode);
        }

        if (!empty($_SERVER['HTTP_X_TX_SOLR_IQ'])) {
            $exception = $this->getDefaultException($errorCode, $message);
            throw $exception;
        }

        $includePath = $GLOBALS['TYPO3_CONF_VARS']['FE'][$configKey] ?? '';
        if (empty($includePath)) {
            throw new RuntimeException('Include path is not configured for ' . $configKey);
        }

        $includePath = str_replace('/typo3conf/ext/', 'EXT:', $includePath);
        $resolvedIncludePath = GeneralUtility::getFileAbsFileName($includePath);
        if (empty($resolvedIncludePath)) {
            throw new RuntimeException('Could not resolve include path: ' . $includePath);
        }

        $headers = $GLOBALS['TYPO3_CONF_VARS']['FE'][$headerConfigKey] ?? '';
        if (empty($headers)) {
            throw new RuntimeException('Header is not configured for ' . $headerConfigKey);
        }

        var_dump($resolvedIncludePath);
        die();

        ob_start();
        /** @noinspection PhpIncludeInspection */
        include $resolvedIncludePath;
        $errorContent = ob_get_clean();

        $response = new HtmlResponse($errorContent);
        $response = $this->applySanitizedHeadersToResponse($response, $headers);
        return $response;
    }

    /**
     * Headers which have been requested, will be added to the response object.
     * If a header is part of the HTTP Response code, the response object will be annotated as well.
     *
     * @param ResponseInterface $response
     * @param string $headers
     * @return ResponseInterface
     */
    protected function applySanitizedHeadersToResponse(ResponseInterface $response, string $headers): ResponseInterface
    {
        if (!empty($headers)) {
            $headerArr = preg_split('/\\r|\\n/', $headers, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($headerArr as $headerLine) {
                if (strpos($headerLine, 'HTTP/') === 0 && strpos($headerLine, ':') === false) {
                    list($protocolVersion, $statusCode, $reasonPhrase) = explode(' ', $headerLine, 3);
                    list(, $protocolVersion) = explode('/', $protocolVersion, 2);
                    $response = $response
                        ->withProtocolVersion((int)$protocolVersion)
                        ->withStatus($statusCode, $reasonPhrase);
                } else {
                    list($headerName, $value) = GeneralUtility::trimExplode(':', $headerLine, 2);
                    $response = $response->withHeader($headerName, $value);
                }
            }
        }
        return $response;
    }

    private function getDefaultException(int $errorCode, string $message): StatusException
    {
        switch ($errorCode) {
            case 403:
            case 404:
                return new PageNotFoundException($message, 1518472189);
                break;
            case 500:
                return new ServiceUnavailableException($message, 1518472181);
            default:
                throw new InvalidArgumentException('Unknown error code: ' . $errorCode);
        }
    }
}
