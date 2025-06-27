# Internacionalização (i18n) - Express PHP Framework

## 🌍 Visão Geral

O Express PHP Framework oferece suporte nativo para internacionalização, permitindo que suas aplicações sejam facilmente adaptadas para diferentes idiomas e regiões.

## 🚀 Configuração Básica

### 1. Estrutura de Arquivos
```
config/
├── locales/
│   ├── en/
│   │   ├── messages.php
│   │   ├── validation.php
│   │   └── errors.php
│   ├── pt-br/
│   │   ├── messages.php
│   │   ├── validation.php
│   │   └── errors.php
│   └── es/
│       ├── messages.php
│       ├── validation.php
│       └── errors.php
└── i18n.php
```

### 2. Configuração Principal
```php
// config/i18n.php
return [
    'default_locale' => 'en',
    'supported_locales' => ['en', 'pt-br', 'es', 'fr'],
    'fallback_locale' => 'en',
    'auto_detect' => true,
    'cache_enabled' => true,
    'cache_ttl' => 3600
];
```

## 🔧 Implementação do Sistema i18n

### Core I18n Class
```php
<?php
namespace Express\Support;

class I18n
{
    private static array $translations = [];
    private static string $currentLocale = 'en';
    private static array $config = [];

    public static function init(array $config = []): void
    {
        self::$config = array_merge([
            'default_locale' => 'en',
            'supported_locales' => ['en'],
            'fallback_locale' => 'en',
            'auto_detect' => true,
            'cache_enabled' => false
        ], $config);

        self::$currentLocale = self::$config['default_locale'];

        if (self::$config['auto_detect']) {
            self::detectLocale();
        }

        self::loadTranslations();
    }

    public static function setLocale(string $locale): void
    {
        if (in_array($locale, self::$config['supported_locales'])) {
            self::$currentLocale = $locale;
            self::loadTranslations();
        }
    }

    public static function getLocale(): string
    {
        return self::$currentLocale;
    }

    public static function translate(string $key, array $params = [], ?string $locale = null): string
    {
        $locale = $locale ?? self::$currentLocale;

        // Tentar encontrar na locale atual
        $translation = self::getTranslation($key, $locale);

        // Se não encontrar, tentar fallback
        if ($translation === null && $locale !== self::$config['fallback_locale']) {
            $translation = self::getTranslation($key, self::$config['fallback_locale']);
        }

        // Se ainda não encontrar, retornar a chave
        if ($translation === null) {
            return $key;
        }

        // Substituir parâmetros
        return self::replaceParameters($translation, $params);
    }

    public static function t(string $key, array $params = []): string
    {
        return self::translate($key, $params);
    }

    private static function detectLocale(): void
    {
        // 1. Verificar query parameter
        if (isset($_GET['lang']) && in_array($_GET['lang'], self::$config['supported_locales'])) {
            self::$currentLocale = $_GET['lang'];
            return;
        }

        // 2. Verificar header Accept-Language
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $languages = self::parseAcceptLanguage($_SERVER['HTTP_ACCEPT_LANGUAGE']);
            foreach ($languages as $lang) {
                if (in_array($lang, self::$config['supported_locales'])) {
                    self::$currentLocale = $lang;
                    return;
                }
            }
        }

        // 3. Verificar sessão/cookie
        if (isset($_SESSION['locale']) && in_array($_SESSION['locale'], self::$config['supported_locales'])) {
            self::$currentLocale = $_SESSION['locale'];
            return;
        }
    }

    private static function loadTranslations(): void
    {
        $locale = self::$currentLocale;

        if (isset(self::$translations[$locale])) {
            return; // Já carregado
        }

        $translations = [];
        $localeDir = __DIR__ . "/../../config/locales/{$locale}/";

        if (is_dir($localeDir)) {
            $files = glob($localeDir . '*.php');
            foreach ($files as $file) {
                $namespace = basename($file, '.php');
                $translations[$namespace] = require $file;
            }
        }

        self::$translations[$locale] = $translations;
    }

    private static function getTranslation(string $key, string $locale): ?string
    {
        $parts = explode('.', $key);
        $namespace = array_shift($parts);
        $path = implode('.', $parts);

        if (!isset(self::$translations[$locale][$namespace])) {
            return null;
        }

        return self::getNestedValue(self::$translations[$locale][$namespace], $path);
    }

    private static function getNestedValue(array $array, string $path): ?string
    {
        $keys = explode('.', $path);
        $value = $array;

        foreach ($keys as $key) {
            if (!isset($value[$key])) {
                return null;
            }
            $value = $value[$key];
        }

        return is_string($value) ? $value : null;
    }

    private static function replaceParameters(string $text, array $params): string
    {
        foreach ($params as $key => $value) {
            $text = str_replace(":{$key}", $value, $text);
        }
        return $text;
    }

    private static function parseAcceptLanguage(string $header): array
    {
        $languages = [];
        $parts = explode(',', $header);

        foreach ($parts as $part) {
            $part = trim($part);
            $subparts = explode(';', $part);
            $lang = trim($subparts[0]);

            // Converter formato de locale (pt-BR -> pt-br)
            $lang = strtolower(str_replace('_', '-', $lang));

            $languages[] = $lang;
        }

        return $languages;
    }
}
```

