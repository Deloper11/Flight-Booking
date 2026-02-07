<?php
/**
 * PHPMailer - PHP email creation and transport class.
 * PHP Version 8.1+
 * @package PHPMailer
 * @see https://github.com/PHPMailer/PHPMailer/ The PHPMailer GitHub project
 * @author Marcus Bointon (Synchro/coolbru) <phpmailer@synchromedia.co.uk>
 * @author Jim Jagielski (jimjag) <jimjag@gmail.com>
 * @author Andy Prevost (codeworxtech) <codeworxtech@users.sourceforge.net>
 * @author Brent R. Matzelle (original founder)
 * @copyright 2012 - 2026 Marcus Bointon
 * @copyright 2010 - 2012 Jim Jagielski
 * @copyright 2004 - 2009 Andy Prevost
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 * @note This program is distributed in the hope that it will be useful - WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.
 * @warning This script is for development purposes only and should not be deployed in production.
 * Always store credentials in secure environment variables or encrypted configuration files.
 */

/**
 * Get an OAuth2 token from an OAuth2 provider.
 * * Install this script on your server so that it's accessible
 * as [https/http]://<yourdomain>/<folder>/get_oauth_token.php
 * e.g.: https://yourdomain.com/phpmailer/get_oauth_token.php
 * * Ensure dependencies are installed with 'composer install'
 * * Set up an app in your Google/Yahoo/Microsoft account
 * * Set the script address as the app's redirect URL
 * If no refresh token is obtained when running this file,
 * revoke access to your app and run the script again.
 */

namespace PHPMailer\PHPMailer;

// Enable strict types for better type safety
declare(strict_types=1);

/**
 * Aliases for League Provider Classes
 * Make sure you have added these to your composer.json and run `composer install`
 * Plenty to choose from here:
 * @see http://oauth2-client.thephpleague.com/providers/thirdparty/
 */
// @see https://github.com/thephpleague/oauth2-google
use League\OAuth2\Client\Provider\Google;
// @see https://packagist.org/packages/hayageek/oauth2-yahoo
use Hayageek\OAuth2\Client\Provider\Yahoo;
// @see https://github.com/stevenmaguire/oauth2-microsoft
use Stevenmaguire\OAuth2\Client\Provider\Microsoft;
// @see https://github.com/greew/oauth2-azure-provider
use TheNetworg\OAuth2\Client\Provider\Azure;

// Error reporting for development (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', '0'); // Set to '1' for debugging only
ini_set('log_errors', '1');

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Start session with secure settings
session_set_cookie_params([
    'lifetime' => 3600,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'] ?? '',
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Strict'
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Regenerate session ID to prevent fixation
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

// Configuration - Load from environment variables or config file in production
$config = [
    'Google' => [
        'clientId' => $_ENV['GOOGLE_CLIENT_ID'] ?? 'RANDOMCHARS-----duv1n2.apps.googleusercontent.com',
        'clientSecret' => $_ENV['GOOGLE_CLIENT_SECRET'] ?? 'RANDOMCHARS-----lGyjPcRtvP',
        'scopes' => [
            'https://mail.google.com/',
            'https://www.googleapis.com/auth/gmail.send'
        ]
    ],
    'Microsoft' => [
        'clientId' => $_ENV['MICROSOFT_CLIENT_ID'] ?? 'RANDOMCHARS-----5678-90ab-cdef',
        'clientSecret' => $_ENV['MICROSOFT_CLIENT_SECRET'] ?? 'RANDOMCHARS-----wxyz-9876',
        'scopes' => [
            'https://outlook.office.com/IMAP.AccessAsUser.All',
            'https://outlook.office.com/SMTP.Send',
            'offline_access'
        ]
    ],
    'Yahoo' => [
        'clientId' => $_ENV['YAHOO_CLIENT_ID'] ?? 'RANDOMCHARS-----1234-5678',
        'clientSecret' => $_ENV['YAHOO_CLIENT_SECRET'] ?? 'RANDOMCHARS-----abcd-efgh',
        'scopes' => []
    ],
    'Azure' => [
        'clientId' => $_ENV['AZURE_CLIENT_ID'] ?? 'RANDOMCHARS-----1234-5678-90ab-cdef',
        'clientSecret' => $_ENV['AZURE_CLIENT_SECRET'] ?? 'RANDOMCHARS-----ijkl-mnop-qrst',
        'tenant' => $_ENV['AZURE_TENANT_ID'] ?? 'common',
        'scopes' => [
            'https://outlook.office.com/IMAP.AccessAsUser.All',
            'https://outlook.office.com/SMTP.Send',
            'offline_access'
        ]
    ]
];

// If this automatic URL doesn't work, set it yourself manually
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
$redirectUri = $protocol . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '');
// Alternative: Set manually if needed
// $redirectUri = 'https://yourdomain.com/phpmailer/get_oauth_token.php';

// Check if Composer autoloader exists
$autoloader = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoloader)) {
    die('<h3>Error: Composer dependencies not installed</h3>
         <p>Please run: <code>composer install</code> in the PHPMailer directory.</p>
         <p>If you don\'t have Composer, download it from: <a href="https://getcomposer.org/">getcomposer.org</a></p>');
}

