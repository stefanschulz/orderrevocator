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
use PrestaShopBundle\Translation\DomainNormalizer;

/**
 * Class OrderRevocatorFormModuleFrontController
 */
class OrderRevocatorFormModuleFrontController extends ModuleFrontController
{
    /**
     * @throws PrestaShopException
     */
    public function initContent(): void
    {
        parent::initContent();

        // form sent?
        if (Tools::isSubmit('submit_revocation')) {
            $this->handleRevocationSubmit();
        }

        $this->context->smarty->assign([
            'action_url' => $this->context->link->getModuleLink(Definitions::MODULE_NAME, 'form'),
            'errors' => $this->errors,
            'success' => (bool) Tools::getValue('success'),
            'token' => Tools::getToken(false),
        ]);

        $this->setTemplate('module:' . Definitions::MODULE_NAME . '/views/templates/front/form.tpl');
    }

    protected function handleRevocationSubmit(): void
    {
        // bot protection, check honeypot
        if (!empty(Tools::getValue('website_hp'))) {
            Tools::redirect($this->context->link->getModuleLink(Definitions::MODULE_NAME, 'form', ['success' => 1]));
        }

        // form protection
        if (Tools::getValue('token') !== Tools::getToken(false)) {
            $this->errors[] = $this->trans('Invalid security token.', [], Definitions::TRANS_ADMIN);

            return;
        }

        // load and validate input
        $customerName = trim(Tools::getValue('customer_name'));
        $orderReference = trim(Tools::getValue('order_reference'));
        $customerEmail = trim((string) Tools::getValue('customer_email'));
        $message = trim(Tools::getValue('message'));

        if (empty($customerName) || !Validate::isName($customerName)) {
            $this->errors[] = $this->trans('Please, enter a valid name.', [], Definitions::TRANS_ADMIN);
        }

        if (empty($customerEmail) || !Validate::isEmail($customerEmail)) {
            $this->errors[] = $this->trans('Please, enter a valid email address.', [], Definitions::TRANS_ADMIN);
        }

        if (empty($orderReference) || !Validate::isReference($orderReference)) {
            $this->errors[] = $this->trans('The order reference contains invalid characters.', [], Definitions::TRANS_ADMIN);
        }

        if (!empty($this->errors)) {
            return;
        }

        // all is fine, send the mails
        $this->sendMails($customerName, $orderReference, $customerEmail, $message);

        // redirect, avoid page refresh duplicate submits
        Tools::redirect($this->context->link->getModuleLink(Definitions::MODULE_NAME, 'form', ['success' => 1]));
    }

    /**
     * Send mails to customer and admin.
     *
     * @param string $customerName
     * @param string $orderReference
     * @param string $customerEmail
     * @param string $message
     *
     * @return void
     */
    public function sendMails(string $customerName, string $orderReference, string $customerEmail, string $message): void
    {
        // prepare e-mails
        $timestamp = date('Y-m-d H:i:s');
        $mailVars = [
            '{customer_name}' => $customerName,
            '{customer_email}' => $customerEmail,
            '{order_reference}' => htmlspecialchars($orderReference),
            '{message}' => nl2br(htmlspecialchars($message)),
            '{timestamp}' => $timestamp,
        ];

        // customer confirmation
        Mail::Send(
            (int) $this->context->language->id,
            'revocation_customer',
            $this->trans('Confirmation of your cancellation request', [], Definitions::TRANS_ADMIN),
            $mailVars,
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
            (int) $this->context->language->id,
            'revocation_admin',
            $this->trans('New cancellation request received: ', [], Definitions::TRANS_ADMIN) . $orderReference,
            $mailVars,
            $shopEmail,
            $shopName,
            null,
            null,
            null,
            null,
            _PS_MODULE_DIR_ . Definitions::MODULE_NAME . '/mails/',
            true
        );
    }
}
