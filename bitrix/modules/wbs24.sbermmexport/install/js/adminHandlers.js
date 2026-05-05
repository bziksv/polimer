class Wbs24SbermmexportAdminHandlers {

  constructor() {
    this.oldPriceWarning = document.querySelector('.wbs24_old_price_warning');
  }

  init(xmlData) {
    document.addEventListener("DOMContentLoaded", () => {
      let oldPriceValue = xmlData.OLD_PRICE ? xmlData.OLD_PRICE : '';
      let oldPriceWarning = this.oldPriceWarning;

      if (
        oldPriceWarning
      ) {
        this.validateCustomOldPrice(
          oldPriceValue,
          oldPriceWarning
        );
      }
    });
  }

  addCustomOldPriceHandler() {
    let oldPriceTypeSelect = document.querySelector('.select_old_price_type');
    let oldPriceWarning = this.oldPriceWarning;

    if (
      oldPriceTypeSelect
      && oldPriceWarning
    ) {
      oldPriceTypeSelect.addEventListener('change', () => {
        this.validateCustomOldPrice(
          oldPriceTypeSelect.value,
          oldPriceWarning
        );
      });
    }
  }

  validateCustomOldPrice(
    oldPriceValue,
    oldPriceWarning
  ) {
    let defaultCustomOldPrice = (oldPriceValue == '');

    defaultCustomOldPrice
      ? oldPriceWarning.style.display = 'none'
      : oldPriceWarning.style.display = 'table-row'
    ;
  }
}
