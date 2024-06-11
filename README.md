## Desarrollado por [@F4cku23](https://f4cku23.github.io)

# User Submission and Notification Plugin

Documentación utilizada ----->
https://developer.wordpress.org/plugins/intro/


## Descripción

Este plugin permite a los usuarios enviar contenido desde el front-end del sitio web y notifica al administrador cuando se recibe una nueva entrada. Incluye un tipo de contenido personalizado llamado "Envío" y una página de ajustes en el área de administración donde se puede configurar el correo electrónico de notificación y el mensaje de agradecimiento.

## Funcionalidades

- **Tipo de Contenido Personalizado**: Crea un tipo de contenido personalizado llamado "Envío".
- **Formulario de Envío en el Front-End**: Permite a los usuarios enviar entradas desde el front-end.
- **Validación de Formularios**: Verifica que todos los campos estén completos y en el formato correcto.
- **Notificaciones por Correo Electrónico**: Envía una notificación al administrador cuando se recibe un nuevo envío.
- **Página de Ajustes del Administrador**: Permite al administrador configurar el correo electrónico de notificación y el mensaje de agradecimiento.

## Instalación

1. Sube los archivos del plugin a la carpeta `/wp-content/plugins/` o instala el plugin directamente a través de la pantalla de plugins de WordPress.
2. Activa el plugin a través de la pantalla de 'Plugins' en WordPress.
3. Ve a la sección de "Ajustes" para configurar el correo electrónico de notificación y el mensaje de agradecimiento.

## Uso

### Formulario de Envío

Para mostrar el formulario de envío en una página o entrada, usa el shortcode `[formulario_envio]`.

### Página de Ajustes

Accede a la página de ajustes del plugin en "Ajustes -> Ajustes de Envíos" para configurar el correo electrónico de notificación y el mensaje de agradecimiento.

## Código

### Seguridad Básica

```php
if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente
}
```

### Registro del Tipo de Contenido Personalizado

```php
function crear_tipo_contenido_envio() {
    $labels = array(
        'name' => 'Envíos',
        'singular_name' => 'Envío',
        'menu_name' => 'Envíos',
        'name_admin_bar' => 'Envío',
        'add_new' => 'Añadir Nuevo',
        'add_new_item' => 'Añadir Nuevo Envío',
        'new_item' => 'Nuevo Envío',
        'edit_item' => 'Editar Envío',
        'view_item' => 'Ver Envío',
        'all_items' => 'Todos los Envíos',
        'search_items' => 'Buscar Envíos',
        'not_found' => 'No se encontraron envíos.',
        'not_found_in_trash' => 'No se encontraron envíos en la papelera.'
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'supports' => array('title', 'editor', 'custom-fields'),
    );

    register_post_type('envio', $args);
}
add_action('init', 'crear_tipo_contenido_envio');
```

### Formulario de Envío en el Front-End

```php
function mostrar_formulario_envio() {
    if (is_user_logged_in()) {
        if (isset($_POST['envio_titulo'])) {
            $titulo = sanitize_text_field($_POST['envio_titulo']);
            $contenido = sanitize_textarea_field($_POST['envio_contenido']);
            $email = sanitize_email($_POST['envio_email']);
            
            $nuevo_envio = array(
                'post_title' => $titulo,
                'post_content' => $contenido,
                'post_status' => 'pending', // Publicación pendiente de revisión
                'post_type' => 'envio',
            );
            $post_id = wp_insert_post($nuevo_envio);
            if ($post_id) {
                update_post_meta($post_id, 'envio_email', $email);
                $admin_email = get_option('envio_admin_email', get_option('admin_email'));
                $mensaje = "Nuevo envío recibido:\n\nTítulo: $titulo\n\nContenido: $contenido\n\nEmail: $email";
                wp_mail($admin_email, 'Nuevo Envío Recibido', $mensaje);
                return '<p>¡Gracias por tu envío!</p>';
            }
        }
        ob_start();
        ?>
        <div class="card">
            <form method="post">
                <div class="form-group row">
                    <label for="envio_titulo">Título</label>
                    <input type="text" id="envio_titulo" name="envio_titulo" placeholder="Ingresa el título" required>
                </div>
                <div class="form-group row">
                    <label for="envio_contenido">Descripción</label>
                    <textarea id="envio_contenido" name="envio_contenido" rows="4" placeholder="Ingresa la descripción" required></textarea>
                </div>
                <div class="form-group row">
                    <label for="envio_email">Correo Electrónico</label>
                    <input type="email" id="envio_email" name="envio_email" placeholder="Ingresa tu correo electrónico" required>
                </div>
                <button type="submit">Enviar</button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    } else {
        return '<p>Debes iniciar sesión para enviar un contenido.</p>';
    }
}
add_shortcode('formulario_envio', 'mostrar_formulario_envio');
```

