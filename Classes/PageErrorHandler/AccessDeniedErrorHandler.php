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
     * @var TypoScriptFrontendController|null
     */
    private $frontendController;

    public function __construct(?TypoScriptFrontendController $tsfe = null)
    {
        $this->frontendController = $tsfe ?? $GLOBALS['TSFE'];
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

        $includeErrorHandler = GeneralUtility::makeInstance(PhpIncludeErrorHandler::class);
        return $includeErrorHandler->handlePageError($request, 403, $message, $reasons);
    }

    /**
     * If a configured login page was found the user will be redirected
     * to this page if he is not already logged in.
     *
     * @return RedirectResponse|null
     */
    private function getLoginPageRedirectResponse(): ?RedirectResponse
    {
        if (!$this->frontendController) {
            return null;
        }

        if (!empty($this->frontendController->fe_user->user['uid'])) {
            // If a user is already logged in we do not redirect. He simply does
            // not have enough access rights.
            $currentUserUid = (integer)$this->frontendController->fe_user->user['uid'];
            if ($currentUserUid !== 0) {
                return null;
            }
        }

        $this->frontendController->getConfigArray();

        $template = $this->frontendController->tmpl;

        if (!isset($template->setup['config.']['tx_errortuner.']['loginPageUrl'])) {
            return null;
        }

        $targetUrl = $template->setup['config.']['tx_errortuner.']['loginPageUrl'];
        if (isset($template->setup['config.']['tx_errortuner.']['loginPageUrl.'])) {
            $contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
            $this->frontendController->tmpl = $template;
            $this->frontendController->config = $template->setup['config.'];
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
