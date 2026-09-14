<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PrestaShop\Module\OrderRevocator\Entity\Definitions;

class OrderRevocatorFormControllerTest extends TestCase
{
    protected function setUp(): void
    {
        $_POST = [];
        $_GET = [];
        Mail::reset();
        Configuration::$values = [
            'PS_SHOP_EMAIL' => 'shop@example.com',
            'PS_SHOP_NAME' => 'Example Shop',
        ];
    }

    private function newController(): OrderRevocatorFormModuleFrontController
    {
        return new OrderRevocatorFormModuleFrontController();
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'submit_revocation' => '1',
            'website_hp' => '',
            'token' => Tools::TEST_TOKEN,
            'customer_name' => 'Max Mustermann',
            'order_reference' => 'ORD12345',
            'customer_email' => 'max@example.com',
            'message' => 'Please cancel my order.',
        ], $overrides);
    }

    private function markFormAsRenderedInThePast(OrderRevocatorFormModuleFrontController $controller, int $secondsAgo = 10): void
    {
        $controller->context->cookie->orderrevocator_rendered_at = time() - $secondsAgo;
    }

    public function testHoneypotFilledRedirectsSilentlyWithoutSendingMail(): void
    {
        $controller = $this->newController();
        $this->markFormAsRenderedInThePast($controller);
        $_POST = $this->validPayload(['website_hp' => 'http://spam.example']);

        try {
            $controller->initContent();
            $this->fail('Expected a redirect.');
        } catch (RedirectException $e) {
            $this->assertStringContainsString('success=1', $e->getUrl());
        }

        $this->assertEmpty(Mail::$log, 'A honeypot hit must never trigger an email.');
    }

    public function testSubmissionWithoutAPriorPageLoadIsTreatedAsBot(): void
    {
        // No orderrevocator_rendered_at cookie was ever set - a real visitor always has one.
        $controller = $this->newController();
        $_POST = $this->validPayload();

        try {
            $controller->initContent();
            $this->fail('Expected a redirect.');
        } catch (RedirectException $e) {
            $this->assertStringContainsString('success=1', $e->getUrl());
        }

        $this->assertEmpty(Mail::$log, 'A submission with no render timestamp must be treated as automated.');
    }

    public function testSubmissionFasterThanTheMinimumFillTimeIsTreatedAsBot(): void
    {
        $controller = $this->newController();
        $this->markFormAsRenderedInThePast($controller, Definitions::RATE_LIMIT_MIN_FILL_SECONDS - 1);
        $_POST = $this->validPayload();

        try {
            $controller->initContent();
            $this->fail('Expected a redirect.');
        } catch (RedirectException $e) {
            $this->assertStringContainsString('success=1', $e->getUrl());
        }

        $this->assertEmpty(Mail::$log);
    }

    public function testCooldownBlocksASecondSubmissionFromTheSameVisitor(): void
    {
        $controller = $this->newController();
        $this->markFormAsRenderedInThePast($controller);
        $controller->context->cookie->orderrevocator_last_submit_at = time() - 1;
        $_POST = $this->validPayload();

        $controller->initContent();

        $this->assertEmpty(Mail::$log, 'No mail must be sent while the cooldown is active.');
        $this->assertCount(1, $controller->errors);
        $this->assertSame(
            'You have already submitted a cancellation request recently. Please wait a moment and try again, or contact us directly if this is urgent.',
            $controller->errors[0]
        );
        $this->assertTranslatedWithDomain($controller, $controller->errors[0], Definitions::TRANS_SHOP);
    }

    public function testInvalidTokenAddsAnErrorWithoutSendingMail(): void
    {
        $controller = $this->newController();
        $this->markFormAsRenderedInThePast($controller);
        $_POST = $this->validPayload(['token' => 'not-the-right-token']);

        $controller->initContent();

        $this->assertEmpty(Mail::$log);
        $this->assertCount(1, $controller->errors);
        $this->assertSame('Invalid security token.', $controller->errors[0]);
        $this->assertTranslatedWithDomain($controller, $controller->errors[0], Definitions::TRANS_SHOP);
    }

    public function testInvalidFieldsAddOneErrorEach(): void
    {
        $controller = $this->newController();
        $this->markFormAsRenderedInThePast($controller);
        $_POST = $this->validPayload([
            'customer_name' => 'Max! Mustermann', // "!" is rejected by isName()
            'customer_email' => 'not-an-email',
            'order_reference' => 'ORD<1>', // "<" and ">" are rejected by isReference()
        ]);

        $controller->initContent();

        $this->assertEmpty(Mail::$log);
        $this->assertSame(
            [
                'Please, enter a valid name.',
                'Please, enter a valid email address.',
                'The order reference contains invalid characters.',
            ],
            $controller->errors
        );

        foreach ($controller->errors as $error) {
            $this->assertTranslatedWithDomain($controller, $error, Definitions::TRANS_SHOP);
        }
    }

    public function testMissingFieldsAreReportedAsInvalid(): void
    {
        $controller = $this->newController();
        $this->markFormAsRenderedInThePast($controller);
        $_POST = $this->validPayload([
            'customer_name' => '',
            'customer_email' => '',
            'order_reference' => '',
        ]);

        $controller->initContent();

        $this->assertEmpty(Mail::$log);
        $this->assertCount(3, $controller->errors);
    }

    public function testValidSubmissionSendsBothMailsAndStartsTheCooldown(): void
    {
        $controller = $this->newController();
        $this->markFormAsRenderedInThePast($controller);
        $_POST = $this->validPayload([
            'customer_name' => "O'Brien & Söhne",
            'order_reference' => 'ORD&1',
        ]);

        try {
            $controller->initContent();
            $this->fail('Expected a redirect after a successful submission.');
        } catch (RedirectException $e) {
            $this->assertStringContainsString('success=1', $e->getUrl());
        }

        $this->assertEmpty($controller->errors);
        $this->assertCount(2, Mail::$log, 'Both the customer confirmation and the admin notification must be sent.');

        [$customerMail, $adminMail] = Mail::$log;

        $this->assertSame('revocation_customer', $customerMail['template']);
        $this->assertSame('max@example.com', $customerMail['to']);

        $this->assertSame('revocation_admin', $adminMail['template']);
        $this->assertSame('shop@example.com', $adminMail['to']);
        $this->assertSame('Example Shop', $adminMail['toName']);
        $this->assertStringContainsString('ORD&1', $adminMail['subject']);

        // The cooldown must be armed so a rapid second submission gets blocked.
        $this->assertGreaterThan(0, (int) $controller->context->cookie->orderrevocator_last_submit_at);
    }

    public function testMailVariablesAreConsistentlyEscaped(): void
    {
        $controller = $this->newController();

        $controller->sendMails(
            "O'Brien & Söhne",
            'ORD&1',
            'customer@example.com',
            "Line one\n<b>not actually bold</b> & 'quoted'"
        );

        $this->assertCount(2, Mail::$log);

        foreach (Mail::$log as $mail) {
            $vars = $mail['templateVars'];
            $this->assertSame('O&#039;Brien &amp; Söhne', $vars['{customer_name}']);
            $this->assertSame('customer@example.com', $vars['{customer_email}']);
            $this->assertSame('ORD&amp;1', $vars['{order_reference}']);
            $this->assertStringContainsString('&lt;b&gt;not actually bold&lt;/b&gt;', $vars['{message}']);
            $this->assertStringContainsString('&#039;quoted&#039;', $vars['{message}']);
        }
    }

    private function assertTranslatedWithDomain(OrderRevocatorFormModuleFrontController $controller, string $id, string $domain): void
    {
        foreach ($controller->translationLog as $entry) {
            if ($entry['id'] === $id) {
                $this->assertSame($domain, $entry['domain'], "Expected '{$id}' to be translated in domain '{$domain}'.");

                return;
            }
        }

        $this->fail("No trans() call recorded for '{$id}'.");
    }
}
