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
import DocumentService from "@typo3/core/document-service.js";
import $ from "jquery";
import {
    SeverityEnum
} from "@typo3/backend/enum/severity.js";
import AjaxRequest from "@typo3/core/ajax/ajax-request.js";
import Icons from "@typo3/backend/icons.js";
import Wizard from "@typo3/backend/wizard.js";
import "@typo3/backend/element/icon-element.js";
class Localization {
    constructor() {
        this.triggerButton = ".t3js-localize", this.localizationMode = null, this.sourceLanguage = null, this.records = [], DocumentService.ready().then((() => {
            this.initialize()
        }))
    }
    initialize() {
        const e = this;
        Icons.getIcon("actions-localize", Icons.sizes.large).then((a => {
            Icons.getIcon("actions-edit-copy", Icons.sizes.large).then((t => {
            Icons.getIcon("actions-localize-deepl", Icons.sizes.large).then((d => {
            Icons.getIcon("actions-localize-google", Icons.sizes.large).then((g => {
            Icons.getIcon("actions-localize-openai", Icons.sizes.large).then((h => {
            Icons.getIcon("actions-localize-geminiai", Icons.sizes.large).then((j => {
            Icons.getIcon("actions-localize-claudeai", Icons.sizes.large).then((cl => {
            Icons.getIcon("actions-localize-cohereai", Icons.sizes.large).then((ch => {    
                $(e.triggerButton).removeClass("disabled"), $(document).on("click", e.triggerButton, (e => {
                    /*Eanble or disable custom icons based on extension settings */
                    var deeplCodeBlock = '';
                    var deeplAutoCodeBlock = '';
                    var googleCodeBlock = '';
                    var googleAutoCodeBlock = '';
                    var openaiCodeBlock = '';
                    var geminiaiCodeBlock = '';
                    var claudeaiCodeBlock = '';
                    var cohereaiCodeBlock = '';
                    this.aiSettingsEanbled().then((async aiSettingsResult => {
                        const aiSettings = await aiSettingsResult.resolve();
                        
                        if(aiSettings['enableDeepl'] == 1) {
                            deeplCodeBlock = '<div class="row" id="deeplTranslate"><div class="col-sm-3"><label class="btn btn-default d-block t3js-localization-option" data-helptext=".t3js-helptext-deepltranslate">' + d + '<input type="radio" name="mode" id="mode_deepltranslate" value="localizedeepl" style="display: none"><br><br>' + TYPO3.lang["localize.wizard.button.deepl"]+ '</label></div><div class="col-sm-9" id="deeplText"><p class="t3js-helptext t3js-helptext-translate text-body-secondary">' + TYPO3.lang["localize.educate.deepltranslate"] + "</p></div></div>";
                            deeplAutoCodeBlock = '<div class="row" id="deeplTranslateAuto"><div class="col-sm-3"><label class="btn btn-default d-block t3js-localization-option" data-helptext=".t3js-helptext-deepltranslateauto">' + d + '<input type="radio" name="mode" id="mode_deepltranslateauto" value="localizedeeplauto" style="display: none"><br><br>' + TYPO3.lang["localize.wizard.button.deeplauto"]+ '</label></div><div class="col-sm-9" id="deeplTextAuto"><p class="t3js-helptext t3js-helptext-translate text-body-secondary">' + TYPO3.lang["localize.educate.deepltranslateAuto"] + "</p></div></div>";
                        }
                        if(aiSettings['enableGoogleTranslator'] == 1) {
                            googleCodeBlock = '<div class="row" id="googleTranslate"><div class="col-sm-3"><label class="btn btn-default d-block t3js-localization-option" data-helptext=".t3js-helptext-googletranslate">' + g + '<input type="radio" name="mode" id="mode_googletranslate" value="localizegoogle" style="display: none"><br><br>' + TYPO3.lang["localize.wizard.button.google"]+ '</label><br></div><div class="col-sm-9" id="googleText"><p class="t3js-helptext t3js-helptext-translate text-body-secondary">' + TYPO3.lang["localize.educate.googleTranslate"] + "</p></div></div>";
                            googleAutoCodeBlock = '<div class="row" id="googleTranslateAuto"><div class="col-sm-3"><label class="btn btn-default d-block t3js-localization-option" data-helptext=".t3js-helptext-googletranslateauto">' + g + '<input type="radio" name="mode" id="mode_googletranslateauto" value="localizegoogleauto" style="display: none"><br><br>' + TYPO3.lang["localize.wizard.button.googleauto"]+ '</label><br></div><div class="col-sm-9" id="googleTextAuto"><p class="t3js-helptext t3js-helptext-translate text-body-secondary">' + TYPO3.lang["localize.educate.googleTranslateAuto"] + "</p></div></div>";
                        }
                        if(aiSettings['enableOpenAi'] == 1) {
                            openaiCodeBlock = '<div class="row" id="openaiTranslate"><div class="col-sm-3"><label class="btn btn-default d-block t3js-localization-option" data-helptext=".t3js-helptext-openaitranslate">' + h + '<input type="radio" name="mode" id="mode_openaitranslate" value="localizeopenai" style="display: none"><br><br>' + TYPO3.lang["localize.wizard.button.openai"]+ '</label><br></div><div class="col-sm-9" id="openaiText"><p class="t3js-helptext t3js-helptext-translate text-body-secondary">' + TYPO3.lang["localize.educate.openai"] + "</p></div></div>";
                        }
                        if(aiSettings['enableGemini'] == 1) {
                            geminiaiCodeBlock = '<div class="row" id="geminiaiTranslate"><div class="col-sm-3"><label class="btn btn-default d-block t3js-localization-option" data-helptext=".t3js-helptext-geminiaitranslate">' + j + '<input type="radio" name="mode" id="mode_opengeminitranslate" value="localizegeminiai" style="display: none"><br><br>' + TYPO3.lang["localize.wizard.button.geminiai"]+ '</label><br></div><div class="col-sm-9" id="geminiaiText"><p class="t3js-helptext t3js-helptext-translate text-body-secondary">' + TYPO3.lang["localize.educate.geminiai"] + "</p></div></div>";
                        }
                        if(aiSettings['enableClaude'] == 1) {
                            claudeaiCodeBlock = '<div class="row" id="claudeaiTranslate"><div class="col-sm-3"><label class="btn btn-default d-block t3js-localization-option" data-helptext=".t3js-helptext-claudeaitranslate">' + cl + '<input type="radio" name="mode" id="mode_openclaudetranslate" value="localizeclaudeai" style="display: none"><br><br>' + TYPO3.lang["localize.wizard.button.claudeai"]+ '</label><br></div><div class="col-sm-9" id="claudeaiText"><p class="t3js-helptext t3js-helptext-translate text-body-secondary">' + TYPO3.lang["localize.educate.claudeai"] + "</p></div></div>";
                        }
                        if(aiSettings['enableCohere'] == 1) {
                            cohereaiCodeBlock = '<div class="row" id="cohereaiTranslate"><div class="col-sm-3"><label class="btn btn-default d-block t3js-localization-option" data-helptext=".t3js-helptext-cohereaitranslate">' + ch + '<input type="radio" name="mode" id="mode_opencoheretranslate" value="localizecohereai" style="display: none"><br><br>' + TYPO3.lang["localize.wizard.button.cohereai"]+ '</label><br></div><div class="col-sm-9" id="cohereaiText"><p class="t3js-helptext t3js-helptext-translate text-body-secondary">' + TYPO3.lang["localize.educate.cohereai"] + "</p></div></div>";
                        }        
                    e.preventDefault();
                    const o = $(e.currentTarget),
                        i = [],
                        l = [];
                    let s = "";
                    o.data("allowTranslate") && (i.push('<div class="row"><div class="col-sm-3"><label class="btn btn-default d-block t3js-localization-option" data-helptext=".t3js-helptext-translate">' + a + '<input type="radio" name="mode" id="mode_translate" value="localize" style="display: none"><br>' + TYPO3.lang["localize.wizard.button.translate"] + '</label></div><div class="col-sm-9"><p class="t3js-helptext t3js-helptext-translate text-body-secondary">' + TYPO3.lang["localize.educate.translate"] + "</p></div></div>" 
                    + deeplCodeBlock
                    + deeplAutoCodeBlock
                    + googleCodeBlock
                    + googleAutoCodeBlock
                    + openaiCodeBlock
                    + geminiaiCodeBlock
                    + claudeaiCodeBlock
                    + cohereaiCodeBlock
                    ), 
                    l.push("localize")), o.data("allowCopy") && (i.push('<div class="row"><div class="col-sm-3"><label class="btn btn-default d-block t3js-localization-option" data-helptext=".t3js-helptext-copy">' + t + '<input type="radio" name="mode" id="mode_copy" value="copyFromLanguage" style="display: none"><br>' + TYPO3.lang["localize.wizard.button.copy"] + '</label></div><div class="col-sm-9"><p class="t3js-helptext t3js-helptext-copy text-body-secondary">' + TYPO3.lang["localize.educate.copy"] + "</p></div></div>"), 
                    l.push("copyFromLanguage")), 0 === o.data("allowTranslate") && 0 === o.data("allowCopy") && i.push('<div class="row"><div class="col-sm-12"><div class="alert alert-warning"><div class="media"><div class="media-left"><span class="icon-emphasized"><typo3-backend-icon identifier="actions-exclamation" size="small"></typo3-backend-icon></span></div><div class="media-body"><p class="alert-message">' + TYPO3.lang["localize.educate.noTranslate"] + "</p></div></div></div></div></div>"), s += '<div data-bs-toggle="buttons">' + i.join("") + "</div>", Wizard.addSlide("localize-choose-action", TYPO3.lang["localize.wizard.header_page"].replace("{0}", o.data("page")).replace("{1}", o.data("languageName")), s, SeverityEnum.info, (() => {
                        1 === l.length && (this.localizationMode = l[0])
                    })), Wizard.addSlide("localize-choose-language", TYPO3.lang["localize.view.chooseLanguage"], "", SeverityEnum.info, (e => {
                        Icons.getIcon("spinner-circle-dark", Icons.sizes.large).then((a => {
                            e.html('<div class="text-center">' + a + "</div>"), this.loadAvailableLanguages(parseInt(o.data("pageId"), 10), parseInt(o.data("languageId"), 10), this.localizationMode).then((async a => {
                                const t = await a.resolve();
                                if (1 === t.length) return this.sourceLanguage = t[0].uid, void Wizard.unlockNextStep().trigger("click");
                                Wizard.getComponent().on("click", ".t3js-language-option", (e => {
                                    const a = $(e.currentTarget).prev();
                                    this.sourceLanguage = a.val(), Wizard.unlockNextStep()
                                }));
                                const o = $("<div />", {
                                    class: "row"
                                });
                                for (const e of t) {
                                    const a = "language" + e.uid,
                                        t = $("<input />", {
                                            type: "radio",
                                            name: "language",
                                            id: a,
                                            value: e.uid,
                                            style: "display: none;",
                                            class: "btn-check"
                                        }),
                                        i = $("<label />", {
                                            class: "btn btn-default d-block t3js-language-option option",
                                            for: a
                                        }).text(" " + e.title).prepend(e.flagIcon);
                                    o.append($("<div />", {
                                        class: "col-sm-4"
                                    }).append(t).append(i))
                                }
                                e.empty().append(o)
                            }))
                        }))
                    })), Wizard.addSlide("localize-summary", TYPO3.lang["localize.view.summary"], "", SeverityEnum.info, (e => {
                        Icons.getIcon("spinner-circle-dark", Icons.sizes.large).then((a => {
                            e.html('<div class="text-center">' + a + "</div>")
                        })), this.getSummary(parseInt(o.data("pageId"), 10), parseInt(o.data("languageId"), 10)).then((async a => {
                            const t = await a.resolve();
                            e.empty(), this.records = [];
                            const o = t.columns.columns;
                            t.columns.columnList.forEach((a => {
                                if (void 0 === t.records[a]) return;
                                const i = o[a],
                                    l = $("<div />", {
                                        class: "row"
                                    });
                                t.records[a].forEach((e => {
                                    const a = " (" + e.uid + ") " + e.title;
                                    this.records.push(e.uid), l.append($("<div />", {
                                        class: "col-sm-6"
                                    }).append($("<div />", {
                                        class: "input-group"
                                    }).append($("<span />", {
                                        class: "input-group-addon"
                                    }).append($("<input />", {
                                        type: "checkbox",
                                        class: "t3js-localization-toggle-record",
                                        id: "record-uid-" + e.uid,
                                        checked: "checked",
                                        "data-uid": e.uid,
                                        "aria-label": a
                                    })), $("<label />", {
                                        class: "form-control",
                                        for: "record-uid-" + e.uid
                                    }).text(a).prepend(e.icon))))
                                })), e.append($("<fieldset />", {
                                    class: "localization-fieldset"
                                }).append($("<label />").text(i).prepend($("<input />", {
                                    class: "t3js-localization-toggle-column",
                                    type: "checkbox",
                                    checked: "checked"
                                })), l))
                            })), Wizard.unlockNextStep(), Wizard.getComponent().on("change", ".t3js-localization-toggle-record", (e => {
                                const a = $(e.currentTarget),
                                    t = a.data("uid"),
                                    o = a.closest("fieldset"),
                                    i = o.find(".t3js-localization-toggle-column");
                                if (a.is(":checked")) this.records.push(t);
                                else {
                                    const e = this.records.indexOf(t);
                                    e > -1 && this.records.splice(e, 1)
                                }
                                const l = o.find(".t3js-localization-toggle-record"),
                                    s = o.find(".t3js-localization-toggle-record:checked");
                                i.prop("checked", s.length > 0), i.prop("indeterminate", s.length > 0 && s.length < l.length), this.records.length > 0 ? Wizard.unlockNextStep() : Wizard.lockNextStep()
                            })).on("change", ".t3js-localization-toggle-column", (e => {
                                const a = $(e.currentTarget),
                                    t = a.closest("fieldset").find(".t3js-localization-toggle-record");
                                t.prop("checked", a.is(":checked")), t.trigger("change")
                            }))
                        }))
                    })), Wizard.addFinalProcessingSlide((() => {
                        this.localizeRecords(parseInt(o.data("pageId"), 10), parseInt(o.data("languageId"), 10), this.records).then((() => {
                            Wizard.dismiss(), document.location.reload()
                        }))
                    })).then((() => {
                        Wizard.show(), Wizard.getComponent().on("click", ".t3js-localization-option", (e => {
                            const a = $(e.currentTarget),
                                t = a.find('input[type="radio"]');                                
                            if (a.data("helptext")) {
                                const t = $(e.delegateTarget);
                                t.find(".t3js-localization-option").removeClass("active"), t.find(".t3js-helptext").addClass("text-body-secondary"), a.addClass("active"), t.find(a.data("helptext")).removeClass("text-body-secondary")
                            }
                           
                            if (t.length > 0) {
                                if(t.val()=='localizedeepl' || t.val()=='localizedeeplauto'){
                                   //checkdeepl settings
                                   this.deeplSettings().then((async a => {
                                            const responseDeepl = await a.resolve();
                                            if(responseDeepl['status']=== false){
                                              if(t.val()=='localizedeepl'){
                                                var divDeepl  = $('#deeplText', window.parent.document);
                                              }
                                              else{
                                                var divDeepl  = $('#deeplTextAuto', window.parent.document);
                                              }
                                              if(divDeepl.find('.alert-danger').length == 0){
                                                var errorMsg = (responseDeepl['message']!='') ? responseDeepl['message'] : TYPO3.lang["localization.labels.deeplSettingsFailure"]; 	
                                                divDeepl.prepend("<div class='alert alert-danger' id='alertClose'>  <a href='#'' class='close'  data-dismiss='alert' aria-label='close'>&times;</a>"+errorMsg +"</div>");
                                                var deeplText = $('#alertClose', window.parent.document);
                                                $(deeplText).fadeTo(1600, 500).slideUp(500, function(){
                                                        $(deeplText).alert('close');
                                                    });
                                                }
                                               Wizard.lockNextStep();
                                            }
                                        }))
                                }
                                //Localization.settings[$radio.attr('name')] = $radio.val();

                                if(t.val()=='localizegoogle' || t.val()=='localizegoogleauto'){
                                    //check google settings
                                    this.googleSettings().then((async a => {
                                             const responseGoogle = await a.resolve();
                                            
                                             if(responseGoogle['status']=== false){
                                               
                                               if(t.val()=='localizegoogle'){
                                                 var divGoogle  = $('#googleText', window.parent.document);
                                               }
                                               else{
                                                 var divGoogle  = $('#googleTextAuto', window.parent.document);
                                               }
                                               if(divGoogle.find('.alert-danger').length == 0){
                                                var errorMsg = (responseGoogle['message']!='') ? responseGoogle['message'] : TYPO3.lang["localization.labels.googleSettingsFailure"]; 	
                                                divGoogle.prepend("<div class='alert alert-danger' id='alertClose'>  <a href='#'' class='close'  data-dismiss='alert' aria-label='close'>&times;</a>"+errorMsg+"</div>");
                                                var googleText = $('#alertClose', window.parent.document);
                                                    $(googleText).fadeTo(1600, 500).slideUp(500, function(){
                                                        $(googleText).alert('close');
                                                    });
                                               } 
                                                Wizard.lockNextStep();
                                             }
                                         }))
                                 }

                                 if(t.val()=='localizeopenai'){
                                    //check openAI settings
                                    this.openaiSettings().then((async a => {
                                             const responseOpenai = await a.resolve();
                                            
                                             if(responseOpenai['status']=== false){
                                               
                                                var divOpenai  = $('#openaiText', window.parent.document);
                                                if(divOpenai.find('.alert-danger').length == 0){
                                                    var errorMsg = (responseOpenai['message']!='') ? responseOpenai['message'] : TYPO3.lang["localization.labels.openaiSettingsFailure"]; 	
                                                    divOpenai.prepend("<div class='alert alert-danger' id='alertClose'>  <a href='#'' class='close'  data-dismiss='alert' aria-label='close'>&times;</a>"+errorMsg+"</div>");
                                                    var openaiText = $('#alertClose', window.parent.document);
                                                    $(openaiText).fadeTo(1600, 500).slideUp(500, function(){
                                                        $(openaiText).alert('close');
                                                    });
                                                }
                                                Wizard.lockNextStep();
                                             }
                                         }))
                                 }

                                 if(t.val()=='localizegeminiai'){
                                    //check Gemini settings
                                    this.geminiSettings().then((async a => {
                                             const responseGemini = await a.resolve();
                                            
                                             if(responseGemini['status'] === false){
                                               
                                                var divGemini  = $('#geminiaiText', window.parent.document);
                                                if(divGemini.find('.alert-danger').length == 0){
                                                    var errorMsg = (responseGemini['message']!='') ? responseGemini['message'] : TYPO3.lang["localization.labels.geminiSettingsFailure"]; 	
                                                    divGemini.prepend("<div class='alert alert-danger' id='alertClose'>  <a href='#'' class='close'  data-dismiss='alert' aria-label='close'>&times;</a>"+errorMsg+"</div>");
                                                    var geminiaiText = $('#alertClose', window.parent.document);
                                                    $(geminiaiText).fadeTo(1600, 500).slideUp(500, function(){
                                                        $(geminiaiText).alert('close');
                                                    });
                                                }
                                                Wizard.lockNextStep();
                                             }
                                         }))
                                 }

                                 if(t.val()=='localizeclaudeai'){
                                    //check claudeai  settings
                                    this.claudeSettings().then((async a => {
                                             const responseClaudeai = await a.resolve();
                                            
                                             if(responseClaudeai['status'] === false){
                                               
                                                var divClaude  = $('#claudeaiText', window.parent.document);
                                                if(divClaude.find('.alert-danger').length == 0){
                                                    var errorMsg = (responseClaudeai['message']!='') ? responseClaudeai['message'] : TYPO3.lang["localization.labels.claudeSettingsFailure"]; 	
                                                    divClaude.prepend("<div class='alert alert-danger' id='alertClose'>  <a href='#'' class='close'  data-dismiss='alert' aria-label='close'>&times;</a>"+errorMsg+"</div>");
                                                    var claudeaiText = $('#alertClose', window.parent.document);
                                                    $(claudeaiText).fadeTo(1600, 500).slideUp(500, function(){
                                                        $(claudeaiText).alert('close');
                                                    });
                                                }
                                                Wizard.lockNextStep();
                                             }
                                         }))
                                 }
                                 if(t.val()=='localizecohereai'){
                                    //check cohereai  settings
                                    this.cohereSettings().then((async a => {
                                             const responseCohereai = await a.resolve();
                                            
                                             if(responseCohereai['status'] === false){
                                               
                                                var divCohere  = $('#cohereaiText', window.parent.document);
                                                if(divCohere.find('.alert-danger').length == 0){
                                                    var errorMsg = (responseCohereai['message']!='') ? responseCohereai['message'] : TYPO3.lang["localization.labels.cohereSettingsFailure"]; 	
                                                    divCohere.prepend("<div class='alert alert-danger' id='alertClose'>  <a href='#'' class='close'  data-dismiss='alert' aria-label='close'>&times;</a>"+errorMsg+"</div>");
                                                    var cohereaiText = $('#alertClose', window.parent.document);
                                                    $(cohereaiText).fadeTo(1600, 500).slideUp(500, function(){
                                                        $(cohereaiText).alert('close');
                                                    });
                                                }
                                                Wizard.lockNextStep();
                                             }
                                         }))
                                 }

                            }
                            
                            this.localizationMode = t.val(), Wizard.unlockNextStep()
                        }))
                    }))
                }))
            }))
            }))
            }))
            }))
        }))
        }))
        }))
            }))
        }))
    }
    loadAvailableLanguages(e, a, m) {
        return new AjaxRequest(TYPO3.settings.ajaxUrls.page_languages).withQueryArguments({
            pageId: e,
            languageId: a,
            mode: m
        }).get()
    }
    getSummary(e, a) {
        return new AjaxRequest(TYPO3.settings.ajaxUrls.records_localize_summary).withQueryArguments({
            pageId: e,
            destLanguageId: a,
            languageId: this.sourceLanguage
        }).get()
    }
    localizeRecords(e, a, t) {
        return new AjaxRequest(TYPO3.settings.ajaxUrls.records_localize).withQueryArguments({
            pageId: e,
            srcLanguageId: this.sourceLanguage,
            destLanguageId: a,
            action: this.localizationMode,
            uidList: t
        }).get()
    }

