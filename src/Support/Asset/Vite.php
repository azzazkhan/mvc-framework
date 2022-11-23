<?php

namespace Illuminate\Support\Asset;

use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class Vite
{
    protected static $devScriptInjected = false;

    /**
     * Parses passed single or multiple built vite asset files.
     * 
     * @param  string|array  $asset
     * @return void
     */
    public static function render(string|array $assets)
    {
        $isProduction = config('app.env', 'local') == 'production';

        if (!$isProduction)
            return static::injectDevScripts($assets);

        $files = [];

        foreach (Arr::wrap($assets) as $asset)
            $files = array_merge($files, static::parseAsset($asset));

        foreach ($files as $file)
            echo "$file\n";
    }

    /**
     * Gets the vite dev server initialization script.
     * 
     * @param  bool  $echo
     * @return void|string
     */
    protected static function injectDevScripts(string|array $assets = [])
    {
        $vite = sprintf('http://%s:%s', config('vite.host', '127.0.0.1'), config('vite.port', '5173'));

        // Dev script is already injected
        if (!static::$devScriptInjected) {
            echo '<script type="module" src="' . $vite . '/@vite/client"></script>' . "\n";

            // Mark the dev client injection flag so we might not inject them again
            // accidentally
            static::$devScriptInjected = true;
        }

        foreach (Arr::wrap($assets) as $asset)
            echo static::getMarkup(sprintf('%s/%s', $vite, $asset)) . "\n";
    }

    /**
     * Parse an asset and all of its css and imports.
     * 
     * @param  string  $asset
     * @return array
     * 
     * @throws \Exception
     */
    protected static function parseAsset(string $asset): array
    {
        $manifest = static::getAssetEntry($asset);

        // The asset's configuration was not found in Vite manifest
        if (!$manifest)
            throw new Exception("The asset [${asset}] was not found in Vite manifest. Did you added it to input path in Vite config file?");

        $files = [static::getMarkup(sprintf('/dist/%s', $manifest['file']))];

        $assetCss = Arr::wrap($manifest['css'] ?? []);
        $assetImports = Arr::wrap($manifest['imports'] ?? []);

        foreach (array_values(array_merge($assetCss, $assetImports)) as $filepath) {
            $childAsset = static::getMarkup(sprintf('/dist/%s', $filepath));

            if ($childAsset) continue;

            $files[] = $childAsset;
        }

        return $files;
    }

    /**
     * Get the markup for importing asset in frontend.
     * 
     * @param  string  $assetPath
     * @return string|null
     */
    protected static function getMarkup(string $assetPath): string|null
    {
        return match (File::extension($assetPath)) {
            'css', 'scss', 'sass'   => sprintf('<link rel="stylesheet" href="%s" type="text/css" />', $assetPath),
            'js', 'ts', 'mjs'       => sprintf('<script type="module" src="%s"></script>', $assetPath),
            default => null,
        };
    }

    /**
     * Gets asset entry from Vite's generated manifest file.
     * 
     * @param  string  $asset
     * @return array
     * 
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected static function getAssetEntry(string $asset): array|null
    {
        $manifest = sprintf('%s/public/dist/manifest.json', app('base_path'));

        if (!File::exists($manifest))
            throw new FileNotFoundException('Vite manifest not found!');

        $manifest = json_decode(File::get($manifest), true);

        if (!array_key_exists($asset, $manifest)) return null;

        return $manifest[$asset];
    }
}
