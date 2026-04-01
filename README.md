# Optica Store - MVP

Tienda en línea de productos ópticos (lentes de contacto, marcos, cristales) construida con Laravel y Lunar PHP.

## Estado del Proyecto

Este proyecto se encuentra en fase de definición del **MVP (Minimum Viable Product)**.

## Características del MVP

### Funcionalidades Planeadas

1. **Variables de Lentes de Contacto**
   - Configuración de ProductOptions de Lunar (Graduación/Power/SPH, Curva Base, Diámetro, Eje, Cilindro)
   - Selectores para ojo izquierdo/derecho

2. **Subida de Recetas Médicas**
   - Modelo `Prescription` vinculado a líneas de orden
   - Integración con Spatie Media Library para almacenamiento de archivos
   - Interfaz de subida en el Carrito Livewire

3. **Workflow de Aprobación**
   - Estado "pendiente de revisión" para órdenes con recetas
   - Panel de administración en Filament para aprobar/rechazar órdenes

4. **Notificaciones WhatsApp**
   - Integración con API externa (Twilio/Meta)
   - Notificaciones al cliente ante cambios de estado de orden

5. **Frontend Mobile-First**
   - Interfaz responsive con Tailwind CSS 3
   - Basado en template anterior

### Tech Stack

- **Backend**: Laravel 12, Lunar PHP (e-commerce headless)
- **Frontend**: Livewire, Tailwind CSS 3
- **Admin**: Filament 3
- **Medios**: Spatie Media Library
- **Docker**: Docker Compose para desarrollo local

## Instalación

```bash
# Copiar configuración
cp .env.docker.example .env

# Iniciar contenedores
docker-compose up -d

# Instalar dependencias
docker-compose exec app composer install
docker-compose exec app npm install

# Generar clave de aplicación
docker-compose exec app php artisan key:generate

# Ejecutar migraciones
docker-compose exec app php artisan migrate
```

## Acceso

- **Tienda**: http://localhost
- **Panel Lunar**: http://localhost/lunar
  - Usuario: admin@lunarphp.io
  - Contraseña: password

## Próximos Pasos

- [ ] Implementar variables de lentes de contacto
- [ ] Crear modelo y migración de Prescription
- [ ] Implementar flujo de aprobación de recetas
- [ ] Integrar notificaciones WhatsApp
- [ ] Completar frontend mobile-first

---

*Este es un proyecto en construcción. Las funcionalidades listadasabove están en fase de planificación.*
