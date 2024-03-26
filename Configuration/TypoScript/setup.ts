# Module configuration
module.tx_ai_translate {
  persistence {
    storagePid = module.tx_ai_translate.persistence.storagePid
  }
  view {
    templateRootPaths.0 = module.tx_ai_translate.view.templateRootPath
    partialRootPaths.0 = module.tx_ai_translate.view.partialRootPath
    layoutRootPaths.0 = module.tx_ai_translate.view.layoutRootPath
  }
}
