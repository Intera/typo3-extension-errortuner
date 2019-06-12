<?php
declare(strict_types=1);

namespace Int\Errortuner\PageErrorHandler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Error\PageErrorHandler\PageErrorHandlerInterface;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class AccessDeniedErrorHandler implements PageErrorHandlerInterface
{
    /**
     * @var array
     */
    private $errorHandlerConfiguration;

    /**
     * @var int
     */
    private $statusCode;

    /**
     * @var TypoScriptFrontendController|null
     */
    private $tsfe;

    public function __construct(
        int $statusCode,
        array $errorHandlerConfiguration,
        ?TypoScriptFrontendController $tsfe = null
    ) {
        $this->errorHandlerConfiguration = $errorHandlerConfiguration;
        $this->statusCode = $statusCode;
        $this->tsfe = $tsfe ?? $GLOBALS['TSFE'];
    }

    public function handlePageError(
        ServerRequestInterface $request,
        string $message,
        array $reasons = []
    ): ResponseInterface {
        $redirectResponse = $this->getLoginPageRedirectResponse();
        if ($redirectResponse) {
            return $redirectResponse;
        }

        $includeErrorHandler = GeneralUtility::makeInstance(PhpIncludeErrorHandler::class, 403, []);
        return $includeErrorHandler->handlePageError($request, $message, $reasons);
    }

    /**
     * If a configured login page was found the user will be redirected
     * to this page if he is not already logged in.
     *
     * @return RedirectResponse|null
     */
    private function getLoginPageRedirectResponse(): ?RedirectResponse
    {
        if (!$this->tsfe) {
            return null;
        }

        if (!empty($this->tsfe->fe_user->user['uid'])) {
            // If a user is already logged in we do not redirect. He simply does
            // not have enough access rights.
            $currentUserUid = (integer)$this->tsfe->fe_user->user['uid'];
            if ($currentUserUid !== 0) {
                return null;
            }
        }

        $this->tsfe->getConfigArray();

        $template = $this->tsfe->tmpl;

        if (!isset($template->setup['config.']['tx_errortuner.']['loginPageUrl'])) {
            return null;
        }

        $targetUrl = $template->setup['config.']['tx_errortuner.']['loginPageUrl'];
        if (isset($template->setup['config.']['tx_errortuner.']['loginPageUrl.'])) {
            $contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
            $this->tsfe->tmpl = $template;
            $this->tsfe->config = $template->setup['config.'];
            $targetUrl = $contentObject->cObjGetSingle(
                $targetUrl,
                $template->setup['config.']['tx_errortuner.']['loginPageUrl.']
            );
        }

        if (empty($targetUrl)) {
            return null;
        }

        // Remove ?logintype=logout from URL
        if (strpos($targetUrl, '%3Flogintype%3Dlogout') !== false) {
            $targetUrl = str_replace('%3Flogintype%3Dlogout', '', $targetUrl);
        }

        // Remove &logintype=logout from URL
        if (strpos($targetUrl, '%26logintype%3Dlogout') !== false) {
            $targetUrl = str_replace('%26logintype%3Dlogout', '', $targetUrl);
        }

        $targetUrl = GeneralUtility::locationHeaderUrl($targetUrl);
        return new RedirectResponse($targetUrl);
    }
}