require $autoloader;

/**
 * Display provider selection page
 */
function showProviderSelection(): void
{
    $providers = ['Google', 'Microsoft', 'Yahoo', 'Azure'];
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="robots" content="noindex,nofollow">
        <title>PHPMailer OAuth2 Token Generator</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
                max-width: 800px;
                margin: 0 auto;
                padding: 20px;
                line-height: 1.6;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .container {
                background: white;
                padding: 2rem;
                border-radius: 10px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            }
            h1 {
                color: #333;
                margin-bottom: 1.5rem;
                text-align: center;
            }
            .provider-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 1rem;
                margin-bottom: 2rem;
            }
            .provider-btn {
                display: block;
                padding: 1rem;
                text-align: center;
                background: #f8f9fa;
                border: 2px solid #dee2e6;
                border-radius: 8px;
                text-decoration: none;
                color: #495057;
                font-weight: 600;
                transition: all 0.3s ease;
            }
            .provider-btn:hover {
                background: #007bff;
                color: white;
                border-color: #007bff;
                transform: translateY(-2px);
            }
            .warning {
                background: #fff3cd;
                border: 1px solid #ffeaa7;
                border-radius: 5px;
                padding: 1rem;
                margin: 1rem 0;
                font-size: 0.9rem;
            }
            .warning strong {
                color: #856404;
            }
            .instructions {
                background: #e7f5ff;
                border: 1px solid #a5d8ff;
                border-radius: 5px;
                padding: 1rem;
                margin: 1rem 0;
                font-size: 0.9rem;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🔐 PHPMailer OAuth2 Token Generator</h1>
            
            <div class="warning">
                <strong>⚠️ Security Notice:</strong> This script is for development use only. 
                Always store credentials in environment variables in production.
            </div>
            
            <div class="instructions">
                <p><strong>Instructions:</strong></p>
                <ol>
                    <li>Select your email provider below</li>
                    <li>You'll be redirected to the provider's authentication page</li>
                    <li>After authorization, your refresh token will be displayed</li>
                    <li>Store the refresh token securely in your application</li>
                </ol>
            </div>
            
            <div class="provider-grid">
                <?php foreach ($providers as $provider): ?>
                    <a href="?provider=<?= htmlspecialchars($provider) ?>" class="provider-btn">
                        <?= htmlspecialchars($provider) ?>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <div class="instructions">
                <p><strong>Setup Required:</strong></p>
                <ol>
                    <li>Create an OAuth2 app in your provider's developer console</li>
                    <li>Set the redirect URI to: <code><?= htmlspecialchars($redirectUri) ?></code></li>
                    <li>Copy your Client ID and Client Secret to the configuration</li>
                </ol>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

/**
 * Display error page
 */
function showError(string $message): void
{
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Error - PHPMailer OAuth2</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                margin: 0;
                background: #f8f9fa;
            }
            .error-container {
                background: white;
                padding: 2rem;
                border-radius: 10px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.1);
                max-width: 500px;
                text-align: center;
            }
            .error-icon {
                font-size: 4rem;
                color: #dc3545;
                margin-bottom: 1rem;
            }
            .error-message {
                color: #721c24;
                background: #f8d7da;
                border: 1px solid #f5c6cb;
                border-radius: 5px;
                padding: 1rem;
                margin: 1rem 0;
                font-family: monospace;
                word-break: break-all;
            }
            .btn-back {
                display: inline-block;
                padding: 0.5rem 1rem;
                background: #007bff;
                color: white;
                text-decoration: none;
                border-radius: 5px;
                margin-top: 1rem;
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="error-icon">❌</div>
            <h2>Authentication Error</h2>
            <div class="error-message"><?= htmlspecialchars($message) ?></div>
            <p>Please try again or check your OAuth2 configuration.</p>
            <a href="<?= htmlspecialchars(basename(__FILE__)) ?>" class="btn-back">← Back to Provider Selection</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

/**
 * Display success page with token
 */
function showSuccess(string $refreshToken, string $providerName): void
{
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Success - PHPMailer OAuth2</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                margin: 0;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            }
            .success-container {
                background: white;
                padding: 2rem;
                border-radius: 10px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                max-width: 600px;
                width: 90%;
            }
            .success-icon {
                font-size: 4rem;
                color: #28a745;
                margin-bottom: 1rem;
                text-align: center;
            }
            .token-container {
                background: #e8f5e9;
                border: 1px solid #c3e6cb;
                border-radius: 5px;
                padding: 1rem;
                margin: 1rem 0;
                position: relative;
            }
            .token {
                font-family: 'Courier New', monospace;
                word-break: break-all;
                color: #155724;
                font-size: 0.9rem;
                line-height: 1.4;
            }
            .copy-btn {
                position: absolute;
                top: 0.5rem;
                right: 0.5rem;
                background: #28a745;
                color: white;
                border: none;
                padding: 0.25rem 0.5rem;
                border-radius: 3px;
                cursor: pointer;
                font-size: 0.8rem;
            }
            .instructions {
                background: #fff3cd;
                border: 1px solid #ffeaa7;
                border-radius: 5px;
                padding: 1rem;
                margin: 1rem 0;
                font-size: 0.9rem;
            }
        </style>
        <script>
            function copyToClipboard(text) {
                navigator.clipboard.writeText(text).then(() => {
                    const btn = document.querySelector('.copy-btn');
                    btn.textContent = 'Copied!';
                    setTimeout(() => {
                        btn.textContent = 'Copy';
                    }, 2000);
                });
            }
        </script>
    </head>
    <body>
        <div class="success-container">
            <div class="success-icon">✅</div>
            <h2 style="text-align: center;">Authentication Successful!</h2>
            <p style="text-align: center;">Your <strong><?= htmlspecialchars($providerName) ?></strong> OAuth2 refresh token:</p>
            
            <div class="token-container">
                <button class="copy-btn" onclick="copyToClipboard('<?= htmlspecialchars($refreshToken) ?>')">
                    Copy
                </button>
                <div class="token" id="refreshToken"><?= htmlspecialchars($refreshToken) ?></div>
            </div>
            
            <div class="instructions">
                <p><strong>⚠️ Important Security Instructions:</strong></p>
                <ol>
                    <li><strong>Store this token securely</strong> in environment variables or encrypted storage</li>
                    <li><strong>Never commit</strong> this token to version control</li>
                    <li><strong>Delete this script</strong> from your production server after use</li>
                    <li>Use this token in PHPMailer like:<br>
                        <code style="font-size: 0.8rem;">$mail->setOAuth(new OAuth([
    'provider' => new <?= htmlspecialchars($providerName) ?>(),
    'clientId' => 'YOUR_CLIENT_ID',
    'clientSecret' => 'YOUR_CLIENT_SECRET',
    'refreshToken' => '<?= substr(htmlspecialchars($refreshToken), 0, 20) ?>...',
    'userName' => 'YOUR_EMAIL'
]));</code>
                    </li>
                </ol>
            </div>
            
            <p style="text-align: center; margin-top: 1.5rem;">
                <a href="<?= htmlspecialchars(basename(__FILE__)) ?>" style="color: #007bff; text-decoration: none;">
                    ← Back to Provider Selection
                </a>
            </p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Main execution logic
try {
    // If no provider selected, show selection page
    if (!isset($_GET['code']) && !isset($_GET['provider'])) {
        showProviderSelection();
    }

    // Get provider name
    $providerName = $_GET['provider'] ?? $_SESSION['provider'] ?? '';
    
    // Validate provider
    $validProviders = ['Google', 'Microsoft', 'Yahoo', 'Azure'];
    if (!in_array($providerName, $validProviders, true)) {
        throw new \RuntimeException('Invalid provider selected. Only Google, Microsoft, Yahoo, and Azure are supported.');
    }

    // Store provider in session for callback
    if (!isset($_GET['code']) && isset($_GET['provider'])) {
        $_SESSION['provider'] = $providerName;
    }

    // Check if provider config exists
    if (!isset($config[$providerName])) {
        throw new \RuntimeException("Configuration for {$providerName} not found.");
    }

    $providerConfig = $config[$providerName];
    
    // Create provider instance
    $params = [
        'clientId' => $providerConfig['clientId'],
        'clientSecret' => $providerConfig['clientSecret'],
        'redirectUri' => $redirectUri,
        'accessType' => 'offline',
        'prompt' => 'consent' // Force consent screen to get refresh token
    ];

    $options = ['scope' => $providerConfig['scopes'] ?? []];
    
    switch ($providerName) {
        case 'Google':
            $provider = new Google($params);
            break;
        case 'Yahoo':
            $provider = new Yahoo($params);
            break;
        case 'Microsoft':
            $provider = new Microsoft($params);
            break;
        case 'Azure':
            $params['tenantId'] = $providerConfig['tenant'];
            $provider = new Azure($params);
            break;
        default:
            throw new \RuntimeException('Unknown provider');
    }

    // If we don't have an authorization code, get one
    if (!isset($_GET['code'])) {
        $authUrl = $provider->getAuthorizationUrl($options);
        $_SESSION['oauth2state'] = $provider->getState();
        header('Location: ' . $authUrl);
        exit;
    }

    // Verify state parameter to prevent CSRF
    if (empty($_GET['state']) || empty($_SESSION['oauth2state']) || 
        !hash_equals($_SESSION['oauth2state'], $_GET['state'])) {
        unset($_SESSION['oauth2state'], $_SESSION['provider']);
        throw new \RuntimeException('Invalid OAuth2 state parameter. Possible CSRF attack.');
    }

    // Clean up session
    unset($_SESSION['oauth2state'], $_SESSION['provider']);

    // Exchange authorization code for access token
    $token = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code']
    ]);

    // Get refresh token (some providers might not return one)
    $refreshToken = $token->getRefreshToken();
    
    if (!$refreshToken) {
        throw new \RuntimeException('No refresh token received. Please make sure to request "offline" access type.');
    }

    // Display success page with the refresh token
    showSuccess($refreshToken, $providerName);

} catch (\Exception $e) {
    // Log error for debugging (in production, log to file instead)
    error_log('OAuth2 Error: ' . $e->getMessage());
    
    // Show user-friendly error
    showError($e->getMessage());
}
