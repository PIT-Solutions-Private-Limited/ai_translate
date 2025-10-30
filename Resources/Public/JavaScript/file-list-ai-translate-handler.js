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

import $ from "jquery";
import { SeverityEnum } from "@typo3/backend/enum/severity.js";
import MultiStepWizard from "@typo3/backend/multi-step-wizard.js";
import Icons from "@typo3/backend/icons.js";
import Viewport from "@typo3/backend/viewport.js";

class FileListAiTranslateHandler {
  constructor() {
    document
      .querySelectorAll('button[data-action="ai_translate"]')
      .forEach((button) => {
        const aiModels = button.dataset.aiModels.split(",");
        const translateUrls = JSON.parse(button.dataset.translateUrls);
        const languages = JSON.parse(button.dataset.languages);
        button.addEventListener("click", (event) => {
          event.preventDefault();
          let translateWizard = MultiStepWizard;
          // Add the initial step: Select AI model for the translation
          translateWizard.addSlide(
            "aiTranslateChooseModel",
            TYPO3.lang[
              "localization.labels.filelist.wizard.aiTranslateChooseModel.title"
            ],
            "",
            SeverityEnum.info,
            TYPO3.lang[
              "localization.labels.filelist.wizard.progressBar.aiTranslateChooseModel.title"
            ],
            (slide) => this.#aiTranslateChooseModel(slide, aiModels)
          );
          // Add the second and last step: Select the language for the translation
          translateWizard.addSlide(
            "aiTranslateChooseLanguage",
            TYPO3.lang[
              "localization.labels.filelist.wizard.aiTranslateChooseLanguage.title"
            ],
            "",
            SeverityEnum.info,
            TYPO3.lang[
              "localization.labels.filelist.wizard.progressBar.aiTranslateChooseLanguage.title"
            ],
            (slide) =>
              this.#aiTranslateChooseLanguage(slide, translateUrls, languages)
          );
          translateWizard.show();
        });
      });
  }

  /**
   *
   * @param {jQuery} slide
   * @param {Array} aiModels
   */
  #aiTranslateChooseModel(slide, aiModels) {
    MultiStepWizard.blurCancelStep();
    MultiStepWizard.lockNextStep();
    MultiStepWizard.lockPrevStep();

    const chunks = [];
    for (let i = 0; i < aiModels.length; i += 3) {
      chunks.push(aiModels.slice(i, i + 3));
    }
    /** 
     * Render the AI model/service buttons
     * It is done with promises to ensure that all icons are loaded before displaying the buttons
     */
    Promise.all(
      chunks.map(async (group) => {
        const buttons = await Promise.all(
          group.map((model) => this.#renderModelButton(model))
        );
        return `<div class="row form-group">${buttons.join("")}</div>`;
      })
    ).then((rows) => {
      // Concat the markup of the buttons
      slide.html(rows.join(""));
      // Add the click event to the buttons to save the selected model
      slide.on("click", "[data-model-select]", (event) => {
        event.preventDefault();
        MultiStepWizard.set(
          "translateModel",
          $(event.currentTarget).data("model")
        );
        // Go to next step
        MultiStepWizard.unlockNextStep().trigger("click");
      });
    });
  }

  /**
   * Renders the button for an AI model/service
   * @param {String} model
   * @returns {String}
   */
  async #renderModelButton(model) {
    const icon = await Icons.getIcon(
      `actions-localize-${model}`,
      Icons.sizes.large
    );
    return `
      <div class="col-sm-4" data-model="${model}" data-model-select="">
        <label class="btn btn-default d-block">
          ${icon}
          <br><br>
          ${TYPO3.lang[`localize.wizard.button.${model}`]}
        </label>
      </div>
    `;
  }

  /**
   *
   * @param {jQuery} slide
   * @param {Array} translateUrls
   * @param {Array<Array<Number, String, String>>} languages
   */
  #aiTranslateChooseLanguage(slide, translateUrls, languages) {
    MultiStepWizard.unlockPrevStep();
    const model = MultiStepWizard.setup.settings.translateModel;
    const chunks = [];
    for (let i = 0; i < languages.length; i += 3) {
      chunks.push(languages.slice(i, i + 3));
    }
    /**
     * Render language selection buttons
     * It is done with promises to ensure that all icons are loaded before displaying the buttons
     */
    Promise.all(
      chunks.map(async (group) => {
        const buttons = await Promise.all(
          group.map((language) =>
            this.#renderLanguageButton(
              model,
              language,
              translateUrls[model][language.uid]
            )
          )
        );
        return `<div class="row form-group">${buttons.join("")}</div>`;
      })
    ).then((rows) => {
      // Concat the markup of the buttons
      slide.html(rows.join(""));
      slide.on("click", "[data-language-select]", (event) => {
        event.preventDefault();
        // Set the URL to the navigation and close the wizard
        Viewport.ContentContainer.setUrl(event.currentTarget.href);
        MultiStepWizard.dismiss();
      });
    });
  }

  /**
   *
   * @param {String} model
   * @param {Array<Number, String, String>} language
   * @param {String} url
   * @returns {String}
   */
  async #renderLanguageButton(model, language, url) {
    const icon = await Icons.getIcon(language.flagIcon, Icons.sizes.large);
    return `
      <div class="col-sm-4">
        <a href="${url}" data-language-select="">
          <label class="btn btn-default d-block">
            ${icon}
            <br><br>
            ${language.title}
          </label>
        </a>
      </div>
    `;
  }
}

export default new FileListAiTranslateHandler();
