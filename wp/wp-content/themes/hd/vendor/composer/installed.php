<?php return array(
    'root' => array(
        'name' => 'hd/theme',
        'pretty_version' => 'dev-master',
        'version' => 'dev-master',
        'reference' => 'e6f632b1b41cea0e957b710226e65c4245138d5d',
        'type' => 'wordpress-theme',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'dev' => true,
    ),
    'versions' => array(
        'hd/theme' => array(
            'pretty_version' => 'dev-master',
            'version' => 'dev-master',
            'reference' => 'e6f632b1b41cea0e957b710226e65c4245138d5d',
            'type' => 'wordpress-theme',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'openspout/openspout' => array(
            'pretty_version' => 'v5.3.0',
            'version' => '5.3.0.0',
            'reference' => 'bc0d2cdefa3fa90c7caceb778192654b20f414f7',
            'type' => 'library',
            'install_path' => __DIR__ . '/../openspout/openspout',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'roave/security-advisories' => array(
            'pretty_version' => 'dev-latest',
            'version' => 'dev-latest',
            'reference' => '5f6b924eae73ca732e81045a64e3e9298639e172',
            'type' => 'metapackage',
            'install_path' => null,
            'aliases' => array(
                0 => '9999999-dev',
            ),
            'dev_requirement' => true,
        ),
    ),
);
