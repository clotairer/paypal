<?php
/**
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
 *  @author 2007-2021 PayPal
 *  @author 202 ecommerce <tech@202-ecommerce.com>
 *  @copyright PayPal
 *  @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace PaypalAddons\classes\API\Request\V_1;


use PayPal\Api\Webhook;
use PaypalAddons\classes\API\Response\Error as PaypalError;
use PaypalAddons\classes\API\Response\Response;
use Symfony\Component\VarDumper\VarDumper;

class GetWebHooks extends RequestAbstract
{

    public function execute()
    {
        $response = $this->getResponse();

        try {
            $webHookList = Webhook::getAll($this->getApiContext());
            $response
                ->setSuccess(true)
                ->setData($webHookList->webhooks);
        } catch (\Exception $e) {
            $error = new PaypalError();
            $error
                ->setMessage($e->getMessage())
                ->setErrorCode($e->getCode());

            $response
                ->setSuccess(false)
                ->setError($error);
        }

        return $response;
    }

    protected function getResponse()
    {
        return new Response();
    }
}