## 📝 Arquivos de Tradução

### Estrutura dos Arquivos

#### config/locales/en/messages.php
```php
<?php
return [
    'welcome' => 'Welcome to Express PHP Framework',
    'user' => [
        'profile' => 'User Profile',
        'settings' => 'User Settings',
        'logout' => 'Logout'
    ],
    'api' => [
        'success' => 'Operation completed successfully',
        'error' => 'An error occurred: :error',
        'not_found' => 'Resource not found'
    ],
    'auth' => [
        'login_required' => 'Authentication required',
        'invalid_credentials' => 'Invalid username or password',
        'token_expired' => 'Authentication token has expired'
    ]
];
```

#### config/locales/pt-br/messages.php
```php
<?php
return [
    'welcome' => 'Bem-vindo ao Express PHP Framework',
    'user' => [
        'profile' => 'Perfil do Usuário',
        'settings' => 'Configurações do Usuário',
        'logout' => 'Sair'
    ],
    'api' => [
        'success' => 'Operação completada com sucesso',
        'error' => 'Ocorreu um erro: :error',
        'not_found' => 'Recurso não encontrado'
    ],
    'auth' => [
        'login_required' => 'Autenticação necessária',
        'invalid_credentials' => 'Usuário ou senha inválidos',
        'token_expired' => 'Token de autenticação expirado'
    ]
];
```

### Validações Localizadas

#### config/locales/en/validation.php
```php
<?php
return [
    'required' => 'The :field field is required',
    'email' => 'The :field field must be a valid email address',
    'min' => 'The :field field must be at least :min characters',
    'max' => 'The :field field must not exceed :max characters',
    'numeric' => 'The :field field must be a number',
    'confirmed' => 'The :field confirmation does not match'
];
```

#### config/locales/pt-br/validation.php
```php
<?php
return [
    'required' => 'O campo :field é obrigatório',
    'email' => 'O campo :field deve ser um endereço de email válido',
    'min' => 'O campo :field deve ter pelo menos :min caracteres',
    'max' => 'O campo :field não pode exceder :max caracteres',
    'numeric' => 'O campo :field deve ser um número',
    'confirmed' => 'A confirmação do campo :field não confere'
];
```

## 🌐 Middleware de Localização

