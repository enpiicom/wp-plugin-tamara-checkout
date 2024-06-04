<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInita3804dc8049b89c915c1055a97b9702a
{
    public static $files = array (
        '0a04fd8b6cb0d1ef5b4816cca310de45' => __DIR__ . '/..' . '/enpii/enpii-base/src/Foundation/Support/helpers-utils.php',
        '1a9d4c5b6fdccdf53758bb42093f047e' => __DIR__ . '/..' . '/enpii/enpii-base/src/Foundation/helpers-wp-app.php',
        'd52cbd35db56d8db88f4e6d99ac7ba97' => __DIR__ . '/..' . '/enpii/enpii-base/src/Foundation/helpers-overrides.php',
        'd87cfd2ed7cce067b66b8a69d0d19e97' => __DIR__ . '/..' . '/enpii/enpii-base/enpii-base-bootstrap.php',
        '1e97b44e360a44a4786951dfe5cec916' => __DIR__ . '/..' . '/enpii/enpii-base/enpii-base-init.php',
    );

    public static $prefixLengthsPsr4 = array (
        'T' => 
        array (
            'Tamara_Checkout\\Deps\\' => 21,
            'Tamara_Checkout\\' => 16,
        ),
        'M' => 
        array (
            'McAskill\\Composer\\' => 18,
        ),
        'E' => 
        array (
            'Enpii_Base\\Deps\\' => 16,
            'Enpii_Base\\' => 11,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Tamara_Checkout\\Deps\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src-deps',
        ),
        'Tamara_Checkout\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
        'McAskill\\Composer\\' => 
        array (
            0 => __DIR__ . '/..' . '/mcaskill/composer-exclude-files/src',
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
            $loader->prefixLengthsPsr4 = ComposerStaticInita3804dc8049b89c915c1055a97b9702a::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInita3804dc8049b89c915c1055a97b9702a::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInita3804dc8049b89c915c1055a97b9702a::$classMap;

        }, null, ClassLoader::class);
    }
}
