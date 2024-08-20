<?php

// namespace

use YOOtheme\Config;
use YOOtheme\Path;
use YOOtheme\Translator;

class TranslationListener
{
    static function initCustomizer(Config $config, Translator $translator)
    {
        $translator->addResource(Path::get("../languages/{$config('locale.code')}.json"));
    }
}