### LocaleMiddleware
```php
<?php
namespace Express\Middleware\Core;

use Express\Middleware\Core\BaseMiddleware;
use Express\Support\I18n;

class LocaleMiddleware extends BaseMiddleware
{
    public function handle($request, $response, callable $next)
    {
        // Detectar locale da requisição
        $locale = $this->detectRequestLocale($request);

        if ($locale) {
            I18n::setLocale($locale);
        }

        // Adicionar helpers ao request
        $request->locale = I18n::getLocale();
        $request->t = function($key, $params = []) {
            return I18n::translate($key, $params);
        };

        return $next($request, $response);
    }

    private function detectRequestLocale($request): ?string
    {
        // 1. Header customizado
        $locale = $request->header('X-Locale');
        if ($locale && I18n::isSupported($locale)) {
            return $locale;
        }

        // 2. Query parameter
        $locale = $request->query('locale');
        if ($locale && I18n::isSupported($locale)) {
            return $locale;
        }

        // 3. Path prefix (/pt-br/api/users)
        $pathParts = explode('/', trim($request->path, '/'));
        if (!empty($pathParts[0]) && I18n::isSupported($pathParts[0])) {
            return $pathParts[0];
        }

        return null;
    }
}
```

## 🚀 Uso no Framework

### Inicialização
```php
use Express\Support\I18n;
use Express\Middleware\Core\LocaleMiddleware;

$app = new ApiExpress();

// Inicializar i18n
I18n::init([
    'default_locale' => 'en',
    'supported_locales' => ['en', 'pt-br', 'es'],
    'fallback_locale' => 'en',
    'auto_detect' => true
]);

// Middleware de localização
$app->use(LocaleMiddleware::create());
```

### Uso em Rotas
```php
$app->get('/welcome', function($request, $response) {
    return $response->json([
        'message' => I18n::t('messages.welcome'),
        'user_profile' => I18n::t('messages.user.profile'),
        'locale' => I18n::getLocale()
    ]);
});

// Com parâmetros
$app->get('/error', function($request, $response) {
    return $response->json([
        'error' => I18n::t('messages.api.error', ['error' => 'Database connection failed'])
    ]);
});
```

### Helper Functions
```php
// Função global para facilitar uso
function __(string $key, array $params = []): string
{
    return I18n::translate($key, $params);
}

// Uso simplificado
$message = __('messages.welcome');
$error = __('messages.api.error', ['error' => 'Invalid input']);
```

## 🔄 Mudança Dinâmica de Idioma

### API Endpoint
```php
$app->post('/locale', function($request, $response) {
    $locale = $request->body('locale');

    if (!I18n::isSupported($locale)) {
        return $response->status(400)->json([
            'error' => __('messages.api.invalid_locale'),
            'supported' => I18n::getSupportedLocales()
        ]);
    }

    I18n::setLocale($locale);
    $_SESSION['locale'] = $locale;

    return $response->json([
        'message' => __('messages.api.locale_changed'),
        'locale' => $locale
    ]);
});
```

### Frontend Integration
```javascript
// JavaScript para mudança de idioma
async function changeLanguage(locale) {
    const response = await fetch('/locale', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Locale': locale
        },
        body: JSON.stringify({ locale })
    });

    if (response.ok) {
        window.location.reload();
    }
}
```

## 📊 Performance e Cache

### Cache de Traduções
```php
class CachedI18n extends I18n
{
    private static $cache;

    protected static function loadTranslations(): void
    {
        $locale = self::$currentLocale;
        $cacheKey = "i18n_translations_{$locale}";

        if (self::$cache && $translations = self::$cache->get($cacheKey)) {
            self::$translations[$locale] = $translations;
            return;
        }

        parent::loadTranslations();

        if (self::$cache) {
            self::$cache->set($cacheKey, self::$translations[$locale], 3600);
        }
    }
}
```

