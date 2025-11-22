# ⚡ TurboWoo - Gestor POS Híbrido para WooCommerce

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D%207.4-blue)](https://www.php.net/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-API%20v3-violet)](https://woocommerce.com/)

**TurboWoo** es una aplicación web "Headless" (Desacoplada) diseñada para gestionar tiendas WooCommerce en entornos con conexión a internet lenta o inestable.

Permite trabajar bajo una filosofía **"Local First" (Modo Avión)**: crea, edita y gestiona tu inventario localmente a máxima velocidad y sincroniza los cambios con la nube solo cuando tú lo decidas. Además, incluye un monitor de pedidos en tiempo real y soporte nativo para impresoras térmicas.

---

## 🚀 Características Principales

### 🛠️ Gestión de Inventario "Local First"
- **Creación Offline:** Crea productos completos (Precio, Stock, SKU, Descripción) sin conexión a internet.
- **Galería de Imágenes Local:** Sube fotos y galerías completas que se guardan temporalmente en el disco local hasta la sincronización.
- **Generador de Códigos de Barra:** Genera SKUs aleatorios automáticamente si el producto no tiene código.
- **Control de Stock:** Interruptor para activar/desactivar la gestión de inventario por producto.

### ☁️ Sincronización Inteligente
- **Subida por Lotes:** Sube 50+ productos nuevos o editados en un solo clic.
- **Gestión de Imágenes:** El sistema sube automáticamente las fotos locales a la librería de medios de WordPress y las asigna al producto.
- **Eliminación Diferida:** Si borras un producto en local, puedes marcarlo para que se elimine también de la tienda online en la siguiente sincronización.

### 📦 Gestión de Pedidos (POS)
- **Monitor en Tiempo Real:** Tablero que se actualiza automáticamente (AJAX) cada 30 segundos al recibir nuevos pedidos.
- **Impresión Térmica:** Generación de tickets de venta optimizados para impresoras de **80mm** con limpieza de estilos CSS para impresión directa.
- **Alertas Sonoras:** Notificación auditiva al recibir una nueva venta.

### 🔐 Seguridad y Roles
- **Instalador Automático:** Asistente visual (Wizard) para configurar la base de datos y las API Keys.
- **Sistema de Roles:**
  - **Admin/Editor:** Acceso total a inventario y sincronización.
  - **Vendedor:** Acceso restringido solo a Pedidos e Impresión.

---

## ⚙️ Requisitos del Sistema

- **Servidor Local:** XAMPP, WAMP o similar (Apache/Nginx).
- **PHP:** Versión 7.4 o superior.
- **Base de Datos:** MySQL o MariaDB.
- **Dependencias:** Composer instalado.
- **Tienda Online:** Un sitio WordPress con WooCommerce activo.

---

## 🔧 Instalación

1. **Clonar el repositorio**
   ```bash
   git clone https://github.com/elpato001/TurboWoo.git

Instalar dependencias de PHP Navega a la carpeta del proyecto y ejecuta:

```bash
composer install

