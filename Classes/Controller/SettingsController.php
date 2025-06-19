<?php
namespace PITS\AiTranslate\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2024 Developer <contact@pitsolutions.com>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use PITS\AiTranslate\Domain\Repository\DeeplSettingsRepository;
use PITS\AiTranslate\Service\DeeplService;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Annotation\Inject;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use Psr\Http\Message\ResponseInterface;


/**
 * Class SettingsController
 */
class SettingsController extends ActionController
{
    /**
     * pageRenderer
     * @var \TYPO3\CMS\Core\Page\PageRenderer
     * 
     */
    #[Inject()]
    protected $pageRenderer;

    /**
     * @var \PITS\AiTranslate\Domain\Repository\DeeplSettingsRepository
     */
    #[Inject()]
    protected $deeplSettingsRepository;    

    /**
     * @var \PITS\AiTranslate\Service\DeeplService
     * 
     */
    #[Inject()]
    protected $deeplService;

     /**
     * siteFinder
     * @var \TYPO3\CMS\Core\Page\SiteFinder
     * 
     */
    #[Inject()]
    protected $siteFinder;

    /**
     * moduleTemplateFactory
     * @var \TYPO3\CMS\Backend\Template\ModuleTemplateFactory
     * 
     */
    #[Inject()]
    protected $moduleTemplateFactory;

     /**
     * Inject the PageRenderer
     *
     * @param \TYPO3\CMS\Core\Page\PageRenderer $pageRenderer
     */

     public function injectPageRenderer(PageRenderer $pageRenderer)
     {
         $this->pageRenderer = $pageRenderer;
     }

    /**
     * Inject the DeeplSettingsRepostory
     *
     * @param \PITS\AiTranslate\Domain\Repository\DeeplSettingsRepository $deeplSettingsRepository
     */

     public function injectDeeplSettingsRepository(DeeplSettingsRepository $deeplSettingsRepository)
     {
         $this->deeplSettingsRepository = $deeplSettingsRepository;
     }
     
    /**
     * Inject the DeeplService
     *
     * @param \PITS\AiTranslate\Service\DeeplService $deeplService
     */

     public function injectDeeplService(DeeplService $deeplService)
     {
         $this->deeplService = $deeplService;
     }

     /**
     * Inject the SiteFinder
     *
     * @param \TYPO3\CMS\Core\Page\SiteFinder $siteFinder
     */

     public function injectSiteFinder(SiteFinder $siteFinder)
     {
         $this->siteFinder = $siteFinder;
     }

      /**
     * Inject the ModuleTemplateFactory
     *
     * @param \TYPO3\CMS\Backend\Template\ModuleTemplateFactory $moduleTemplateFactory
     */

     public function injectModuleTemplateFactory(ModuleTemplateFactory $moduleTemplateFactory)
     {
         $this->moduleTemplateFactory = $moduleTemplateFactory;
     }

    /**
     * Default action
     * @return void
     */
    public function indexAction(): ResponseInterface
    {
        $args = $this->request->getArguments();
        if (isset($args['redirectFrom']) && $args['redirectFrom'] == 'savesetting') {
            $successMessage = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('settings_success', 'Deepl');
            $this->pageRenderer->addJsInlineCode("success", "top.TYPO3.Notification.success('Saved', '" . $successMessage . "');");
        }
        $sites = $this->siteFinder->getAllSites();
        foreach($sites as $site){
            $siteLanguageArray[] = $site->getAllLanguages();
        }
        $sysLanguages = call_user_func_array('array_merge', $siteLanguageArray);        
        $data         = [];
        $preSelect    = [];
        //get existing assignments if any
        $languageAssignments = $this->deeplSettingsRepository->getAssignments();
        if (!empty($languageAssignments) && !empty($languageAssignments[0]['languages_assigned'])) {
            $preSelect = array_filter(unserialize($languageAssignments[0]['languages_assigned']));
        }
        $selectBox = $this->buildTableAssignments($sysLanguages, $preSelect);
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $moduleTemplate->assignMultiple(['sysLanguages' => $sysLanguages, 'selectBox' => $selectBox]);
        return $moduleTemplate->renderResponse('Index');
    
    }

    /**
     * save language assignments
     * @return void
     */
    public function saveSettingsAction(): ResponseInterface
    {
        $args = $this->request->getArguments();
        if (!empty($args['languages'])) {
            $languages = array_filter($args['languages']);
        }

        $data = [];
        //get existing assignments if any
        $languageAssignments = $this->deeplSettingsRepository->getAssignments();
        if (!empty($languages)) {
            $data['languages_assigned'] = serialize($languages);
        }
        if (empty($languageAssignments)) {
            $data['crdate']      = time();
            $languageAssignments = $this->deeplSettingsRepository->insertDeeplSettings($data);
        } else {
            $data['uid']    = $languageAssignments[0]['uid'];
            $updateSettings = $this->deeplSettingsRepository->updateDeeplSettings($data);
        }
        $args['redirectFrom'] = 'savesetting';
        
        return $this->redirect('index', 'Settings', 'Deepl', $args);
    }

    /**
     * return an array of options for multiple selectbox
     * @param array $sysLanguages
     * @param array $preselectedValues
     * @return array
     */
    public function buildTableAssignments($sysLanguages, $preselectedValues)
    {
        $table        = [];
        $selectedKeys = array_keys($preselectedValues);
        foreach ($sysLanguages as $sysLanguage) {
            $syslangIso = $sysLanguage->getLocale()->getLanguageCode();
            $option     =  $sysLanguage->toArray();
            $languageId = $sysLanguage->getLanguageId();
            if (in_array($languageId, $selectedKeys) || 
            in_array(strtoupper($syslangIso), $this->deeplService->apiSupportedLanguages)) {
                $option['value'] = $preselectedValues[$languageId] ?? strtoupper($syslangIso);
            }
            $table[] = $option;
        }
        
        return $table;
    }

}