### Lazy Loading
```php
class LazyI18n extends I18n
{
    private static array $loadedNamespaces = [];

    protected static function getTranslation(string $key, string $locale): ?string
    {
        $parts = explode('.', $key);
        $namespace = $parts[0];

        if (!isset(self::$loadedNamespaces[$locale][$namespace])) {
            self::loadNamespace($namespace, $locale);
        }

        return parent::getTranslation($key, $locale);
    }

    private static function loadNamespace(string $namespace, string $locale): void
    {
        $file = __DIR__ . "/../../config/locales/{$locale}/{$namespace}.php";

        if (file_exists($file)) {
            if (!isset(self::$translations[$locale])) {
                self::$translations[$locale] = [];
            }

            self::$translations[$locale][$namespace] = require $file;
            self::$loadedNamespaces[$locale][$namespace] = true;
        }
    }
}
```

## 🧪 Testes de Internacionalização

### Unit Tests
```php
class I18nTest extends TestCase
{
    public function setUp(): void
    {
        I18n::init([
            'default_locale' => 'en',
            'supported_locales' => ['en', 'pt-br'],
            'fallback_locale' => 'en'
        ]);
    }

    public function testBasicTranslation()
    {
        $this->assertEquals('Welcome', I18n::t('messages.welcome'));
    }

    public function testParameterReplacement()
    {
        $result = I18n::t('messages.api.error', ['error' => 'Test error']);
        $this->assertStringContains('Test error', $result);
    }

    public function testFallbackLocale()
    {
        I18n::setLocale('pt-br');
        $result = I18n::t('messages.nonexistent');

        // Should fallback to English
        $this->assertEquals('messages.nonexistent', $result);
    }

    public function testLocaleDetection()
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'pt-BR,pt;q=0.9,en;q=0.8';

        I18n::init(['auto_detect' => true]);

        $this->assertEquals('pt-br', I18n::getLocale());
    }
}
```

## 📚 Melhores Práticas

### 1. Nomenclatura de Chaves
```php
// ✅ Bom - hierárquico e descritivo
'user.profile.edit.title'
'api.validation.email.invalid'
'auth.login.form.submit'

// ❌ Evitar - genérico demais
'text1'
'msg'
'error'
```

### 2. Organização de Arquivos
```
locales/
├── en/
│   ├── common.php      # Textos comuns
│   ├── auth.php        # Autenticação
│   ├── user.php        # Usuário
│   ├── api.php         # API responses
│   └── validation.php  # Validações
```

### 3. Pluralização
```php
// Suporte a pluralização
'items.count' => [
    0 => 'No items',
    1 => '1 item',
    'other' => ':count items'
]

// Uso
echo I18n::plural('items.count', $count, ['count' => $count]);
```

### 4. Formatação Regional
```php
class RegionalFormatter
{
    public static function formatDate(\DateTime $date, string $locale): string
    {
        $formatter = new IntlDateFormatter(
            $locale,
            IntlDateFormatter::MEDIUM,
            IntlDateFormatter::NONE
        );

        return $formatter->format($date);
    }

    public static function formatCurrency(float $amount, string $currency, string $locale): string
    {
        $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
        return $formatter->formatCurrency($amount, $currency);
    }
}
```

## 🌍 Recursos Avançados

### 1. Right-to-Left (RTL) Support
```php
class LocaleHelper
{
    private static array $rtlLocales = ['ar', 'he', 'fa'];

    public static function isRtl(string $locale): bool
    {
        return in_array($locale, self::$rtlLocales);
    }

    public static function getDirection(string $locale): string
    {
        return self::isRtl($locale) ? 'rtl' : 'ltr';
    }
}
```

### 2. Time Zone Support
```php
class TimeZoneMiddleware extends BaseMiddleware
{
    public function handle($request, $response, callable $next)
    {
        $timezone = $request->header('X-Timezone') ?? 'UTC';

        if (in_array($timezone, timezone_identifiers_list())) {
            date_default_timezone_set($timezone);
            $request->timezone = $timezone;
        }

        return $next($request, $response);
    }
}
```

## 📖 Documentação Multilíngue

O próprio framework mantém documentação em múltiplos idiomas:

- **Português (pt-br):** Documentação completa em português
- **English (en):** Full English documentation
- **Español (es):** Documentación en español (planejado)

Contribuições para tradução da documentação são bem-vindas!
