<?php
define('URL_IMG',"http://img.aa.com");
define('URL_USER','http://u.aa.com');

define('URL_PUBLIC','http://public.aa.com');
define('URL_SKIN','http://public.vpn.com'); 

return array(
		'DB_TYPE'=>'mysql',
		'DB_HOST'=>'localhost',

		'DB_PORT' => '3306',
		'DB_NAME'=>'api',
		'DB_USER'=>'api',
		'DB_PWD'=>'aaa',
		'DB_PREFIX'=>'think_',
		

		//缓存类型
		//'DATA_CACHE_TYPE' => 'Memcache',
		//'SHOW_PAGE_TRACE'=>true,
		//'FIRE_SHOW_PAGE_TRACE' => true,
		//memcache
		'MC_CONF' => array(
				'host'=>'127.0.0.1',
				'port'=>'11211',
				'expire'=>'60'
		),
    
    'DEFAULT_MODULE'       =>    'Api',
    'APP_SUB_DOMAIN_RULES'    =>    array(
        'api'   => 'Api',
    
    ),
);