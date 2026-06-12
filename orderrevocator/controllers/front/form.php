<?php
/**
 * Copyright 2026 Stefan Schulz
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @author    Stefan Schulz <schulz@the-loom.de>
 * @copyright 2026 Stefan Schulz
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */

use PrestaShop\Module\OrderRevocator\Entity\Definitions;

/**
 * Class OrderRevocatorFormModuleFrontController
 */
class OrderRevocatorFormModuleFrontController extends ModuleFrontController
{
    public function initContent(): void
    {
        parent::initContent();

        // Falls das Formular abgeschickt wurde
        if (Tools::isSubmit('submit_revocation')) {
            $this->handleRevocationSubmit();
        }

        $this->context->smarty->assign([
            'action_url' => $this->context->link->getModuleLink(Definitions::MODULE_NAME, 'form'),
            'errors' => $this->errors,
            'success' => (bool) Tools::getValue('success'),
        ]);

        $this->setTemplate('module:' . Definitions::MODULE_NAME . '/views/templates/front/form.tpl');
    }

    protected function handleRevocationSubmit()
    {
        // bot protection, check honeypot
        if (!empty(Tools::getValue('website_hp'))) {
            Tools::redirect($this->context->link->getModuleLink(Definitions::MODULE_NAME, 'form', ['success' => 1]));
        }

        $order_ref = trim(Tools::getValue('order_reference'));
        $message = trim(Tools::getValue('message'));

        // validate input
        if (empty($order_ref)) {
            $this->errors[] = $this->trans('Please complete all the required fields.', [], Definitions::TRANS_SHOP);
            return;
        }

        if (!Validate::isReference($order_ref)) {
            $this->errors[] = $this->trans('The order reference contains invalid characters.', [], Definitions::TRANS_SHOP);
            return;
        }

        // find order in database
        $orders = Order::getByReference($order_ref);

        if ($orders->count() === 0) {
            $this->errors[] = $this->trans('The specified order reference could not be found in our system. Please check your entry.', [], Definitions::TRANS_SHOP);
            return;
        }

        /** @var Order $order */
        $order = $orders->getFirst();
        $customer = $order->getCustomer();
        if (!Validate::isLoadedObject($customer)) {
            $this->errors[] = $this->trans('No customer data could be found for this order.', [], Definitions::TRANS_SHOP);
            return;
        }

        $canceled_state_id = (int) Configuration::get('PS_OS_CANCELED');
        $current_state_id = $order->getCurrentState();

        if ($current_state_id === $canceled_state_id) {
            $this->errors[] = $this->trans('This order has already been canceled. You do not need to cancel it again.', [], 'Modules.Orderrevocator.Shop');
            return;
        }

        $customerName = $customer->firstname . ' ' . $customer->lastname;
        $customerEmail = $customer->email;

        // prepare e-mails
        $timestamp = date('Y-m-d H:i:s');
        $mail_vars = [
            '{customer_name}' => htmlspecialchars($customerName),
            '{customer_email}' => htmlspecialchars($customerEmail),
            '{order_reference}' => htmlspecialchars($order_ref),
            '{message}' => nl2br(htmlspecialchars($message)),
            '{timestamp}' => $timestamp,
        ];

        // customer confirmation
        Mail::Send(
            (int)$this->context->language->id,
            'revocation_customer',
            $this->trans('Confirmation of your cancellation', [], Definitions::TRANS_ADMIN),
            $mail_vars,
            $customerEmail,
            $customerName,
            null,
            null,
            null,
            null,
            _PS_MODULE_DIR_ . Definitions::MODULE_NAME . '/mails/',
            true
        );

        // shop admin notification
        $shopEmail = (string) Configuration::get('PS_SHOP_EMAIL');
        $shopName = (string) Configuration::get('PS_SHOP_NAME');
        Mail::Send(
            (int)$this->context->language->id,
            'revocation_admin',
            $this->trans('New cancellation received: ', [], Definitions::TRANS_ADMIN) . $order_ref,
            $mail_vars,
            $shopEmail,
            $shopName,
            null,
            null,
            null,
            null,
            _PS_MODULE_DIR_ . Definitions::MODULE_NAME . '/mails/',
            true
        );

        // redirect, avoid page refresh duplicate submits
        Tools::redirect($this->context->link->getModuleLink(Definitions::MODULE_NAME, 'form', ['success' => 1]));
    }
}
