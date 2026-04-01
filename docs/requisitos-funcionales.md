# Requisitos Funcionales - Optica Store MVP

## 1. Autenticación y Usuarios

### RF-001: Registro de Usuarios
El sistema debe permitir a los clientes registrarse con email y contraseña.
- Campos: nombre, apellido, email, teléfono, contraseña
- Validación de email único
- Contraseña mínima 8 caracteres

### RF-002: Inicio de Sesión
El sistema debe permitir a los usuarios registrados iniciar sesión.
- Login con email y contraseña
- Recordar sesión (remember me)
- Recuperación de contraseña (opcional MVP)

### RF-003: Gestión de Perfil
El sistema debe permitir a los usuarios gestionar su información personal.
- Editar datos de perfil
- Actualizar contraseña

---

## 2. Catálogo de Productos

### RF-010: Listado de Productos
El sistema debe mostrar un catálogo de productos光学.
- Listado paginado de productos
- Filtrar por categoría (lentes de contacto, marcos, cristales)
- Ordenar por precio, nombre, relevancia
- Búsqueda de productos

### RF-011: Detalle de Producto
El sistema debe mostrar información detallada de cada producto.
- Nombre, descripción, precio, imágenes
- Opciones de producto (variantes)
- Disponibilidad de stock

### RF-012: Variantes de Lentes de Contacto
El sistema debe gestionar variantes específicas para lentes de contacto.
- **Graduación (SPH/Power)**: Valores desde -20.00 hasta +20.00
- **Curva Base (BC)**: Valores típicos (8.3, 8.4, 8.5, 8.6, 8.7, 8.8, 8.9, 9.0)
- **Diámetro (DIA)**: Valores típicos (13.5, 14.0, 14.2, 14.5)
- **Eje (AXIS)**: Valores de 0° a 180° (para astigmatismo)
- **Cilindro (CYL)**: Valores típicos (-0.75, -1.00, -1.25, -1.50, -1.75, -2.00)
- **Ojo**: Selector Izquierdo (OI) / Derecho (OD)
- Cantidad por caja

### RF-013: Categorías
El sistema debe organizar productos en categorías.
- Lentes de Contacto
- Marcos de lentes
- Cristales
- Accesorios

---

## 3. Carrito de Compras

### RF-020: Agregar al Carrito
El sistema debe permitir agregar productos al carrito.
- Seleccionar variante (para lentes de contacto)
- Seleccionar cantidad
- Validar disponibilidad de stock

### RF-021: Gestionar Carrito
El sistema debe permitir modificar el carrito.
- Actualizar cantidad
- Eliminar productos
- Ver resumen de items y total

### RF-022: Subida de Receta Médica
El sistema debe permitir subir receta médica para productos que la requieran.
- Tipos permitidos: PDF, JPG, PNG
- Tamaño máximo: 5MB
- Vinculación de receta a línea de orden

---

## 4. Proceso de Checkout

### RF-030: Información de Envío
El sistema debe recolectar datos de envío del cliente.
- Dirección de entrega (calle, número, piso, ciudad, provincia, CP)
- Teléfono de contacto

### RF-031: Método de Envío
El sistema debe permitir seleccionar método de envío.
- **Andreani** (integración requerida)
- Opciones adicionales a explorar (OCA, Correo Argentino, etc.)
- Cálculo de costo basado en zona/peso

### RF-032: Método de Pago
El sistema debe permitir seleccionar método de pago.
- **Efectivo Contra Entrega** (disponible en MVP)
- **Tarjeta de crédito/débito** (Stripe - disponible en starter kit)
- **Mercado Pago** (a evaluar para Argentina)

### RF-033: Confirmación de Orden
El sistema debe confirmar la creación de la orden.
- Generar número de orden único
- Mostrar resumen de compra
- Enviar email de confirmación

---

## 5. Gestión de Órdenes

### RF-040: Estados de Orden
El sistema debe gestionar los estados de una orden.
- **Pendiente de Pago**: Orden creada, esperando pago
- **Pendiente de Revisión**: Orden con receta médica esperando aprobación
- **Aprobada**: Orden aprobada, lista para preparación
- **En Preparación**: Orden siendo preparada
- **Enviada**: Orden dispatched
- **Entregada**: Orden entregada
- **Cancelada**: Orden cancelada

### RF-041: Revisión de Recetas
El sistema debe permitir a administradores revisar recetas médicas.
- Visualizar imagen de receta
- Aprobar o rechazar orden
- Agregar comentarios de revisión

### RF-042: Historial de Órdenes
El sistema debe permitir a clientes ver su historial de órdenes.
- Listado de órdenes con estado
- Detalle de cada orden
- Tracking de envío (si aplica)

---

## 6. Notificaciones

### RF-050: Notificaciones por Email
El sistema debe enviar emails transaccionales.
- Confirmación de orden
- Cambio de estado de orden
- Aprobación/rechazo de receta

### RF-051: Notificaciones WhatsApp
El sistema debe enviar notificaciones por WhatsApp.
- Notificación de orden creada
- Notificación de estado actualizado
- Recordatorio de pago (opcional)

---

## 7. Administración

### RF-060: Gestión de Productos
El sistema debe permitir a administradores gestionar productos.
- Crear, editar, eliminar productos
- Gestionar variantes y opciones
- Configurar stock

### RF-061: Gestión de Órdenes
El sistema debe permitir a administradores gestionar órdenes.
- Listado de órdenes
- Cambiar estado de orden
- Revisar recetas médicas

### RF-062: Gestión de Usuarios
El sistema debe permitir a administradores gestionar usuarios.
- Listado de usuarios
- Ver historial de compras

---

## 8. Búsqueda

### RF-070: Búsqueda de Productos
El sistema debe permitir buscar productos.
- Búsqueda por nombre, descripción
- Filtros combinados
- Resultados ordenados por relevancia
