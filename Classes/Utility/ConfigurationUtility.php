<?php
namespace Tx\Errortuner\Utility;

/*                                                                        *
 * This script belongs to the TYPO3 Extension "errortuner".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\CMS\Core\SingletonInterface;

/**
 * Utility class for retrieving configuration.
 */
class ConfigurationUtility implements SingletonInterface {

	/**
	 * @var bool
	 */
	protected $configurationInitialized = FALSE;

	/**
	 * @var string
	 */
	protected $errorPageTemplate = '';

	/**
	 * @var array
	 */
	protected $extensionConfiguration = array();

	/**
	 * @var bool
	 */
	protected $registerErrorHandlers = FALSE;

	/**
	 * Returns an instance of the ConfigurationUtility.
	 *
	 * @return ConfigurationUtility
	 */
	static public function getInstance() {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ConfigurationUtility::class);
	}

	/**
	 * Returns the error page template path.
	 *
	 * @return string
	 */
	public function getErrorPageTemplate() {
		$this->initialize();
		return $this->errorPageTemplate;
	}

	/**
	 * Returns TRUE when the custom error handlers should be used.
	 *
	 * @return bool
	 */
	public function getRegisterErrorHandlers() {
		$this->initialize();
		return $this->registerErrorHandlers;
	}

	/**
	 * Initializes the Extension configuration.
	 */
	protected function initialize() {

		if ($this->configurationInitialized) {
			return;
		}

		if (!empty($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['errortuner'])) {

			$this->extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['errortuner']);
			if (isset($this->extensionConfiguration['errorPageTemplate'])) {
				$errorPageTemplate = trim($this->extensionConfiguration['errorPageTemplate']);
				if ($errorPageTemplate !== '') {
					$this->errorPageTemplate = trim($errorPageTemplate);
				}
			}
			if (isset($this->extensionConfiguration['registerErrorHandlers'])) {
				$this->registerErrorHandlers = (bool)$this->extensionConfiguration['registerErrorHandlers'];
			}
		}

		$this->configurationInitialized = TRUE;
	}
}