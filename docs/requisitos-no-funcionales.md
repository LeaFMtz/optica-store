# Requisitos No Funcionales - Optica Store MVP

## 1. Rendimiento

### RNF-001: Tiempo de Carga
- La página principal debe cargar en menos de 3 segundos.
- Las páginas de producto deben cargar en menos de 2 segundos.
- El checkout debe completarse en menos de 5 segundos.

### RNF-002: Capacidad
- El sistema debe soportar al menos 100 usuarios concurrentes.
- El sistema debe manejar al menos 1000 productos en el catálogo.

### RNF-003: Rendimiento de Búsqueda
- Los resultados de búsqueda deben mostrarse en menos de 500ms.

---

## 2. Seguridad

### RNF-010: Autenticación
- Las contraseñas deben almacenarse usando bcrypt (cost round 12).
- Sesiones deben tener lifetime configurable (por defecto 120 minutos).
- Tokens de API deben ser encriptados.

### RNF-011: Protección
- El sistema debe implementar protección CSRF en todos los formularios.
- El sistema debe validar y sanitizar todas las entradas de usuario.
- El sistema debe implementar rate limiting en autenticación y API.

### RNF-012: Datos Sensibles
- No almacenar información de tarjetas de pago directamente.
- Usar HTTPS en producción.
- Variables sensibles deben estar en variables de entorno.

### RNF-013: Subida de Archivos
- Validar tipos de archivo (PDF, JPG, PNG).
- Tamaño máximo: 5MB por archivo.
- Almacenar en directorio no accesible públicamente.

---

## 3. Escalabilidad

### RNF-020: Arquitectura
- El sistema debe estar preparado para escalar horizontalmente.
- Sesiones almacenadas en Redis (no file-based).
- Archivos estáticos servidos por CDN.

### RNF-021: Base de Datos
- Índices apropiados para consultas frecuentes.
- Preparado para reader/writer split si es necesario.

---

## 4. Disponibilidad

### RNF-030: Uptime
- El sistema debe tener disponibilidad del 99.5% en producción.
- Mantenimiento planificado fuera de horas pico.

### RNF-031: Recuperación
- Backups diarios de base de datos.
- Plan de recuperación ante desastres (DRP).
- Logs de errores disponibles para diagnóstico.

---

## 5. Usabilidad

### RNF-040: Diseño Responsive
- El sistema debe funcionar en dispositivos móviles (320px+).
- El sistema debe funcionar en tablets (768px+).
- El sistema debe funcionar en desktop (1024px+).

### RNF-041: Accesibilidad
- Cumplir con WCAG 2.1 nivel AA.
- Navegación por teclado.
- Alternativas textuales para imágenes.

### RNF-042: Experiencia de Usuario
- Mensajes de error claros y accionables.
- Feedback visual para acciones del usuario.
- Loading states apropiados.

---

## 6. Compatibilidad

### RNF-050: Navegadores Soportados
- Chrome (últimas 2 versiones)
- Firefox (últimas 2 versiones)
- Safari (últimas 2 versiones)
- Edge (últimas 2 versiones)

### RNF-051: Dispositivos Móviles
- iOS Safari (últimas 2 versiones)
- Chrome Android (últimas 2 versiones)

---

## 7. Mantenibilidad

### RNF-060: Código
- Código formateado con Laravel Pint.
- TypeScript strict mode.
- Documentación de funciones complejas.
- Tests para funcionalidades críticas.

### RNF-061: Arquitectura
- Separación clara de responsabilidades.
- Configuración externa (no hardcoded).
- Logging estructurado.

---

## 8. SEO

### RNF-070: Optimización
- URLs amigables.
- Meta tags configurables por producto.
- Sitemap.xml.
- Schema.org para productos.

### RNF-071: Rendimiento SEO
- Core Web Vitals dentro de umbrales:
  - LCP < 2.5s
  - FID < 100ms
  - CLS < 0.1

---

## 9. Legal

### RNF-080: Cumplimiento
- Política de privacidad accesible.
- Términos y condiciones.
- Política de cookies (GDPR/LGPD).
- Facturación fiscal requerida para Argentina (AFIP).

---

## 10. Testing y Calidad

### RNF-091: Tests End-to-End
El sistema debe contar con tests E2E automatizados para cada feature del MVP.
- Cada requisito funcional debe tener al menos un escenario de test E2E cubriendo el happy path.
- Escenarios de error y edge cases deben ser cubiertos.
- Tests deben ejecutarse en CI/CD antes de cada merge.
- Herramienta recomendada: Playwright o Cypress.

### RNF-092: Cobertura de Tests
- El sistema debe mantener cobertura de tests unitarios para lógica de negocio crítica.
- Los tests E2E deben ejecutarse en múltiples navegadores (Chrome, Firefox, Safari).
- El tiempo de ejecución de tests E2E no debe exceder 10 minutos por suite.

### RNF-093: Testing Mobile
- Los tests E2E deben ejecutarse en viewport móvil (375x667) además de desktop.
- Verificar funcionamiento de gestures táctiles si aplica.

---

## 11. Monitoreo

### RNF-090: Observabilidad
- Logs de aplicación centralizados.
- Métricas de rendimiento.
- Alertas para errores críticos.
- Health check endpoint.
