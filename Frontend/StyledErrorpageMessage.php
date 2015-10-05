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

use TYPO3\CMS\Core\Messaging\ErrorpageMessage;

/**
 * Overwrites the default error page message and uses
 * a custom HTML template for rendering.
 */
class StyledErrorpageMessage extends ErrorpageMessage {

	/**
	 * Constructor for an Error message
	 *
	 * @param string $message The error message
	 * @param string $title Title of the message, can be empty
	 * @param integer $severity Optional severity, must be either of AbstractMessage::INFO or related constants
	 */
	public function __construct($message = '', $title = '', $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR) {
		parent::__construct($message, $title, $severity);
		$templatePath = \Tx\Errortuner\Utility\ConfigurationUtility::getInstance()->getErrorPageTemplate();
		if ($templatePath !== '') {
			$this->setHtmlTemplate($templatePath);
		}
	}
}