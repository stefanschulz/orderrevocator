<?php

/**
 * Minimal stand-ins for the PrestaShop core classes used by this module's
 * controllers, so they can be exercised by PHPUnit without a full shop install.
 * These are deliberately not full re-implementations - only the behaviour
 * this module actually relies on is reproduced.
 */

/**
 * Thrown by the Tools::redirect() stub instead of calling exit(), so tests
 * can catch it and inspect the URL the controller intended to redirect to.
 */
class RedirectException extends Exception
{
    public function __construct(private readonly string $url)
    {
        parent::__construct('Redirect to: ' . $url);
    }

    public function getUrl(): string
    {
        return $this->url;
    }
}

class Tools
{
    public const string TEST_TOKEN = 'unit-test-token';

    public static function getValue(string $key, $default = false)
    {
        if (isset($_POST[$key])) {
            return $_POST[$key];
        }

        if (isset($_GET[$key])) {
            return $_GET[$key];
        }

        return $default;
    }

    public static function isSubmit(string $key): bool
    {
        return isset($_POST[$key]) || isset($_GET[$key]);
    }

    public static function getToken(bool $page = true): string
    {
        return self::TEST_TOKEN;
    }

    public static function redirect(string $url): void
    {
        throw new RedirectException($url);
    }
}

class Validate
{
    // Mirrors the real PrestaShop 9.x regex, verified against a live instance.
    public static function isName($name): bool
    {
        return is_string($name) && (bool) preg_match('/^[^0-9!<>,;?=+()@#"°{}_$%:¤|]*$/u', $name);
    }

    public static function isEmail($email): bool
    {
        return is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    // Mirrors the real PrestaShop 9.x regex, verified against a live instance.
    public static function isReference($reference): bool
    {
        return is_string($reference) && (bool) preg_match('/^[^<>;={}]*$/u', $reference);
    }
}

class Configuration
{
    public static array $values = [];

    public static function get(string $key, $idLang = null, $idShopGroup = null, $idShop = null)
    {
        return self::$values[$key] ?? '';
    }
}

class Mail
{
    /** @var array<int, array<string, mixed>> */
    public static array $log = [];

    public static function Send(
        int $idLang,
        string $template,
        string $subject,
        array $templateVars,
        $to,
        $toName = null,
        $fromEmail = null,
        $fromName = null,
        $fileAttachment = null,
        $modeSmtp = null,
        $templatePath = null,
        $die = true,
    ): bool {
        self::$log[] = [
            'idLang' => $idLang,
            'template' => $template,
            'subject' => $subject,
            'templateVars' => $templateVars,
            'to' => $to,
            'toName' => $toName,
            'templatePath' => $templatePath,
        ];

        return true;
    }

    public static function reset(): void
    {
        self::$log = [];
    }
}

class Cookie
{
    private array $data = [];

    public function __get($key)
    {
        return $this->data[$key] ?? null;
    }

    public function __set($key, $value): void
    {
        $this->data[$key] = $value;
    }

    public function __isset($key): bool
    {
        return isset($this->data[$key]);
    }

    public function write(): void
    {
        // no-op: values already live in $this->data for the duration of the test
    }
}

class Language
{
    public int $id = 1;
}

class Link
{
    public function getModuleLink(string $module, string $controller, array $params = []): string
    {
        $url = "https://shop.test/module/{$module}/{$controller}";

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        return $url;
    }
}

class SmartyStub
{
    public array $assigned = [];

    public function assign(array $vars): void
    {
        $this->assigned = array_merge($this->assigned, $vars);
    }
}

class ControllerStub
{
    /** @var array<int, array{id: string, path: string, params: array}> */
    public array $registeredStylesheets = [];

    public function registerStylesheet(string $id, string $path, array $params = []): void
    {
        $this->registeredStylesheets[] = ['id' => $id, 'path' => $path, 'params' => $params];
    }
}

class Context
{
    public Cookie $cookie;
    public Language $language;
    public Link $link;
    public SmartyStub $smarty;
    public ControllerStub $controller;

    public function __construct()
    {
        $this->cookie = new Cookie();
        $this->language = new Language();
        $this->link = new Link();
        $this->smarty = new SmartyStub();
        $this->controller = new ControllerStub();
    }
}

class Module
{
    public Context $context;
    public array $errors = [];

    // Properties a real module sets on itself in its constructor - declared
    // here so PHP 8.2+'s deprecation of dynamic properties doesn't trip tests.
    public string $name = '';
    public string $tab = '';
    public string $version = '';
    public string $author = '';
    public int $need_instance = 0;
    public array $ps_versions_compliancy = [];
    public bool $bootstrap = false;
    public string $displayName = '';
    public string $description = '';

    /** @var array<int, string> hooks registered via registerHook(), for assertions */
    public array $registeredHooks = [];
    /** @var array<int, string> hooks removed via unregisterHook(), for assertions */
    public array $unregisteredHooks = [];
    public ?string $lastFetchedTemplate = null;
    /** @var array<int, array{id: string, domain: ?string}> recorded trans() calls, for asserting translation domains */
    public array $translationLog = [];

    public function __construct()
    {
        $this->context = new Context();
    }

    public function trans(string $id, array $params = [], ?string $domain = null): string
    {
        $this->translationLog[] = ['id' => $id, 'domain' => $domain];

        return $id;
    }

    public function install(): bool
    {
        return true;
    }

    public function uninstall(): bool
    {
        return true;
    }

    public function registerHook(string $hookName): bool
    {
        $this->registeredHooks[] = $hookName;

        return true;
    }

    public function unregisterHook(string $hookName): bool
    {
        $this->unregisteredHooks[] = $hookName;

        return true;
    }

    public function fetch(string $template): string
    {
        $this->lastFetchedTemplate = $template;

        return '<!-- rendered: ' . $template . ' -->';
    }
}

class ModuleFrontController extends Module
{
    public ?string $lastTemplate = null;

    public function initContent(): void
    {
        // no-op stand-in for the real parent controller's heavy lifting
    }

    public function setTemplate(string $template): void
    {
        $this->lastTemplate = $template;
    }
}
