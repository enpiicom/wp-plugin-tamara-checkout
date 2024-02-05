<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInita69b5e188e4b34c68d431efd8980cd2c
{
    public static $files = array (
        '05606250504f1174fd702cad64105781' => __DIR__ . '/..' . '/enpii/enpii-base/src/Foundation/Support/helpers-utils.php',
        '7d5f78e8cb8025ac305683f3d838a3fc' => __DIR__ . '/..' . '/enpii/enpii-base/src/Foundation/helpers-wp-app.php',
        'ab9c87db46218bf6a8d5a29aea0ad298' => __DIR__ . '/..' . '/enpii/enpii-base/src/Foundation/helpers-overrides.php',
        '0f59fad7c9b61fab8c403fdbffb776bb' => __DIR__ . '/..' . '/enpii/enpii-base/enpii-base-bootstrap.php',
        'd784741bc6b753c2977bb46f2ba02513' => __DIR__ . '/..' . '/enpii/enpii-base/enpii-base-init.php',
    );

    public static $prefixLengthsPsr4 = array (
        'T' => 
        array (
            'Tamara_Checkout\\Tests\\Unit\\' => 27,
            'Tamara_Checkout\\Tests\\' => 22,
            'Tamara_Checkout\\Deps\\' => 21,
            'Tamara_Checkout\\' => 16,
        ),
        'E' => 
        array (
            'Enpii_Base\\Deps\\' => 16,
            'Enpii_Base\\' => 11,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Tamara_Checkout\\Tests\\Unit\\' => 
        array (
            0 => __DIR__ . '/../..' . '/tests/unit',
        ),
        'Tamara_Checkout\\Tests\\' => 
        array (
            0 => __DIR__ . '/../..' . '/tests',
        ),
        'Tamara_Checkout\\Deps\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src-deps',
        ),
        'Tamara_Checkout\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
        'Enpii_Base\\Deps\\' => 
        array (
            0 => __DIR__ . '/..' . '/enpii/enpii-base/src-deps',
        ),
        'Enpii_Base\\' => 
        array (
            0 => __DIR__ . '/..' . '/enpii/enpii-base/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInita69b5e188e4b34c68d431efd8980cd2c::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInita69b5e188e4b34c68d431efd8980cd2c::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInita69b5e188e4b34c68d431efd8980cd2c::$classMap;

        }, null, ClassLoader::class);
    }
}
