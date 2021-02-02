/*
 * 2007-2021 PayPal
 *
 *  NOTICE OF LICENSE
 *
 *  This source file is subject to the Academic Free License (AFL 3.0)
 *  that is bundled with this package in the file LICENSE.txt.
 *  It is also available through the world-wide-web at this URL:
 *  http://opensource.org/licenses/afl-3.0.php
 *  If you did not receive a copy of the license and are unable to
 *  obtain it through the world-wide-web, please send an email
 *  to license@prestashop.com so we can send you a copy immediately.
 *
 *  DISCLAIMER
 *
 *  Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 *  versions in the future. If you wish to customize PrestaShop for your
 *  needs please refer to http://www.prestashop.com for more information.
 *
 *  @author 2007-2020 PayPal
 *  @author 202 ecommerce <tech@202-ecommerce.com>
 *  @copyright PayPal
 *  @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

// Import functions for scrolling effect to necessary block on click
import {hoverConfig, hoverTabConfig} from './functions.js';
import {Tools} from './tools.js';

var InstallmentSetting = {
  init() {
    this.checkConfigurations();
    document.querySelectorAll('form#pp_config_installment input').forEach((elem) => {
      elem.addEventListener('change', this.checkConfigurations);
    });

    let initBannerEvent = document.createEvent('HTMLEvents');
    initBannerEvent.initEvent('initPaypalBanner');
    document.dispatchEvent(initBannerEvent);

    document.querySelector('[name="PAYPAL_INSTALLMENT_COLOR"]').addEventListener('change', this.updateBannerTextColor)
  },

  checkConfigurations() {
    const installmentEnabled = document.querySelector('input[name="PAYPAL_ENABLE_INSTALLMENT"]');
    const displayingSettings = document.querySelector('[installment-page-displaying-setting-container]');
    const advancedOptions = document.querySelector('input[name="PAYPAL_ADVANCED_OPTIONS_INSTALLMENT"]');
    const widgetCode = document.querySelector('input[name="installmentWidgetCode"]');
    const colorConf = document.querySelector('[name="PAYPAL_INSTALLMENT_COLOR"]');

    if (installmentEnabled.checked) {
      displayingSettings.style.display = 'block';
      Tools.showConfiguration(advancedOptions.getAttribute('name'));
    } else {
      displayingSettings.style.display = 'none';
      Tools.hideConfiguration(advancedOptions.getAttribute('name'));
    }

    if (advancedOptions.checked === false || installmentEnabled.checked === false) {
      Tools.hideConfiguration(widgetCode.getAttribute('name'));
      Tools.hideConfiguration(colorConf.getAttribute('name'));
    } else {
      Tools.showConfiguration(widgetCode.getAttribute('name'));
      Tools.showConfiguration(colorConf.getAttribute('name'));
    }
  },

  updateBannerTextColor() {
    const color = document.querySelector('[name="PAYPAL_INSTALLMENT_COLOR"]').value;
    document
      .querySelector('[installment-container] [data-pp-message]')
      .setAttribute('data-pp-style-text-color', color);
  }

};

$(document).ready(() => {
  InstallmentSetting.init();
  // Handle click on "Install Prestashop Checkout" button
  $('.install-ps-checkout').click(() => {
    SetupAdmin.psCheckoutHandleAction('install');
  })
});
