<?php

namespace Ekremogul\TenaEditor;

use Filament\PluginServiceProvider;
use Spatie\LaravelPackageTools\Package;

class TenaEditorServiceProvider extends PluginServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('tena-editor')
            ->hasConfigFile()
            ->hasViews();
    }
}