    deeplSettings(e, a) {
        return new AjaxRequest(TYPO3.settings.ajaxUrls.records_localizedeepl).withQueryArguments({
            pageId: e,           
            destLanguageId: a,           
        }).get()

    }

    googleSettings(e, a) {
        return new AjaxRequest(TYPO3.settings.ajaxUrls.records_localizegoogle).withQueryArguments({
            pageId: e,           
            destLanguageId: a,           
        }).get()

    }

    openaiSettings(e, a) {
        return new AjaxRequest(TYPO3.settings.ajaxUrls.records_localizeopenai).withQueryArguments({
            pageId: e,           
            destLanguageId: a,           
        }).get()

    }

    geminiSettings(e, a) {
        return new AjaxRequest(TYPO3.settings.ajaxUrls.records_localizegemini).withQueryArguments({
            pageId: e,           
            destLanguageId: a,           
        }).get()

    }

    claudeSettings(e, a) {
        return new AjaxRequest(TYPO3.settings.ajaxUrls.records_localizeclaude).withQueryArguments({
            pageId: e,           
            destLanguageId: a,           
        }).get()

    }
    cohereSettings(e, a) {
        return new AjaxRequest(TYPO3.settings.ajaxUrls.records_localizecohere).withQueryArguments({
            pageId: e,           
            destLanguageId: a,           
        }).get()

    }
   aiSettingsEanbled() {
    return  new AjaxRequest(TYPO3.settings.ajaxUrls.records_settingsenabled).withQueryArguments({      
        }).get()

                
    }  
        
}
export default new Localization;