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
import { default as MultiStepWizard } from "@typo3/backend/multi-step-wizard.js";

class FileListAiTranslateHandler {
  constructor() {
    document
      .querySelectorAll('button[data-action="ai_translate"]')
      .forEach((button) => {
        button.addEventListener("click", (event) => {
          console.log("Test");
          event.preventDefault();
          let translateWizard = TYPO3.MultiStepWizard;
          translateWizard.addSlide(
            "aiTranslateChooseModel",
            "Choose model",
            $("<p>Step 1</p>"),
            SeverityEnum.info,
            "Choose model to generate the translation"
          );
          translateWizard.addSlide(
            "aiTranslateChooseLanguage",
            "Choose language to translate",
            $("<p>Step 2</p>"),
            SeverityEnum.info,
            "Choose language to translate"
          );
          translateWizard.show();
        });
      });
  }
}
export default new FileListAiTranslateHandler();
