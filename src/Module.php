<?php

namespace Crud;

class Module
{
    public function getConfig()
    {
        return [
            'view_manager' => array(
                'template_path_stack' => array(
                    'crud' => __DIR__ . '/../view',
                ),
            ),
            'services' => [
                'factories' => [
                    'Crud\Service\Crud' => 'Crud\Service\Crud'
                ]
            ]
        ];
    }

    public function getAutoloaderConfig()
    {
    }
}