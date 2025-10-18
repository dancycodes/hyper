<?php

namespace Dancycodes\Hyper\Routing\Discovery;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

/**
 * View Route Discovery Implementation
 *
 * Automatically registers routes for Blade view files based on filesystem location
 * and naming conventions. Scans directories for .blade.php files and creates GET
 * routes using Laravel's Route::view() method with auto-generated URIs and names.
 *
 * URI generation converts filesystem paths to kebab-case URL paths, with special
 * handling for index.blade.php files which map to their parent directory path.
 * Route names are generated from URI structure with optional prefix support.
 *
 * @see \Dancycodes\Hyper\Routing\Discovery\Discover
 */
class DiscoverViews
{
    /**
     * Discover and register routes for views in directory
     *
     * Recursively scans directory for .blade.php files and registers GET routes
     * for each view using auto-generated URIs and route names. Optional prefix
     * is prepended to all generated route names for namespacing.
     *
     * @param string $directory Absolute path to directory containing Blade views
     * @param string $prefix Optional route name prefix for namespacing discovered routes
     */
    public function in(string $directory, string $prefix = ''): void
    {
        $files = (new Finder)->files()->name('*.blade.php')->in($directory);

        /** @var \Illuminate\Support\Collection<int, SplFileInfo> $fileCollection */
        $fileCollection = collect($files);

        $fileCollection->each(function (SplFileInfo $file) use ($directory, $prefix) {
            $this->registerRouteForView($file, $directory, $prefix);
        });
    }

    /**
     * Register route for individual Blade view file
     *
     * Creates route registration using Laravel's Route::view() with auto-generated
     * view name, URI, and route name based on file location and naming conventions.
     *
     * @param SplFileInfo $file Blade view file information
     * @param string $directory Base directory for relative path calculation
     * @param string $prefix Route name prefix
     */
    protected function registerRouteForView(SplFileInfo $file, string $directory, string $prefix): void
    {
        $view = $this->determineView($file, $directory);
        $uri = $this->determineUri($file, $directory);
        $name = $this->determineName($file, $directory, $prefix);

        Route::view($uri, $view)->name($name);
    }

    /**
     * Determine Laravel view name from file path
     *
     * Converts absolute Blade file path to dot-notation view name by extracting
     * relative path from resources/views directory and replacing directory separators
     * with dots. Strips .blade.php extension.
     *
     * @param SplFileInfo $file Blade view file information
     * @param string $directory Base directory (unused in current implementation)
     *
     * @return string View name in dot notation (e.g., 'pages.users.index')
     */
    protected function determineView(SplFileInfo $file, string $directory): string
    {
        $viewPath = Str::of($file->getPathname())
            ->after(resource_path('views'))
            ->beforeLast('.blade.php')
            ->ltrim('/');

        return $viewPath->replace(DIRECTORY_SEPARATOR, '.');
    }

    /**
     * Determine route URI from file path
     *
     * Converts file path to kebab-case URI by extracting relative path from base
     * directory, converting directory and file names to kebab-case, and joining
     * with forward slashes. Special handling for index.blade.php files which map
     * to their parent directory URI.
     *
     * @param SplFileInfo $file Blade view file information
     * @param string $directory Base directory for relative path calculation
     *
     * @return string Generated URI path with leading slash
     */
    protected function determineUri(SplFileInfo $file, string $directory): string
    {
        $uri = Str::of($file->getPathname())
            ->after($directory)
            ->beforeLast('.blade.php');

        $uri = Str::replaceLast(DIRECTORY_SEPARATOR . 'index', DIRECTORY_SEPARATOR, (string) $uri);

        return collect(explode(DIRECTORY_SEPARATOR, $uri))
            ->map(function (string $uriSegment) {
                return Str::kebab($uriSegment);
            })
            ->join('/');
    }

    /**
     * Determine route name from file path
     *
     * Generates route name from URI structure with optional prefix. Root path
     * ('/') maps to 'home' or prefix-only name. Other paths convert forward
     * slashes to dots for hierarchical naming. Prefix is prepended with dot
     * separator when present.
     *
     * @param SplFileInfo $file Blade view file information
     * @param string $baseDirectory Base directory for relative path calculation
     * @param string $prefix Optional route name prefix
     *
     * @return string Generated route name in dot notation
     */
    protected function determineName(SplFileInfo $file, string $baseDirectory, string $prefix): string
    {
        $uri = $this->determineUri($file, $baseDirectory);

        if ($uri === '/') {
            return $prefix ?: 'home';
        }

        $name = Str::of($uri)
            ->after('/')
            ->replace('/', '.')
            ->rtrim('.');

        return $prefix ? "{$prefix}.{$name}" : $name;
    }
}
