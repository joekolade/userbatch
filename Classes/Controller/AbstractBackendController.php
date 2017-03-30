<?php
namespace JS\Userbatch\Controller;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

// use TYPO3\CMS\Backend\Utility\BackendUtility;
// use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
// use TYPO3\CMS\Core\Database\DatabaseConnection;
// use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
// use TYPO3\CMS\Lang\LanguageService;

/**
 * Backend module user administration controller
 */
class AbstractBackendController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{

    /**
     * $beusergroupRepository
     *
     * @var \TYPO3\CMS\Extbase\Domain\Repository\BackendUserGroupRepository
     * @inject
     */
    protected $beusergroupRepository = NULL;

    /**
     * $beuserRepository
     *
     * @var \TYPO3\CMS\Extbase\Domain\Repository\BackendUserRepository
     * @inject
     */
    protected $beuserRepository = NULL;

    /**
     * $feusergroupRepository
     *
     * @var \TYPO3\CMS\Extbase\Domain\Repository\FrontendUserGroupRepository
     * @inject
     */
    protected $feusergroupRepository = NULL;

    /**
     * feuserRepository
     *
     * @var \TYPO3\CMS\Extbase\Domain\Repository\FrontendUserRepository
     * @inject
     */
    protected $feuserRepository = NULL;

    /**
     * downloads: download
     *
     * @var \JS\Userbatch\Domain\Repository\ImportuserRepository
     * @inject
     */
    protected $importUserRepository = NULL;


    protected function beMessage(
        $message = 'My message',
        $title = '',
        $severity = '',
        $storeInSession = TRUE
    ) {
        /** @var $flashMessage FlashMessage */
        $flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
          'JS\\Userbatch\\Messaging\\FlashMessage',
          htmlspecialchars($message),
          htmlspecialchars($title),
          $this->_parseSeverity($severity),
          $storeInSession // store in session
        );
        /** @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
        $flashMessageService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessageService');
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $defaultFlashMessageQueue->enqueue($flashMessage);
    }

    private function _parseSeverity($sev){

        switch ($sev) {
            case 'NOTICE':
                $sev = \JS\Userbatch\Messaging\FlashMessage::NOTICE;
                break;
            case 'INFO':
                $sev = \JS\Userbatch\Messaging\FlashMessage::INFO;
                break;
            case 'OK':
                $sev = \JS\Userbatch\Messaging\FlashMessage::OK;
                break;
            case 'WARNING':
                $sev = \JS\Userbatch\Messaging\FlashMessage::WARNING;
                break;
            case 'ERROR':
                $sev = \JS\Userbatch\Messaging\FlashMessage::ERROR;
                break;
            default:
                break;
        }

        return $sev;
    }

    public function clearCaches(){
        // Flush all Caches
        // RealURL -- Code from dix_urltool
        $GLOBALS['TYPO3_DB']->exec_TRUNCATEquery('tx_realurl_uniqalias');
        $GLOBALS['TYPO3_DB']->exec_TRUNCATEquery('tx_realurl_pathcache');
        $GLOBALS['TYPO3_DB']->exec_TRUNCATEquery('tx_realurl_urlcache');

        // Clear FE-Cache
        $GLOBALS['TYPO3_DB']->exec_TRUNCATEquery('cf_cache_pages');
        $GLOBALS['TYPO3_DB']->exec_TRUNCATEquery('cf_cache_pagesection');
    }

    /**
    * @param array $recipient recipient of the email in the format array('recipient@domain.tld' => 'Recipient Name')
    * @param array $sender sender of the email in the format array('sender@domain.tld' => 'Sender Name')
    * @param string $subject subject of the email
    * @param string $templateName template name (UpperCamelCase)
    * @param array $variables variables to be passed to the Fluid view
    */
    protected function sendTemplateEmail(array $recipient, array $sender, $subject, $templateName, array $variables = array()) {
      /** @var \TYPO3\CMS\Fluid\View\StandaloneView $emailView */
      $emailView = $this->objectManager->get('TYPO3\\CMS\\Fluid\\View\\StandaloneView');

      $extbaseFrameworkConfiguration = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
      $emailRootPath = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($extbaseFrameworkConfiguration['view']['templateRootPaths'][1]);

      $templatePathAndFilename = $emailRootPath . 'Emails/' . $templateName . '.html';

      $emailView->setTemplatePathAndFilename($templatePathAndFilename);
      $emailView->assignMultiple($variables);
      $emailBody = $emailView->render();

      /** @var $message \TYPO3\CMS\Core\Mail\MailMessage */
      $message = $this->objectManager->get('TYPO3\\CMS\\Core\\Mail\\MailMessage');
      $message->setTo($recipient)
          ->setFrom($sender)
          ->setSubject($subject);

      // Plain text example
      // $message->setBody($emailBody, 'text/plain');

      // HTML Email
      $message->setBody($emailBody, 'text/html');

      $message->send();
      return $message->isSent();
    }
}
