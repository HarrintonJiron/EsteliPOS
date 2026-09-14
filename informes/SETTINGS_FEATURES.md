# Funcionalidades del módulo de Configuración

Última actualización: 2026-08-01

## Disponibles

- Centro administrativo con 15 áreas visibles.
- Indicadores de usuarios, módulos, secciones accesibles y problemas detectados.
- Búsqueda instantánea de configuraciones.
- Filtros por General, Acceso, Operaciones y Sistema.
- Estados honestos: disponible, parcial, en desarrollo, planificado o no disponible.
- Navegación responsive con menú móvil accesible.
- Configuración general básica.
- Gestión completa de usuarios con búsqueda, filtros, paginación y detalle de actividad.
- Nombre de usuario, teléfono, foto, estado, último acceso y fecha de creación.
- Roles y permisos especiales acumulativos por usuario.
- Acceso compatible por correo electrónico o nombre de usuario.
- Restablecimiento seguro de contraseña sin mostrar ni registrar el texto plano.
- Cambio obligatorio de contraseña temporal antes de entrar al resto del sistema.
- Protección contra autoeliminación y contra desactivar, degradar o eliminar al último administrador activo.
- Conservación del historial operativo: cuentas referenciadas deben desactivarse en lugar de eliminarse.
- Catálogo operativo de 13 módulos con estado, orden, ruta, permiso requerido y fecha de activación/desactivación.
- Dependencias explícitas entre módulos y bloqueo de desactivaciones inseguras.
- Acceso a módulos por roles con privilegio mínimo y acceso total del Administrador únicamente mientras el módulo esté activo.
- Menú, accesos rápidos, consultas y widgets del dashboard ocultos cuando el módulo está inactivo o el rol no está autorizado.
- Protección backend de todas las rutas operativas mediante middleware de módulo.
- Confirmación accesible y auditoría de cada actualización del catálogo de módulos.
- Apariencia del sistema.
- Catálogo de impuestos dentro de Configuración, conectado con ventas, proformas y compras.
- Cálculo de IVA por producto, con respaldo en el impuesto activo predeterminado y tasa cero si no existe uno.
- Los impuestos inactivos no se aplican ni pueden permanecer como predeterminados.
- Perfil completo de empresa: nombre comercial, razón social, RUC, contacto, dirección, ciudad y país.
- Moneda, símbolo, zona horaria, formato de fecha e idioma globales.
- Logo principal y logo térmico con vista previa, validación y almacenamiento seguro.
- Pie de factura y mensaje personalizado para recibos.
- Identidad empresarial aplicada al menú, factura imprimible, factura PDF y recibo térmico.
- Logo empresarial destacado y crédito visible de Northlink Microsystem en la navegación y el inicio de sesión.
- Punto de Venta con guía y atajos de teclado para búsqueda, clientes, tickets, cobro y corte diario.
- Recibo POS optimizado para impresoras térmicas de 80 mm, con área imprimible segura y productos de longitud variable.
- Pagos con tarjeta compatibles con el flujo POS y conservación de su número de referencia.
- Auditoría de cambios de empresa con valores anteriores y nuevos.
- Auditoría de creación, edición, estado, restablecimiento, cambio y eliminación de usuarios.
- Roles con slug único, búsqueda, filtros, clonación y detalle de cobertura.
- Matriz global de 63 permisos en 13 módulos.
- Comparación simultánea de hasta cuatro roles.
- Selección masiva de permisos global y por módulo.
- Edición controlada de permisos para roles del sistema.
- Eliminación segura con reasignación obligatoria de usuarios.
- Autorización independiente para configuración, usuarios, roles y permisos.
- Menú y tarjetas administrativos alineados con los permisos del backend.
- Respaldo reversible de los 12 roles duplicados consolidados.
- Aviso de cambios sin guardar en el formulario de empresa.

## Mejoras técnicas activas

- Lectura compatible de ajustes legacy doblemente serializados.
- Escritura tipada de ajustes booleanos, enteros y flotantes.
- Bloqueo de login y Configuración para usuarios inactivos.
- Registro de `last_login_at` en nuevos ingresos exitosos.
- Compatibilidad temporal entre `users.role` y roles pivote.
- Invalidación automática del caché de módulos.
- Servicio central de acceso y servicio transaccional de administración de módulos.
- Seeders idempotentes para roles, módulos, ajustes y secuencias críticas.
- Reparaciones de datos respaldadas por tablas reversibles.

## Pendientes

Las áreas incompletas aparecen con la etiqueta **Próximamente**, sin enlaces a pantallas parciales. Numeraciones, Seguridad avanzada, Caja y facturación, Inventario, Respaldos, consulta general de Auditoría y Diagnóstico se completarán en la versión 2.0.
