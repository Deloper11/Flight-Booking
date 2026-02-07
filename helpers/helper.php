<?php

declare(strict_types=1);

namespace App\Helpers;

use RuntimeException;
use InvalidArgumentException;

/**
 * 2026 Advanced Subview Helper
 * 
 * Features:
 * - Strict type declarations
 * - Caching system
 * - Template variables injection
 * - Security validation
 * - Performance monitoring
 * - PSR-12 compliant
 * 
 * @package App\Helpers
 * @version 2026.1.0
 */
class ViewHelper
{
    private const ALLOWED_EXTENSIONS = ['php', 'html', 'phtml'];
    private const MAX_FILE_SIZE = 5242880; // 5MB
    
    private static array $cache = [];
    private static array $metrics = [
        'loads' => 0,
        'cache_hits' => 0,
        'cache_misses' => 0,
        'total_time' => 0.0
    ];

    /**
     * Render a subview with optional data injection and caching
     * 
     * @param string $file Relative path to subview file
     * @param array $data Associative array of variables to inject
     * @param bool $useCache Enable/disable caching
     * @param int $cacheTTL Cache time-to-live in seconds
     * @return string Rendered content
     * 
     * @throws InvalidArgumentException If file is invalid
     * @throws RuntimeException If file cannot be loaded
     */
    public static function render(
        string $file, 
        array $data = [], 
        bool $useCache = true,
        int $cacheTTL = 3600
    ): string {
        $startTime = microtime(true);
        
        // Validate input
        self::validateFile($file);
        
        // Generate cache key
        $cacheKey = $useCache ? self::generateCacheKey($file, $data) : null;
        
        // Check cache
        if ($useCache && isset(self::$cache[$cacheKey])) {
            $cacheEntry = self::$cache[$cacheKey];
            
            if (time() - $cacheEntry['timestamp'] < $cacheTTL) {
                self::$metrics['cache_hits']++;
                self::recordMetrics($startTime);
                return $cacheEntry['content'];
            }
            
            unset(self::$cache[$cacheKey]);
        }
        
        self::$metrics['cache_misses']++;
        
        // Prepare file path
        $filePath = self::buildFilePath($file);
        
        // Extract data to local scope
        extract($data, EXTR_SKIP);
        
        // Start output buffering
        ob_start();
        
        try {
            // Include the file
            include $filePath;
            
            // Get the buffered content
            $content = ob_get_clean();
            
            // Cache the content if enabled
            if ($useCache && $cacheKey) {
                self::$cache[$cacheKey] = [
                    'content' => $content,
                    'timestamp' => time(),
                    'file' => $file,
                    'data_keys' => array_keys($data)
                ];
                
                // Clean old cache entries (LRU)
                self::cleanCache();
            }
            
            self::recordMetrics($startTime);
            
            return $content;
            
        } catch (Throwable $e) {
            ob_end_clean();
            throw new RuntimeException(
                "Failed to render subview '{$file}': " . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Render subview and echo immediately
     * 
     * @param string $file Relative path to subview file
     * @param array $data Associative array of variables to inject
     * @param bool $useCache Enable/disable caching
     */
    public static function echo(
        string $file, 
        array $data = [], 
        bool $useCache = true
    ): void {
        echo self::render($file, $data, $useCache);
    }

    /**
     * Render subview with layout wrapper
     * 
     * @param string $file Relative path to subview file
     * @param string $layout Layout file to wrap content
     * @param array $data Associative array of variables to inject
     * @param array $layoutData Additional data for layout
     * @return string Rendered content with layout
     */
    public static function renderWithLayout(
        string $file,
        string $layout = 'default',
        array $data = [],
        array $layoutData = []
    ): string {
        $content = self::render($file, $data);
        
        $layoutData['content'] = $content;
        $layoutData['view'] = $file;
        
        return self::render("layouts/{$layout}", $layoutData);
    }

    /**
     * Check if subview exists
     * 
     * @param string $file Relative path to subview file
     * @return bool True if file exists and is readable
     */
    public static function exists(string $file): bool
    {
        try {
            $filePath = self::buildFilePath($file);
            return file_exists($filePath) && is_readable($filePath);
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * Get list of all available subviews in a directory
     * 
     * @param string $directory Directory to scan (relative to sub-views)
     * @param string $pattern File pattern to match
     * @return array List of subview files
     */
    public static function list(string $directory = '', string $pattern = '*.php'): array
    {
        $basePath = __DIR__ . '/../sub-views/';
        $scanPath = $basePath . ltrim($directory, '/');
        
        if (!is_dir($scanPath)) {
            return [];
        }
        
        $files = glob(rtrim($scanPath, '/') . '/' . $pattern);
        
        return array_map(function($file) use ($basePath) {
            return str_replace($basePath, '', $file);
        }, $files);
    }

    /**
     * Clear the subview cache
     * 
     * @param string|null $pattern Clear only matching files (glob pattern)
     * @return int Number of cache entries cleared
     */
    public static function clearCache(?string $pattern = null): int
    {
        if ($pattern === null) {
            $count = count(self::$cache);
            self::$cache = [];
            return $count;
        }
        
        $cleared = 0;
        foreach (self::$cache as $key => $entry) {
            if (fnmatch($pattern, $entry['file'])) {
                unset(self::$cache[$key]);
                $cleared++;
            }
        }
        
        return $cleared;
    }

    /**
     * Get performance metrics
     * 
     * @return array Performance metrics array
     */
    public static function getMetrics(): array
    {
        return array_merge(self::$metrics, [
            'cache_size' => count(self::$cache),
            'avg_load_time' => self::$metrics['loads'] > 0 
                ? self::$metrics['total_time'] / self::$metrics['loads'] 
                : 0,
            'cache_hit_rate' => self::$metrics['loads'] > 0
                ? (self::$metrics['cache_hits'] / self::$metrics['loads']) * 100
                : 0
        ]);
    }

    /**
     * Reset performance metrics
     */
    public static function resetMetrics(): void
    {
        self::$metrics = [
            'loads' => 0,
            'cache_hits' => 0,
            'cache_misses' => 0,
            'total_time' => 0.0
        ];
    }

    /**
     * Build the absolute file path with security checks
     * 
     * @param string $file Relative file path
     * @return string Absolute file path
     * @throws InvalidArgumentException If path is invalid
     */
    private static function buildFilePath(string $file): string
    {
        $baseDir = realpath(__DIR__ . '/../sub-views');
        
        if ($baseDir === false) {
            throw new RuntimeException('Base sub-views directory does not exist');
        }
        
        $requestedPath = realpath($baseDir . '/' . ltrim($file, '/'));
        
        // Security check: ensure the file is within the base directory
        if ($requestedPath === false || strpos($requestedPath, $baseDir) !== 0) {
            throw new InvalidArgumentException(
                "Invalid subview path: '{$file}' is outside the allowed directory"
            );
        }
        
        return $requestedPath;
    }

    /**
     * Validate the subview file
     * 
     * @param string $file File path to validate
     * @throws InvalidArgumentException If validation fails
     */
    private static function validateFile(string $file): void
    {
        // Check for empty file name
        if (empty($file)) {
            throw new InvalidArgumentException('Subview file name cannot be empty');
        }
        
        // Check for null byte injection
        if (strpos($file, "\0") !== false) {
            throw new InvalidArgumentException('Subview file name contains null bytes');
        }
        
        // Check for directory traversal
        if (preg_match('/\.\.(\/|\\\)/', $file)) {
            throw new InvalidArgumentException('Subview file name contains directory traversal');
        }
        
        // Check file extension
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        if (!in_array(strtolower($extension), self::ALLOWED_EXTENSIONS, true)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Subview file extension must be one of: %s',
                    implode(', ', self::ALLOWED_EXTENSIONS)
                )
            );
        }
        
        // Build file path for additional checks
        $filePath = self::buildFilePath($file);
        
        // Check file size limit
        if (file_exists($filePath) && filesize($filePath) > self::MAX_FILE_SIZE) {
            throw new InvalidArgumentException(
                sprintf(
                    'Subview file exceeds maximum size of %d bytes',
                    self::MAX_FILE_SIZE
                )
            );
        }
    }

    /**
     * Generate cache key for file and data
     * 
     * @param string $file File path
     * @param array $data Data array
     * @return string Cache key
     */
    private static function generateCacheKey(string $file, array $data): string
    {
        return hash('xxh3', $file . serialize($data));
    }

    /**
     * Clean old cache entries (LRU strategy)
     * 
     * @param int $maxEntries Maximum cache entries to keep
     */
    private static function cleanCache(int $maxEntries = 100): void
    {
        if (count(self::$cache) > $maxEntries) {
            // Sort by timestamp (oldest first)
            uasort(self::$cache, function($a, $b) {
                return $a['timestamp'] <=> $b['timestamp'];
            });
            
            // Remove oldest entries
            self::$cache = array_slice(self::$cache, 0, $maxEntries, true);
        }
    }

    /**
     * Record performance metrics
     * 
     * @param float $startTime Start time from microtime(true)
     */
    private static function recordMetrics(float $startTime): void
    {
        self::$metrics['loads']++;
        self::$metrics['total_time'] += microtime(true) - $startTime;
    }

    /**
     * Alias for backward compatibility
     * 
     * @deprecated Use ViewHelper::render() instead
     */
    public static function subview(string $file): void
    {
        self::echo($file);
    }
}

/**
 * Global helper function for convenience
 * 
 * @param string $file Relative path to subview file
 * @param array $data Associative array of variables to inject
 * @param bool $useCache Enable/disable caching
 * @return string Rendered content
 */
function subview(string $file, array $data = [], bool $useCache = true): string
{
    return ViewHelper::render($file, $data, $useCache);
}

/**
 * Global helper function to echo subview
 * 
 * @param string $file Relative path to subview file
 * @param array $data Associative array of variables to inject
 * @param bool $useCache Enable/disable caching
 */
function subview_echo(string $file, array $data = [], bool $useCache = true): void
{
    ViewHelper::echo($file, $data, $useCache);
}
