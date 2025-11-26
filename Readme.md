🍫 Sistema de Ventas "Chocolates Helena"

Solución integral para la gestión comercial física y digital, unificando inventario, ventas y facturación en una plataforma robusta y escalable.

📖 Descripción del Proyecto

Este sistema web fue desarrollado a medida para la empresa "Chocolates Helena" con el objetivo de modernizar su infraestructura tecnológica. La plataforma resuelve la desconexión entre las ventas en tienda física y las ventas en línea, centralizando toda la información en una única base de datos relacional.

🌟 Características Principales

Gestión Híbrida: Administra ventas de mostrador (POS) y pedidos web (E-commerce) en tiempo real.

Arquitectura Centrada en Personas: Modelo de base de datos único donde una PERSONA puede tener múltiples roles (Cliente, Empleado, Contacto) sin duplicidad de datos.

Interfaz Premium "Chocolate": Diseño personalizado con paleta de colores de marca, sidebar estático, modo responsivo y animaciones sutiles.

KPIs en Tiempo Real: Tableros de control con estadísticas vitales (Ventas, Stock, Ingresos).

Control de Acceso (RBAC): Sistema de permisos granulares para Administradores, Vendedores y Clientes.

📂 Estructura del Proyecto (File System)

El sistema se organiza en la carpeta raíz SIS_VENTAS_C.H con la siguiente distribución exacta:

📁 backend/ (Lógica de Negocio)

Procesa formularios y transacciones (sin vista).

Autenticación: validar_login.php, registrar_cliente.php, actualizar_mi_clave.php, actualizar_mis_datos.php.

Gestión (CRUDs): * gestionar_producto.php, gestionar_categoria.php, gestionar_promocion.php.

gestionar_cliente.php, gestionar_empresa.php, gestionar_persona.php, gestionar_empleado.php, gestionar_usuario.php.

gestionar_contacto_empresa.php, gestionar_rol.php.

gestionar_cargo.php, gestionar_contrato.php.

gestionar_metodo_pago.php, gestionar_tipo_documento.php.

gestionar_departamento.php, gestionar_provincia.php, gestionar_distrito.php.

gestionar_valoracion.php, enviar_valoracion.php.

Ventas: registrar_venta.php (POS), gestionar_carrito.php procesar_checkout.php (Web), anular_venta.php, generar_comprobante.php.

📁 css/ (Estilos)

custom-layout.css: [CORE] Estilos maestros del panel administrativo/vendedor (Sidebar, colores, layout, tablas, modales).

formularios.css: Estilos específicos para modales y formularios de edición.

Estilos Frontend: index.css, mi_tienda.css, login.css, mi_cuenta.css, index.css, listado_ventas.css, productos.css, public_footer.css

listado_generales: Estilos específicos para modales, tablas de ediciónm etc.

📁 includes/ (Componentes)

conexion.php: Driver de conexión SQL Server.

seguridad.php: Middleware de sesión.

header.php, footer.php: Plantilla Admin (Sidebar, Navbar).

public_header.php, public_footer.php: Plantilla E-commerce.

login_header.php, login_footer.php: Plantilla Login.

paginador.php: Componente de paginación inteligente.

mi_cuenta_historial.php: Fragmento de historial para clientes.

📁 img/

Repositorio de almacenamiento para las imágenes de los productos.

📄 Archivos Raíz (Vistas)

🌍 Portal Público (Clientes)

index.php: Landing page.

tienda.php: Catálogo de productos con filtros.

producto_detalle.php: Vista individual.

carrito.php: Gestión del carrito.

checkout.php: Pasarela de pago.

pedido_confirmado.php: Confirmación.

login.php: Acceso unificado.

cliente_registro.php: Registro de clientes.

mi_cuenta.php: Panel de cliente (Historial, Datos).

⚙️ Panel Administrativo

Dashboard: dashboard.php.

Ventas: * ventas.php: Punto de Venta (POS).

listado_ventas.php: Historial de ventas.

comprobantes.php: Emisión de documentos.

Catálogo: * productos.php, categorias.php, promociones.php, valoraciones.php.

Directorio: * personas.php, clientes.php, empresas.php, contactos_empresa.php.

empleados.php, usuarios.php.

Reportes: * reportes.php: Ventas por fecha.

reporte_productos.php: Productos más vendidos.

reporte_clientes.php: Mejores clientes.

Configuración: * cargos.php, contratos.php, roles.php.

metodos_pago.php, tipos_documento.php.

departamentos.php, provincias.php, distritos.php.

Archivos Legacy (Edición): editar_producto.php (Otros módulos usan modales).

🗄️ Modelo de Base de Datos

El sistema utiliza una base de datos SQL Server llamada SIS_VENTAS_CHOCOLATES_HELENA.

Tablas Principales:

PERSONA: Tabla maestra de datos personales.

EMPRESA: Tabla maestra de datos jurídicos.

CLIENTE: Vincula a una Persona o Empresa como cliente.

EMPLEADO: Vincula a una Persona como empleado.

USUARIO: Credenciales de acceso vinculadas a una Persona.

PRODUCTO, CATEGORIA_PRODUCTO, PROMOCION.

VENTA, DETALLE_VENTA, BOLETA, FACTURA.

💻 Requisitos e Instalación

Requisitos del Servidor

Servidor Web: Apache (XAMPP, Laragon o IIS).

PHP: Versión 8.0 o superior.

Base de Datos: Microsoft SQL Server 2019+.

Drivers PHP: Es CRÍTICO tener habilitados los drivers de Microsoft para PHP en php.ini:

extension=php_sqlsrv_82_ts.dll

extension=php_pdo_sqlsrv_82_ts.dll

Pasos de Despliegue

Base de Datos:

Ejecuta el script SQL completo en tu instancia de SQL Server.

Archivos:

Copia la carpeta SIS_VENTAS_C.H a tu directorio público (htdocs o www).

Conexión:

Abre includes/conexion.php y configura tus credenciales:

$serverName = "TU_SERVIDOR"; // Ej: LAPTOP-NAME\SQLEXPRESS
$connectionOptions = array(
    "Database" => "SIS_VENTAS_CHOCOLATES_HELENA_V1",
    "Uid" => "TuUsuario",  Ej: sa
    "PWD" => "TuClave"
);

👤 Autores
Desarrollado por: 
-Jesús Tenorio (CEO)
-Diego Roque
-Danilo Salas
-Italo Saavedra
-Piero Aybar
-Andy Mondragon
-Rocio Vargas

 para el curso de Administracion de base de datos (DBA).

© 2025 Chocolates Helena - Todos los derechos reservados.