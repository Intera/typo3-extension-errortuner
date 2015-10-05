<?php
namespace Tx\Errortuner\Frontend;

/*                                                                        *
 * This script belongs to the TYPO3 Extension "errortuner".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Advanced error handling for the Frontend.
 *
 * The error handler will check the reason for a page not found error.
 *
 * If the reason was that the user had not enough access rights a 403
 * (Forbidden) error will be thrown instead of a 404 (Not found) error.
 *
 * If no user is logged in he will be redirected to the login form (if
 * configured in config.tx_errortuner.loginPageTypolink).
 */
class FrontendErrorHandler {

	/**
	 * @var \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
	 */
	protected $frontendController;

	/**
	 * Handles 403 errors.
	 * Not yet implemented in TYPO3 core, see http://forge.typo3.org/issues/23178
	 *
	 * @param array $params
	 * @param \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $frontendController
	 */
	public function handlePageForbiddenError(
		/** @noinspection PhpUnusedParameterInspection */
		$params,
		$frontendController
	) {
		$this->frontendController = $frontendController;
		$this->pageAccessForbiddenHandler();
	}

	/**
	 * Handles page not found errors (404) and checks if they are not
	 * forbidden (403) errors in reality
	 *
	 * @param array $params array containing 'currentUrl', 'reasonText' and 'pageAccessFailureReasons'
	 * @param \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $frontendController
	 * @return void
	 */
	public function handlePageNotFoundError($params, $frontendController) {

		$this->frontendController = $frontendController;
		$pid = (int)$this->frontendController->id;

		if (
			$params['reasonText'] === 'ID was not an accessible page'
			|| $params['reasonText'] === 'Subsection was found and not accessible'
		) {

			/** @var \TYPO3\CMS\Frontend\Page\PageRepository $pageRepository */
			$pageRepository = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Page\\PageRepository');
			$pageRepository->init(FALSE);

			$failureHistory = $this->frontendController->pageAccessFailureHistory;
			if (is_array($failureHistory['direct_access']) && count($failureHistory['direct_access'])) {
				$pid = $failureHistory['direct_access'][0]['uid'];
			}

			$pageWithoutAccessRestriction = $pageRepository->getPage($pid, TRUE);
			if (count($pageWithoutAccessRestriction)) {
				$this->pageAccessForbiddenHandler();
			}
		}

		/** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
		$signalSlotDispatcher = GeneralUtility::makeInstance('TYPO3\CMS\Extbase\SignalSlot\Dispatcher');
		$signalSlotDispatcher->dispatch(get_class($this), 'pageNotFoundPreProcess', array($pid, $this->frontendController));

		$header = $frontendController->TYPO3_CONF_VARS['FE']['pageNotFound_handling_statheader'];
		$this->handlePageError($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['errortuner']['pageNotFound_handling'], $header, $params['reasonText']);
	}

	/**
	 * Handles page unavailable errors (503) and checks if they are not
	 * forbidden (403) errors in reality
	 *
	 * @param array $params array containing 'currentUrl', 'reasonText' and 'pageAccessFailureReasons'
	 * @param \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $frontendController
	 * @return void
	 */
	public function handlePageUnavailableError($params, $frontendController) {

		$this->frontendController = $frontendController;

		if (array_key_exists('pageAccessFailureReasons', $params)) {
			if (array_key_exists('fe_group', $params['pageAccessFailureReasons'])) {
				foreach ($params['pageAccessFailureReasons']['fe_group'] as $pageId => $requiredGroug) {
					if ($pageId > 0) {
						$this->pageAccessForbiddenHandler();
					}
				}
			}
		}

		$header = $frontendController->TYPO3_CONF_VARS['FE']['pageUnavailable_handling_statheader'];
		$this->handlePageError($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['errortuner']['pageUnavailable_handling'], $header, $params['reasonText']);
	}

	/**
	 * If a valid page was requested (current page UID is not zero) the
	 * TypoScript configuration for this page will be initialized and
	 * returned.
	 *
	 * @see \TYPO3\CMS\Extbase\Configuration\BackendConfigurationManager::getTypoScriptSetup()
	 * @return \TYPO3\CMS\Core\TypoScript\TemplateService
	 */
	protected function getTypoScriptTemplate() {

		$currentPageId = (integer)$this->frontendController->id;
		if ($currentPageId === 0) {
			return array();
		}

		/** @var $template \TYPO3\CMS\Core\TypoScript\TemplateService */
		$template = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\TypoScript\\TemplateService');
		// do not log time-performance information
		$template->tt_track = 0;
		// Explicitly trigger processing of extension static files
		$template->setProcessExtensionStatics(TRUE);
		$template->init();

		/** @var $sysPage \TYPO3\CMS\Frontend\Page\PageRepository */
		$sysPage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Page\\PageRepository');
		// Get the rootline for the current page
		$rootline = $sysPage->getRootLine($currentPageId, $this->frontendController->MP, TRUE);

		// This generates the constants/config + hierarchy info for the template.
		$template->start($rootline);

		return $template;
	}

	/**
	 * Processes the current error by using pageErrorHandler() in the TypoScriptFrontendController.
	 *
	 * @param string $code
	 * @param string $header
	 * @param string $reason
	 */
	protected function handlePageError($code, $header, $reason) {
		$this->frontendController->pageErrorHandler($code, $header, $reason);
	}

	/**
	 * Prints a 403 - Forbidden error message
	 *
	 * @return void
	 */
	protected function pageAccessForbiddenHandler() {
		$this->redirectToLoginPage();
		$header = $this->frontendController->TYPO3_CONF_VARS['FE']['pageForbidden_handling_statheader'];
		$this->handlePageError($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['errortuner']['pageForbidden_handling'], $header, 'The requested page was not accessible!');
	}

	/**
	 * If a configured login page was found the user will be redirected
	 * to this page if he is not already logged in.
	 */
	protected function redirectToLoginPage() {

		if (!isset($this->frontendController)) {
			return;
		}

		if (isset($this->frontendController->fe_user->user['uid'])) {
			// If a user is already logged in we do not redirect. He simply does
			// not have enough access rights.
			$currentUserUid = (integer)$this->frontendController->fe_user->user['uid'];
			if ($currentUserUid !== 0) {
				return;
			}
		}

		$template = $this->getTypoScriptTemplate();

		if (!isset($template->setup['config.']['tx_errortuner.']['loginPageUrl'])) {
			return;
		}

		$targetUrl = $template->setup['config.']['tx_errortuner.']['loginPageUrl'];
		if (isset($template->setup['config.']['tx_errortuner.']['loginPageUrl.'])) {
			/** @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObject */
			$contentObject = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
			$this->frontendController->tmpl = $template;
			$this->frontendController->config = $template->setup['config.'];
			$targetUrl = $contentObject->cObjGetSingle($targetUrl, $template->setup['config.']['tx_errortuner.']['loginPageUrl.']);
		}

		if (!empty($targetUrl)) {

			// Remove ?logintype=logout from URL
			if (strpos($targetUrl, '%3Flogintype%3Dlogout') !== FALSE) {
				$targetUrl = str_replace('%3Flogintype%3Dlogout', '', $targetUrl);
			}

			// Remove &logintype=logout from URL
			if (strpos($targetUrl, '%26logintype%3Dlogout') !== FALSE) {
				$targetUrl = str_replace('%26logintype%3Dlogout', '', $targetUrl);
			}

			\TYPO3\CMS\Core\Utility\HttpUtility::redirect($targetUrl);
		}
	}
}