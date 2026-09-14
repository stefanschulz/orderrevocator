<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PrestaShop\Module\OrderRevocator\Entity\Definitions;

class OrderRevocatorModuleTest extends TestCase
{
    public function testConstructorSetsModuleMetadata(): void
    {
        $module = new OrderRevocator();

        $this->assertSame(Definitions::MODULE_NAME, $module->name);
        $this->assertSame('front_office_features', $module->tab);
        $this->assertSame('Statutory Cancellation Button', $module->displayName);
        $this->assertSame(
            'Provides the legally required two-stage cancellation process without mandatory login.',
            $module->description
        );

        foreach ([$module->displayName, $module->description] as $translated) {
            $this->assertTranslatedWithDomain($module, $translated, Definitions::TRANS_ADMIN);
        }
    }

    public function testInstallRegistersBothHooks(): void
    {
        $module = new OrderRevocator();

        $this->assertTrue($module->install());
        $this->assertSame(['displayFooter', 'displayHeader'], $module->registeredHooks);
    }

    public function testUninstallRemovesBothHooks(): void
    {
        $module = new OrderRevocator();

        $this->assertTrue($module->uninstall());
        $this->assertSame(['displayFooter', 'displayHeader'], $module->unregisteredHooks);
    }

    public function testHookDisplayFooterAssignsLinkAndRendersTemplate(): void
    {
        $module = new OrderRevocator();

        $html = $module->hookDisplayFooter([]);

        $this->assertSame(
            'https://shop.test/module/orderrevocator/form',
            $module->context->smarty->assigned['revocation_url']
        );
        $this->assertSame(
            'module:orderrevocator/views/templates/hook/footer_link.tpl',
            $module->lastFetchedTemplate
        );
        $this->assertStringContainsString($module->lastFetchedTemplate, $html);
    }

    public function testHookDisplayHeaderRegistersTheStylesheet(): void
    {
        $module = new OrderRevocator();

        $module->hookDisplayHeader();

        $this->assertCount(1, $module->context->controller->registeredStylesheets);
        $stylesheet = $module->context->controller->registeredStylesheets[0];
        $this->assertSame('modules-orderrevocator-css', $stylesheet['id']);
        $this->assertSame('modules/orderrevocator/views/css/orderrevocator.css', $stylesheet['path']);
    }

    private function assertTranslatedWithDomain(OrderRevocator $module, string $id, string $domain): void
    {
        foreach ($module->translationLog as $entry) {
            if ($entry['id'] === $id) {
                $this->assertSame($domain, $entry['domain'], "Expected '{$id}' to be translated in domain '{$domain}'.");

                return;
            }
        }

        $this->fail("No trans() call recorded for '{$id}'.");
    }
}