### Página de Ajustes en el Administrador

```php
function crear_pagina_ajustes_envio() {
    add_options_page(
        'Ajustes de Envíos',
        'Ajustes de Envíos',
        'manage_options',
        'ajustes-envio',
        'mostrar_pagina_ajustes_envio'
    );
}
add_action('admin_menu', 'crear_pagina_ajustes_envio');

function mostrar_pagina_ajustes_envio() {
    ?>
    <div class="wrap">
        <h1>Ajustes de Envíos</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('grupo_ajustes_envio');
            do_settings_sections('ajustes-envio');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}
```

### Registro de Ajustes

```php
function registrar_ajustes_envio() {
    register_setting('grupo_ajustes_envio', 'envio_admin_email');
    register_setting('grupo_ajustes_envio', 'envio_gracias_mensaje');

    add_settings_section(
        'seccion_envio',
        'Ajustes de Notificación',
        null,
        'ajustes-envio'
    );

    add_settings_field(
        'envio_admin_email',
        'Correo electrónico de notificación',
        'campo_envio_admin_email',
        'ajustes-envio',
        'seccion_envio'
    );

    add_settings_field(
        'envio_gracias_mensaje',
        'Mensaje de agradecimiento',
        'campo_envio_gracias_mensaje',
        'ajustes-envio',
        'seccion_envio'
    );
}
add_action('admin_init', 'registrar_ajustes_envio');
```

### Campos de Ajustes

```php
function campo_envio_admin_email() {
    $email = get_option('envio_admin_email', get_option('admin_email'));
    return '<input type="email" name="envio_admin_email" value="' . esc_attr($email) . '" class="regular-text">';
}

function campo_envio_gracias_mensaje() {
    $mensaje = get_option('envio_gracias_mensaje', '¡Gracias por tu envío!');
    return '<textarea name="envio_gracias_mensaje" class="large-text" rows="3">' . esc_textarea($mensaje) . '</textarea>';
}
```

### Mostrar Mensaje de Agradecimiento Personalizado

```php
function mostrar_mensaje_gracias() {
    $mensaje = get_option('envio_gracias_mensaje', '¡Gracias por tu envío!');
    return '<p>' . esc_html($mensaje) . '</p>';
}
```
## Adicionar estos estilo CSS para mejorar visualmente

```css
.container {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 100%;
    height: 100%;
}

.card {
    background-color: #ffffff;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
    max-width: 500px;
    width: 100%;
    text-align: center;
}

h3 {
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 15px;
    text-align: left;
}

label {
    display: block;
    margin-bottom: 5px;
}

input[type="text"],
input[type="email"],
textarea {
    width: 100%;
    padding: 10px;
    margin-top: 5px;
    border: 1px solid #ccc;
    border-radius: 5px;
    box-sizing: border-box;
}

button {
    background-color: #007bff;
    color: #ffffff;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
    width: 100%;
    margin-top: 10px;
}

button:hover {
    background-color: #0056b3;
}
```