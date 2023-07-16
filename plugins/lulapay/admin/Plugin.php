<?php namespace Lulapay\Admin;

use Backend;
use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    public function registerComponents()
    {
    }

    public function registerNavigation()
    {
        return [
            'admin' => [
                'label'       => 'Admin',
                'url'         => Backend::url('lulapay/admin/admins'),
                'icon'        => 'icon-users',
                'permissions' => ['lulapay.admins.*'],
                'order'       => 10,
                'sideMenu' => [
                    'admins' => [
                        'label' => 'Admins',
                        'icon'  => 'icon-users',
                        'url'   => Backend::url('lulapay/admin/admins'),
                        'permissions' => ['lulapay.admin.access_admins']
                    ],
                    'roles' => [
                        'label' => 'Roles',
                        'icon'  => 'icon-cog',
                        'url'   => Backend::url('lulapay/admin/roles'),
                        'permissions' => ['lulapay.admin.access_admin_roles']
                    ],
                ]
            ]
        ];
    }

    public function registerSettings()
    {
    }
}
