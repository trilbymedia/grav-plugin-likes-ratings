<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit1db22500a382ce5980b5e2d805c76d49
{
    public static $prefixLengthsPsr4 = array (
        'G' => 
        array (
            'Grav\\Plugin\\LikesRatings\\' => 25,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Grav\\Plugin\\LikesRatings\\' => 
        array (
            0 => __DIR__ . '/../..' . '/classes',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit1db22500a382ce5980b5e2d805c76d49::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit1db22500a382ce5980b5e2d805c76d49::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
