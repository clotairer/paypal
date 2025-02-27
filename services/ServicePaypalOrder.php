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

namespace PaypalAddons\services;

use Db;
use DbQuery;
use Exception;
use PrestaShopBundle\Security\Admin\Employee;

require_once dirname(__FILE__) . '/../classes/PaypalOrder.php';
require_once dirname(__FILE__) . '/../classes/PaypalCapture.php';

use PaypalAddons\classes\AbstractMethodPaypal;

class ServicePaypalOrder
{
    /**
     * @param $paypalOrder \PaypalOrder object
     * @param $idStatus integer id of the order status
     * @return bool
     */
    public function setOrderStatus($paypalOrder, $idStatus, $checkHistory = true)
    {
        $psOrders = $this->getPsOrders($paypalOrder);

        if (empty($psOrders)) {
            return false;
        }

        /* @var $psOrder \Order*/
        foreach ($psOrders as $psOrder) {
            if ($checkHistory) {
                if (empty($psOrder->getHistory(\Context::getContext()->language->id, $idStatus)) == false) {
                    continue;
                }
            } else {
                if ($psOrder->current_state == $idStatus) {
                    continue;
                }
            }

            if (in_array($idStatus, array((int)\Configuration::get('PS_OS_REFUND'), (int)\Configuration::get('PAYPAL_OS_REFUNDED_PAYPAL')))) {
                $paypalOrder->payment_status = 'refunded';
                $paypalOrder->save();
            }

            $psOrder->setCurrentState($idStatus);
        }

        return true;
    }

    /**
     * @param $paypalOrder \PaypalOrder object
     * @return \PaypalCapture object
     */
    public function getCapture($paypalOrder)
    {
        $collection = new \PrestaShopCollection('PaypalCapture');
        $collection->where('id_paypal_order', '=', $paypalOrder->id);

        return $collection->getFirst();
    }

    /**
     * @param $transactionId string id of the Prestashop Customer object
     * @return \PaypalOrder
     */
    public function getPaypalOrderByTransaction($transactionId)
    {
        $query = (new DbQuery())
            ->select('po.id_paypal_order')
            ->from('paypal_order', 'po')
            ->leftJoin('paypal_capture', 'pc', 'po.id_paypal_order = pc.id_paypal_order')
            ->where(implode(
                ' OR ',
                [
                    'po.id_transaction = "' . pSQL($transactionId).'"',
                    'pc.id_capture = "' . pSQL($transactionId).'"'
                ])
            );
        $idPaypalOrder = (int)Db::getInstance()->getValue($query);

        if ($idPaypalOrder) {
            return new \PaypalOrder($idPaypalOrder);
        }

        return false;
    }

    /**
     * @param $paypalOrder \PaypalOrder object
     * @return array of the Order objects
     */
    public function getPsOrders($paypalOrder)
    {
        $collection = new \PrestaShopCollection('Order');
        $collection->where('id_cart', '=', $paypalOrder->id_cart);

        return $collection->getResults();
    }

    public function waitForWebhook(\PaypalOrder $paypalOrder)
    {
        $pendingWebhooks = (new WebhookService())->getPendingWebhooks($paypalOrder);
        return empty($pendingWebhooks) == false;
    }
}
