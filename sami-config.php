<?php

require dirname(__DIR__, 3) . '/vendor/autoload.php';

use Sami\{
    Parser\Filter\TrueFilter,
    Sami
};

$sami = new Sami(__DIR__ . '/src', array(
    'theme'                => 'github',
    'title'                => 'Db API',
    'build_dir'            => __DIR__.'/docs_ru',
    'cache_dir'            => __DIR__.'/cache',
    'template_dirs'        => [dirname(__DIR__, 3) . '/vendor/avtomon/sami-github']
));

$sami['filter'] = function () {
    return new TrueFilter();
};

return $sami;