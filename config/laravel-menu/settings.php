<?php

return [
    'default' => [
        'auto_activate'    => true,
        'activate_parents' => true,
        'active_class'     => 'activ3e',
        'restful'          => true,
        'cascade_data'     => true,
        'rest_base'        => '',      // string|array
        'active_element'   => 'item',  // item|link
    ],
    'sidenav' => [
        'auto_activate'    => true,
        'activate_parents' => true,
        'active_class'     => 'activ2e',
        'restful'          => true,
        'cascade_data'     => true,
        'rest_base'        => '',      // string|array
        'active_element'   => 'link',  // item|link
    ],
];
