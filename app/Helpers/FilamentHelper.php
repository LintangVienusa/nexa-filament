<?php

namespace App\Helpers;

use Filament\Resources\Resource;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;

class FilamentHelper
{
    public static function getResources(): array
    {
        $resourceNamespace = 'App\\Filament\\Resources';
        $resourcePath = app_path('Filament/Resources');

        $filesystem = new Filesystem();
        $resources = [];

        foreach ($filesystem->allFiles($resourcePath) as $file) {
            $relativePath = str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname());
            $className = $resourceNamespace . '\\' . $relativePath;

            if (class_exists($className) && is_subclass_of($className, Resource::class)) {
                $label = $className::getModelLabel() ?? class_basename($className);

                $base = preg_replace('/Resource$/', '', class_basename($className));
                $key = Str::snake($base);

                $resources[$key] = Str::title($label);
            }
        }

        return $resources;
    }
}
