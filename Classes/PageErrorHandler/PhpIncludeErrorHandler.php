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
use TYPO3\CMS\Core\Error\PageErrorHandler\PageErrorHandlerInterface;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PhpIncludeErrorHandler extends AbstractPhpIncludeHandler implements PageErrorHandlerInterface
{
    /**
     * @var array
     */
    private $errorHandlerConfiguration;

    /**
     * @var int
     */
    private $statusCode;

    public function __construct(int $statusCode, array $errorHandlerConfiguration)
    {
        $this->statusCode = $statusCode;
        $this->errorHandlerConfiguration = $errorHandlerConfiguration;
    }

    public function handlePageError(
        ServerRequestInterface $request,
        string $message,
        array $reasons = []
    ): ResponseInterface {
        switch ($this->statusCode) {
            case 403:
                $headerConfigKey = 'pageNotFound_handling_accessdeniedheader';
                break;
            case 404:
                $headerConfigKey = 'pageNotFound_handling_statheader';
                break;
            case 500:
                $headerConfigKey = 'pageUnavailable_handling_statheader';
                break;
            default:
                throw new InvalidArgumentException('Can not handle error code ' . $this->statusCode);
        }

        $this->handleSolrRequest($this->statusCode, $message);

        $errorContents = $this->includeErrorFile($this->statusCode, $message);

        $headers = $GLOBALS['TYPO3_CONF_VARS']['FE'][$headerConfigKey] ?? '';
        if (empty($headers)) {
            throw new RuntimeException('Header is not configured for ' . $headerConfigKey);
        }

        $response = new HtmlResponse($errorContents);
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
}
