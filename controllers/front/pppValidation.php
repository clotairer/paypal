<?php
/**
 * 2007-2022 PayPal
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
 *  @author 2007-2022 PayPal
 *  @author 202 ecommerce <tech@202-ecommerce.com>
 *  @copyright PayPal
 *  @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

use PaypalAddons\classes\AbstractMethodPaypal;

/**
 * Validate PPP payment
 */
class PaypalPppValidationModuleFrontController extends PaypalAbstarctModuleFrontController
{
    public function init()
    {
        parent::init();

        if (Tools::isSubmit('paymentData')) {
            $paymentData = $this->parsePaymentData(Tools::getValue('paymentData'));
            $this->values['paymentId'] = $paymentData->getOrderId();
        }

        $this->values['short_cut'] = Tools::getvalue('short_cut');

        if (empty($this->values['paymentId'])) {
            $this->values['paymentId'] = Tools::getvalue('token');
        }
    }

    /**
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        $method_ppp = AbstractMethodPaypal::load('PPP');
        $paypal = Module::getInstanceByName($this->name);
        try {
            $method_ppp->setParameters($this->values);

            if ($method_ppp->getShortCut()) {
                /** @var $resultPath \PaypalAddons\classes\API\Response\Response*/
                $resultPath = $method_ppp->doOrderPatch();

                if ($resultPath->isSuccess() == false) {
                    throw new Exception($resultPath->getError()->getMessage());
                }
            }

            $method_ppp->validation();
            $cart = Context::getContext()->cart;
            $customer = new Customer($cart->id_customer);
            $this->redirectUrl = 'index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$paypal->id.'&id_order='.$paypal->currentOrder.'&key='.$customer->secure_key;
        } catch (PayPal\Exception\PayPalConnectionException $e) {
            $decoded_message = Tools::jsonDecode($e->getData());
            $this->_errors['error_code'] = $e->getCode();
            $this->_errors['error_msg'] = $decoded_message->message;
            $this->_errors['msg_long'] = $decoded_message->name.' - '.$decoded_message->details[0]->issue;
        } catch (PayPal\Exception\PayPalInvalidCredentialException $e) {
            $this->_errors['error_msg'] = $e->errorMessage();
        } catch (PayPal\Exception\PayPalMissingCredentialException $e) {
            $this->_errors['error_msg'] = $paypal->l('Invalid configuration. Please check your configuration file.', pathinfo(__FILE__)['filename']);
        } catch (Exception $e) {
            $this->_errors['error_code'] = $e->getCode();
            $this->_errors['error_msg'] = $e->getMessage();
        } finally {
            $this->transaction_detail = $method_ppp->getDetailsTransaction();
        }

        Context::getContext()->cookie->__unset('paypal_pSc');
        Context::getContext()->cookie->__unset('paypal_pSc_email');
        if (!empty($this->_errors)) {
            $this->redirectUrl = Context::getContext()->link->getModuleLink($this->name, 'error', $this->_errors);
        }
    }
}
