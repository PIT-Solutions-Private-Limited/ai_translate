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
          translateWizard.addFinalProcessingSlide(() =>
            translateWizard.dismiss()
          );
          translateWizard.show();
        });
      });
  }

  #aiTranslateChooseModel(slide, aiModels) {
    MultiStepWizard.blurCancelStep();
    MultiStepWizard.lockNextStep();
    MultiStepWizard.lockPrevStep();
    Promise.all(
      aiModels.map((model) => this.#renderModelSelection(model))
    ).then((modelsMarkup) => {
      slide.html(
        modelsMarkup.reduce((acc, modelMarkup) => (acc += modelMarkup), "")
      );
      slide.on("click", "[data-model-select]", (event) => {
        event.preventDefault();
        MultiStepWizard.set(
          "translateModel",
          $(event.currentTarget).data("model")
        );
        MultiStepWizard.unlockNextStep().trigger("click");
      });
    });
  }

  async #renderModelSelection(model) {
    const icon = await Icons.getIcon(
      `actions-localize-${model}`,
      Icons.sizes.large
    );
    return `
      <div class="row" data-model="${model}" data-model-select="">
        <div class="col-sm-3">
            <label class="btn btn-default d-block">
                ${icon}
                <br>
                <br>
                ${TYPO3.lang[`localize.wizard.button.${model}`]}
            </label>
        </div>
      </div>
    `;
  }

  #aiTranslateChooseLanguage(slide, translateUrls, languages) {
    MultiStepWizard.unlockPrevStep();
    const model = MultiStepWizard.setup.settings.translateModel;
    Promise.all(
      languages.map((language) =>
        this.#renderLanguageSelection(
          model,
          language,
          translateUrls[model][language.uid]
        )
      )
    ).then((modelsMarkup) => {
      slide.html(
        modelsMarkup.reduce(
          (acc, languageMarkup) => (acc += languageMarkup),
          ""
        )
      );
      slide.on("click", "[data-language-select]", (event) => {
        event.preventDefault();
        Viewport.ContentContainer.setUrl(event.currentTarget.href);
        MultiStepWizard.dismiss();
      });
    });
  }

  async #renderLanguageSelection(model, language, url) {
    const icon = await Icons.getIcon(language.flagIcon, Icons.sizes.large);
    return `
    <div class="row" id="${model}Translate" data-model="${model}">
      <div class="col-sm-3">
          <a href="${url}" data-language-select="">
            <label class="btn btn-default d-block">
                ${icon}
                <br>
                <br>
                ${language.title}
            </label>
          </a>
      </div>
    </div>
  `;
  }
}
export default new FileListAiTranslateHandler();
