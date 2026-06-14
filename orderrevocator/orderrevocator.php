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
declare(strict_types=1);

use PrestaShop\Module\OrderRevocator\Entity\Definitions;

require_once __DIR__ . '/vendor/autoload.php';

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Class OrderRevocator
 */
class OrderRevocator extends Module
{
    public function __construct()
    {
        $this->name = Definitions::MODULE_NAME;
        $this->tab = 'front_office_features';
        $this->version = '1.1.0';
        $this->author = 'Stefan Schulz';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '9.0.0', 'max' => '9.9.9'];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('Statutory Cancellation Button', [], Definitions::TRANS_ADMIN);
        $this->description = $this->trans('Provides the legally required two-stage cancellation process without mandatory login.', [], Definitions::TRANS_ADMIN);
    }

    public function install(): bool
    {
        return parent::install()
            && $this->registerHook('displayFooter')
            && $this->registerHook('displayHeader');
    }

    public function uninstall(): bool
    {
        return parent::uninstall()
            && $this->unregisterHook('displayFooter')
            && $this->unregisterHook('displayHeader');
    }

    /**
     * Hook for the footer button (Step 1)
     *
     * @noinspection PhpUnused
     */
    public function hookDisplayFooter($params): string
    {
        // Link to frontend controller
        $revocation_url = $this->context->link->getModuleLink(Definitions::MODULE_NAME, 'form');

        $this->context->smarty->assign([
            'revocation_url' => $revocation_url,
        ]);

        return $this->fetch('module:' . Definitions::MODULE_NAME . '/views/templates/hook/footer_link.tpl');
    }

    /**
     * Hook for adding stylesheet
     *
     * @noinspection PhpUnused
     */
    public function hookDisplayHeader(): void
    {
        $this->context->controller->registerStylesheet(
            'modules-orderrevocator-css',
            'modules/' . $this->name . '/views/css/orderrevocator.css',
            [
                'media' => 'all',
                'priority' => 150,
            ]
        );
    }
}
