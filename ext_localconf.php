<?php
defined('TYPO3_MODE') or die();

// We register our own error handlers but only if the current
// request is not made by the solr indexer.
if (
	TYPO3_MODE === 'FE'
	&& \Tx\Errortuner\Utility\ConfigurationUtility::getInstance()->getRegisterErrorHandlers()
) {

	if (!isset($_SERVER['HTTP_X_TX_SOLR_IQ'])) {

		// Make sure the required configuration for 403 errors is available but do not override it when it was set.
		if (!isset($GLOBALS['TYPO3_CONF_VARS']['FE']['pageForbidden_handling_statheader'])) {
			$GLOBALS['TYPO3_CONF_VARS']['FE']['pageForbidden_handling_statheader'] = 'HTTP/1.0 403 Forbidden';
		}
		if (!isset($GLOBALS['TYPO3_CONF_VARS']['FE']['pageForbidden_handling'])) {
			$GLOBALS['TYPO3_CONF_VARS']['FE']['pageForbidden_handling'] = '';
		}

		$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Core\\Messaging\\ErrorpageMessage']['className'] = \Tx\Errortuner\Frontend\StyledErrorpageMessage::class;

		// Backup the current handler configuration (see AdditionalConfiguration.php for pageForbidden_handling)
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['errortuner']['pageNotFound_handling'] = $GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFound_handling'];
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['errortuner']['pageUnavailable_handling'] = $GLOBALS['TYPO3_CONF_VARS']['FE']['pageUnavailable_handling'];
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['errortuner']['pageForbidden_handling'] = $GLOBALS['TYPO3_CONF_VARS']['FE']['pageForbidden_handling'];

		// Overwrite the handlers with the errortuner handlers.
		$GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFound_handling'] = 'USER_FUNCTION:' . \Tx\Errortuner\Frontend\FrontendErrorHandler::class . '->handlePageNotFoundError';
		$GLOBALS['TYPO3_CONF_VARS']['FE']['pageUnavailable_handling'] = 'USER_FUNCTION:' . \Tx\Errortuner\Frontend\FrontendErrorHandler::class . '->handlePageUnavailableError';
		$GLOBALS['TYPO3_CONF_VARS']['FE']['pageForbidden_handling'] = 'USER_FUNCTION:' . \Tx\Errortuner\Frontend\FrontendErrorHandler::class . '->handlePageForbiddenError';

	} else {
		// For the solr indexer we need the defalut page not found handling.
		$GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFound_handling'] = '';
	}
}