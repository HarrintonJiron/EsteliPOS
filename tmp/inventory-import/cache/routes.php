<?php

app('router')->setCompiledRoutes(
    [
        'compiled' => [
            0 => false,
            1 => [
                '/up' => [
                    0 => [
                        0 => [
                            '_route' => 'generated::qf1vvNlpcPciFOaO',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/login' => [
                    0 => [
                        0 => [
                            '_route' => 'login',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'generated::sttjQUmthzJTnn36',
                        ],
                        1 => null,
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/password/change' => [
                    0 => [
                        0 => [
                            '_route' => 'password.change',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'password.update',
                        ],
                        1 => null,
                        2 => [
                            'PUT' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/' => [
                    0 => [
                        0 => [
                            '_route' => 'home',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/acceso-limitado' => [
                    0 => [
                        0 => [
                            '_route' => 'access.unavailable',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/logout' => [
                    0 => [
                        0 => [
                            '_route' => 'logout',
                        ],
                        1 => null,
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/cambiar-usuario' => [
                    0 => [
                        0 => [
                            '_route' => 'auth.switch-user',
                        ],
                        1 => null,
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/ayuda' => [
                    0 => [
                        0 => [
                            '_route' => 'help.index',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/facturacion/create' => [
                    0 => [
                        0 => [
                            '_route' => 'facturacion.create',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/facturacion' => [
                    0 => [
                        0 => [
                            '_route' => 'facturacion.index',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/facturacion/pos' => [
                    0 => [
                        0 => [
                            '_route' => 'facturacion.pos',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/facturacion/pos/products' => [
                    0 => [
                        0 => [
                            '_route' => 'facturacion.pos-products',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/facturacion/pos/daily-report' => [
                    0 => [
                        0 => [
                            '_route' => 'facturacion.pos-daily-report',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/facturacion/print' => [
                    0 => [
                        0 => [
                            '_route' => 'facturacion.print',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/facturacion/pdf' => [
                    0 => [
                        0 => [
                            '_route' => 'facturacion.pdf',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/creditos' => [
                    0 => [
                        0 => [
                            '_route' => 'creditos.index',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/creditos/search' => [
                    0 => [
                        0 => [
                            '_route' => 'creditos.search',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/creditos/vencidos' => [
                    0 => [
                        0 => [
                            '_route' => 'creditos.overdue',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/creditos/reporte' => [
                    0 => [
                        0 => [
                            '_route' => 'creditos.report',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/creditos/abono' => [
                    0 => [
                        0 => [
                            '_route' => 'creditos.store',
                        ],
                        1 => null,
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/creditos/reporte/export' => [
                    0 => [
                        0 => [
                            '_route' => 'creditos.export',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/arqueo' => [
                    0 => [
                        0 => [
                            '_route' => 'arqueo.index',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/arqueo/open' => [
                    0 => [
                        0 => [
                            '_route' => 'arqueo.open',
                        ],
                        1 => null,
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/arqueo/run' => [
                    0 => [
                        0 => [
                            '_route' => 'arqueo.run',
                        ],
                        1 => null,
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/inventario' => [
                    0 => [
                        0 => [
                            '_route' => 'inventario.index',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'inventario.store',
                        ],
                        1 => null,
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/inventario/create' => [
                    0 => [
                        0 => [
                            '_route' => 'inventario.create',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/inventario/rapido' => [
                    0 => [
                        0 => [
                            '_route' => 'inventario.quick',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'inventario.quick-store',
                        ],
                        1 => null,
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/inventario/dashboard' => [
                    0 => [
                        0 => [
                            '_route' => 'inventario.dashboard',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/inventario/unidades' => [
                    0 => [
                        0 => [
                            '_route' => 'inventario.units.index',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'inventario.units.store',
                        ],
                        1 => null,
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/inventario/convertir' => [
                    0 => [
                        0 => [
                            '_route' => 'inventario.convert',
                        ],
                        1 => null,
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/inventario/bodegas' => [
                    0 => [
                        0 => [
                            '_route' => 'inventario.warehouses.index',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'inventario.warehouses.store',
                        ],
                        1 => null,
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/inventario/bodegas/nueva' => [
                    0 => [
                        0 => [
                            '_route' => 'inventario.warehouses.create',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/inventario/transferencias' => [
                    0 => [
                        0 => [
                            '_route' => 'inventario.transfers.index',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'inventario.transfers.store',
                        ],
                        1 => null,
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/inventario/transferencias/stock' => [
                    0 => [
                        0 => [
                            '_route' => 'inventario.transfers.stock',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/inventario/listas-precios' => [
                    0 => [
                        0 => [
                            '_route' => 'inventario.price-lists.index',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'inventario.price-lists.store',
                        ],
                        1 => null,
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/inventario/listas-precios/nueva' => [
                    0 => [
                        0 => [
                            '_route' => 'inventario.price-lists.create',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/inventario/carga-masiva' => [
                    0 => [
                        0 => [
                            '_route' => 'inventario.bulk',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'inventario.bulk-store',
                        ],
                        1 => null,
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/inventario/next-code' => [
                    0 => [
                        0 => [
                            '_route' => 'inventario.next-code',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/inventario/reconciliar' => [
                    0 => [
                        0 => [
                            '_route' => 'inventario.reconcile',
                        ],
                        1 => null,
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/categorias' => [
                    0 => [
                        0 => [
                            '_route' => 'categorias.store',
                        ],
                        1 => null,
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/inventario/export' => [
                    0 => [
                        0 => [
                            '_route' => 'inventario.export',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/movimientos' => [
                    0 => [
                        0 => [
                            '_route' => 'movimientos.index',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/dashboard-general' => [
                    0 => [
                        0 => [
                            '_route' => 'dashboard.general',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/analitica' => [
                    0 => [
                        0 => [
                            '_route' => 'analitica.index',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/sucursales' => [
                    0 => [
                        0 => [
                            '_route' => 'sucursales.index',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'sucursales.store',
                        ],
                        1 => null,
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/sucursales/crear' => [
                    0 => [
                        0 => [
                            '_route' => 'sucursales.create',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/proveedores' => [
                    0 => [
                        0 => [
                            '_route' => 'proveedores.index',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'proveedores.store',
                        ],
                        1 => null,
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/proveedores/create' => [
                    0 => [
                        0 => [
                            '_route' => 'proveedores.create',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/proveedores/export' => [
                    0 => [
                        0 => [
                            '_route' => 'proveedores.export',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/compras/productos/buscar' => [
                    0 => [
                        0 => [
                            '_route' => 'compras.products.search',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/compras/productos/siguiente-codigo' => [
                    0 => [
                        0 => [
                            '_route' => 'compras.products.next-code',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/compras/productos/rapido' => [
                    0 => [
                        0 => [
                            '_route' => 'compras.products.quick-store',
                        ],
                        1 => null,
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/compras/proveedores/rapido' => [
                    0 => [
                        0 => [
                            '_route' => 'compras.suppliers.quick-store',
                        ],
                        1 => null,
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/compras' => [
                    0 => [
                        0 => [
                            '_route' => 'compras.index',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'compras.store',
                        ],
                        1 => null,
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/compras/create' => [
                    0 => [
                        0 => [
                            '_route' => 'compras.create',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/clientes' => [
                    0 => [
                        0 => [
                            '_route' => 'clientes.index',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'clientes.store',
                        ],
                        1 => null,
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/clientes/create' => [
                    0 => [
                        0 => [
                            '_route' => 'clientes.create',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/clientes/quick-store' => [
                    0 => [
                        0 => [
                            '_route' => 'clientes.quick-store',
                        ],
                        1 => null,
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/planilla' => [
                    0 => [
                        0 => [
                            '_route' => 'planilla.index',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/planilla/charts' => [
                    0 => [
                        0 => [
                            '_route' => 'planilla.charts',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/rrhh' => [
                    0 => [
                        0 => [
                            '_route' => 'rrhh.hub',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/rrhh/directorio' => [
                    0 => [
                        0 => [
                            '_route' => 'rrhh.directory',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/rrhh/organigrama' => [
                    0 => [
                        0 => [
                            '_route' => 'rrhh.organigram',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/rrhh/asistencia' => [
                    0 => [
                        0 => [
                            '_route' => 'rrhh.attendance',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'rrhh.attendance.store',
                        ],
                        1 => null,
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/rrhh/turnos' => [
                    0 => [
                        0 => [
                            '_route' => 'rrhh.shifts',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/rrhh/aguinaldo' => [
                    0 => [
                        0 => [
                            '_route' => 'rrhh.thirteenth',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/rrhh/inss' => [
                    0 => [
                        0 => [
                            '_route' => 'rrhh.inss',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/rrhh/evaluaciones' => [
                    0 => [
                        0 => [
                            '_route' => 'rrhh.evaluations',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'rrhh.evaluations.store',
                        ],
                        1 => null,
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/employees' => [
                    0 => [
                        0 => [
                            '_route' => 'employees.index',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'employees.store',
                        ],
                        1 => null,
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/employees/create' => [
                    0 => [
                        0 => [
                            '_route' => 'employees.create',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/leave' => [
                    0 => [
                        0 => [
                            '_route' => 'leave.index',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'leave.store',
                        ],
                        1 => null,
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/leave/create' => [
                    0 => [
                        0 => [
                            '_route' => 'leave.create',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/loans' => [
                    0 => [
                        0 => [
                            '_route' => 'loans.index',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'loans.store',
                        ],
                        1 => null,
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/loans/create' => [
                    0 => [
                        0 => [
                            '_route' => 'loans.create',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/bonuses' => [
                    0 => [
                        0 => [
                            '_route' => 'bonuses.index',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'bonuses.store',
                        ],
                        1 => null,
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/bonuses/create' => [
                    0 => [
                        0 => [
                            '_route' => 'bonuses.create',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/deductions' => [
                    0 => [
                        0 => [
                            '_route' => 'deductions.index',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'deductions.store',
                        ],
                        1 => null,
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/deductions/create' => [
                    0 => [
                        0 => [
                            '_route' => 'deductions.create',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/proformas' => [
                    0 => [
                        0 => [
                            '_route' => 'proformas.index',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'proformas.store',
                        ],
                        1 => null,
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/proformas/nueva' => [
                    0 => [
                        0 => [
                            '_route' => 'proformas.pos',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/proformas/productos' => [
                    0 => [
                        0 => [
                            '_route' => 'proformas.products',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/device-brands' => [
                    0 => [
                        0 => [
                            '_route' => 'device-brands.index',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'device-brands.store',
                        ],
                        1 => null,
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/repair-services' => [
                    0 => [
                        0 => [
                            '_route' => 'repair-services.store',
                        ],
                        1 => null,
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/reparaciones/gastos-operativos/nuevo' => [
                    0 => [
                        0 => [
                            '_route' => 'reparaciones.gastos.create',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/reparaciones/gastos-operativos' => [
                    0 => [
                        0 => [
                            '_route' => 'reparaciones.gastos.store',
                        ],
                        1 => null,
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'reparaciones.gastos.index',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/reparaciones' => [
                    0 => [
                        0 => [
                            '_route' => 'reparaciones.index',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'reparaciones.store',
                        ],
                        1 => null,
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/reparaciones/nueva' => [
                    0 => [
                        0 => [
                            '_route' => 'reparaciones.create',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/reportes' => [
                    0 => [
                        0 => [
                            '_route' => 'reportes.index',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/reportes/export' => [
                    0 => [
                        0 => [
                            '_route' => 'reportes.export',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/nomina' => [
                    0 => [
                        0 => [
                            '_route' => 'nomina.index',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/nomina/charts' => [
                    0 => [
                        0 => [
                            '_route' => 'nomina.charts',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/nomina/pagar' => [
                    0 => [
                        0 => [
                            '_route' => 'nomina.pay',
                        ],
                        1 => null,
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/nomina/ticket' => [
                    0 => [
                        0 => [
                            '_route' => 'nomina.ticket',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/ajustes' => [
                    0 => [
                        0 => [
                            '_route' => 'ajustes.index',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'ajustes.store',
                        ],
                        1 => null,
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/ajustes/create' => [
                    0 => [
                        0 => [
                            '_route' => 'ajustes.create',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/settings' => [
                    0 => [
                        0 => [
                            '_route' => 'settings.index',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/settings/users' => [
                    0 => [
                        0 => [
                            '_route' => 'settings.users',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'settings.users.store',
                        ],
                        1 => null,
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/settings/users/create' => [
                    0 => [
                        0 => [
                            '_route' => 'settings.users.create',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/settings/roles' => [
                    0 => [
                        0 => [
                            '_route' => 'settings.roles',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'settings.roles.store',
                        ],
                        1 => null,
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/settings/roles/create' => [
                    0 => [
                        0 => [
                            '_route' => 'settings.roles.create',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/settings/roles/compare' => [
                    0 => [
                        0 => [
                            '_route' => 'settings.roles.compare',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/settings/permissions' => [
                    0 => [
                        0 => [
                            '_route' => 'settings.permissions',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/settings/general' => [
                    0 => [
                        0 => [
                            '_route' => 'settings.general',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'settings.general.update',
                        ],
                        1 => null,
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/settings/taxes' => [
                    0 => [
                        0 => [
                            '_route' => 'settings.taxes.index',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'settings.taxes.store',
                        ],
                        1 => null,
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/settings/taxes/display-mode' => [
                    0 => [
                        0 => [
                            '_route' => 'settings.taxes.display-mode.update',
                        ],
                        1 => null,
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/settings/taxes/create' => [
                    0 => [
                        0 => [
                            '_route' => 'settings.taxes.create',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/settings/exchange-rates' => [
                    0 => [
                        0 => [
                            '_route' => 'settings.exchange-rates.index',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'settings.exchange-rates.store',
                        ],
                        1 => null,
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/settings/exchange-rates/create' => [
                    0 => [
                        0 => [
                            '_route' => 'settings.exchange-rates.create',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/settings/modules' => [
                    0 => [
                        0 => [
                            '_route' => 'settings.modules',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'settings.modules.update',
                        ],
                        1 => null,
                        2 => [
                            'PUT' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/settings/security' => [
                    0 => [
                        0 => [
                            '_route' => 'settings.security',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'settings.security.update',
                        ],
                        1 => null,
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/settings/appearance' => [
                    0 => [
                        0 => [
                            '_route' => 'settings.appearance',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'settings.appearance.update',
                        ],
                        1 => null,
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/settings/sequences' => [
                    0 => [
                        0 => [
                            '_route' => 'settings.sequences',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'settings.sequences.update',
                        ],
                        1 => null,
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/settings/system-reset' => [
                    0 => [
                        0 => [
                            '_route' => 'settings.system-reset.create',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'settings.system-reset.store',
                        ],
                        1 => null,
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/contabilidad' => [
                    0 => [
                        0 => [
                            '_route' => 'contabilidad.dashboard',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/contabilidad/cuentas' => [
                    0 => [
                        0 => [
                            '_route' => 'contabilidad.cuentas.index',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'contabilidad.cuentas.store',
                        ],
                        1 => null,
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/contabilidad/cuentas/create' => [
                    0 => [
                        0 => [
                            '_route' => 'contabilidad.cuentas.create',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/contabilidad/asientos' => [
                    0 => [
                        0 => [
                            '_route' => 'contabilidad.asientos.index',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'contabilidad.asientos.store',
                        ],
                        1 => null,
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/contabilidad/asientos-nuevo' => [
                    0 => [
                        0 => [
                            '_route' => 'contabilidad.asientos.create',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/contabilidad/diario' => [
                    0 => [
                        0 => [
                            '_route' => 'contabilidad.diario.index',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/contabilidad/diario/export' => [
                    0 => [
                        0 => [
                            '_route' => 'contabilidad.diario.export',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/contabilidad/mayor' => [
                    0 => [
                        0 => [
                            '_route' => 'contabilidad.mayor.index',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/contabilidad/balance-comprobacion' => [
                    0 => [
                        0 => [
                            '_route' => 'contabilidad.balance-comprobacion.index',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/contabilidad/balance-comprobacion/export' => [
                    0 => [
                        0 => [
                            '_route' => 'contabilidad.balance-comprobacion.export',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/contabilidad/estado-resultados' => [
                    0 => [
                        0 => [
                            '_route' => 'contabilidad.estado-resultados.index',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/contabilidad/estado-resultados/export' => [
                    0 => [
                        0 => [
                            '_route' => 'contabilidad.estado-resultados.export',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/contabilidad/balance-general' => [
                    0 => [
                        0 => [
                            '_route' => 'contabilidad.balance-general.index',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/contabilidad/balance-general/export' => [
                    0 => [
                        0 => [
                            '_route' => 'contabilidad.balance-general.export',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/contabilidad/flujo-caja' => [
                    0 => [
                        0 => [
                            '_route' => 'contabilidad.flujo-caja.index',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/contabilidad/flujo-caja/export' => [
                    0 => [
                        0 => [
                            '_route' => 'contabilidad.flujo-caja.export',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/contabilidad/centros-costo' => [
                    0 => [
                        0 => [
                            '_route' => 'contabilidad.centros-costo.index',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'contabilidad.centros-costo.store',
                        ],
                        1 => null,
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/contabilidad/centros-costo/analisis' => [
                    0 => [
                        0 => [
                            '_route' => 'contabilidad.centros-costo.analytics',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/contabilidad/centros-costo/create' => [
                    0 => [
                        0 => [
                            '_route' => 'contabilidad.centros-costo.create',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                '/contabilidad/periodos' => [
                    0 => [
                        0 => [
                            '_route' => 'contabilidad.periodos.index',
                        ],
                        1 => null,
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
            ],
            2 => [
                0 => '{^(?|/media/(company|products)/([A-Za-z0-9._-]+)(*:50)|/facturacion/(?|change/([^/]++)(*:88)|receipt/([^/]++)(*:111)|([^/]++)(*:127)|credit\\-override(*:151)|pos(?|/products/([0-9]+)/image(*:189)|\\-store(*:204))|([^/]++)(?|/edit(*:229)|(*:237)))|/c(?|reditos/(?|statement/([^/]++)(*:281)|cliente/([^/]++)(*:305)|payment/([^/]++)/invoice(*:337)|abono/nuevo/([^/]++)(*:365))|o(?|mpras/([^/]++)(?|(*:395)|/e(?|dit(*:411)|stado(*:424))|(*:433))|ntabilidad/(?|reportes/(estado-resultados|balance-general|flujo-caja|balance-comprobacion|diario-general|mayor-general)/(?|pdf(*:568)|excel(*:581))|c(?|uentas/([^/]++)(?|/edit(*:617)|(*:625))|entros\\-costo/([^/]++)(?|/edit(*:664)|(*:672)))|asientos/([^/]++)(?|(*:702)|/(?|contabilizar(*:726)|anular(*:740))|(*:749))|periodos/([^/]++)/(?|cerrar(?|(*:788)|\\-anio(*:802))|reabrir(?|(*:821)|\\-anio(*:835)))))|lientes/(?|([0-9]+)(*:866)|([0-9]+)/edit(*:887)|([0-9]+)(*:903)|([0-9]+)/toggle\\-credit(*:934)|([0-9]+)(*:950)))|/inventario/(?|b(?|uscar/([^/]++)(*:993)|odegas/([^/]++)(?|(*:1019)|/(?|e(?|dit(*:1039)|stantes(?|(*:1058)|/([^/]++)(*:1076)))|transferir(*:1097))|(*:1107)))|unidades/([^/]++)(*:1135)|listas\\-precios/([^/]++)(?|(*:1171)|/(?|edit(*:1188)|items(?|(*:1205)|/([^/]++)(*:1223)))|(*:1234))|([0-9]+)/conversiones(*:1265)|([0-9]+)/conversiones/predeterminada(*:1310)|([0-9]+)/conversiones/([^/]++)(*:1349)|([0-9]+)(*:1366)|([0-9]+)/edit(*:1388)|([0-9]+)(?|(*:1408)))|/s(?|ucursales/([^/]++)(?|(*:1445)|/editar(*:1461)|(*:1470))|ettings/(?|users/([^/]++)(?|(*:1508)|/(?|edit(*:1525)|toggle\\-active(*:1548)|reset\\-password(?|(*:1575)))|(*:1586))|roles/([^/]++)(?|/(?|clone(?|(*:1625))|delete(*:1641)|edit(*:1654))|(*:1664))|taxes/([^/]++)(?|/edit(*:1696)|(*:1705))|exchange\\-rates/([^/]++)(?|/edit(*:1747)|(*:1756)))|torage/(.*)(?|(*:1781)))|/pro(?|veedores/(?|([0-9]+)(*:1819)|([0-9]+)/credit\\-info(*:1849)|([0-9]+)/edit(*:1871)|([0-9]+)(?|(*:1891))|([^/]++)/productos(?|(*:1922)|/buscar(*:1938)))|formas/(?|([0-9]+)(*:1967)|([0-9]+)/pdf(*:1988)|([0-9]+)/ticket(*:2012)|([0-9]+)/status(*:2036)|([0-9]+)(*:2053)|([0-9]+)/convert(*:2078)))|/employees/([^/]++)(?|(*:2111)|/edit(*:2125)|(*:2134))|/l(?|eave/([^/]++)(?|(*:2165)|/(?|edit(*:2182)|approve(*:2198)|reject(*:2213))|(*:2223))|oans/([^/]++)(?|(*:2249)|/(?|edit(*:2266)|approve(*:2282)|reject(*:2297))|(*:2307)))|/bonuses/([^/]++)(?|(*:2338)|/(?|edit(*:2355)|approve(*:2371)|mark\\-paid(*:2390))|(*:2400))|/deductions/([^/]++)(?|(*:2433)|/(?|edit(*:2450)|approve(*:2466)|mark\\-deducted(*:2489))|(*:2499))|/reparaciones/(?|gastos\\-operativos/([^/]++)(?|/edit(*:2561)|(*:2570))|([0-9]+)(*:2588)|([0-9]+)/ticket(*:2612)|([0-9]+)/pdf(*:2633)|([0-9]+)/edit(*:2655)|([0-9]+)(*:2672)|([0-9]+)/status(*:2696)|([0-9]+)(*:2713))|/a(?|justes/([^/]++)(?|(*:2746))|pi/products/([^/]++)/info(*:2781)))/?$}sDu',
            ],
            3 => [
                50 => [
                    0 => [
                        0 => [
                            '_route' => 'media.show',
                        ],
                        1 => [
                            0 => 'directory',
                            1 => 'filename',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                88 => [
                    0 => [
                        0 => [
                            '_route' => 'facturacion.change',
                        ],
                        1 => [
                            0 => 'saleId',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                111 => [
                    0 => [
                        0 => [
                            '_route' => 'facturacion.receipt',
                        ],
                        1 => [
                            0 => 'saleId',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                127 => [
                    0 => [
                        0 => [
                            '_route' => 'facturacion.show',
                        ],
                        1 => [
                            0 => 'id',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                151 => [
                    0 => [
                        0 => [
                            '_route' => 'facturacion.credit-override',
                        ],
                        1 => [
                        ],
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                189 => [
                    0 => [
                        0 => [
                            '_route' => 'facturacion.pos-product-image',
                        ],
                        1 => [
                            0 => 'product',
                        ],
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                204 => [
                    0 => [
                        0 => [
                            '_route' => 'facturacion.pos-store',
                        ],
                        1 => [
                        ],
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                229 => [
                    0 => [
                        0 => [
                            '_route' => 'facturacion.edit',
                        ],
                        1 => [
                            0 => 'id',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                237 => [
                    0 => [
                        0 => [
                            '_route' => 'facturacion.update',
                        ],
                        1 => [
                            0 => 'id',
                        ],
                        2 => [
                            'PUT' => 0,
                            'PATCH' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'facturacion.destroy',
                        ],
                        1 => [
                            0 => 'id',
                        ],
                        2 => [
                            'DELETE' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                281 => [
                    0 => [
                        0 => [
                            '_route' => 'creditos.statement',
                        ],
                        1 => [
                            0 => 'clientId',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                305 => [
                    0 => [
                        0 => [
                            '_route' => 'creditos.show',
                        ],
                        1 => [
                            0 => 'clientId',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                337 => [
                    0 => [
                        0 => [
                            '_route' => 'creditos.invoice',
                        ],
                        1 => [
                            0 => 'paymentId',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                365 => [
                    0 => [
                        0 => [
                            '_route' => 'creditos.create',
                        ],
                        1 => [
                            0 => 'clientId',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                395 => [
                    0 => [
                        0 => [
                            '_route' => 'compras.show',
                        ],
                        1 => [
                            0 => 'id',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                411 => [
                    0 => [
                        0 => [
                            '_route' => 'compras.edit',
                        ],
                        1 => [
                            0 => 'id',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                424 => [
                    0 => [
                        0 => [
                            '_route' => 'compras.status',
                        ],
                        1 => [
                            0 => 'id',
                        ],
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                433 => [
                    0 => [
                        0 => [
                            '_route' => 'compras.update',
                        ],
                        1 => [
                            0 => 'id',
                        ],
                        2 => [
                            'PUT' => 0,
                            'PATCH' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'compras.destroy',
                        ],
                        1 => [
                            0 => 'id',
                        ],
                        2 => [
                            'DELETE' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                568 => [
                    0 => [
                        0 => [
                            '_route' => 'contabilidad.reportes.pdf',
                        ],
                        1 => [
                            0 => 'report',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                581 => [
                    0 => [
                        0 => [
                            '_route' => 'contabilidad.reportes.excel',
                        ],
                        1 => [
                            0 => 'report',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                617 => [
                    0 => [
                        0 => [
                            '_route' => 'contabilidad.cuentas.edit',
                        ],
                        1 => [
                            0 => 'account',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                625 => [
                    0 => [
                        0 => [
                            '_route' => 'contabilidad.cuentas.update',
                        ],
                        1 => [
                            0 => 'account',
                        ],
                        2 => [
                            'PUT' => 0,
                            'PATCH' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'contabilidad.cuentas.destroy',
                        ],
                        1 => [
                            0 => 'account',
                        ],
                        2 => [
                            'DELETE' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                664 => [
                    0 => [
                        0 => [
                            '_route' => 'contabilidad.centros-costo.edit',
                        ],
                        1 => [
                            0 => 'centro_costo',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                672 => [
                    0 => [
                        0 => [
                            '_route' => 'contabilidad.centros-costo.update',
                        ],
                        1 => [
                            0 => 'centro_costo',
                        ],
                        2 => [
                            'PUT' => 0,
                            'PATCH' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'contabilidad.centros-costo.destroy',
                        ],
                        1 => [
                            0 => 'centro_costo',
                        ],
                        2 => [
                            'DELETE' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                702 => [
                    0 => [
                        0 => [
                            '_route' => 'contabilidad.asientos.show',
                        ],
                        1 => [
                            0 => 'journalEntry',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                726 => [
                    0 => [
                        0 => [
                            '_route' => 'contabilidad.asientos.post',
                        ],
                        1 => [
                            0 => 'journalEntry',
                        ],
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                740 => [
                    0 => [
                        0 => [
                            '_route' => 'contabilidad.asientos.void',
                        ],
                        1 => [
                            0 => 'journalEntry',
                        ],
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                749 => [
                    0 => [
                        0 => [
                            '_route' => 'contabilidad.asientos.destroy',
                        ],
                        1 => [
                            0 => 'journalEntry',
                        ],
                        2 => [
                            'DELETE' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                788 => [
                    0 => [
                        0 => [
                            '_route' => 'contabilidad.periodos.cerrar',
                        ],
                        1 => [
                            0 => 'periodo',
                        ],
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                802 => [
                    0 => [
                        0 => [
                            '_route' => 'contabilidad.periodos.cerrar-anio',
                        ],
                        1 => [
                            0 => 'periodo',
                        ],
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                821 => [
                    0 => [
                        0 => [
                            '_route' => 'contabilidad.periodos.reabrir',
                        ],
                        1 => [
                            0 => 'periodo',
                        ],
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                835 => [
                    0 => [
                        0 => [
                            '_route' => 'contabilidad.periodos.reabrir-anio',
                        ],
                        1 => [
                            0 => 'periodo',
                        ],
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                866 => [
                    0 => [
                        0 => [
                            '_route' => 'clientes.show',
                        ],
                        1 => [
                            0 => 'id',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                887 => [
                    0 => [
                        0 => [
                            '_route' => 'clientes.edit',
                        ],
                        1 => [
                            0 => 'id',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                903 => [
                    0 => [
                        0 => [
                            '_route' => 'clientes.update',
                        ],
                        1 => [
                            0 => 'id',
                        ],
                        2 => [
                            'PUT' => 0,
                            'PATCH' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                934 => [
                    0 => [
                        0 => [
                            '_route' => 'clientes.toggle_credit',
                        ],
                        1 => [
                            0 => 'id',
                        ],
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                950 => [
                    0 => [
                        0 => [
                            '_route' => 'clientes.destroy',
                        ],
                        1 => [
                            0 => 'id',
                        ],
                        2 => [
                            'DELETE' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                993 => [
                    0 => [
                        0 => [
                            '_route' => 'inventario.lookup',
                        ],
                        1 => [
                            0 => 'code',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                1019 => [
                    0 => [
                        0 => [
                            '_route' => 'inventario.warehouses.show',
                        ],
                        1 => [
                            0 => 'warehouse',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                1039 => [
                    0 => [
                        0 => [
                            '_route' => 'inventario.warehouses.edit',
                        ],
                        1 => [
                            0 => 'warehouse',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                1058 => [
                    0 => [
                        0 => [
                            '_route' => 'inventario.warehouses.shelves.store',
                        ],
                        1 => [
                            0 => 'warehouse',
                        ],
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                1076 => [
                    0 => [
                        0 => [
                            '_route' => 'inventario.warehouses.shelves.destroy',
                        ],
                        1 => [
                            0 => 'warehouse',
                            1 => 'shelf',
                        ],
                        2 => [
                            'DELETE' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                1097 => [
                    0 => [
                        0 => [
                            '_route' => 'inventario.warehouses.transfer',
                        ],
                        1 => [
                            0 => 'warehouse',
                        ],
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                1107 => [
                    0 => [
                        0 => [
                            '_route' => 'inventario.warehouses.update',
                        ],
                        1 => [
                            0 => 'warehouse',
                        ],
                        2 => [
                            'PUT' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'inventario.warehouses.destroy',
                        ],
                        1 => [
                            0 => 'warehouse',
                        ],
                        2 => [
                            'DELETE' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                1135 => [
                    0 => [
                        0 => [
                            '_route' => 'inventario.units.update',
                        ],
                        1 => [
                            0 => 'unit',
                        ],
                        2 => [
                            'PUT' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                1171 => [
                    0 => [
                        0 => [
                            '_route' => 'inventario.price-lists.show',
                        ],
                        1 => [
                            0 => 'priceList',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                1188 => [
                    0 => [
                        0 => [
                            '_route' => 'inventario.price-lists.edit',
                        ],
                        1 => [
                            0 => 'priceList',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                1205 => [
                    0 => [
                        0 => [
                            '_route' => 'inventario.price-lists.items.store',
                        ],
                        1 => [
                            0 => 'priceList',
                        ],
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                1223 => [
                    0 => [
                        0 => [
                            '_route' => 'inventario.price-lists.items.destroy',
                        ],
                        1 => [
                            0 => 'priceList',
                            1 => 'item',
                        ],
                        2 => [
                            'DELETE' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                1234 => [
                    0 => [
                        0 => [
                            '_route' => 'inventario.price-lists.update',
                        ],
                        1 => [
                            0 => 'priceList',
                        ],
                        2 => [
                            'PUT' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                1265 => [
                    0 => [
                        0 => [
                            '_route' => 'inventario.conversions.store',
                        ],
                        1 => [
                            0 => 'id',
                        ],
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                1310 => [
                    0 => [
                        0 => [
                            '_route' => 'inventario.conversions.default',
                        ],
                        1 => [
                            0 => 'id',
                        ],
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                1349 => [
                    0 => [
                        0 => [
                            '_route' => 'inventario.conversions.destroy',
                        ],
                        1 => [
                            0 => 'id',
                            1 => 'conversion',
                        ],
                        2 => [
                            'DELETE' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                1366 => [
                    0 => [
                        0 => [
                            '_route' => 'inventario.show',
                        ],
                        1 => [
                            0 => 'id',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                1388 => [
                    0 => [
                        0 => [
                            '_route' => 'inventario.edit',
                        ],
                        1 => [
                            0 => 'id',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                1408 => [
                    0 => [
                        0 => [
                            '_route' => 'inventario.update',
                        ],
                        1 => [
                            0 => 'id',
                        ],
                        2 => [
                            'PUT' => 0,
                            'PATCH' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'inventario.destroy',
                        ],
                        1 => [
                            0 => 'id',
                        ],
                        2 => [
                            'DELETE' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                1445 => [
                    0 => [
                        0 => [
                            '_route' => 'sucursales.show',
                        ],
                        1 => [
                            0 => 'branch',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                1461 => [
                    0 => [
                        0 => [
                            '_route' => 'sucursales.edit',
                        ],
                        1 => [
                            0 => 'branch',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                1470 => [
                    0 => [
                        0 => [
                            '_route' => 'sucursales.update',
                        ],
                        1 => [
                            0 => 'branch',
                        ],
                        2 => [
                            'PUT' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                1508 => [
                    0 => [
                        0 => [
                            '_route' => 'settings.users.show',
                        ],
                        1 => [
                            0 => 'user',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                1525 => [
                    0 => [
                        0 => [
                            '_route' => 'settings.users.edit',
                        ],
                        1 => [
                            0 => 'user',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                1548 => [
                    0 => [
                        0 => [
                            '_route' => 'settings.users.toggle-active',
                        ],
                        1 => [
                            0 => 'user',
                        ],
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                1575 => [
                    0 => [
                        0 => [
                            '_route' => 'settings.users.reset-password.form',
                        ],
                        1 => [
                            0 => 'user',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'settings.users.reset-password',
                        ],
                        1 => [
                            0 => 'user',
                        ],
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                1586 => [
                    0 => [
                        0 => [
                            '_route' => 'settings.users.update',
                        ],
                        1 => [
                            0 => 'user',
                        ],
                        2 => [
                            'PUT' => 0,
                            'PATCH' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'settings.users.destroy',
                        ],
                        1 => [
                            0 => 'user',
                        ],
                        2 => [
                            'DELETE' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                1625 => [
                    0 => [
                        0 => [
                            '_route' => 'settings.roles.clone.form',
                        ],
                        1 => [
                            0 => 'role',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'settings.roles.clone',
                        ],
                        1 => [
                            0 => 'role',
                        ],
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                1641 => [
                    0 => [
                        0 => [
                            '_route' => 'settings.roles.delete.form',
                        ],
                        1 => [
                            0 => 'role',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                1654 => [
                    0 => [
                        0 => [
                            '_route' => 'settings.roles.edit',
                        ],
                        1 => [
                            0 => 'role',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                1664 => [
                    0 => [
                        0 => [
                            '_route' => 'settings.roles.show',
                        ],
                        1 => [
                            0 => 'role',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'settings.roles.update',
                        ],
                        1 => [
                            0 => 'role',
                        ],
                        2 => [
                            'PUT' => 0,
                            'PATCH' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                    2 => [
                        0 => [
                            '_route' => 'settings.roles.destroy',
                        ],
                        1 => [
                            0 => 'role',
                        ],
                        2 => [
                            'DELETE' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                1696 => [
                    0 => [
                        0 => [
                            '_route' => 'settings.taxes.edit',
                        ],
                        1 => [
                            0 => 'tax',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                1705 => [
                    0 => [
                        0 => [
                            '_route' => 'settings.taxes.update',
                        ],
                        1 => [
                            0 => 'tax',
                        ],
                        2 => [
                            'PUT' => 0,
                            'PATCH' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'settings.taxes.destroy',
                        ],
                        1 => [
                            0 => 'tax',
                        ],
                        2 => [
                            'DELETE' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                1747 => [
                    0 => [
                        0 => [
                            '_route' => 'settings.exchange-rates.edit',
                        ],
                        1 => [
                            0 => 'exchangeRate',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                1756 => [
                    0 => [
                        0 => [
                            '_route' => 'settings.exchange-rates.update',
                        ],
                        1 => [
                            0 => 'exchangeRate',
                        ],
                        2 => [
                            'PUT' => 0,
                            'PATCH' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'settings.exchange-rates.destroy',
                        ],
                        1 => [
                            0 => 'exchangeRate',
                        ],
                        2 => [
                            'DELETE' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                1781 => [
                    0 => [
                        0 => [
                            '_route' => 'storage.local',
                        ],
                        1 => [
                            0 => 'path',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'storage.local.upload',
                        ],
                        1 => [
                            0 => 'path',
                        ],
                        2 => [
                            'PUT' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                1819 => [
                    0 => [
                        0 => [
                            '_route' => 'proveedores.show',
                        ],
                        1 => [
                            0 => 'id',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                1849 => [
                    0 => [
                        0 => [
                            '_route' => 'proveedores.credit_info',
                        ],
                        1 => [
                            0 => 'id',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                1871 => [
                    0 => [
                        0 => [
                            '_route' => 'proveedores.edit',
                        ],
                        1 => [
                            0 => 'id',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                1891 => [
                    0 => [
                        0 => [
                            '_route' => 'proveedores.update',
                        ],
                        1 => [
                            0 => 'id',
                        ],
                        2 => [
                            'PUT' => 0,
                            'PATCH' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'proveedores.destroy',
                        ],
                        1 => [
                            0 => 'id',
                        ],
                        2 => [
                            'DELETE' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                1922 => [
                    0 => [
                        0 => [
                            '_route' => 'proveedores.productos',
                        ],
                        1 => [
                            0 => 'supplier',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                1938 => [
                    0 => [
                        0 => [
                            '_route' => 'proveedores.productos.buscar',
                        ],
                        1 => [
                            0 => 'supplier',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                1967 => [
                    0 => [
                        0 => [
                            '_route' => 'proformas.show',
                        ],
                        1 => [
                            0 => 'id',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                1988 => [
                    0 => [
                        0 => [
                            '_route' => 'proformas.pdf',
                        ],
                        1 => [
                            0 => 'id',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                2012 => [
                    0 => [
                        0 => [
                            '_route' => 'proformas.ticket',
                        ],
                        1 => [
                            0 => 'id',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                2036 => [
                    0 => [
                        0 => [
                            '_route' => 'proformas.status',
                        ],
                        1 => [
                            0 => 'id',
                        ],
                        2 => [
                            'PATCH' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                2053 => [
                    0 => [
                        0 => [
                            '_route' => 'proformas.destroy',
                        ],
                        1 => [
                            0 => 'id',
                        ],
                        2 => [
                            'DELETE' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                2078 => [
                    0 => [
                        0 => [
                            '_route' => 'proformas.convert',
                        ],
                        1 => [
                            0 => 'id',
                        ],
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                2111 => [
                    0 => [
                        0 => [
                            '_route' => 'employees.show',
                        ],
                        1 => [
                            0 => 'employee',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                2125 => [
                    0 => [
                        0 => [
                            '_route' => 'employees.edit',
                        ],
                        1 => [
                            0 => 'employee',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                2134 => [
                    0 => [
                        0 => [
                            '_route' => 'employees.update',
                        ],
                        1 => [
                            0 => 'employee',
                        ],
                        2 => [
                            'PUT' => 0,
                            'PATCH' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'employees.destroy',
                        ],
                        1 => [
                            0 => 'employee',
                        ],
                        2 => [
                            'DELETE' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                2165 => [
                    0 => [
                        0 => [
                            '_route' => 'leave.show',
                        ],
                        1 => [
                            0 => 'leave',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                2182 => [
                    0 => [
                        0 => [
                            '_route' => 'leave.edit',
                        ],
                        1 => [
                            0 => 'leave',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                2198 => [
                    0 => [
                        0 => [
                            '_route' => 'leave.approve',
                        ],
                        1 => [
                            0 => 'leave',
                        ],
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                2213 => [
                    0 => [
                        0 => [
                            '_route' => 'leave.reject',
                        ],
                        1 => [
                            0 => 'leave',
                        ],
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                2223 => [
                    0 => [
                        0 => [
                            '_route' => 'leave.update',
                        ],
                        1 => [
                            0 => 'leave',
                        ],
                        2 => [
                            'PUT' => 0,
                            'PATCH' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'leave.destroy',
                        ],
                        1 => [
                            0 => 'leave',
                        ],
                        2 => [
                            'DELETE' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                2249 => [
                    0 => [
                        0 => [
                            '_route' => 'loans.show',
                        ],
                        1 => [
                            0 => 'loan',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                2266 => [
                    0 => [
                        0 => [
                            '_route' => 'loans.edit',
                        ],
                        1 => [
                            0 => 'loan',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                2282 => [
                    0 => [
                        0 => [
                            '_route' => 'loans.approve',
                        ],
                        1 => [
                            0 => 'loan',
                        ],
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                2297 => [
                    0 => [
                        0 => [
                            '_route' => 'loans.reject',
                        ],
                        1 => [
                            0 => 'loan',
                        ],
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                2307 => [
                    0 => [
                        0 => [
                            '_route' => 'loans.update',
                        ],
                        1 => [
                            0 => 'loan',
                        ],
                        2 => [
                            'PUT' => 0,
                            'PATCH' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'loans.destroy',
                        ],
                        1 => [
                            0 => 'loan',
                        ],
                        2 => [
                            'DELETE' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                2338 => [
                    0 => [
                        0 => [
                            '_route' => 'bonuses.show',
                        ],
                        1 => [
                            0 => 'bonus',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                2355 => [
                    0 => [
                        0 => [
                            '_route' => 'bonuses.edit',
                        ],
                        1 => [
                            0 => 'bonus',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                2371 => [
                    0 => [
                        0 => [
                            '_route' => 'bonuses.approve',
                        ],
                        1 => [
                            0 => 'bonus',
                        ],
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                2390 => [
                    0 => [
                        0 => [
                            '_route' => 'bonuses.mark-paid',
                        ],
                        1 => [
                            0 => 'bonus',
                        ],
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                2400 => [
                    0 => [
                        0 => [
                            '_route' => 'bonuses.update',
                        ],
                        1 => [
                            0 => 'bonus',
                        ],
                        2 => [
                            'PUT' => 0,
                            'PATCH' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'bonuses.destroy',
                        ],
                        1 => [
                            0 => 'bonus',
                        ],
                        2 => [
                            'DELETE' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                2433 => [
                    0 => [
                        0 => [
                            '_route' => 'deductions.show',
                        ],
                        1 => [
                            0 => 'deduction',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                2450 => [
                    0 => [
                        0 => [
                            '_route' => 'deductions.edit',
                        ],
                        1 => [
                            0 => 'deduction',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                2466 => [
                    0 => [
                        0 => [
                            '_route' => 'deductions.approve',
                        ],
                        1 => [
                            0 => 'deduction',
                        ],
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                2489 => [
                    0 => [
                        0 => [
                            '_route' => 'deductions.mark-deducted',
                        ],
                        1 => [
                            0 => 'deduction',
                        ],
                        2 => [
                            'POST' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                2499 => [
                    0 => [
                        0 => [
                            '_route' => 'deductions.update',
                        ],
                        1 => [
                            0 => 'deduction',
                        ],
                        2 => [
                            'PUT' => 0,
                            'PATCH' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'deductions.destroy',
                        ],
                        1 => [
                            0 => 'deduction',
                        ],
                        2 => [
                            'DELETE' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                2561 => [
                    0 => [
                        0 => [
                            '_route' => 'reparaciones.gastos.edit',
                        ],
                        1 => [
                            0 => 'operationalExpense',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                2570 => [
                    0 => [
                        0 => [
                            '_route' => 'reparaciones.gastos.update',
                        ],
                        1 => [
                            0 => 'operationalExpense',
                        ],
                        2 => [
                            'PUT' => 0,
                            'PATCH' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'reparaciones.gastos.show',
                        ],
                        1 => [
                            0 => 'operationalExpense',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                    2 => [
                        0 => [
                            '_route' => 'reparaciones.gastos.destroy',
                        ],
                        1 => [
                            0 => 'operationalExpense',
                        ],
                        2 => [
                            'DELETE' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                2588 => [
                    0 => [
                        0 => [
                            '_route' => 'reparaciones.show',
                        ],
                        1 => [
                            0 => 'id',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                2612 => [
                    0 => [
                        0 => [
                            '_route' => 'reparaciones.ticket',
                        ],
                        1 => [
                            0 => 'id',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                2633 => [
                    0 => [
                        0 => [
                            '_route' => 'reparaciones.pdf',
                        ],
                        1 => [
                            0 => 'id',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                2655 => [
                    0 => [
                        0 => [
                            '_route' => 'reparaciones.edit',
                        ],
                        1 => [
                            0 => 'id',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                2672 => [
                    0 => [
                        0 => [
                            '_route' => 'reparaciones.update',
                        ],
                        1 => [
                            0 => 'id',
                        ],
                        2 => [
                            'PUT' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                2696 => [
                    0 => [
                        0 => [
                            '_route' => 'reparaciones.status',
                        ],
                        1 => [
                            0 => 'id',
                        ],
                        2 => [
                            'PATCH' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                ],
                2713 => [
                    0 => [
                        0 => [
                            '_route' => 'reparaciones.destroy',
                        ],
                        1 => [
                            0 => 'id',
                        ],
                        2 => [
                            'DELETE' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                2746 => [
                    0 => [
                        0 => [
                            '_route' => 'ajustes.show',
                        ],
                        1 => [
                            0 => 'id',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                    1 => [
                        0 => [
                            '_route' => 'ajustes.destroy',
                        ],
                        1 => [
                            0 => 'id',
                        ],
                        2 => [
                            'DELETE' => 0,
                        ],
                        3 => null,
                        4 => false,
                        5 => true,
                        6 => null,
                    ],
                ],
                2781 => [
                    0 => [
                        0 => [
                            '_route' => 'api.products.info',
                        ],
                        1 => [
                            0 => 'id',
                        ],
                        2 => [
                            'GET' => 0,
                            'HEAD' => 1,
                        ],
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => null,
                    ],
                    1 => [
                        0 => null,
                        1 => null,
                        2 => null,
                        3 => null,
                        4 => false,
                        5 => false,
                        6 => 0,
                    ],
                ],
            ],
            4 => null,
        ],
        'attributes' => [
            'generated::qf1vvNlpcPciFOaO' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'up',
                'action' => [
                    'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:0:{}s:8:"function";s:828:"function () {
                    $exception = null;

                    try {
                        \\Illuminate\\Support\\Facades\\Event::dispatch(new \\Illuminate\\Foundation\\Events\\DiagnosingHealth);
                    } catch (\\Throwable $e) {
                        if (app()->hasDebugModeEnabled()) {
                            throw $e;
                        }

                        report($e);

                        $exception = $e->getMessage();
                    }

                    return response(\\Illuminate\\Support\\Facades\\View::file(\'/Users/harrintonjiron/agroservicio/vendor/laravel/framework/src/Illuminate/Foundation/Configuration\'.\'/../resources/health-up.blade.php\', [
                        \'exception\' => $exception,
                    ]), status: $exception ? 500 : 200);
                }";s:5:"scope";s:54:"Illuminate\\Foundation\\Configuration\\ApplicationBuilder";s:4:"this";N;s:4:"self";s:32:"00000000000008520000000000000000";}}',
                    'as' => 'generated::qf1vvNlpcPciFOaO',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'media.show' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'media/{directory}/{filename}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                    ],
                    'uses' => 'App\\Http\\Controllers\\PublicImageController@__invoke',
                    'controller' => 'App\\Http\\Controllers\\PublicImageController',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'media.show',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                    'directory' => 'company|products',
                    'filename' => '[A-Za-z0-9._-]+',
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'login' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'login',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                    ],
                    'uses' => 'App\\Http\\Controllers\\AuthController@showLogin',
                    'controller' => 'App\\Http\\Controllers\\AuthController@showLogin',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'login',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'generated::sttjQUmthzJTnn36' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'login',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                    ],
                    'uses' => 'App\\Http\\Controllers\\AuthController@login',
                    'controller' => 'App\\Http\\Controllers\\AuthController@login',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'generated::sttjQUmthzJTnn36',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'password.change' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'password/change',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                    ],
                    'uses' => 'App\\Http\\Controllers\\PasswordChangeController@edit',
                    'controller' => 'App\\Http\\Controllers\\PasswordChangeController@edit',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'password.change',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'password.update' => [
                'methods' => [
                    0 => 'PUT',
                ],
                'uri' => 'password/change',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                    ],
                    'uses' => 'App\\Http\\Controllers\\PasswordChangeController@update',
                    'controller' => 'App\\Http\\Controllers\\PasswordChangeController@update',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'password.update',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'home' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => '/',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                    ],
                    'uses' => 'App\\Http\\Controllers\\HomeController@index',
                    'controller' => 'App\\Http\\Controllers\\HomeController@index',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'home',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'access.unavailable' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'acceso-limitado',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                    ],
                    'uses' => 'App\\Http\\Controllers\\HomeController@unavailable',
                    'controller' => 'App\\Http\\Controllers\\HomeController@unavailable',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'access.unavailable',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'logout' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'logout',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                    ],
                    'uses' => 'App\\Http\\Controllers\\AuthController@logout',
                    'controller' => 'App\\Http\\Controllers\\AuthController@logout',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'logout',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'auth.switch-user' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'cambiar-usuario',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                    ],
                    'uses' => 'App\\Http\\Controllers\\AuthController@switchUser',
                    'controller' => 'App\\Http\\Controllers\\AuthController@switchUser',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'auth.switch-user',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'help.index' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'ayuda',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                    ],
                    'uses' => 'App\\Http\\Controllers\\HelpCenterController@__invoke',
                    'controller' => 'App\\Http\\Controllers\\HelpCenterController',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'help.index',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'facturacion.create' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'facturacion/create',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:ventas',
                        3 => 'permission:ventas.create',
                    ],
                    'uses' => 'App\\Http\\Controllers\\FacturacionController@create',
                    'controller' => 'App\\Http\\Controllers\\FacturacionController@create',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'facturacion.create',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'facturacion.index' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'facturacion',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:ventas',
                        3 => 'permission:ventas.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\FacturacionController@index',
                    'controller' => 'App\\Http\\Controllers\\FacturacionController@index',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'facturacion.index',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'facturacion.pos' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'facturacion/pos',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:ventas',
                        3 => 'permission:ventas.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\FacturacionController@pos',
                    'controller' => 'App\\Http\\Controllers\\FacturacionController@pos',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'facturacion.pos',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'facturacion.pos-products' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'facturacion/pos/products',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:ventas',
                        3 => 'permission:ventas.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\FacturacionController@posProducts',
                    'controller' => 'App\\Http\\Controllers\\FacturacionController@posProducts',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'facturacion.pos-products',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'facturacion.pos-daily-report' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'facturacion/pos/daily-report',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:ventas',
                        3 => 'permission:ventas.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\FacturacionController@posDailyReport',
                    'controller' => 'App\\Http\\Controllers\\FacturacionController@posDailyReport',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'facturacion.pos-daily-report',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'facturacion.change' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'facturacion/change/{saleId}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:ventas',
                        3 => 'permission:ventas.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\FacturacionController@change',
                    'controller' => 'App\\Http\\Controllers\\FacturacionController@change',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'facturacion.change',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'facturacion.receipt' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'facturacion/receipt/{saleId}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:ventas',
                        3 => 'permission:ventas.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\FacturacionController@receipt',
                    'controller' => 'App\\Http\\Controllers\\FacturacionController@receipt',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'facturacion.receipt',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'facturacion.print' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'facturacion/print',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:ventas',
                        3 => 'permission:ventas.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\FacturacionController@print',
                    'controller' => 'App\\Http\\Controllers\\FacturacionController@print',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'facturacion.print',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'facturacion.pdf' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'facturacion/pdf',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:ventas',
                        3 => 'permission:ventas.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\FacturacionController@pdf',
                    'controller' => 'App\\Http\\Controllers\\FacturacionController@pdf',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'facturacion.pdf',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'facturacion.show' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'facturacion/{id}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:ventas',
                        3 => 'permission:ventas.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\FacturacionController@show',
                    'controller' => 'App\\Http\\Controllers\\FacturacionController@show',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'facturacion.show',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'facturacion.credit-override' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'facturacion/credit-override',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:ventas',
                        3 => 'permission:ventas.create',
                        4 => 'throttle:5,10',
                    ],
                    'uses' => 'App\\Http\\Controllers\\CreditOverrideController@__invoke',
                    'controller' => 'App\\Http\\Controllers\\CreditOverrideController',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'facturacion.credit-override',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'facturacion.pos-product-image' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'facturacion/pos/products/{product}/image',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:ventas',
                        3 => 'permission:ventas.create',
                        4 => 'permission:inventario.edit',
                    ],
                    'uses' => 'App\\Http\\Controllers\\FacturacionController@updateProductImage',
                    'controller' => 'App\\Http\\Controllers\\FacturacionController@updateProductImage',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'facturacion.pos-product-image',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                    'product' => '[0-9]+',
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'facturacion.pos-store' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'facturacion/pos-store',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:ventas',
                        3 => 'permission:ventas.create',
                    ],
                    'uses' => 'App\\Http\\Controllers\\FacturacionController@posStore',
                    'controller' => 'App\\Http\\Controllers\\FacturacionController@posStore',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'facturacion.pos-store',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'facturacion.edit' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'facturacion/{id}/edit',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:ventas',
                        3 => 'permission:ventas.edit',
                    ],
                    'uses' => 'App\\Http\\Controllers\\FacturacionController@edit',
                    'controller' => 'App\\Http\\Controllers\\FacturacionController@edit',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'facturacion.edit',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'facturacion.update' => [
                'methods' => [
                    0 => 'PUT',
                    1 => 'PATCH',
                ],
                'uri' => 'facturacion/{id}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:ventas',
                        3 => 'permission:ventas.edit',
                    ],
                    'uses' => 'App\\Http\\Controllers\\FacturacionController@update',
                    'controller' => 'App\\Http\\Controllers\\FacturacionController@update',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'facturacion.update',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'facturacion.destroy' => [
                'methods' => [
                    0 => 'DELETE',
                ],
                'uri' => 'facturacion/{id}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:ventas',
                        3 => 'permission:ventas.delete',
                    ],
                    'uses' => 'App\\Http\\Controllers\\FacturacionController@destroy',
                    'controller' => 'App\\Http\\Controllers\\FacturacionController@destroy',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'facturacion.destroy',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'creditos.index' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'creditos',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:creditos',
                        3 => 'permission:creditos.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\CreditController@index',
                    'controller' => 'App\\Http\\Controllers\\CreditController@index',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'creditos.index',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'creditos.search' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'creditos/search',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:creditos',
                        3 => 'permission:creditos.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\CreditController@search',
                    'controller' => 'App\\Http\\Controllers\\CreditController@search',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'creditos.search',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'creditos.statement' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'creditos/statement/{clientId}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:creditos',
                        3 => 'permission:creditos.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\CreditController@statement',
                    'controller' => 'App\\Http\\Controllers\\CreditController@statement',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'creditos.statement',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'creditos.show' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'creditos/cliente/{clientId}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:creditos',
                        3 => 'permission:creditos.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\CreditController@show',
                    'controller' => 'App\\Http\\Controllers\\CreditController@show',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'creditos.show',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'creditos.invoice' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'creditos/payment/{paymentId}/invoice',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:creditos',
                        3 => 'permission:creditos.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\CreditController@invoice',
                    'controller' => 'App\\Http\\Controllers\\CreditController@invoice',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'creditos.invoice',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'creditos.overdue' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'creditos/vencidos',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:creditos',
                        3 => 'permission:creditos.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\CreditController@overdue',
                    'controller' => 'App\\Http\\Controllers\\CreditController@overdue',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'creditos.overdue',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'creditos.report' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'creditos/reporte',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:creditos',
                        3 => 'permission:creditos.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\CreditController@report',
                    'controller' => 'App\\Http\\Controllers\\CreditController@report',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'creditos.report',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'creditos.create' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'creditos/abono/nuevo/{clientId}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:creditos',
                        3 => 'permission:creditos.create',
                    ],
                    'uses' => 'App\\Http\\Controllers\\CreditController@create',
                    'controller' => 'App\\Http\\Controllers\\CreditController@create',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'creditos.create',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'creditos.store' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'creditos/abono',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:creditos',
                        3 => 'permission:creditos.create',
                    ],
                    'uses' => 'App\\Http\\Controllers\\CreditController@store',
                    'controller' => 'App\\Http\\Controllers\\CreditController@store',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'creditos.store',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'creditos.export' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'creditos/reporte/export',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:creditos',
                        3 => 'permission:creditos.export',
                    ],
                    'uses' => 'App\\Http\\Controllers\\CreditController@export',
                    'controller' => 'App\\Http\\Controllers\\CreditController@export',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'creditos.export',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'arqueo.index' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'arqueo',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:caja',
                        3 => 'permission:caja.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ArqueoController@index',
                    'controller' => 'App\\Http\\Controllers\\ArqueoController@index',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'arqueo.index',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'arqueo.open' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'arqueo/open',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:caja',
                        3 => 'permission:caja.open',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ArqueoController@open',
                    'controller' => 'App\\Http\\Controllers\\ArqueoController@open',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'arqueo.open',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'arqueo.run' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'arqueo/run',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:caja',
                        3 => 'permission:caja.close',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ArqueoController@run',
                    'controller' => 'App\\Http\\Controllers\\ArqueoController@run',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'arqueo.run',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'inventario.index' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'inventario',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\InventarioController@index',
                    'controller' => 'App\\Http\\Controllers\\InventarioController@index',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'inventario.index',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'inventario.create' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'inventario/create',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.create',
                    ],
                    'uses' => 'App\\Http\\Controllers\\InventarioController@create',
                    'controller' => 'App\\Http\\Controllers\\InventarioController@create',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'inventario.create',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'inventario.quick' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'inventario/rapido',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.create',
                    ],
                    'uses' => 'App\\Http\\Controllers\\InventarioController@quick',
                    'controller' => 'App\\Http\\Controllers\\InventarioController@quick',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'inventario.quick',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'inventario.quick-store' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'inventario/rapido',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.create',
                    ],
                    'uses' => 'App\\Http\\Controllers\\InventarioController@quickStore',
                    'controller' => 'App\\Http\\Controllers\\InventarioController@quickStore',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'inventario.quick-store',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'inventario.lookup' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'inventario/buscar/{code}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\InventarioController@lookupCode',
                    'controller' => 'App\\Http\\Controllers\\InventarioController@lookupCode',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'inventario.lookup',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'inventario.store' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'inventario',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.create',
                    ],
                    'uses' => 'App\\Http\\Controllers\\InventarioController@store',
                    'controller' => 'App\\Http\\Controllers\\InventarioController@store',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'inventario.store',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'inventario.dashboard' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'inventario/dashboard',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\InventarioController@dashboard',
                    'controller' => 'App\\Http\\Controllers\\InventarioController@dashboard',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'inventario.dashboard',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'inventario.units.index' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'inventario/unidades',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\InventarioController@units',
                    'controller' => 'App\\Http\\Controllers\\InventarioController@units',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'inventario.units.index',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'inventario.units.store' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'inventario/unidades',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.create',
                    ],
                    'uses' => 'App\\Http\\Controllers\\InventarioController@storeUnit',
                    'controller' => 'App\\Http\\Controllers\\InventarioController@storeUnit',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'inventario.units.store',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'inventario.units.update' => [
                'methods' => [
                    0 => 'PUT',
                ],
                'uri' => 'inventario/unidades/{unit}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.edit',
                    ],
                    'uses' => 'App\\Http\\Controllers\\InventarioController@updateUnit',
                    'controller' => 'App\\Http\\Controllers\\InventarioController@updateUnit',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'inventario.units.update',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'inventario.convert' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'inventario/convertir',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.adjust',
                    ],
                    'uses' => 'App\\Http\\Controllers\\InventarioController@convertUnits',
                    'controller' => 'App\\Http\\Controllers\\InventarioController@convertUnits',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'inventario.convert',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'inventario.warehouses.index' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'inventario/bodegas',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\WarehouseController@index',
                    'controller' => 'App\\Http\\Controllers\\WarehouseController@index',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'inventario.warehouses.index',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'inventario.warehouses.create' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'inventario/bodegas/nueva',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.create',
                    ],
                    'uses' => 'App\\Http\\Controllers\\WarehouseController@create',
                    'controller' => 'App\\Http\\Controllers\\WarehouseController@create',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'inventario.warehouses.create',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'inventario.warehouses.store' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'inventario/bodegas',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.create',
                    ],
                    'uses' => 'App\\Http\\Controllers\\WarehouseController@store',
                    'controller' => 'App\\Http\\Controllers\\WarehouseController@store',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'inventario.warehouses.store',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'inventario.warehouses.show' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'inventario/bodegas/{warehouse}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\WarehouseController@show',
                    'controller' => 'App\\Http\\Controllers\\WarehouseController@show',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'inventario.warehouses.show',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'inventario.warehouses.edit' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'inventario/bodegas/{warehouse}/edit',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.edit',
                    ],
                    'uses' => 'App\\Http\\Controllers\\WarehouseController@edit',
                    'controller' => 'App\\Http\\Controllers\\WarehouseController@edit',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'inventario.warehouses.edit',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'inventario.warehouses.update' => [
                'methods' => [
                    0 => 'PUT',
                ],
                'uri' => 'inventario/bodegas/{warehouse}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.edit',
                    ],
                    'uses' => 'App\\Http\\Controllers\\WarehouseController@update',
                    'controller' => 'App\\Http\\Controllers\\WarehouseController@update',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'inventario.warehouses.update',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'inventario.warehouses.transfer' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'inventario/bodegas/{warehouse}/transferir',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.adjust',
                    ],
                    'uses' => 'App\\Http\\Controllers\\WarehouseController@transfer',
                    'controller' => 'App\\Http\\Controllers\\WarehouseController@transfer',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'inventario.warehouses.transfer',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'inventario.warehouses.shelves.store' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'inventario/bodegas/{warehouse}/estantes',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.edit',
                    ],
                    'uses' => 'App\\Http\\Controllers\\WarehouseController@storeShelf',
                    'controller' => 'App\\Http\\Controllers\\WarehouseController@storeShelf',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'inventario.warehouses.shelves.store',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'inventario.warehouses.shelves.destroy' => [
                'methods' => [
                    0 => 'DELETE',
                ],
                'uri' => 'inventario/bodegas/{warehouse}/estantes/{shelf}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.edit',
                    ],
                    'uses' => 'App\\Http\\Controllers\\WarehouseController@destroyShelf',
                    'controller' => 'App\\Http\\Controllers\\WarehouseController@destroyShelf',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'inventario.warehouses.shelves.destroy',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'inventario.warehouses.destroy' => [
                'methods' => [
                    0 => 'DELETE',
                ],
                'uri' => 'inventario/bodegas/{warehouse}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.delete',
                    ],
                    'uses' => 'App\\Http\\Controllers\\WarehouseController@destroy',
                    'controller' => 'App\\Http\\Controllers\\WarehouseController@destroy',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'inventario.warehouses.destroy',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'inventario.transfers.index' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'inventario/transferencias',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\WarehouseTransferController@index',
                    'controller' => 'App\\Http\\Controllers\\WarehouseTransferController@index',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'inventario.transfers.index',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'inventario.transfers.store' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'inventario/transferencias',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.adjust',
                    ],
                    'uses' => 'App\\Http\\Controllers\\WarehouseTransferController@store',
                    'controller' => 'App\\Http\\Controllers\\WarehouseTransferController@store',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'inventario.transfers.store',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'inventario.transfers.stock' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'inventario/transferencias/stock',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\WarehouseTransferController@stockAvailability',
                    'controller' => 'App\\Http\\Controllers\\WarehouseTransferController@stockAvailability',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'inventario.transfers.stock',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'inventario.price-lists.index' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'inventario/listas-precios',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\PriceListController@index',
                    'controller' => 'App\\Http\\Controllers\\PriceListController@index',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'inventario.price-lists.index',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'inventario.price-lists.create' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'inventario/listas-precios/nueva',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.create',
                    ],
                    'uses' => 'App\\Http\\Controllers\\PriceListController@create',
                    'controller' => 'App\\Http\\Controllers\\PriceListController@create',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'inventario.price-lists.create',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'inventario.price-lists.store' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'inventario/listas-precios',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.create',
                    ],
                    'uses' => 'App\\Http\\Controllers\\PriceListController@store',
                    'controller' => 'App\\Http\\Controllers\\PriceListController@store',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'inventario.price-lists.store',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'inventario.price-lists.show' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'inventario/listas-precios/{priceList}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\PriceListController@show',
                    'controller' => 'App\\Http\\Controllers\\PriceListController@show',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'inventario.price-lists.show',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'inventario.price-lists.edit' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'inventario/listas-precios/{priceList}/edit',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.edit',
                    ],
                    'uses' => 'App\\Http\\Controllers\\PriceListController@edit',
                    'controller' => 'App\\Http\\Controllers\\PriceListController@edit',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'inventario.price-lists.edit',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'inventario.price-lists.update' => [
                'methods' => [
                    0 => 'PUT',
                ],
                'uri' => 'inventario/listas-precios/{priceList}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.edit',
                    ],
                    'uses' => 'App\\Http\\Controllers\\PriceListController@update',
                    'controller' => 'App\\Http\\Controllers\\PriceListController@update',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'inventario.price-lists.update',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'inventario.price-lists.items.store' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'inventario/listas-precios/{priceList}/items',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.edit',
                    ],
                    'uses' => 'App\\Http\\Controllers\\PriceListController@storeItem',
                    'controller' => 'App\\Http\\Controllers\\PriceListController@storeItem',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'inventario.price-lists.items.store',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'inventario.price-lists.items.destroy' => [
                'methods' => [
                    0 => 'DELETE',
                ],
                'uri' => 'inventario/listas-precios/{priceList}/items/{item}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.delete',
                    ],
                    'uses' => 'App\\Http\\Controllers\\PriceListController@destroyItem',
                    'controller' => 'App\\Http\\Controllers\\PriceListController@destroyItem',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'inventario.price-lists.items.destroy',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'inventario.bulk' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'inventario/carga-masiva',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.create',
                    ],
                    'uses' => 'App\\Http\\Controllers\\InventarioController@bulk',
                    'controller' => 'App\\Http\\Controllers\\InventarioController@bulk',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'inventario.bulk',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'inventario.bulk-store' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'inventario/carga-masiva',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.create',
                    ],
                    'uses' => 'App\\Http\\Controllers\\InventarioController@bulkStore',
                    'controller' => 'App\\Http\\Controllers\\InventarioController@bulkStore',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'inventario.bulk-store',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'inventario.next-code' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'inventario/next-code',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.create',
                    ],
                    'uses' => 'App\\Http\\Controllers\\InventarioController@nextCode',
                    'controller' => 'App\\Http\\Controllers\\InventarioController@nextCode',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'inventario.next-code',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'inventario.reconcile' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'inventario/reconciliar',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.adjust',
                    ],
                    'uses' => 'App\\Http\\Controllers\\InventarioController@reconcile',
                    'controller' => 'App\\Http\\Controllers\\InventarioController@reconcile',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'inventario.reconcile',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'categorias.store' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'categorias',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.create',
                    ],
                    'uses' => 'App\\Http\\Controllers\\InventarioController@storeCategory',
                    'controller' => 'App\\Http\\Controllers\\InventarioController@storeCategory',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'categorias.store',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'inventario.export' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'inventario/export',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.export',
                    ],
                    'uses' => 'App\\Http\\Controllers\\InventarioController@export',
                    'controller' => 'App\\Http\\Controllers\\InventarioController@export',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'inventario.export',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'inventario.conversions.store' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'inventario/{id}/conversiones',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.edit',
                    ],
                    'uses' => 'App\\Http\\Controllers\\InventarioController@storeUnitConversion',
                    'controller' => 'App\\Http\\Controllers\\InventarioController@storeUnitConversion',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'inventario.conversions.store',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                    'id' => '[0-9]+',
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'inventario.conversions.default' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'inventario/{id}/conversiones/predeterminada',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.edit',
                    ],
                    'uses' => 'App\\Http\\Controllers\\InventarioController@setDefaultSaleUnit',
                    'controller' => 'App\\Http\\Controllers\\InventarioController@setDefaultSaleUnit',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'inventario.conversions.default',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                    'id' => '[0-9]+',
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'inventario.conversions.destroy' => [
                'methods' => [
                    0 => 'DELETE',
                ],
                'uri' => 'inventario/{id}/conversiones/{conversion}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.delete',
                    ],
                    'uses' => 'App\\Http\\Controllers\\InventarioController@destroyUnitConversion',
                    'controller' => 'App\\Http\\Controllers\\InventarioController@destroyUnitConversion',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'inventario.conversions.destroy',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                    'id' => '[0-9]+',
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'inventario.show' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'inventario/{id}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\InventarioController@show',
                    'controller' => 'App\\Http\\Controllers\\InventarioController@show',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'inventario.show',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                    'id' => '[0-9]+',
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'inventario.edit' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'inventario/{id}/edit',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.edit',
                    ],
                    'uses' => 'App\\Http\\Controllers\\InventarioController@edit',
                    'controller' => 'App\\Http\\Controllers\\InventarioController@edit',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'inventario.edit',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                    'id' => '[0-9]+',
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'inventario.update' => [
                'methods' => [
                    0 => 'PUT',
                    1 => 'PATCH',
                ],
                'uri' => 'inventario/{id}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.edit',
                    ],
                    'uses' => 'App\\Http\\Controllers\\InventarioController@update',
                    'controller' => 'App\\Http\\Controllers\\InventarioController@update',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'inventario.update',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                    'id' => '[0-9]+',
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'inventario.destroy' => [
                'methods' => [
                    0 => 'DELETE',
                ],
                'uri' => 'inventario/{id}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.delete',
                    ],
                    'uses' => 'App\\Http\\Controllers\\InventarioController@destroy',
                    'controller' => 'App\\Http\\Controllers\\InventarioController@destroy',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'inventario.destroy',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                    'id' => '[0-9]+',
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'movimientos.index' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'movimientos',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\MovimientosController@index',
                    'controller' => 'App\\Http\\Controllers\\MovimientosController@index',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'movimientos.index',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'dashboard.general' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'dashboard-general',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'permission:dashboard.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\DashboardController@index',
                    'controller' => 'App\\Http\\Controllers\\DashboardController@index',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'dashboard.general',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'analitica.index' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'analitica',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'permission:dashboard.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ExecutiveAnalyticsController@__invoke',
                    'controller' => 'App\\Http\\Controllers\\ExecutiveAnalyticsController',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'analitica.index',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'sucursales.index' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'sucursales',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'permission:dashboard.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\BranchController@index',
                    'controller' => 'App\\Http\\Controllers\\BranchController@index',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'sucursales.index',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'sucursales.create' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'sucursales/crear',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'permission:settings.update',
                    ],
                    'uses' => 'App\\Http\\Controllers\\BranchController@create',
                    'controller' => 'App\\Http\\Controllers\\BranchController@create',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'sucursales.create',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'sucursales.store' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'sucursales',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'permission:settings.update',
                    ],
                    'uses' => 'App\\Http\\Controllers\\BranchController@store',
                    'controller' => 'App\\Http\\Controllers\\BranchController@store',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'sucursales.store',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'sucursales.show' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'sucursales/{branch}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'permission:dashboard.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\BranchController@show',
                    'controller' => 'App\\Http\\Controllers\\BranchController@show',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'sucursales.show',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'sucursales.edit' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'sucursales/{branch}/editar',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'permission:settings.update',
                    ],
                    'uses' => 'App\\Http\\Controllers\\BranchController@edit',
                    'controller' => 'App\\Http\\Controllers\\BranchController@edit',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'sucursales.edit',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'sucursales.update' => [
                'methods' => [
                    0 => 'PUT',
                ],
                'uri' => 'sucursales/{branch}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'permission:settings.update',
                    ],
                    'uses' => 'App\\Http\\Controllers\\BranchController@update',
                    'controller' => 'App\\Http\\Controllers\\BranchController@update',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'sucursales.update',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'proveedores.index' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'proveedores',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:proveedores',
                        3 => 'permission:proveedores.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ProveedorController@index',
                    'controller' => 'App\\Http\\Controllers\\ProveedorController@index',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'proveedores.index',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'proveedores.show' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'proveedores/{id}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:proveedores',
                        3 => 'permission:proveedores.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ProveedorController@show',
                    'controller' => 'App\\Http\\Controllers\\ProveedorController@show',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'proveedores.show',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                    'id' => '[0-9]+',
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'proveedores.credit_info' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'proveedores/{id}/credit-info',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:proveedores',
                        3 => 'permission:proveedores.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ProveedorController@getCreditInfo',
                    'controller' => 'App\\Http\\Controllers\\ProveedorController@getCreditInfo',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'proveedores.credit_info',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                    'id' => '[0-9]+',
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'proveedores.create' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'proveedores/create',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:proveedores',
                        3 => 'permission:proveedores.create',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ProveedorController@create',
                    'controller' => 'App\\Http\\Controllers\\ProveedorController@create',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'proveedores.create',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'proveedores.store' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'proveedores',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:proveedores',
                        3 => 'permission:proveedores.create',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ProveedorController@store',
                    'controller' => 'App\\Http\\Controllers\\ProveedorController@store',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'proveedores.store',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'proveedores.edit' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'proveedores/{id}/edit',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:proveedores',
                        3 => 'permission:proveedores.edit',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ProveedorController@edit',
                    'controller' => 'App\\Http\\Controllers\\ProveedorController@edit',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'proveedores.edit',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                    'id' => '[0-9]+',
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'proveedores.update' => [
                'methods' => [
                    0 => 'PUT',
                    1 => 'PATCH',
                ],
                'uri' => 'proveedores/{id}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:proveedores',
                        3 => 'permission:proveedores.edit',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ProveedorController@update',
                    'controller' => 'App\\Http\\Controllers\\ProveedorController@update',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'proveedores.update',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                    'id' => '[0-9]+',
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'proveedores.destroy' => [
                'methods' => [
                    0 => 'DELETE',
                ],
                'uri' => 'proveedores/{id}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:proveedores',
                        3 => 'permission:proveedores.delete',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ProveedorController@destroy',
                    'controller' => 'App\\Http\\Controllers\\ProveedorController@destroy',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'proveedores.destroy',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                    'id' => '[0-9]+',
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'proveedores.export' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'proveedores/export',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:proveedores',
                        3 => 'permission:proveedores.export',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ProveedorController@export',
                    'controller' => 'App\\Http\\Controllers\\ProveedorController@export',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'proveedores.export',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'compras.products.search' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'compras/productos/buscar',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:compras',
                        3 => 'permission:compras.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\CompraController@searchProducts',
                    'controller' => 'App\\Http\\Controllers\\CompraController@searchProducts',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'compras.products.search',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'compras.products.next-code' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'compras/productos/siguiente-codigo',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:compras',
                        3 => 'permission:compras.create',
                    ],
                    'uses' => 'App\\Http\\Controllers\\CompraController@nextProductCode',
                    'controller' => 'App\\Http\\Controllers\\CompraController@nextProductCode',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'compras.products.next-code',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'compras.products.quick-store' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'compras/productos/rapido',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:compras',
                        3 => 'permission:compras.create',
                    ],
                    'uses' => 'App\\Http\\Controllers\\CompraController@quickStoreProduct',
                    'controller' => 'App\\Http\\Controllers\\CompraController@quickStoreProduct',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'compras.products.quick-store',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'compras.suppliers.quick-store' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'compras/proveedores/rapido',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:compras',
                        3 => 'permission:compras.create',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ProveedorController@quickStore',
                    'controller' => 'App\\Http\\Controllers\\ProveedorController@quickStore',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'compras.suppliers.quick-store',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'compras.index' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'compras',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:compras',
                        3 => 'permission:compras.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\CompraController@index',
                    'controller' => 'App\\Http\\Controllers\\CompraController@index',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'compras.index',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'compras.create' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'compras/create',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:compras',
                        3 => 'permission:compras.create',
                    ],
                    'uses' => 'App\\Http\\Controllers\\CompraController@create',
                    'controller' => 'App\\Http\\Controllers\\CompraController@create',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'compras.create',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'compras.show' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'compras/{id}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:compras',
                        3 => 'permission:compras.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\CompraController@show',
                    'controller' => 'App\\Http\\Controllers\\CompraController@show',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'compras.show',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'compras.store' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'compras',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:compras',
                        3 => 'permission:compras.create',
                    ],
                    'uses' => 'App\\Http\\Controllers\\CompraController@store',
                    'controller' => 'App\\Http\\Controllers\\CompraController@store',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'compras.store',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'compras.edit' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'compras/{id}/edit',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:compras',
                        3 => 'permission:compras.edit',
                    ],
                    'uses' => 'App\\Http\\Controllers\\CompraController@edit',
                    'controller' => 'App\\Http\\Controllers\\CompraController@edit',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'compras.edit',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'compras.update' => [
                'methods' => [
                    0 => 'PUT',
                    1 => 'PATCH',
                ],
                'uri' => 'compras/{id}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:compras',
                        3 => 'permission:compras.edit',
                    ],
                    'uses' => 'App\\Http\\Controllers\\CompraController@update',
                    'controller' => 'App\\Http\\Controllers\\CompraController@update',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'compras.update',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'compras.status' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'compras/{id}/estado',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:compras',
                        3 => 'permission:compras.edit',
                    ],
                    'uses' => 'App\\Http\\Controllers\\CompraController@updateStatus',
                    'controller' => 'App\\Http\\Controllers\\CompraController@updateStatus',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'compras.status',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'compras.destroy' => [
                'methods' => [
                    0 => 'DELETE',
                ],
                'uri' => 'compras/{id}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:compras',
                        3 => 'permission:compras.delete',
                    ],
                    'uses' => 'App\\Http\\Controllers\\CompraController@destroy',
                    'controller' => 'App\\Http\\Controllers\\CompraController@destroy',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'compras.destroy',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'clientes.index' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'clientes',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:clientes',
                        3 => 'permission:clientes.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ClienteController@index',
                    'controller' => 'App\\Http\\Controllers\\ClienteController@index',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'clientes.index',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'clientes.show' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'clientes/{id}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:clientes',
                        3 => 'permission:clientes.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ClienteController@show',
                    'controller' => 'App\\Http\\Controllers\\ClienteController@show',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'clientes.show',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                    'id' => '[0-9]+',
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'clientes.create' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'clientes/create',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:clientes',
                        3 => 'permission:clientes.create',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ClienteController@create',
                    'controller' => 'App\\Http\\Controllers\\ClienteController@create',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'clientes.create',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'clientes.store' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'clientes',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:clientes',
                        3 => 'permission:clientes.create',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ClienteController@store',
                    'controller' => 'App\\Http\\Controllers\\ClienteController@store',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'clientes.store',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'clientes.quick-store' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'clientes/quick-store',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:clientes',
                        3 => 'permission:clientes.create',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ClienteController@quickStore',
                    'controller' => 'App\\Http\\Controllers\\ClienteController@quickStore',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'clientes.quick-store',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'clientes.edit' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'clientes/{id}/edit',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:clientes',
                        3 => 'permission:clientes.edit',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ClienteController@edit',
                    'controller' => 'App\\Http\\Controllers\\ClienteController@edit',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'clientes.edit',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                    'id' => '[0-9]+',
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'clientes.update' => [
                'methods' => [
                    0 => 'PUT',
                    1 => 'PATCH',
                ],
                'uri' => 'clientes/{id}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:clientes',
                        3 => 'permission:clientes.edit',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ClienteController@update',
                    'controller' => 'App\\Http\\Controllers\\ClienteController@update',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'clientes.update',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                    'id' => '[0-9]+',
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'clientes.toggle_credit' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'clientes/{id}/toggle-credit',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:clientes',
                        3 => 'permission:clientes.edit',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ClienteController@toggleCredit',
                    'controller' => 'App\\Http\\Controllers\\ClienteController@toggleCredit',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'clientes.toggle_credit',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                    'id' => '[0-9]+',
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'clientes.destroy' => [
                'methods' => [
                    0 => 'DELETE',
                ],
                'uri' => 'clientes/{id}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:clientes',
                        3 => 'permission:clientes.delete',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ClienteController@destroy',
                    'controller' => 'App\\Http\\Controllers\\ClienteController@destroy',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'clientes.destroy',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                    'id' => '[0-9]+',
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'planilla.index' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'planilla',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\PlanillaController@index',
                    'controller' => 'App\\Http\\Controllers\\PlanillaController@index',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'planilla.index',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'planilla.charts' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'planilla/charts',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\PlanillaController@charts',
                    'controller' => 'App\\Http\\Controllers\\PlanillaController@charts',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'planilla.charts',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'rrhh.hub' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'rrhh',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\HumanResourcesHubController@hub',
                    'controller' => 'App\\Http\\Controllers\\HumanResourcesHubController@hub',
                    'as' => 'rrhh.hub',
                    'namespace' => null,
                    'prefix' => '/rrhh',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'rrhh.directory' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'rrhh/directorio',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\HumanResourcesHubController@directory',
                    'controller' => 'App\\Http\\Controllers\\HumanResourcesHubController@directory',
                    'as' => 'rrhh.directory',
                    'namespace' => null,
                    'prefix' => '/rrhh',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'rrhh.organigram' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'rrhh/organigrama',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\HumanResourcesHubController@organigram',
                    'controller' => 'App\\Http\\Controllers\\HumanResourcesHubController@organigram',
                    'as' => 'rrhh.organigram',
                    'namespace' => null,
                    'prefix' => '/rrhh',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'rrhh.attendance' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'rrhh/asistencia',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\HumanResourcesHubController@attendance',
                    'controller' => 'App\\Http\\Controllers\\HumanResourcesHubController@attendance',
                    'as' => 'rrhh.attendance',
                    'namespace' => null,
                    'prefix' => '/rrhh',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'rrhh.attendance.store' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'rrhh/asistencia',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.view',
                        4 => 'permission:planilla.edit',
                    ],
                    'uses' => 'App\\Http\\Controllers\\HumanResourcesHubController@storeAttendance',
                    'controller' => 'App\\Http\\Controllers\\HumanResourcesHubController@storeAttendance',
                    'as' => 'rrhh.attendance.store',
                    'namespace' => null,
                    'prefix' => '/rrhh',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'rrhh.shifts' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'rrhh/turnos',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\HumanResourcesHubController@shifts',
                    'controller' => 'App\\Http\\Controllers\\HumanResourcesHubController@shifts',
                    'as' => 'rrhh.shifts',
                    'namespace' => null,
                    'prefix' => '/rrhh',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'rrhh.thirteenth' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'rrhh/aguinaldo',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\HumanResourcesHubController@thirteenth',
                    'controller' => 'App\\Http\\Controllers\\HumanResourcesHubController@thirteenth',
                    'as' => 'rrhh.thirteenth',
                    'namespace' => null,
                    'prefix' => '/rrhh',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'rrhh.inss' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'rrhh/inss',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\HumanResourcesHubController@inss',
                    'controller' => 'App\\Http\\Controllers\\HumanResourcesHubController@inss',
                    'as' => 'rrhh.inss',
                    'namespace' => null,
                    'prefix' => '/rrhh',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'rrhh.evaluations' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'rrhh/evaluaciones',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\HumanResourcesHubController@evaluations',
                    'controller' => 'App\\Http\\Controllers\\HumanResourcesHubController@evaluations',
                    'as' => 'rrhh.evaluations',
                    'namespace' => null,
                    'prefix' => '/rrhh',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'rrhh.evaluations.store' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'rrhh/evaluaciones',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.view',
                        4 => 'permission:planilla.edit',
                    ],
                    'uses' => 'App\\Http\\Controllers\\HumanResourcesHubController@storeEvaluation',
                    'controller' => 'App\\Http\\Controllers\\HumanResourcesHubController@storeEvaluation',
                    'as' => 'rrhh.evaluations.store',
                    'namespace' => null,
                    'prefix' => '/rrhh',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'employees.index' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'employees',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.view',
                    ],
                    'as' => 'employees.index',
                    'uses' => 'App\\Http\\Controllers\\EmployeeController@index',
                    'controller' => 'App\\Http\\Controllers\\EmployeeController@index',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'employees.create' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'employees/create',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.create',
                    ],
                    'as' => 'employees.create',
                    'uses' => 'App\\Http\\Controllers\\EmployeeController@create',
                    'controller' => 'App\\Http\\Controllers\\EmployeeController@create',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'employees.store' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'employees',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.create',
                    ],
                    'as' => 'employees.store',
                    'uses' => 'App\\Http\\Controllers\\EmployeeController@store',
                    'controller' => 'App\\Http\\Controllers\\EmployeeController@store',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'employees.show' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'employees/{employee}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.view',
                    ],
                    'as' => 'employees.show',
                    'uses' => 'App\\Http\\Controllers\\EmployeeController@show',
                    'controller' => 'App\\Http\\Controllers\\EmployeeController@show',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'employees.edit' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'employees/{employee}/edit',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.edit',
                    ],
                    'as' => 'employees.edit',
                    'uses' => 'App\\Http\\Controllers\\EmployeeController@edit',
                    'controller' => 'App\\Http\\Controllers\\EmployeeController@edit',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'employees.update' => [
                'methods' => [
                    0 => 'PUT',
                    1 => 'PATCH',
                ],
                'uri' => 'employees/{employee}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.edit',
                    ],
                    'as' => 'employees.update',
                    'uses' => 'App\\Http\\Controllers\\EmployeeController@update',
                    'controller' => 'App\\Http\\Controllers\\EmployeeController@update',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'employees.destroy' => [
                'methods' => [
                    0 => 'DELETE',
                ],
                'uri' => 'employees/{employee}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.delete',
                    ],
                    'as' => 'employees.destroy',
                    'uses' => 'App\\Http\\Controllers\\EmployeeController@destroy',
                    'controller' => 'App\\Http\\Controllers\\EmployeeController@destroy',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'leave.index' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'leave',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.view',
                    ],
                    'as' => 'leave.index',
                    'uses' => 'App\\Http\\Controllers\\LeaveRequestController@index',
                    'controller' => 'App\\Http\\Controllers\\LeaveRequestController@index',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'leave.create' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'leave/create',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.create',
                    ],
                    'as' => 'leave.create',
                    'uses' => 'App\\Http\\Controllers\\LeaveRequestController@create',
                    'controller' => 'App\\Http\\Controllers\\LeaveRequestController@create',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'leave.store' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'leave',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.create',
                    ],
                    'as' => 'leave.store',
                    'uses' => 'App\\Http\\Controllers\\LeaveRequestController@store',
                    'controller' => 'App\\Http\\Controllers\\LeaveRequestController@store',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'leave.show' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'leave/{leave}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.view',
                    ],
                    'as' => 'leave.show',
                    'uses' => 'App\\Http\\Controllers\\LeaveRequestController@show',
                    'controller' => 'App\\Http\\Controllers\\LeaveRequestController@show',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'leave.edit' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'leave/{leave}/edit',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.edit',
                    ],
                    'as' => 'leave.edit',
                    'uses' => 'App\\Http\\Controllers\\LeaveRequestController@edit',
                    'controller' => 'App\\Http\\Controllers\\LeaveRequestController@edit',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'leave.update' => [
                'methods' => [
                    0 => 'PUT',
                    1 => 'PATCH',
                ],
                'uri' => 'leave/{leave}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.edit',
                    ],
                    'as' => 'leave.update',
                    'uses' => 'App\\Http\\Controllers\\LeaveRequestController@update',
                    'controller' => 'App\\Http\\Controllers\\LeaveRequestController@update',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'leave.destroy' => [
                'methods' => [
                    0 => 'DELETE',
                ],
                'uri' => 'leave/{leave}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.delete',
                    ],
                    'as' => 'leave.destroy',
                    'uses' => 'App\\Http\\Controllers\\LeaveRequestController@destroy',
                    'controller' => 'App\\Http\\Controllers\\LeaveRequestController@destroy',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'leave.approve' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'leave/{leave}/approve',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.approve',
                    ],
                    'uses' => 'App\\Http\\Controllers\\LeaveRequestController@approve',
                    'controller' => 'App\\Http\\Controllers\\LeaveRequestController@approve',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'leave.approve',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'leave.reject' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'leave/{leave}/reject',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.approve',
                    ],
                    'uses' => 'App\\Http\\Controllers\\LeaveRequestController@reject',
                    'controller' => 'App\\Http\\Controllers\\LeaveRequestController@reject',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'leave.reject',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'loans.index' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'loans',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.view',
                    ],
                    'as' => 'loans.index',
                    'uses' => 'App\\Http\\Controllers\\LoanController@index',
                    'controller' => 'App\\Http\\Controllers\\LoanController@index',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'loans.create' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'loans/create',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.create',
                    ],
                    'as' => 'loans.create',
                    'uses' => 'App\\Http\\Controllers\\LoanController@create',
                    'controller' => 'App\\Http\\Controllers\\LoanController@create',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'loans.store' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'loans',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.create',
                    ],
                    'as' => 'loans.store',
                    'uses' => 'App\\Http\\Controllers\\LoanController@store',
                    'controller' => 'App\\Http\\Controllers\\LoanController@store',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'loans.show' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'loans/{loan}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.view',
                    ],
                    'as' => 'loans.show',
                    'uses' => 'App\\Http\\Controllers\\LoanController@show',
                    'controller' => 'App\\Http\\Controllers\\LoanController@show',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'loans.edit' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'loans/{loan}/edit',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.edit',
                    ],
                    'as' => 'loans.edit',
                    'uses' => 'App\\Http\\Controllers\\LoanController@edit',
                    'controller' => 'App\\Http\\Controllers\\LoanController@edit',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'loans.update' => [
                'methods' => [
                    0 => 'PUT',
                    1 => 'PATCH',
                ],
                'uri' => 'loans/{loan}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.edit',
                    ],
                    'as' => 'loans.update',
                    'uses' => 'App\\Http\\Controllers\\LoanController@update',
                    'controller' => 'App\\Http\\Controllers\\LoanController@update',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'loans.destroy' => [
                'methods' => [
                    0 => 'DELETE',
                ],
                'uri' => 'loans/{loan}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.delete',
                    ],
                    'as' => 'loans.destroy',
                    'uses' => 'App\\Http\\Controllers\\LoanController@destroy',
                    'controller' => 'App\\Http\\Controllers\\LoanController@destroy',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'loans.approve' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'loans/{loan}/approve',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.approve',
                    ],
                    'uses' => 'App\\Http\\Controllers\\LoanController@approve',
                    'controller' => 'App\\Http\\Controllers\\LoanController@approve',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'loans.approve',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'loans.reject' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'loans/{loan}/reject',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.approve',
                    ],
                    'uses' => 'App\\Http\\Controllers\\LoanController@reject',
                    'controller' => 'App\\Http\\Controllers\\LoanController@reject',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'loans.reject',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'bonuses.index' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'bonuses',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.view',
                    ],
                    'as' => 'bonuses.index',
                    'uses' => 'App\\Http\\Controllers\\BonusController@index',
                    'controller' => 'App\\Http\\Controllers\\BonusController@index',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'bonuses.create' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'bonuses/create',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.create',
                    ],
                    'as' => 'bonuses.create',
                    'uses' => 'App\\Http\\Controllers\\BonusController@create',
                    'controller' => 'App\\Http\\Controllers\\BonusController@create',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'bonuses.store' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'bonuses',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.create',
                    ],
                    'as' => 'bonuses.store',
                    'uses' => 'App\\Http\\Controllers\\BonusController@store',
                    'controller' => 'App\\Http\\Controllers\\BonusController@store',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'bonuses.show' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'bonuses/{bonus}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.view',
                    ],
                    'as' => 'bonuses.show',
                    'uses' => 'App\\Http\\Controllers\\BonusController@show',
                    'controller' => 'App\\Http\\Controllers\\BonusController@show',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'bonuses.edit' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'bonuses/{bonus}/edit',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.edit',
                    ],
                    'as' => 'bonuses.edit',
                    'uses' => 'App\\Http\\Controllers\\BonusController@edit',
                    'controller' => 'App\\Http\\Controllers\\BonusController@edit',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'bonuses.update' => [
                'methods' => [
                    0 => 'PUT',
                    1 => 'PATCH',
                ],
                'uri' => 'bonuses/{bonus}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.edit',
                    ],
                    'as' => 'bonuses.update',
                    'uses' => 'App\\Http\\Controllers\\BonusController@update',
                    'controller' => 'App\\Http\\Controllers\\BonusController@update',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'bonuses.destroy' => [
                'methods' => [
                    0 => 'DELETE',
                ],
                'uri' => 'bonuses/{bonus}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.delete',
                    ],
                    'as' => 'bonuses.destroy',
                    'uses' => 'App\\Http\\Controllers\\BonusController@destroy',
                    'controller' => 'App\\Http\\Controllers\\BonusController@destroy',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'bonuses.approve' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'bonuses/{bonus}/approve',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.approve',
                    ],
                    'uses' => 'App\\Http\\Controllers\\BonusController@approve',
                    'controller' => 'App\\Http\\Controllers\\BonusController@approve',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'bonuses.approve',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'bonuses.mark-paid' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'bonuses/{bonus}/mark-paid',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.pay',
                    ],
                    'uses' => 'App\\Http\\Controllers\\BonusController@markAsPaid',
                    'controller' => 'App\\Http\\Controllers\\BonusController@markAsPaid',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'bonuses.mark-paid',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'deductions.index' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'deductions',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.view',
                    ],
                    'as' => 'deductions.index',
                    'uses' => 'App\\Http\\Controllers\\DeductionController@index',
                    'controller' => 'App\\Http\\Controllers\\DeductionController@index',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'deductions.create' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'deductions/create',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.create',
                    ],
                    'as' => 'deductions.create',
                    'uses' => 'App\\Http\\Controllers\\DeductionController@create',
                    'controller' => 'App\\Http\\Controllers\\DeductionController@create',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'deductions.store' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'deductions',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.create',
                    ],
                    'as' => 'deductions.store',
                    'uses' => 'App\\Http\\Controllers\\DeductionController@store',
                    'controller' => 'App\\Http\\Controllers\\DeductionController@store',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'deductions.show' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'deductions/{deduction}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.view',
                    ],
                    'as' => 'deductions.show',
                    'uses' => 'App\\Http\\Controllers\\DeductionController@show',
                    'controller' => 'App\\Http\\Controllers\\DeductionController@show',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'deductions.edit' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'deductions/{deduction}/edit',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.edit',
                    ],
                    'as' => 'deductions.edit',
                    'uses' => 'App\\Http\\Controllers\\DeductionController@edit',
                    'controller' => 'App\\Http\\Controllers\\DeductionController@edit',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'deductions.update' => [
                'methods' => [
                    0 => 'PUT',
                    1 => 'PATCH',
                ],
                'uri' => 'deductions/{deduction}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.edit',
                    ],
                    'as' => 'deductions.update',
                    'uses' => 'App\\Http\\Controllers\\DeductionController@update',
                    'controller' => 'App\\Http\\Controllers\\DeductionController@update',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'deductions.destroy' => [
                'methods' => [
                    0 => 'DELETE',
                ],
                'uri' => 'deductions/{deduction}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.delete',
                    ],
                    'as' => 'deductions.destroy',
                    'uses' => 'App\\Http\\Controllers\\DeductionController@destroy',
                    'controller' => 'App\\Http\\Controllers\\DeductionController@destroy',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'deductions.approve' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'deductions/{deduction}/approve',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.approve',
                    ],
                    'uses' => 'App\\Http\\Controllers\\DeductionController@approve',
                    'controller' => 'App\\Http\\Controllers\\DeductionController@approve',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'deductions.approve',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'deductions.mark-deducted' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'deductions/{deduction}/mark-deducted',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.pay',
                    ],
                    'uses' => 'App\\Http\\Controllers\\DeductionController@markAsDeducted',
                    'controller' => 'App\\Http\\Controllers\\DeductionController@markAsDeducted',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'deductions.mark-deducted',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'proformas.index' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'proformas',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:proformas',
                        3 => 'permission:proformas.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ProformaController@index',
                    'controller' => 'App\\Http\\Controllers\\ProformaController@index',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'proformas.index',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'proformas.show' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'proformas/{id}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:proformas',
                        3 => 'permission:proformas.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ProformaController@show',
                    'controller' => 'App\\Http\\Controllers\\ProformaController@show',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'proformas.show',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                    'id' => '[0-9]+',
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'proformas.pdf' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'proformas/{id}/pdf',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:proformas',
                        3 => 'permission:proformas.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ProformaController@pdf',
                    'controller' => 'App\\Http\\Controllers\\ProformaController@pdf',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'proformas.pdf',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                    'id' => '[0-9]+',
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'proformas.ticket' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'proformas/{id}/ticket',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:proformas',
                        3 => 'permission:proformas.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ProformaController@ticket',
                    'controller' => 'App\\Http\\Controllers\\ProformaController@ticket',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'proformas.ticket',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                    'id' => '[0-9]+',
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'proformas.pos' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'proformas/nueva',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:proformas',
                        3 => 'permission:proformas.create',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ProformaController@pos',
                    'controller' => 'App\\Http\\Controllers\\ProformaController@pos',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'proformas.pos',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'proformas.products' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'proformas/productos',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:proformas',
                        3 => 'permission:proformas.create',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ProformaController@products',
                    'controller' => 'App\\Http\\Controllers\\ProformaController@products',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'proformas.products',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'proformas.store' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'proformas',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:proformas',
                        3 => 'permission:proformas.create',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ProformaController@store',
                    'controller' => 'App\\Http\\Controllers\\ProformaController@store',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'proformas.store',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'proformas.status' => [
                'methods' => [
                    0 => 'PATCH',
                ],
                'uri' => 'proformas/{id}/status',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:proformas',
                        3 => 'permission:proformas.edit',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ProformaController@updateStatus',
                    'controller' => 'App\\Http\\Controllers\\ProformaController@updateStatus',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'proformas.status',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                    'id' => '[0-9]+',
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'proformas.destroy' => [
                'methods' => [
                    0 => 'DELETE',
                ],
                'uri' => 'proformas/{id}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:proformas',
                        3 => 'permission:proformas.delete',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ProformaController@destroy',
                    'controller' => 'App\\Http\\Controllers\\ProformaController@destroy',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'proformas.destroy',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                    'id' => '[0-9]+',
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'proformas.convert' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'proformas/{id}/convert',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:proformas',
                        3 => 'permission:proformas.convert',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ProformaController@convertToSale',
                    'controller' => 'App\\Http\\Controllers\\ProformaController@convertToSale',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'proformas.convert',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                    'id' => '[0-9]+',
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'device-brands.index' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'device-brands',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:reparaciones',
                        3 => 'permission:reparaciones.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\DeviceBrandController@index',
                    'controller' => 'App\\Http\\Controllers\\DeviceBrandController@index',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'device-brands.index',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'device-brands.store' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'device-brands',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:reparaciones',
                        3 => 'permission:reparaciones.create',
                    ],
                    'uses' => 'App\\Http\\Controllers\\DeviceBrandController@store',
                    'controller' => 'App\\Http\\Controllers\\DeviceBrandController@store',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'device-brands.store',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'repair-services.store' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'repair-services',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:reparaciones',
                        3 => 'permission:reparaciones.create',
                    ],
                    'uses' => 'App\\Http\\Controllers\\RepairServiceController@store',
                    'controller' => 'App\\Http\\Controllers\\RepairServiceController@store',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'repair-services.store',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'reparaciones.gastos.create' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'reparaciones/gastos-operativos/nuevo',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:reparaciones',
                        3 => 'permission:reparaciones.create_expenses',
                    ],
                    'uses' => 'App\\Http\\Controllers\\OperationalExpenseController@create',
                    'controller' => 'App\\Http\\Controllers\\OperationalExpenseController@create',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'reparaciones.gastos.create',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'reparaciones.gastos.store' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'reparaciones/gastos-operativos',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:reparaciones',
                        3 => 'permission:reparaciones.create_expenses',
                    ],
                    'uses' => 'App\\Http\\Controllers\\OperationalExpenseController@store',
                    'controller' => 'App\\Http\\Controllers\\OperationalExpenseController@store',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'reparaciones.gastos.store',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'reparaciones.gastos.edit' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'reparaciones/gastos-operativos/{operationalExpense}/edit',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:reparaciones',
                        3 => 'permission:reparaciones.edit_expenses',
                    ],
                    'uses' => 'App\\Http\\Controllers\\OperationalExpenseController@edit',
                    'controller' => 'App\\Http\\Controllers\\OperationalExpenseController@edit',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'reparaciones.gastos.edit',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'reparaciones.gastos.update' => [
                'methods' => [
                    0 => 'PUT',
                    1 => 'PATCH',
                ],
                'uri' => 'reparaciones/gastos-operativos/{operationalExpense}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:reparaciones',
                        3 => 'permission:reparaciones.edit_expenses',
                    ],
                    'uses' => 'App\\Http\\Controllers\\OperationalExpenseController@update',
                    'controller' => 'App\\Http\\Controllers\\OperationalExpenseController@update',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'reparaciones.gastos.update',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'reparaciones.gastos.index' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'reparaciones/gastos-operativos',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:reparaciones',
                        3 => 'permission:reparaciones.view_expenses',
                    ],
                    'uses' => 'App\\Http\\Controllers\\OperationalExpenseController@index',
                    'controller' => 'App\\Http\\Controllers\\OperationalExpenseController@index',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'reparaciones.gastos.index',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'reparaciones.gastos.show' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'reparaciones/gastos-operativos/{operationalExpense}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:reparaciones',
                        3 => 'permission:reparaciones.view_expenses',
                    ],
                    'uses' => 'App\\Http\\Controllers\\OperationalExpenseController@show',
                    'controller' => 'App\\Http\\Controllers\\OperationalExpenseController@show',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'reparaciones.gastos.show',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'reparaciones.gastos.destroy' => [
                'methods' => [
                    0 => 'DELETE',
                ],
                'uri' => 'reparaciones/gastos-operativos/{operationalExpense}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:reparaciones',
                        3 => 'permission:reparaciones.delete_expenses',
                    ],
                    'uses' => 'App\\Http\\Controllers\\OperationalExpenseController@destroy',
                    'controller' => 'App\\Http\\Controllers\\OperationalExpenseController@destroy',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'reparaciones.gastos.destroy',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'reparaciones.index' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'reparaciones',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:reparaciones',
                        3 => 'permission:reparaciones.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ReparacionController@index',
                    'controller' => 'App\\Http\\Controllers\\ReparacionController@index',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'reparaciones.index',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'reparaciones.show' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'reparaciones/{id}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:reparaciones',
                        3 => 'permission:reparaciones.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ReparacionController@show',
                    'controller' => 'App\\Http\\Controllers\\ReparacionController@show',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'reparaciones.show',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                    'id' => '[0-9]+',
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'reparaciones.ticket' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'reparaciones/{id}/ticket',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:reparaciones',
                        3 => 'permission:reparaciones.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ReparacionController@ticket',
                    'controller' => 'App\\Http\\Controllers\\ReparacionController@ticket',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'reparaciones.ticket',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                    'id' => '[0-9]+',
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'reparaciones.pdf' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'reparaciones/{id}/pdf',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:reparaciones',
                        3 => 'permission:reparaciones.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ReparacionController@pdf',
                    'controller' => 'App\\Http\\Controllers\\ReparacionController@pdf',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'reparaciones.pdf',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                    'id' => '[0-9]+',
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'reparaciones.create' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'reparaciones/nueva',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:reparaciones',
                        3 => 'permission:reparaciones.create',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ReparacionController@create',
                    'controller' => 'App\\Http\\Controllers\\ReparacionController@create',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'reparaciones.create',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'reparaciones.store' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'reparaciones',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:reparaciones',
                        3 => 'permission:reparaciones.create',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ReparacionController@store',
                    'controller' => 'App\\Http\\Controllers\\ReparacionController@store',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'reparaciones.store',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'reparaciones.edit' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'reparaciones/{id}/edit',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:reparaciones',
                        3 => 'permission:reparaciones.edit',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ReparacionController@edit',
                    'controller' => 'App\\Http\\Controllers\\ReparacionController@edit',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'reparaciones.edit',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                    'id' => '[0-9]+',
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'reparaciones.update' => [
                'methods' => [
                    0 => 'PUT',
                ],
                'uri' => 'reparaciones/{id}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:reparaciones',
                        3 => 'permission:reparaciones.edit',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ReparacionController@update',
                    'controller' => 'App\\Http\\Controllers\\ReparacionController@update',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'reparaciones.update',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                    'id' => '[0-9]+',
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'reparaciones.status' => [
                'methods' => [
                    0 => 'PATCH',
                ],
                'uri' => 'reparaciones/{id}/status',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:reparaciones',
                        3 => 'permission:reparaciones.edit',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ReparacionController@updateStatus',
                    'controller' => 'App\\Http\\Controllers\\ReparacionController@updateStatus',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'reparaciones.status',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                    'id' => '[0-9]+',
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'reparaciones.destroy' => [
                'methods' => [
                    0 => 'DELETE',
                ],
                'uri' => 'reparaciones/{id}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:reparaciones',
                        3 => 'permission:reparaciones.delete',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ReparacionController@destroy',
                    'controller' => 'App\\Http\\Controllers\\ReparacionController@destroy',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'reparaciones.destroy',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                    'id' => '[0-9]+',
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'reportes.index' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'reportes',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:reportes',
                        3 => 'permission:reportes.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ReporteController@index',
                    'controller' => 'App\\Http\\Controllers\\ReporteController@index',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'reportes.index',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'reportes.export' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'reportes/export',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:reportes',
                        3 => 'permission:reportes.view',
                        4 => 'permission:reportes.export',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ReporteController@exportExcel',
                    'controller' => 'App\\Http\\Controllers\\ReporteController@exportExcel',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'reportes.export',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'nomina.index' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'nomina',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\NominaController@index',
                    'controller' => 'App\\Http\\Controllers\\NominaController@index',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'nomina.index',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'nomina.charts' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'nomina/charts',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\NominaController@charts',
                    'controller' => 'App\\Http\\Controllers\\NominaController@charts',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'nomina.charts',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'nomina.pay' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'nomina/pagar',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.pay',
                    ],
                    'uses' => 'App\\Http\\Controllers\\NominaController@pay',
                    'controller' => 'App\\Http\\Controllers\\NominaController@pay',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'nomina.pay',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'nomina.ticket' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'nomina/ticket',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:planilla',
                        3 => 'permission:planilla.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\NominaController@ticket',
                    'controller' => 'App\\Http\\Controllers\\NominaController@ticket',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'nomina.ticket',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'ajustes.index' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'ajustes',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.adjust',
                    ],
                    'uses' => 'App\\Http\\Controllers\\AjusteInventarioController@index',
                    'controller' => 'App\\Http\\Controllers\\AjusteInventarioController@index',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'ajustes.index',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'ajustes.create' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'ajustes/create',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.adjust',
                    ],
                    'uses' => 'App\\Http\\Controllers\\AjusteInventarioController@create',
                    'controller' => 'App\\Http\\Controllers\\AjusteInventarioController@create',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'ajustes.create',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'ajustes.store' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'ajustes',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.adjust',
                    ],
                    'uses' => 'App\\Http\\Controllers\\AjusteInventarioController@store',
                    'controller' => 'App\\Http\\Controllers\\AjusteInventarioController@store',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'ajustes.store',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'ajustes.show' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'ajustes/{id}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.adjust',
                    ],
                    'uses' => 'App\\Http\\Controllers\\AjusteInventarioController@show',
                    'controller' => 'App\\Http\\Controllers\\AjusteInventarioController@show',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'ajustes.show',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'ajustes.destroy' => [
                'methods' => [
                    0 => 'DELETE',
                ],
                'uri' => 'ajustes/{id}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.adjust',
                    ],
                    'uses' => 'App\\Http\\Controllers\\AjusteInventarioController@destroy',
                    'controller' => 'App\\Http\\Controllers\\AjusteInventarioController@destroy',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'ajustes.destroy',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'api.products.info' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'api/products/{id}/info',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:inventario',
                        3 => 'permission:inventario.adjust',
                    ],
                    'uses' => 'App\\Http\\Controllers\\AjusteInventarioController@getProductInfo',
                    'controller' => 'App\\Http\\Controllers\\AjusteInventarioController@getProductInfo',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'api.products.info',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'settings.index' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'settings',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:configuracion',
                        3 => 'permission:configuracion.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\SettingsController@index',
                    'controller' => 'App\\Http\\Controllers\\SettingsController@index',
                    'as' => 'settings.index',
                    'namespace' => null,
                    'prefix' => '/settings',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'settings.users' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'settings/users',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:configuracion',
                        3 => 'permission:configuracion.view',
                        4 => 'permission:configuracion.manage_users',
                    ],
                    'uses' => 'App\\Http\\Controllers\\UserController@index',
                    'controller' => 'App\\Http\\Controllers\\UserController@index',
                    'as' => 'settings.users',
                    'namespace' => null,
                    'prefix' => '/settings',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'settings.users.create' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'settings/users/create',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:configuracion',
                        3 => 'permission:configuracion.view',
                        4 => 'permission:configuracion.manage_users',
                    ],
                    'uses' => 'App\\Http\\Controllers\\UserController@create',
                    'controller' => 'App\\Http\\Controllers\\UserController@create',
                    'as' => 'settings.users.create',
                    'namespace' => null,
                    'prefix' => '/settings',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'settings.users.store' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'settings/users',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:configuracion',
                        3 => 'permission:configuracion.view',
                        4 => 'permission:configuracion.manage_users',
                    ],
                    'uses' => 'App\\Http\\Controllers\\UserController@store',
                    'controller' => 'App\\Http\\Controllers\\UserController@store',
                    'as' => 'settings.users.store',
                    'namespace' => null,
                    'prefix' => '/settings',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'settings.users.show' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'settings/users/{user}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:configuracion',
                        3 => 'permission:configuracion.view',
                        4 => 'permission:configuracion.manage_users',
                    ],
                    'uses' => 'App\\Http\\Controllers\\UserController@show',
                    'controller' => 'App\\Http\\Controllers\\UserController@show',
                    'as' => 'settings.users.show',
                    'namespace' => null,
                    'prefix' => '/settings',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'settings.users.edit' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'settings/users/{user}/edit',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:configuracion',
                        3 => 'permission:configuracion.view',
                        4 => 'permission:configuracion.manage_users',
                    ],
                    'uses' => 'App\\Http\\Controllers\\UserController@edit',
                    'controller' => 'App\\Http\\Controllers\\UserController@edit',
                    'as' => 'settings.users.edit',
                    'namespace' => null,
                    'prefix' => '/settings',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'settings.users.update' => [
                'methods' => [
                    0 => 'PUT',
                    1 => 'PATCH',
                ],
                'uri' => 'settings/users/{user}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:configuracion',
                        3 => 'permission:configuracion.view',
                        4 => 'permission:configuracion.manage_users',
                    ],
                    'uses' => 'App\\Http\\Controllers\\UserController@update',
                    'controller' => 'App\\Http\\Controllers\\UserController@update',
                    'as' => 'settings.users.update',
                    'namespace' => null,
                    'prefix' => '/settings',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'settings.users.toggle-active' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'settings/users/{user}/toggle-active',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:configuracion',
                        3 => 'permission:configuracion.view',
                        4 => 'permission:configuracion.manage_users',
                    ],
                    'uses' => 'App\\Http\\Controllers\\UserController@toggleActive',
                    'controller' => 'App\\Http\\Controllers\\UserController@toggleActive',
                    'as' => 'settings.users.toggle-active',
                    'namespace' => null,
                    'prefix' => '/settings',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'settings.users.reset-password.form' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'settings/users/{user}/reset-password',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:configuracion',
                        3 => 'permission:configuracion.view',
                        4 => 'permission:configuracion.manage_users',
                    ],
                    'uses' => 'App\\Http\\Controllers\\UserController@resetPasswordForm',
                    'controller' => 'App\\Http\\Controllers\\UserController@resetPasswordForm',
                    'as' => 'settings.users.reset-password.form',
                    'namespace' => null,
                    'prefix' => '/settings',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'settings.users.reset-password' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'settings/users/{user}/reset-password',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:configuracion',
                        3 => 'permission:configuracion.view',
                        4 => 'permission:configuracion.manage_users',
                    ],
                    'uses' => 'App\\Http\\Controllers\\UserController@resetPassword',
                    'controller' => 'App\\Http\\Controllers\\UserController@resetPassword',
                    'as' => 'settings.users.reset-password',
                    'namespace' => null,
                    'prefix' => '/settings',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'settings.users.destroy' => [
                'methods' => [
                    0 => 'DELETE',
                ],
                'uri' => 'settings/users/{user}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:configuracion',
                        3 => 'permission:configuracion.view',
                        4 => 'permission:configuracion.manage_users',
                    ],
                    'uses' => 'App\\Http\\Controllers\\UserController@destroy',
                    'controller' => 'App\\Http\\Controllers\\UserController@destroy',
                    'as' => 'settings.users.destroy',
                    'namespace' => null,
                    'prefix' => '/settings',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'settings.roles' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'settings/roles',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:configuracion',
                        3 => 'permission:configuracion.view',
                        4 => 'permission:configuracion.manage_roles',
                    ],
                    'uses' => 'App\\Http\\Controllers\\RoleController@index',
                    'controller' => 'App\\Http\\Controllers\\RoleController@index',
                    'as' => 'settings.roles',
                    'namespace' => null,
                    'prefix' => '/settings',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'settings.roles.create' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'settings/roles/create',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:configuracion',
                        3 => 'permission:configuracion.view',
                        4 => 'permission:configuracion.manage_roles',
                    ],
                    'uses' => 'App\\Http\\Controllers\\RoleController@create',
                    'controller' => 'App\\Http\\Controllers\\RoleController@create',
                    'as' => 'settings.roles.create',
                    'namespace' => null,
                    'prefix' => '/settings',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'settings.roles.store' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'settings/roles',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:configuracion',
                        3 => 'permission:configuracion.view',
                        4 => 'permission:configuracion.manage_roles',
                    ],
                    'uses' => 'App\\Http\\Controllers\\RoleController@store',
                    'controller' => 'App\\Http\\Controllers\\RoleController@store',
                    'as' => 'settings.roles.store',
                    'namespace' => null,
                    'prefix' => '/settings',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'settings.roles.compare' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'settings/roles/compare',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:configuracion',
                        3 => 'permission:configuracion.view',
                        4 => 'permission:configuracion.manage_roles',
                    ],
                    'uses' => 'App\\Http\\Controllers\\RoleController@compare',
                    'controller' => 'App\\Http\\Controllers\\RoleController@compare',
                    'as' => 'settings.roles.compare',
                    'namespace' => null,
                    'prefix' => '/settings',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'settings.roles.clone.form' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'settings/roles/{role}/clone',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:configuracion',
                        3 => 'permission:configuracion.view',
                        4 => 'permission:configuracion.manage_roles',
                    ],
                    'uses' => 'App\\Http\\Controllers\\RoleController@cloneForm',
                    'controller' => 'App\\Http\\Controllers\\RoleController@cloneForm',
                    'as' => 'settings.roles.clone.form',
                    'namespace' => null,
                    'prefix' => '/settings',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'settings.roles.clone' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'settings/roles/{role}/clone',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:configuracion',
                        3 => 'permission:configuracion.view',
                        4 => 'permission:configuracion.manage_roles',
                    ],
                    'uses' => 'App\\Http\\Controllers\\RoleController@clone',
                    'controller' => 'App\\Http\\Controllers\\RoleController@clone',
                    'as' => 'settings.roles.clone',
                    'namespace' => null,
                    'prefix' => '/settings',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'settings.roles.delete.form' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'settings/roles/{role}/delete',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:configuracion',
                        3 => 'permission:configuracion.view',
                        4 => 'permission:configuracion.manage_roles',
                    ],
                    'uses' => 'App\\Http\\Controllers\\RoleController@deleteForm',
                    'controller' => 'App\\Http\\Controllers\\RoleController@deleteForm',
                    'as' => 'settings.roles.delete.form',
                    'namespace' => null,
                    'prefix' => '/settings',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'settings.roles.show' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'settings/roles/{role}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:configuracion',
                        3 => 'permission:configuracion.view',
                        4 => 'permission:configuracion.manage_roles',
                    ],
                    'uses' => 'App\\Http\\Controllers\\RoleController@show',
                    'controller' => 'App\\Http\\Controllers\\RoleController@show',
                    'as' => 'settings.roles.show',
                    'namespace' => null,
                    'prefix' => '/settings',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'settings.roles.edit' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'settings/roles/{role}/edit',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:configuracion',
                        3 => 'permission:configuracion.view',
                        4 => 'permission:configuracion.manage_roles',
                    ],
                    'uses' => 'App\\Http\\Controllers\\RoleController@edit',
                    'controller' => 'App\\Http\\Controllers\\RoleController@edit',
                    'as' => 'settings.roles.edit',
                    'namespace' => null,
                    'prefix' => '/settings',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'settings.roles.update' => [
                'methods' => [
                    0 => 'PUT',
                    1 => 'PATCH',
                ],
                'uri' => 'settings/roles/{role}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:configuracion',
                        3 => 'permission:configuracion.view',
                        4 => 'permission:configuracion.manage_roles',
                    ],
                    'uses' => 'App\\Http\\Controllers\\RoleController@update',
                    'controller' => 'App\\Http\\Controllers\\RoleController@update',
                    'as' => 'settings.roles.update',
                    'namespace' => null,
                    'prefix' => '/settings',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'settings.roles.destroy' => [
                'methods' => [
                    0 => 'DELETE',
                ],
                'uri' => 'settings/roles/{role}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:configuracion',
                        3 => 'permission:configuracion.view',
                        4 => 'permission:configuracion.manage_roles',
                    ],
                    'uses' => 'App\\Http\\Controllers\\RoleController@destroy',
                    'controller' => 'App\\Http\\Controllers\\RoleController@destroy',
                    'as' => 'settings.roles.destroy',
                    'namespace' => null,
                    'prefix' => '/settings',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'settings.permissions' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'settings/permissions',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:configuracion',
                        3 => 'permission:configuracion.view',
                        4 => 'permission:configuracion.manage_permissions',
                    ],
                    'uses' => 'App\\Http\\Controllers\\PermissionController@index',
                    'controller' => 'App\\Http\\Controllers\\PermissionController@index',
                    'as' => 'settings.permissions',
                    'namespace' => null,
                    'prefix' => '/settings',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'settings.general' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'settings/general',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:configuracion',
                        3 => 'permission:configuracion.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\SettingsController@general',
                    'controller' => 'App\\Http\\Controllers\\SettingsController@general',
                    'as' => 'settings.general',
                    'namespace' => null,
                    'prefix' => '/settings',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'settings.general.update' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'settings/general',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:configuracion',
                        3 => 'permission:configuracion.view',
                        4 => 'permission:configuracion.edit',
                    ],
                    'uses' => 'App\\Http\\Controllers\\SettingsController@updateGeneral',
                    'controller' => 'App\\Http\\Controllers\\SettingsController@updateGeneral',
                    'as' => 'settings.general.update',
                    'namespace' => null,
                    'prefix' => '/settings',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'settings.taxes.index' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'settings/taxes',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:configuracion',
                        3 => 'permission:configuracion.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\TaxController@index',
                    'controller' => 'App\\Http\\Controllers\\TaxController@index',
                    'as' => 'settings.taxes.index',
                    'namespace' => null,
                    'prefix' => '/settings',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'settings.taxes.display-mode.update' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'settings/taxes/display-mode',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:configuracion',
                        3 => 'permission:configuracion.view',
                        4 => 'permission:configuracion.edit',
                    ],
                    'uses' => 'App\\Http\\Controllers\\TaxController@updateDisplayMode',
                    'controller' => 'App\\Http\\Controllers\\TaxController@updateDisplayMode',
                    'as' => 'settings.taxes.display-mode.update',
                    'namespace' => null,
                    'prefix' => '/settings',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'settings.taxes.create' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'settings/taxes/create',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:configuracion',
                        3 => 'permission:configuracion.view',
                        4 => 'permission:configuracion.edit',
                    ],
                    'uses' => 'App\\Http\\Controllers\\TaxController@create',
                    'controller' => 'App\\Http\\Controllers\\TaxController@create',
                    'as' => 'settings.taxes.create',
                    'namespace' => null,
                    'prefix' => '/settings',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'settings.taxes.store' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'settings/taxes',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:configuracion',
                        3 => 'permission:configuracion.view',
                        4 => 'permission:configuracion.edit',
                    ],
                    'uses' => 'App\\Http\\Controllers\\TaxController@store',
                    'controller' => 'App\\Http\\Controllers\\TaxController@store',
                    'as' => 'settings.taxes.store',
                    'namespace' => null,
                    'prefix' => '/settings',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'settings.taxes.edit' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'settings/taxes/{tax}/edit',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:configuracion',
                        3 => 'permission:configuracion.view',
                        4 => 'permission:configuracion.edit',
                    ],
                    'uses' => 'App\\Http\\Controllers\\TaxController@edit',
                    'controller' => 'App\\Http\\Controllers\\TaxController@edit',
                    'as' => 'settings.taxes.edit',
                    'namespace' => null,
                    'prefix' => '/settings',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'settings.taxes.update' => [
                'methods' => [
                    0 => 'PUT',
                    1 => 'PATCH',
                ],
                'uri' => 'settings/taxes/{tax}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:configuracion',
                        3 => 'permission:configuracion.view',
                        4 => 'permission:configuracion.edit',
                    ],
                    'uses' => 'App\\Http\\Controllers\\TaxController@update',
                    'controller' => 'App\\Http\\Controllers\\TaxController@update',
                    'as' => 'settings.taxes.update',
                    'namespace' => null,
                    'prefix' => '/settings',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'settings.taxes.destroy' => [
                'methods' => [
                    0 => 'DELETE',
                ],
                'uri' => 'settings/taxes/{tax}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:configuracion',
                        3 => 'permission:configuracion.view',
                        4 => 'permission:configuracion.edit',
                    ],
                    'uses' => 'App\\Http\\Controllers\\TaxController@destroy',
                    'controller' => 'App\\Http\\Controllers\\TaxController@destroy',
                    'as' => 'settings.taxes.destroy',
                    'namespace' => null,
                    'prefix' => '/settings',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'settings.exchange-rates.index' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'settings/exchange-rates',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:configuracion',
                        3 => 'permission:configuracion.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ExchangeRateController@index',
                    'controller' => 'App\\Http\\Controllers\\ExchangeRateController@index',
                    'as' => 'settings.exchange-rates.index',
                    'namespace' => null,
                    'prefix' => '/settings',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'settings.exchange-rates.create' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'settings/exchange-rates/create',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:configuracion',
                        3 => 'permission:configuracion.view',
                        4 => 'permission:configuracion.edit',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ExchangeRateController@create',
                    'controller' => 'App\\Http\\Controllers\\ExchangeRateController@create',
                    'as' => 'settings.exchange-rates.create',
                    'namespace' => null,
                    'prefix' => '/settings',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'settings.exchange-rates.store' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'settings/exchange-rates',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:configuracion',
                        3 => 'permission:configuracion.view',
                        4 => 'permission:configuracion.edit',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ExchangeRateController@store',
                    'controller' => 'App\\Http\\Controllers\\ExchangeRateController@store',
                    'as' => 'settings.exchange-rates.store',
                    'namespace' => null,
                    'prefix' => '/settings',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'settings.exchange-rates.edit' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'settings/exchange-rates/{exchangeRate}/edit',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:configuracion',
                        3 => 'permission:configuracion.view',
                        4 => 'permission:configuracion.edit',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ExchangeRateController@edit',
                    'controller' => 'App\\Http\\Controllers\\ExchangeRateController@edit',
                    'as' => 'settings.exchange-rates.edit',
                    'namespace' => null,
                    'prefix' => '/settings',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'settings.exchange-rates.update' => [
                'methods' => [
                    0 => 'PUT',
                    1 => 'PATCH',
                ],
                'uri' => 'settings/exchange-rates/{exchangeRate}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:configuracion',
                        3 => 'permission:configuracion.view',
                        4 => 'permission:configuracion.edit',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ExchangeRateController@update',
                    'controller' => 'App\\Http\\Controllers\\ExchangeRateController@update',
                    'as' => 'settings.exchange-rates.update',
                    'namespace' => null,
                    'prefix' => '/settings',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'settings.exchange-rates.destroy' => [
                'methods' => [
                    0 => 'DELETE',
                ],
                'uri' => 'settings/exchange-rates/{exchangeRate}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:configuracion',
                        3 => 'permission:configuracion.view',
                        4 => 'permission:configuracion.edit',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ExchangeRateController@destroy',
                    'controller' => 'App\\Http\\Controllers\\ExchangeRateController@destroy',
                    'as' => 'settings.exchange-rates.destroy',
                    'namespace' => null,
                    'prefix' => '/settings',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'settings.modules' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'settings/modules',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:configuracion',
                        3 => 'permission:configuracion.view',
                        4 => 'permission:configuracion.manage_modules',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ModuleController@index',
                    'controller' => 'App\\Http\\Controllers\\ModuleController@index',
                    'as' => 'settings.modules',
                    'namespace' => null,
                    'prefix' => '/settings',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'settings.modules.update' => [
                'methods' => [
                    0 => 'PUT',
                ],
                'uri' => 'settings/modules',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:configuracion',
                        3 => 'permission:configuracion.view',
                        4 => 'permission:configuracion.manage_modules',
                    ],
                    'uses' => 'App\\Http\\Controllers\\ModuleController@update',
                    'controller' => 'App\\Http\\Controllers\\ModuleController@update',
                    'as' => 'settings.modules.update',
                    'namespace' => null,
                    'prefix' => '/settings',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'settings.security' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'settings/security',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:configuracion',
                        3 => 'permission:configuracion.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\SettingsController@security',
                    'controller' => 'App\\Http\\Controllers\\SettingsController@security',
                    'as' => 'settings.security',
                    'namespace' => null,
                    'prefix' => '/settings',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'settings.security.update' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'settings/security',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:configuracion',
                        3 => 'permission:configuracion.view',
                        4 => 'permission:configuracion.edit',
                    ],
                    'uses' => 'App\\Http\\Controllers\\SettingsController@security',
                    'controller' => 'App\\Http\\Controllers\\SettingsController@security',
                    'as' => 'settings.security.update',
                    'namespace' => null,
                    'prefix' => '/settings',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'settings.appearance' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'settings/appearance',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:configuracion',
                        3 => 'permission:configuracion.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\SettingsController@appearance',
                    'controller' => 'App\\Http\\Controllers\\SettingsController@appearance',
                    'as' => 'settings.appearance',
                    'namespace' => null,
                    'prefix' => '/settings',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'settings.appearance.update' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'settings/appearance',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:configuracion',
                        3 => 'permission:configuracion.view',
                        4 => 'permission:configuracion.edit',
                    ],
                    'uses' => 'App\\Http\\Controllers\\SettingsController@appearance',
                    'controller' => 'App\\Http\\Controllers\\SettingsController@appearance',
                    'as' => 'settings.appearance.update',
                    'namespace' => null,
                    'prefix' => '/settings',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'settings.sequences' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'settings/sequences',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:configuracion',
                        3 => 'permission:configuracion.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\SettingsController@sequences',
                    'controller' => 'App\\Http\\Controllers\\SettingsController@sequences',
                    'as' => 'settings.sequences',
                    'namespace' => null,
                    'prefix' => '/settings',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'settings.sequences.update' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'settings/sequences',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:configuracion',
                        3 => 'permission:configuracion.view',
                        4 => 'permission:configuracion.edit',
                    ],
                    'uses' => 'App\\Http\\Controllers\\SettingsController@sequences',
                    'controller' => 'App\\Http\\Controllers\\SettingsController@sequences',
                    'as' => 'settings.sequences.update',
                    'namespace' => null,
                    'prefix' => '/settings',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'settings.system-reset.create' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'settings/system-reset',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:configuracion',
                        3 => 'permission:configuracion.view',
                        4 => 'permission:configuracion.reset_system',
                    ],
                    'uses' => 'App\\Http\\Controllers\\SystemResetController@create',
                    'controller' => 'App\\Http\\Controllers\\SystemResetController@create',
                    'as' => 'settings.system-reset.create',
                    'namespace' => null,
                    'prefix' => '/settings',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'settings.system-reset.store' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'settings/system-reset',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:configuracion',
                        3 => 'permission:configuracion.view',
                        4 => 'permission:configuracion.reset_system',
                        5 => 'throttle:2,10',
                    ],
                    'uses' => 'App\\Http\\Controllers\\SystemResetController@store',
                    'controller' => 'App\\Http\\Controllers\\SystemResetController@store',
                    'as' => 'settings.system-reset.store',
                    'namespace' => null,
                    'prefix' => '/settings',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'contabilidad.dashboard' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'contabilidad',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:contabilidad',
                        3 => 'permission:contabilidad.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\AccountingDashboardController@__invoke',
                    'controller' => 'App\\Http\\Controllers\\AccountingDashboardController',
                    'as' => 'contabilidad.dashboard',
                    'namespace' => null,
                    'prefix' => '/contabilidad',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'contabilidad.reportes.pdf' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'contabilidad/reportes/{report}/pdf',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:contabilidad',
                        3 => 'permission:contabilidad.view',
                        4 => 'permission:contabilidad.export',
                    ],
                    'uses' => 'App\\Http\\Controllers\\AccountingReportExportController@pdf',
                    'controller' => 'App\\Http\\Controllers\\AccountingReportExportController@pdf',
                    'as' => 'contabilidad.reportes.pdf',
                    'namespace' => null,
                    'prefix' => '/contabilidad',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                    'report' => 'estado-resultados|balance-general|flujo-caja|balance-comprobacion|diario-general|mayor-general',
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'contabilidad.reportes.excel' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'contabilidad/reportes/{report}/excel',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:contabilidad',
                        3 => 'permission:contabilidad.view',
                        4 => 'permission:contabilidad.export',
                    ],
                    'uses' => 'App\\Http\\Controllers\\AccountingReportExportController@excel',
                    'controller' => 'App\\Http\\Controllers\\AccountingReportExportController@excel',
                    'as' => 'contabilidad.reportes.excel',
                    'namespace' => null,
                    'prefix' => '/contabilidad',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                    'report' => 'estado-resultados|balance-general|flujo-caja|balance-comprobacion|diario-general|mayor-general',
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'contabilidad.cuentas.index' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'contabilidad/cuentas',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:contabilidad',
                        3 => 'permission:contabilidad.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\AccountController@index',
                    'controller' => 'App\\Http\\Controllers\\AccountController@index',
                    'as' => 'contabilidad.cuentas.index',
                    'namespace' => null,
                    'prefix' => '/contabilidad',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'contabilidad.cuentas.create' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'contabilidad/cuentas/create',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:contabilidad',
                        3 => 'permission:contabilidad.view',
                        4 => 'permission:contabilidad.create',
                    ],
                    'uses' => 'App\\Http\\Controllers\\AccountController@create',
                    'controller' => 'App\\Http\\Controllers\\AccountController@create',
                    'as' => 'contabilidad.cuentas.create',
                    'namespace' => null,
                    'prefix' => '/contabilidad',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'contabilidad.cuentas.store' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'contabilidad/cuentas',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:contabilidad',
                        3 => 'permission:contabilidad.view',
                        4 => 'permission:contabilidad.create',
                    ],
                    'uses' => 'App\\Http\\Controllers\\AccountController@store',
                    'controller' => 'App\\Http\\Controllers\\AccountController@store',
                    'as' => 'contabilidad.cuentas.store',
                    'namespace' => null,
                    'prefix' => '/contabilidad',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'contabilidad.cuentas.edit' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'contabilidad/cuentas/{account}/edit',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:contabilidad',
                        3 => 'permission:contabilidad.view',
                        4 => 'permission:contabilidad.edit',
                    ],
                    'uses' => 'App\\Http\\Controllers\\AccountController@edit',
                    'controller' => 'App\\Http\\Controllers\\AccountController@edit',
                    'as' => 'contabilidad.cuentas.edit',
                    'namespace' => null,
                    'prefix' => '/contabilidad',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'contabilidad.cuentas.update' => [
                'methods' => [
                    0 => 'PUT',
                    1 => 'PATCH',
                ],
                'uri' => 'contabilidad/cuentas/{account}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:contabilidad',
                        3 => 'permission:contabilidad.view',
                        4 => 'permission:contabilidad.edit',
                    ],
                    'uses' => 'App\\Http\\Controllers\\AccountController@update',
                    'controller' => 'App\\Http\\Controllers\\AccountController@update',
                    'as' => 'contabilidad.cuentas.update',
                    'namespace' => null,
                    'prefix' => '/contabilidad',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'contabilidad.cuentas.destroy' => [
                'methods' => [
                    0 => 'DELETE',
                ],
                'uri' => 'contabilidad/cuentas/{account}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:contabilidad',
                        3 => 'permission:contabilidad.view',
                        4 => 'permission:contabilidad.delete',
                    ],
                    'uses' => 'App\\Http\\Controllers\\AccountController@destroy',
                    'controller' => 'App\\Http\\Controllers\\AccountController@destroy',
                    'as' => 'contabilidad.cuentas.destroy',
                    'namespace' => null,
                    'prefix' => '/contabilidad',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'contabilidad.asientos.index' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'contabilidad/asientos',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:contabilidad',
                        3 => 'permission:contabilidad.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\JournalEntryController@index',
                    'controller' => 'App\\Http\\Controllers\\JournalEntryController@index',
                    'as' => 'contabilidad.asientos.index',
                    'namespace' => null,
                    'prefix' => '/contabilidad',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'contabilidad.asientos.show' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'contabilidad/asientos/{journalEntry}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:contabilidad',
                        3 => 'permission:contabilidad.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\JournalEntryController@show',
                    'controller' => 'App\\Http\\Controllers\\JournalEntryController@show',
                    'as' => 'contabilidad.asientos.show',
                    'namespace' => null,
                    'prefix' => '/contabilidad',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'contabilidad.asientos.create' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'contabilidad/asientos-nuevo',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:contabilidad',
                        3 => 'permission:contabilidad.view',
                        4 => 'permission:contabilidad.create',
                    ],
                    'uses' => 'App\\Http\\Controllers\\JournalEntryController@create',
                    'controller' => 'App\\Http\\Controllers\\JournalEntryController@create',
                    'as' => 'contabilidad.asientos.create',
                    'namespace' => null,
                    'prefix' => '/contabilidad',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'contabilidad.asientos.store' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'contabilidad/asientos',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:contabilidad',
                        3 => 'permission:contabilidad.view',
                        4 => 'permission:contabilidad.create',
                    ],
                    'uses' => 'App\\Http\\Controllers\\JournalEntryController@store',
                    'controller' => 'App\\Http\\Controllers\\JournalEntryController@store',
                    'as' => 'contabilidad.asientos.store',
                    'namespace' => null,
                    'prefix' => '/contabilidad',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'contabilidad.asientos.post' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'contabilidad/asientos/{journalEntry}/contabilizar',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:contabilidad',
                        3 => 'permission:contabilidad.view',
                        4 => 'permission:contabilidad.edit',
                    ],
                    'uses' => 'App\\Http\\Controllers\\JournalEntryController@post',
                    'controller' => 'App\\Http\\Controllers\\JournalEntryController@post',
                    'as' => 'contabilidad.asientos.post',
                    'namespace' => null,
                    'prefix' => '/contabilidad',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'contabilidad.asientos.void' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'contabilidad/asientos/{journalEntry}/anular',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:contabilidad',
                        3 => 'permission:contabilidad.view',
                        4 => 'permission:contabilidad.edit',
                    ],
                    'uses' => 'App\\Http\\Controllers\\JournalEntryController@void',
                    'controller' => 'App\\Http\\Controllers\\JournalEntryController@void',
                    'as' => 'contabilidad.asientos.void',
                    'namespace' => null,
                    'prefix' => '/contabilidad',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'contabilidad.asientos.destroy' => [
                'methods' => [
                    0 => 'DELETE',
                ],
                'uri' => 'contabilidad/asientos/{journalEntry}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:contabilidad',
                        3 => 'permission:contabilidad.view',
                        4 => 'permission:contabilidad.delete',
                    ],
                    'uses' => 'App\\Http\\Controllers\\JournalEntryController@destroy',
                    'controller' => 'App\\Http\\Controllers\\JournalEntryController@destroy',
                    'as' => 'contabilidad.asientos.destroy',
                    'namespace' => null,
                    'prefix' => '/contabilidad',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'contabilidad.diario.index' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'contabilidad/diario',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:contabilidad',
                        3 => 'permission:contabilidad.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\DiarioController@index',
                    'controller' => 'App\\Http\\Controllers\\DiarioController@index',
                    'as' => 'contabilidad.diario.index',
                    'namespace' => null,
                    'prefix' => '/contabilidad',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'contabilidad.diario.export' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'contabilidad/diario/export',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:contabilidad',
                        3 => 'permission:contabilidad.view',
                        4 => 'permission:contabilidad.export',
                    ],
                    'uses' => 'App\\Http\\Controllers\\DiarioController@export',
                    'controller' => 'App\\Http\\Controllers\\DiarioController@export',
                    'as' => 'contabilidad.diario.export',
                    'namespace' => null,
                    'prefix' => '/contabilidad',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'contabilidad.mayor.index' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'contabilidad/mayor',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:contabilidad',
                        3 => 'permission:contabilidad.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\LedgerController@index',
                    'controller' => 'App\\Http\\Controllers\\LedgerController@index',
                    'as' => 'contabilidad.mayor.index',
                    'namespace' => null,
                    'prefix' => '/contabilidad',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'contabilidad.balance-comprobacion.index' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'contabilidad/balance-comprobacion',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:contabilidad',
                        3 => 'permission:contabilidad.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\TrialBalanceController@index',
                    'controller' => 'App\\Http\\Controllers\\TrialBalanceController@index',
                    'as' => 'contabilidad.balance-comprobacion.index',
                    'namespace' => null,
                    'prefix' => '/contabilidad',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'contabilidad.balance-comprobacion.export' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'contabilidad/balance-comprobacion/export',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:contabilidad',
                        3 => 'permission:contabilidad.view',
                        4 => 'permission:contabilidad.export',
                    ],
                    'uses' => 'App\\Http\\Controllers\\TrialBalanceController@export',
                    'controller' => 'App\\Http\\Controllers\\TrialBalanceController@export',
                    'as' => 'contabilidad.balance-comprobacion.export',
                    'namespace' => null,
                    'prefix' => '/contabilidad',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'contabilidad.estado-resultados.index' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'contabilidad/estado-resultados',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:contabilidad',
                        3 => 'permission:contabilidad.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\IncomeStatementController@index',
                    'controller' => 'App\\Http\\Controllers\\IncomeStatementController@index',
                    'as' => 'contabilidad.estado-resultados.index',
                    'namespace' => null,
                    'prefix' => '/contabilidad',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'contabilidad.estado-resultados.export' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'contabilidad/estado-resultados/export',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:contabilidad',
                        3 => 'permission:contabilidad.view',
                        4 => 'permission:contabilidad.export',
                    ],
                    'uses' => 'App\\Http\\Controllers\\IncomeStatementController@export',
                    'controller' => 'App\\Http\\Controllers\\IncomeStatementController@export',
                    'as' => 'contabilidad.estado-resultados.export',
                    'namespace' => null,
                    'prefix' => '/contabilidad',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'contabilidad.balance-general.index' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'contabilidad/balance-general',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:contabilidad',
                        3 => 'permission:contabilidad.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\BalanceSheetController@index',
                    'controller' => 'App\\Http\\Controllers\\BalanceSheetController@index',
                    'as' => 'contabilidad.balance-general.index',
                    'namespace' => null,
                    'prefix' => '/contabilidad',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'contabilidad.balance-general.export' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'contabilidad/balance-general/export',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:contabilidad',
                        3 => 'permission:contabilidad.view',
                        4 => 'permission:contabilidad.export',
                    ],
                    'uses' => 'App\\Http\\Controllers\\BalanceSheetController@export',
                    'controller' => 'App\\Http\\Controllers\\BalanceSheetController@export',
                    'as' => 'contabilidad.balance-general.export',
                    'namespace' => null,
                    'prefix' => '/contabilidad',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'contabilidad.flujo-caja.index' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'contabilidad/flujo-caja',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:contabilidad',
                        3 => 'permission:contabilidad.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\CashFlowController@index',
                    'controller' => 'App\\Http\\Controllers\\CashFlowController@index',
                    'as' => 'contabilidad.flujo-caja.index',
                    'namespace' => null,
                    'prefix' => '/contabilidad',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'contabilidad.flujo-caja.export' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'contabilidad/flujo-caja/export',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:contabilidad',
                        3 => 'permission:contabilidad.view',
                        4 => 'permission:contabilidad.export',
                    ],
                    'uses' => 'App\\Http\\Controllers\\CashFlowController@export',
                    'controller' => 'App\\Http\\Controllers\\CashFlowController@export',
                    'as' => 'contabilidad.flujo-caja.export',
                    'namespace' => null,
                    'prefix' => '/contabilidad',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'contabilidad.centros-costo.index' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'contabilidad/centros-costo',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:contabilidad',
                        3 => 'permission:contabilidad.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\CostCenterController@index',
                    'controller' => 'App\\Http\\Controllers\\CostCenterController@index',
                    'as' => 'contabilidad.centros-costo.index',
                    'namespace' => null,
                    'prefix' => '/contabilidad',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'contabilidad.centros-costo.analytics' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'contabilidad/centros-costo/analisis',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:contabilidad',
                        3 => 'permission:contabilidad.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\CostCenterController@analytics',
                    'controller' => 'App\\Http\\Controllers\\CostCenterController@analytics',
                    'as' => 'contabilidad.centros-costo.analytics',
                    'namespace' => null,
                    'prefix' => '/contabilidad',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'contabilidad.centros-costo.create' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'contabilidad/centros-costo/create',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:contabilidad',
                        3 => 'permission:contabilidad.view',
                        4 => 'permission:contabilidad.create',
                    ],
                    'uses' => 'App\\Http\\Controllers\\CostCenterController@create',
                    'controller' => 'App\\Http\\Controllers\\CostCenterController@create',
                    'as' => 'contabilidad.centros-costo.create',
                    'namespace' => null,
                    'prefix' => '/contabilidad',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'contabilidad.centros-costo.store' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'contabilidad/centros-costo',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:contabilidad',
                        3 => 'permission:contabilidad.view',
                        4 => 'permission:contabilidad.create',
                    ],
                    'uses' => 'App\\Http\\Controllers\\CostCenterController@store',
                    'controller' => 'App\\Http\\Controllers\\CostCenterController@store',
                    'as' => 'contabilidad.centros-costo.store',
                    'namespace' => null,
                    'prefix' => '/contabilidad',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'contabilidad.centros-costo.edit' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'contabilidad/centros-costo/{centro_costo}/edit',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:contabilidad',
                        3 => 'permission:contabilidad.view',
                        4 => 'permission:contabilidad.edit',
                    ],
                    'uses' => 'App\\Http\\Controllers\\CostCenterController@edit',
                    'controller' => 'App\\Http\\Controllers\\CostCenterController@edit',
                    'as' => 'contabilidad.centros-costo.edit',
                    'namespace' => null,
                    'prefix' => '/contabilidad',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'contabilidad.centros-costo.update' => [
                'methods' => [
                    0 => 'PUT',
                    1 => 'PATCH',
                ],
                'uri' => 'contabilidad/centros-costo/{centro_costo}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:contabilidad',
                        3 => 'permission:contabilidad.view',
                        4 => 'permission:contabilidad.edit',
                    ],
                    'uses' => 'App\\Http\\Controllers\\CostCenterController@update',
                    'controller' => 'App\\Http\\Controllers\\CostCenterController@update',
                    'as' => 'contabilidad.centros-costo.update',
                    'namespace' => null,
                    'prefix' => '/contabilidad',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'contabilidad.centros-costo.destroy' => [
                'methods' => [
                    0 => 'DELETE',
                ],
                'uri' => 'contabilidad/centros-costo/{centro_costo}',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:contabilidad',
                        3 => 'permission:contabilidad.view',
                        4 => 'permission:contabilidad.delete',
                    ],
                    'uses' => 'App\\Http\\Controllers\\CostCenterController@destroy',
                    'controller' => 'App\\Http\\Controllers\\CostCenterController@destroy',
                    'as' => 'contabilidad.centros-costo.destroy',
                    'namespace' => null,
                    'prefix' => '/contabilidad',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'contabilidad.periodos.index' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'contabilidad/periodos',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:contabilidad',
                        3 => 'permission:contabilidad.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\FiscalPeriodController@index',
                    'controller' => 'App\\Http\\Controllers\\FiscalPeriodController@index',
                    'as' => 'contabilidad.periodos.index',
                    'namespace' => null,
                    'prefix' => '/contabilidad',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'contabilidad.periodos.cerrar' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'contabilidad/periodos/{periodo}/cerrar',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:contabilidad',
                        3 => 'permission:contabilidad.view',
                        4 => 'permission:contabilidad.close_period',
                    ],
                    'uses' => 'App\\Http\\Controllers\\FiscalPeriodController@closeMonth',
                    'controller' => 'App\\Http\\Controllers\\FiscalPeriodController@closeMonth',
                    'as' => 'contabilidad.periodos.cerrar',
                    'namespace' => null,
                    'prefix' => '/contabilidad',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'contabilidad.periodos.reabrir' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'contabilidad/periodos/{periodo}/reabrir',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:contabilidad',
                        3 => 'permission:contabilidad.view',
                        4 => 'permission:contabilidad.close_period',
                    ],
                    'uses' => 'App\\Http\\Controllers\\FiscalPeriodController@reopenMonth',
                    'controller' => 'App\\Http\\Controllers\\FiscalPeriodController@reopenMonth',
                    'as' => 'contabilidad.periodos.reabrir',
                    'namespace' => null,
                    'prefix' => '/contabilidad',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'contabilidad.periodos.cerrar-anio' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'contabilidad/periodos/{periodo}/cerrar-anio',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:contabilidad',
                        3 => 'permission:contabilidad.view',
                        4 => 'permission:contabilidad.close_period',
                    ],
                    'uses' => 'App\\Http\\Controllers\\FiscalPeriodController@closeYear',
                    'controller' => 'App\\Http\\Controllers\\FiscalPeriodController@closeYear',
                    'as' => 'contabilidad.periodos.cerrar-anio',
                    'namespace' => null,
                    'prefix' => '/contabilidad',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'contabilidad.periodos.reabrir-anio' => [
                'methods' => [
                    0 => 'POST',
                ],
                'uri' => 'contabilidad/periodos/{periodo}/reabrir-anio',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:contabilidad',
                        3 => 'permission:contabilidad.view',
                        4 => 'permission:contabilidad.close_period',
                    ],
                    'uses' => 'App\\Http\\Controllers\\FiscalPeriodController@reopenYear',
                    'controller' => 'App\\Http\\Controllers\\FiscalPeriodController@reopenYear',
                    'as' => 'contabilidad.periodos.reabrir-anio',
                    'namespace' => null,
                    'prefix' => '/contabilidad',
                    'where' => [
                    ],
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'proveedores.productos' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'proveedores/{supplier}/productos',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:compras',
                        3 => 'permission:compras.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\CompraController@productosPorProveedor',
                    'controller' => 'App\\Http\\Controllers\\CompraController@productosPorProveedor',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'proveedores.productos',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'proveedores.productos.buscar' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'proveedores/{supplier}/productos/buscar',
                'action' => [
                    'middleware' => [
                        0 => 'web',
                        1 => 'auth',
                        2 => 'module:compras',
                        3 => 'permission:compras.view',
                    ],
                    'uses' => 'App\\Http\\Controllers\\CompraController@buscarProductos',
                    'controller' => 'App\\Http\\Controllers\\CompraController@buscarProductos',
                    'namespace' => null,
                    'prefix' => '',
                    'where' => [
                    ],
                    'as' => 'proveedores.productos.buscar',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'storage.local' => [
                'methods' => [
                    0 => 'GET',
                    1 => 'HEAD',
                ],
                'uri' => 'storage/{path}',
                'action' => [
                    'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:3:{s:4:"disk";s:5:"local";s:6:"config";a:5:{s:6:"driver";s:5:"local";s:4:"root";s:54:"/Users/harrintonjiron/agroservicio/storage/app/private";s:5:"serve";b:1;s:5:"throw";b:0;s:6:"report";b:0;}s:12:"isProduction";b:1;}s:8:"function";s:323:"function (\\Illuminate\\Http\\Request $request, string $path) use ($disk, $config, $isProduction) {
                    return (new \\Illuminate\\Filesystem\\ServeFile(
                        $disk,
                        $config,
                        $isProduction
                    ))($request, $path);
                }";s:5:"scope";s:47:"Illuminate\\Filesystem\\FilesystemServiceProvider";s:4:"this";N;s:4:"self";s:32:"00000000000008530000000000000000";}}',
                    'as' => 'storage.local',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                    'path' => '.*',
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
            'storage.local.upload' => [
                'methods' => [
                    0 => 'PUT',
                ],
                'uri' => 'storage/{path}',
                'action' => [
                    'uses' => 'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure":1:{s:12:"serializable";O:46:"Laravel\\SerializableClosure\\Serializers\\Native":5:{s:3:"use";a:3:{s:4:"disk";s:5:"local";s:6:"config";a:5:{s:6:"driver";s:5:"local";s:4:"root";s:54:"/Users/harrintonjiron/agroservicio/storage/app/private";s:5:"serve";b:1;s:5:"throw";b:0;s:6:"report";b:0;}s:12:"isProduction";b:1;}s:8:"function";s:325:"function (\\Illuminate\\Http\\Request $request, string $path) use ($disk, $config, $isProduction) {
                    return (new \\Illuminate\\Filesystem\\ReceiveFile(
                        $disk,
                        $config,
                        $isProduction
                    ))($request, $path);
                }";s:5:"scope";s:47:"Illuminate\\Filesystem\\FilesystemServiceProvider";s:4:"this";N;s:4:"self";s:32:"000000000000085c0000000000000000";}}',
                    'as' => 'storage.local.upload',
                ],
                'fallback' => false,
                'defaults' => [
                ],
                'wheres' => [
                    'path' => '.*',
                ],
                'bindingFields' => [
                ],
                'lockSeconds' => null,
                'waitSeconds' => null,
                'withTrashed' => false,
            ],
        ],
    ]
);
