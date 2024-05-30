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

/**
 * Module: TYPO3/CMS/Backend/Localization
 * UI for localization workflow.
 */
define([
    'jquery',
    'TYPO3/CMS/Backend/AjaxDataHandler',
    'TYPO3/CMS/Backend/Wizard',
    'TYPO3/CMS/Backend/Icons',
    'TYPO3/CMS/Backend/Severity',
    'bootstrap'
  ], function($, DataHandler, Wizard, Icons, Severity) {
    'use strict';
  
    /**
     * @type {{identifier: {triggerButton: string}, actions: {translate: $, copy: $}, settings: {}, records: []}}
     * @exports TYPO3/CMS/Backend/Localization
     */
    var Localization = {
      identifier: {
          triggerButton: '.t3js-localize'
      },
      actions: {
        translate: $('<label />', {
            class: 'btn btn-block btn-default t3js-option',
            'data-helptext': '.t3js-helptext-translate'
        }).html('<br>Translate').prepend(
            $('<input />', {
                type: 'radio',
                name: 'mode',
                id: 'mode_translate',
                value: 'localize',
                style: 'display: none'
            })
        ),
        copy: $('<label />', {
            class: 'btn btn-block btn-default t3js-option',
            'data-helptext': '.t3js-helptext-copy'
        }).html('<br>Copy').prepend(
            $('<input />', {
                type: 'radio',
                name: 'mode',
                id: 'mode_copy',
                value: 'copyFromLanguage',
                style: 'display: none'
            })
        ),
        deepltranslate: $('<label />', {
            class: 'btn btn-block btn-default t3js-option',
            'data-helptext': '.t3js-helptext-translate'
        }).html('<br>Translate<br>(deepl)').prepend(
            $('<input />', {
                type: 'radio',
                name: 'mode',
                id: 'mode_deepltranslate',
                value: 'localizedeepl',
                style: 'display: none'
            })
        ),
        deepltranslateAuto: $('<label />', {
            class: 'btn btn-block btn-default t3js-option',
            'data-helptext': '.t3js-helptext-translate'
        }).html('<br>Translate<br>(deepl)<br>(autodetect)').prepend(
            $('<input />', {
                type: 'radio',
                name: 'mode',
                id: 'mode_deepltranslateauto',
                value: 'localizedeeplauto',
                style: 'display: none'
            })
        ),
        googletranslateAuto: $('<label />', {
            class: 'btn btn-block btn-default t3js-option',
            'data-helptext': '.t3js-helptext-translate'
        }).html('<br>Translate<br>(Google)<br>(autodetect)').prepend(
            $('<input />', {
                type: 'radio',
                name: 'mode',
                id: 'mode_googletranslateauto',
                value: 'localizegoogleauto',
                style: 'display: none'
            })
        ),
        googletranslate: $('<label />', {
            class: 'btn btn-block btn-default t3js-option',
            'data-helptext': '.t3js-helptext-translate'
        }).html('<br>Translate<br>(Google)').prepend(
            $('<input />', {
                type: 'radio',
                name: 'mode',
                id: 'mode_googletranslate',
                value: 'localizegoogle',
                style: 'display: none'
            })
        ),
        openaitranslate: $('<label />', {
          class: 'btn btn-block btn-default t3js-option',
          'data-helptext': '.t3js-helptext-translate'
      }).html('<br>Translate with <br>OpenAI').prepend(
          $('<input />', {
              type: 'radio',
              name: 'mode',
              id: 'mode_openaitranslate',
              value: 'localizeopenai',
              style: 'display: none'
          })
      ),
      geminiaitranslate: $('<label />', {
        class: 'btn btn-block btn-default t3js-option',
        'data-helptext': '.t3js-helptext-translate'
    }).html('<br>Translate with <br>Gemini').prepend(
        $('<input />', {
            type: 'radio',
            name: 'mode',
            id: 'mode_opengeminitranslate',
            value: 'localizegeminiai',
            style: 'display: none'
        })
      ),
      claudeaitranslate: $('<label />', {
        class: 'btn btn-block btn-default t3js-option',
        'data-helptext': '.t3js-helptext-translate'
    }).html('<br>Translate with <br>Claude').prepend(
        $('<input />', {
            type: 'radio',
            name: 'mode',
            id: 'mode_openclaudetranslate',
            value: 'localizeclaudeai',
            style: 'display: none'
        })
      )	  
          
      },
      settings: {},
      records: [],
      labels:{
          deeplSettingsFailure:'Please complete missing deepl configurations.',
		  googleSettingsFailure:'Please complete missing google translator configurations.',
		  openaiSettingsFailure:'Please complete missing openai configurations.',
		  geminiSettingsFailure:'Please complete missing gemini configurations.',
		  claudeSettingsFailure:'Please complete missing claude configurations.',
		  deeplTranslate :'Translating content via Deepl translate will create a translation  from source language to the language you translate to.The translated content will be from deepl translation service which is 99 percentage accurate.Deepl service supports translation to and from English, French, German, Spanish, Italian, Dutch, and Polish languages.Other languages will be ignored and will default to the normal translate operation of TYPO3.',
		  deeplTranslateAuto:'Translating content via Deepl translate (autodetect) will create a translation  from auto detected source language to the language you translate to.The translated content will be from deepl translation service which is 99 percentage accurate.Deepl service supports translation to and from English, French, German, Spanish, Italian, Dutch, and Polish languages.Other languages will be ignored and will default to the normal translate operation of TYPO3.',
		  googleTranslate:'Translating content via Google Translate will create a translation  from source language to the language you translate to.The translated content will be from Google translation service.Google service supports a fair share of <a href="https://cloud.google.com/translate/docs/languages" target="_blank">languages</a>',
		  googleTranslateAuto:'Translating content via Google Translate (autodetect) will create a translation  from auto detected source language to the language you translate to.The translated content will be from Google translation service.Google service supports a fair share of <a href="https://cloud.google.com/translate/docs/languages" target="_blank">languages</a>',
		  openAi:'Translation functionality establishes a direct linkage between the original language and the translated version as your website"s configurations. Relocating an element or configuring meta information, like start- or endtime, will be derived from the original content. Please note that the translation is limited to AI"s Capabilities.',
		  geminiAi:'Google Gemini AI can translate between languages with a high degree of accuracy. This makes it ideal for tasks such as real-time translation, document translation, and website translation. Please note that the translation is limited to AI"s Capabilities.',
		  claudeAi:'The process is straightforward – simply provide the text you need translated, and Claude will quickly generate the translation in your desired target language. Please note that the translation is limited to AI"s Capabilities.'

      }
    };
  
  Localization.initialize = function() {
    Icons.getIcon('actions-localize', Icons.sizes.large).done(function(localizeIconMarkup) {
        Icons.getIcon('actions-edit-copy', Icons.sizes.large).done(function(copyIconMarkup) {
            Icons.getIcon('actions-localize-deepl', Icons.sizes.large).done(function(localizeDeeplIconMarkup) {
                Icons.getIcon('actions-localize-deepl', Icons.sizes.large).done(function(localizeDeeplIconMarkup) {
                        Icons.getIcon('actions-localize-google', Icons.sizes.large).done(function(localizeGoogleIconMarkup) {
                            Icons.getIcon('actions-localize-google', Icons.sizes.large).done(function(localizeGoogleIconMarkup) {
                            Icons.getIcon('actions-localize-openai', Icons.sizes.large).done(function(localizeOpenAiIconMarkup) {  
                            Icons.getIcon('actions-localize-geminiai', Icons.sizes.large).done(function(localizeGeminiAiIconMarkup) {  
							Icons.getIcon('actions-localize-claudeai', Icons.sizes.large).done(function(localizeClaudeAiIconMarkup) {	              
                            Localization.actions.translate.prepend(localizeIconMarkup);
                            Localization.actions.copy.prepend(copyIconMarkup);
                            Localization.actions.deepltranslate.prepend(localizeDeeplIconMarkup);
                            Localization.actions.deepltranslateAuto.prepend(localizeDeeplIconMarkup);
                            Localization.actions.googletranslate.prepend(localizeGoogleIconMarkup);
                            Localization.actions.googletranslateAuto.prepend(localizeGoogleIconMarkup);
                            Localization.actions.openaitranslate.prepend(localizeOpenAiIconMarkup);
                            Localization.actions.geminiaitranslate.prepend(localizeGeminiAiIconMarkup);
							Localization.actions.claudeaitranslate.prepend(localizeClaudeAiIconMarkup);
                            $(Localization.identifier.triggerButton).removeClass('disabled');
							});
	                        });
                         });
                    });
              });
            });
        });
    });
});
  
    $(document).on('click', Localization.identifier.triggerButton, function() {
      var $triggerButton = $(this),
          actions = [],
          slideStep1 = '';
		  $.ajax({url: TYPO3.settings.ajaxUrls['records_settingsenabled'], success: function(result){
		  var deeplTranslate = (TYPO3.lang['localize.educate.deepltranslate']) ?  TYPO3.lang['localize.educate.deepltranslate'] : Localization.labels.deeplTranslate;
		  var deeplTranslateAuto = (TYPO3.lang['localize.educate.deepltranslateAuto']) ?  TYPO3.lang['localize.educate.deepltranslateAuto'] : Localization.labels.deeplTranslateAuto;	
		  var googleTranslate = (TYPO3.lang['localize.educate.deepltranslate']) ?  TYPO3.lang['localize.educate.googleTranslate'] : Localization.labels.googleTranslate;
		  var googleTranslateAuto = (TYPO3.lang['localize.educate.deepltranslate']) ?  TYPO3.lang['localize.educate.googleTranslateAuto'] : Localization.labels.googleTranslateAuto;	
		  var openAi = (TYPO3.lang['localize.educate.deepltranslate']) ?  TYPO3.lang['localize.educate.openai'] : Localization.labels.openAi;	
		  var geminiAi = (TYPO3.lang['localize.educate.deepltranslate']) ?  TYPO3.lang['localize.educate.geminiai'] : Localization.labels.geminiAi;	
		  var claudeAi = (TYPO3.lang['localize.educate.claudeai']) ?  TYPO3.lang['localize.educate.claudeai'] : Localization.labels.claudeAi;		  
		  var aiSetting = JSON.parse(result);
		  if ($triggerButton.data('allowTranslate')) {
			  actions.push(
				  '<div class="row">'
					  + '<div class="btn-group col-sm-3">' + Localization.actions.translate[0].outerHTML + '</div>'
					  + '<div class="col-sm-9">'
						  + '<p class="t3js-helptext t3js-helptext-translate text-muted">' + TYPO3.lang['localize.educate.translate'] + '</p>'
					  + '</div>'
				  + '</div>'
			  );
			  if(aiSetting.enableDeepl==1) 
				  {
				  actions.push(
					  '<div class="row" id="deeplTranslateAuto">'
						  + '<div class="btn-group col-sm-3">' + Localization.actions.deepltranslateAuto[0].outerHTML + '</div>'
						  + '<div class="col-sm-9" id="deeplTextAuto">'
							  + '<p class="t3js-helptext t3js-helptext-translate text-muted">' + deeplTranslateAuto+ '</p>'
						  + '</div>'
					  + '</div>'
				  );
				  actions.push(
					  '<div class="row" id="deeplTranslate">'
						  + '<div class="btn-group col-sm-3">' + Localization.actions.deepltranslate[0].outerHTML + '</div>'
						  + '<div class="col-sm-9" id="deeplText">'
							  + '<p class="t3js-helptext t3js-helptext-translate text-muted">' + deeplTranslate + '</p>'
						  + '</div>'
					  + '</div>'
				  );				  
				  }

			  if(aiSetting.enableGoogleTranslator==1) 
				  {
				  actions.push(
					  '<div class="row" id="googleTranslate">'
						  + '<div class="btn-group col-sm-3">' + Localization.actions.googletranslate[0].outerHTML + '</div>'
						  + '<div class="col-sm-9" id="googleText">'
							  + '<p class="t3js-helptext t3js-helptext-translate text-muted">' + googleTranslate + '</p>'
						  + '</div>'
					  + '</div>'
				  );
				  actions.push(
					  '<div class="row" id="googleTranslateAuto">'
						  + '<div class="btn-group col-sm-3">' + Localization.actions.googletranslateAuto[0].outerHTML + '</div>'
						  + '<div class="col-sm-9" id="googleTextAuto">'
							  + '<p class="t3js-helptext t3js-helptext-translate text-muted">' + googleTranslateAuto + '</p>'
						  + '</div>'
					  + '</div>'
				  );				  
				  }
			  if(aiSetting.enableOpenAi==1) 
				  {
				  actions.push(
					  '<div class="row" id="openaiTranslate">'
						  + '<div class="btn-group col-sm-3">' + Localization.actions.openaitranslate[0].outerHTML + '</div>'
						  + '<div class="col-sm-9" id="openaiText">'
							  + '<p class="t3js-helptext t3js-helptext-translate text-muted">' + openAi + '</p>'
						  + '</div>'
					  + '</div>'
				  ); 				  
				  }   
			  if(aiSetting.enableGemini==1) 
				  {
				  actions.push(
					'<div class="row" id="geminiaiTranslate">'
						+ '<div class="btn-group col-sm-3">' + Localization.actions.geminiaitranslate[0].outerHTML + '</div>'
						+ '<div class="col-sm-9" id="geminiaiText">'
							+ '<p class="t3js-helptext t3js-helptext-translate text-muted">' + geminiAi + '</p>'
						+ '</div>'
					+ '</div>'
					);				  
				  }     
			  if(aiSetting.enableClaude==1) 
				  {  
				  actions.push(
					'<div class="row" id="claudeaiTranslate">'
						+ '<div class="btn-group col-sm-3">' + Localization.actions.claudeaitranslate[0].outerHTML + '</div>'
						+ '<div class="col-sm-9" id="claudeaiText">'
							+ '<p class="t3js-helptext t3js-helptext-translate text-muted">' + claudeAi + '</p>'
						+ '</div>'
					+ '</div>'
					);	
				  }					      
		  }
	  
		  if ($triggerButton.data('allowCopy')) {
			  actions.push(
				  '<div class="row">'
					  + '<div class="col-sm-3 btn-group">' + Localization.actions.copy[0].outerHTML + '</div>'
					  + '<div class="col-sm-9">'
						  + '<p class="t3js-helptext t3js-helptext-copy text-muted">' + TYPO3.lang['localize.educate.copy'] + '</p>'
					  + '</div>'
				  + '</div>'
			  );
		  }
	  
		  slideStep1 += '<div data-toggle="buttons">' + actions.join('<hr>') + '</div>';
	  
		  Wizard.addSlide('localize-choose-action', TYPO3.lang['localize.wizard.header'].replace('{0}', $triggerButton.data('page')).replace('{1}', $triggerButton.data('languageName')), slideStep1, Severity.info);
		  Wizard.addSlide('localize-choose-language', TYPO3.lang['localize.view.chooseLanguage'], '', Severity.info, function($slide) {
			  Icons.getIcon('spinner-circle-dark', Icons.sizes.large).done(function(markup) {
				  $slide.html(
					  $('<div />', {class: 'text-center'}).append(markup)
				  );
				  Localization.loadAvailableLanguages(
					  $triggerButton.data('pageId'),
					  $triggerButton.data('languageId'),
					  Localization.settings.mode
				  ).done(function(result) {
					  if (result.length === 1) {
						  // We only have one result, auto select the record and continue
						  Localization.settings.language = result[0].uid + ''; // we need a string
						  Wizard.unlockNextStep().trigger('click');
						  return;
					  }
	  
					  var $languageButtons = $('<div />', {class: 'row', 'data-toggle': 'buttons'});
					  
					  $.each(result, function(_, languageObject) {
						  $languageButtons.append(
							  $('<div />', {class: 'col-sm-4',style: 'margin-top: 6px;'}).append(
								  $('<label />', {class: 'btn btn-default btn-block t3js-option option'}).text(' ' + languageObject.title).prepend(
									  languageObject.flagIcon
								  ).prepend(
									  $('<input />', {
										  type: 'radio',
										  name: 'language',
										  id: 'language' + languageObject.uid,
										  value: languageObject.uid,
										  style: 'display: none;'
									  })
								  )
							  )
						  );
					  });
					  $slide.html($languageButtons);
				  });
			  });
		  });
		  Wizard.addSlide('localize-summary', TYPO3.lang['localize.view.summary'], '', Severity.info, function($slide) {
			  Icons.getIcon('spinner-circle-dark', Icons.sizes.large).done(function(markup) {
				  $slide.html(
					  $('<div />', {class: 'text-center'}).append(markup)
				  );
				  Localization.getSummary(
					  $triggerButton.data('pageId'),
					  $triggerButton.data('languageId')
				  ).done(function(result) {
					  //
					$slide.empty();
					Localization.records = [];
	  
					var columns = result.columns.columns;
					var columnList = result.columns.columnList;
	  
					columnList.forEach(function(colPos) {
					  if (typeof result.records[colPos] === 'undefined') {
						return;
					  }
	  
					  var column = columns[colPos];
					  var $row = $('<div />', {class: 'row'});
	  
					  result.records[colPos].forEach(function(record) {
						var label = ' (' + record.uid + ') ' + record.title;
						Localization.records.push(record.uid);
	  
						$row.append(
						  $('<div />', {'class': 'col-sm-6'}).append(
							$('<div />', {'class': 'input-group'}).append(
							  $('<span />', {'class': 'input-group-addon'}).append(
								$('<input />', {
								  type: 'checkbox',
								  'class': 't3js-localization-toggle-record',
								  id: 'record-uid-' + record.uid,
								  checked: 'checked',
								  'data-uid': record.uid,
								  'aria-label': label
								})
							  ),
							  $('<label />', {
								'class': 'form-control',
								for: 'record-uid-' + record.uid
							  }).text(label).prepend(record.icon)
							)
						  )
						);
					  });
	  
					  $slide.append(
						$('<fieldset />', {
						  'class': 'localization-fieldset'
						}).append(
						  $('<label />').text(column).prepend(
							$('<input />', {
							  'class': 't3js-localization-toggle-column',
							  type: 'checkbox',
							  checked: 'checked'
							})
						  ),
						  $row
						)
					  );
					});
					Wizard.unlockNextStep();
	  
					Wizard.getComponent().on('change', '.t3js-localization-toggle-record', function() {
					  var $me = $(this),
						uid = $me.data('uid'),
						$parent = $me.closest('fieldset'),
						$columnCheckbox = $parent.find('.t3js-localization-toggle-column');
	  
					  if ($me.is(':checked')) {
						Localization.records.push(uid);
					  } else {
						var index = Localization.records.indexOf(uid);
						if (index > -1) {
						  Localization.records.splice(index, 1);
						}
					  }
	  
					  var $allChildren = $parent.find('.t3js-localization-toggle-record');
					  var $checkedChildren = $parent.find('.t3js-localization-toggle-record:checked');
	  
					  $columnCheckbox.prop('checked', $checkedChildren.length > 0);
					  $columnCheckbox.prop('indeterminate', $checkedChildren.length > 0 && $checkedChildren.length < $allChildren.length);
	  
					  if (Localization.records.length > 0) {
						Wizard.unlockNextStep();
					  } else {
						Wizard.lockNextStep();
					  }
					}).on('change', '.t3js-localization-toggle-column', function() {
					  var $me = $(this),
						$children = $me.closest('fieldset').find('.t3js-localization-toggle-record');
	  
					  $children.prop('checked', $me.is(':checked'));
					  $children.trigger('change');
					});
					  //
				  });
			  });
		  });
		  Wizard.addFinalProcessingSlide(function() {
			
			  Localization.localizeRecords(
				  $triggerButton.data('pageId'),
				  $triggerButton.data('languageId'),
				  Localization.records
			  ).done(function(result) {
				  if (result.length > 0) {
					 var response = JSON.parse(result);
					 var divFinalSlide  = $("div[data-slide='final-processing-slide']", window.parent.document);
					 divFinalSlide.append("<div class='alert alert-danger' id='alertClose'>  <a href='#'' class='close'  data-dismiss='alert' aria-label='close'>&times;</a>"+ response.message +"</div>");
					 $(divFinalSlide).fadeTo(4500, 500).slideUp(500, function(){
						  Wizard.dismiss();
						  document.location.reload();
					  });
				  }
				  else{
					Wizard.dismiss();
					document.location.reload();
				  }
			  });
		  }).done(function() {
			  Wizard.show();
	  
			  Wizard.getComponent().on('click', '.t3js-option', function(e) {
				  var $me = $(this),
					  $radio = $me.find('input[type="radio"]');
	  
				  if ($me.data('helptext')) {
					  var $container = $(e.delegateTarget);
					  $container.find('.t3js-helptext').addClass('text-muted');
					  $container.find($me.data('helptext')).removeClass('text-muted');
				  }

				  if (typeof $container === "undefined") {
					$radio.closest('.row').find('.t3js-option').removeClass('active')
					$(this).addClass('active');
				  }  else {
					$container.find('.t3js-option').removeClass('active');
					$(this).addClass('active');
				  }

				  if ($radio.length > 0) {
					  if($radio.val()=='localizedeepl' || $radio.val()=='localizedeeplauto'){
						 //checkdeepl settings
						   Localization.deeplSettings(
								  $triggerButton.data('pageId'),
								  $triggerButton.data('languageId'),
								  Localization.records
							  ).done(function(result) {
								  var responseDeepl = JSON.parse(result);
								  if(responseDeepl.status=="false"){
									
									if($radio.val()=='localizedeepl'){
									  var divDeepl  = $('#deeplText', window.parent.document);
									}
									else{
									  var divDeepl  = $('#deeplTextAuto', window.parent.document);
									}
									if(divDeepl.find('.alert-danger').length == 0) 
										{  
										var errorMsg = (responseDeepl.message!='') ? responseDeepl.message : Localization.labels.deeplSettingsFailure; 	
										divDeepl.prepend("<div class='alert alert-danger' id='alertClose'>  <a href='#'' class='close'  data-dismiss='alert' aria-label='close'>&times;</a>"+ errorMsg +"</div>");
										var deeplText = $('#alertClose', window.parent.document);
										 $(deeplText).fadeTo(1600, 500).slideUp(500, function(){
											  $(deeplText).alert('close');
										  });		
									    }	
									 Wizard.lockNextStep();
								  }
						   });
					  }
					  if($radio.val()=='localizegoogle' || $radio.val()=='localizegoogleauto'){
						 //checkgoogle settings
						   Localization.googleSettings(
								  $triggerButton.data('pageId'),
								  $triggerButton.data('languageId'),
								  Localization.records
							  ).done(function(result) {
								  var responseGoogle = JSON.parse(result);
								  if(responseGoogle.status=="false"){
									
									if($radio.val()=='localizegoogle'){
									  var divGoogle  = $('#googleText', window.parent.document);
									}
									else{
									  var divGoogle  = $('#googleTextAuto', window.parent.document);
									}
									if(divGoogle.find('.alert-danger').length == 0) 
									{  
									var errorMsg = (responseGoogle.message!='') ? responseGoogle.message : Localization.labels.googleSettingsFailure; 		
									divGoogle.prepend("<div class='alert alert-danger' id='alertClose'>  <a href='#'' class='close'  data-dismiss='alert' aria-label='close'>&times;</a>"+ errorMsg +"</div>");
									var googlelText = $('#alertClose', window.parent.document);
									$(googlelText).fadeTo(1600, 500).slideUp(500, function(){
											$(googlelText).alert('close');
											})
									}
					  
									 Wizard.lockNextStep();
								  }
						   });
					  }
					  if($radio.val()=='localizeopenai'){
						 //checkopenai settings
						   Localization.openaiSettings(
								  $triggerButton.data('pageId'),
								  $triggerButton.data('languageId'),
								  Localization.records
							  ).done(function(result) {
								  var responseOpenai = JSON.parse(result);
								  if(responseOpenai.status=="false"){
									var divOpenai  = $('#openaiText', window.parent.document);
									if(divOpenai.find('.alert-danger').length == 0) { 
										var errorMsg = (responseOpenai.message!='') ? responseOpenai.message : Localization.labels.openaiSettingsFailure; 	 
										divOpenai.prepend("<div class='alert alert-danger' id='alertClose'>  <a href='#'' class='close'  data-dismiss='alert' aria-label='close'>&times;</a>"+ errorMsg +"</div>");
										var openaiText = $('#alertClose', window.parent.document);
										$(openaiText).fadeTo(1600, 500).slideUp(500, function()
											{
											$(openaiText).alert('close');
											});
									 }
									 Wizard.lockNextStep();
								  }
						   });
					  }
					  if($radio.val()=='localizegeminiai'){
						   //checkgemin ai settings
						   Localization.geminiSettings(
								  $triggerButton.data('pageId'),
								  $triggerButton.data('languageId'),
								  Localization.records
							  ).done(function(result) {
								  var responseGeminiai = JSON.parse(result);
								  if(responseGeminiai.status=="false"){
									var divGemini  = $('#geminiaiText', window.parent.document);
									if(divGemini.find('.alert-danger').length == 0) 
										{ 
										var errorMsg = (responseGeminiai.message!='') ? responseGeminiai.message : Localization.labels.geminiSettingsFailure; 	 	
										divGemini.prepend("<div class='alert alert-danger' id='alertClose'>  <a href='#'' class='close'  data-dismiss='alert' aria-label='close'>&times;</a>"+ errorMsg +"</div>");
										var geminiaiText = $('#alertClose', window.parent.document);
										$(geminiaiText).fadeTo(1600, 500).slideUp(500, function(){
											  $(geminiaiText).alert('close');
										  });									
										}								
									 Wizard.lockNextStep();
								  }
						   });
					  }	
					  if($radio.val()=='localizeclaudeai'){
						//check claudeai settings
						Localization.claudeSettings(
							   $triggerButton.data('pageId'),
							   $triggerButton.data('languageId'),
							   Localization.records
						   ).done(function(result) {
							   var responseClaudeai = JSON.parse(result);
							   if(responseClaudeai.status=="false"){
								 var divClaude  = $('#claudeaiText', window.parent.document);
								 if(divClaude.find('.alert-danger').length == 0) 
									 { 
									 var errorMsg = (responseClaudeai.message!='') ? responseClaudeai.message : Localization.labels.claudeSettingsFailure; 	 	
									 divClaude.prepend("<div class='alert alert-danger' id='alertClose'>  <a href='#'' class='close'  data-dismiss='alert' aria-label='close'>&times;</a>"+ errorMsg +"</div>");
									 var claudeaiText = $('#alertClose', window.parent.document);
									 $(claudeaiText).fadeTo(1600, 500).slideUp(500, function(){
										   $(claudeaiText).alert('close');
									   });									
									 }								
								  Wizard.lockNextStep();
							   }
						});
				   }						  					  
					  Localization.settings[$radio.attr('name')] = $radio.val();
				  }
				  Wizard.unlockNextStep();
			  });
		  });			
	 
	}});	  
    });
  
    /**
     * deeplSettings
     * @param {Integer} pageId
     * @param {Integer} languageId
     * @param {Array} uidList
     * @return {Promise}
     */
    Localization.deeplSettings = function(pageId, languageId, uidList) {
        return $.ajax({
            url: TYPO3.settings.ajaxUrls['records_localizedeepl'],
            data: {
                pageId: pageId,
                srcLanguageId: Localization.settings.language,
                destLanguageId: languageId,
                action: Localization.settings.mode,
                uidList: uidList
            }
        });
    };
	
    /**
     * googleSettings
     * @param {Integer} pageId
     * @param {Integer} languageId
     * @param {Array} uidList
     * @return {Promise}
     */
    Localization.googleSettings = function(pageId, languageId, uidList) {
        return $.ajax({
            url: TYPO3.settings.ajaxUrls['records_localizegoogle'],
            data: {
                pageId: pageId,
                srcLanguageId: Localization.settings.language,
                destLanguageId: languageId,
                action: Localization.settings.mode,
                uidList: uidList
            }
        });
    };	
	
    /**
     * openaiSettings
     * @param {Integer} pageId
     * @param {Integer} languageId
     * @param {Array} uidList
     * @return {Promise}
     */
    Localization.openaiSettings = function(pageId, languageId, uidList) {
        return $.ajax({
            url: TYPO3.settings.ajaxUrls['records_localizeopenai'],
            data: {
                pageId: pageId,
                srcLanguageId: Localization.settings.language,
                destLanguageId: languageId,
                action: Localization.settings.mode,
                uidList: uidList
            }
        });
    };	
	
    /**
     * geminiSettings
     * @param {Integer} pageId
     * @param {Integer} languageId
     * @param {Array} uidList
     * @return {Promise}
     */
    Localization.geminiSettings = function(pageId, languageId, uidList) {
        return $.ajax({
            url: TYPO3.settings.ajaxUrls['records_localizegemini'],
            data: {
                pageId: pageId,
                srcLanguageId: Localization.settings.language,
                destLanguageId: languageId,
                action: Localization.settings.mode,
                uidList: uidList
            }
        });
    };		

    /**
     * claudeSettings
     * @param {Integer} pageId
     * @param {Integer} languageId
     * @param {Array} uidList
     * @return {Promise}
     */
    Localization.claudeSettings = function(pageId, languageId, uidList) {
        return $.ajax({
            url: TYPO3.settings.ajaxUrls['records_localizeclaude'],
            data: {
                pageId: pageId,
                srcLanguageId: Localization.settings.language,
                destLanguageId: languageId,
                action: Localization.settings.mode,
                uidList: uidList
            }
        });
    };	
  
    /**
     * Load available languages from page and colPos
     *
     * @param {Integer} pageId
     * @param {Integer} colPos
     * @param {Integer} languageId
     * @return {Promise}
     */
    Localization.loadAvailableLanguages = function(pageId, languageId, mode) {
        return $.ajax({
            url: TYPO3.settings.ajaxUrls['page_languages'],
            data: {
                pageId: pageId,
                languageId: languageId,
                mode: mode
            }
        });
    };
  
    /**
     * Get summary for record processing
     *
     * @param {Integer} pageId
     * @param {Integer} colPos
     * @param {Integer} languageId
     * @return {Promise}
     */
    Localization.getSummary = function(pageId, languageId) {
        return $.ajax({
            url: TYPO3.settings.ajaxUrls['records_localize_summary'],
            data: {
                pageId: pageId,
                destLanguageId: languageId,
                languageId: Localization.settings.language
            }
        });
    };
  
    /**
     * Localize records
     *
     * @param {Integer} pageId
     * @param {Integer} languageId
     * @param {Array} uidList
     * @return {Promise}
     */
    Localization.localizeRecords = function(pageId, languageId, uidList) {
        return $.ajax({
            url: TYPO3.settings.ajaxUrls['records_localize'],
            data: {
                pageId: pageId,
                srcLanguageId: Localization.settings.language,
                destLanguageId: languageId,
                action: Localization.settings.mode,
                uidList: uidList
            }
        });
    };
  
  };
  
  $(Localization.initialize);
  
  return Localization;
  });
  