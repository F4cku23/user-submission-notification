<?php
/*
Plugin Name: Plugin de Envíos y Notificaciones de Usuarios
Description: Permite a los usuarios enviar publicaciones y notifica al administrador por correo electrónico.
Version: 1.0
Author: Paulo Nievas
*/


// medida de seguridad común en los plugins y temas de WordPress
if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente
}

// Registrar el tipo de contenido personalizado 'envio'
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

    //El array $args define las características y comportamientos del nuevo tipo de contenido.
    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'supports' => array('title', 'editor', 'custom-fields'),
    );
    //La función register_post_type registra el nuevo tipo de contenido con los argumentos especificados.
    register_post_type('envio', $args);
}
//Este hook  add_action asegura que la función crear_tipo_contenido_envio se ejecuta cuando WordPress inicializa (en el init hook).
add_action('init', 'crear_tipo_contenido_envio');


//Este código crea un tipo de contenido personalizado llamado "envío" en WordPress con varias configuraciones personalizadas, y lo hace disponible tanto en el frontend como en el backend.

// Crear el formulario en el front-end - permite a los usuarios enviar contenido mediante un formulario en el frontend, siempre y cuando estén autenticados, y notifica al administrador del sitio por correo electrónico sobre cada nuevo envío
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
                echo '<p>¡Gracias por tu envío!</p>';
            }
        }
        ?>
        <!--Si el formulario no ha sido enviado, se muestra el formulario HTML.-->
        <form method="post">
            <div class="row">
                <label for="envio_titulo">Título:</label>
                <input type="text" id="envio_titulo" name="envio_titulo" required><br>
            </div>
            <div class="row">
                <label for="envio_contenido">Contenido:</label>
                <textarea id="envio_contenido" name="envio_contenido" required></textarea><br>
            </div>
            <div class="row">
                <label for="envio_email">Correo electrónico:</label>
                <input type="email" id="envio_email" name="envio_email" required><br>
            </div>
            <input type="submit" value="Enviar">
        </form>
        <?php
    } else {
        echo '<p>Debes iniciar sesión para enviar un contenido.</p>';
    }
}
//El siguiente código registra un shortcode para la función mostrar_formulario_envio
add_shortcode('formulario_envio', 'mostrar_formulario_envio');

// Crear la página de ajustes en el admin - Esta función utiliza la función add_options_page de WordPress para crear una nueva página de ajustes en el menú de administración.
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

//Esta función genera la interfaz HTML para la página de ajustes de un plugin en el área de administración de WordPress.
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

//Esta función para registrar los ajustes, secciones y campos personalizados en el área de administración de WordPress.
function registrar_ajustes_envio() {
    register_setting('grupo_ajustes_envio', 'envio_admin_email');
    register_setting('grupo_ajustes_envio', 'envio_gracias_mensaje');

    //Añade una nueva sección de ajustes a la página de ajustes
    add_settings_section(
        'seccion_envio',
        'Ajustes de Notificación',
        null,
        'ajustes-envio'
    );

    //Añade un nuevo campo de ajuste a una sección específica en una página de ajustes
    add_settings_field(
        'envio_admin_email',
        'Correo electrónico de notificación',
        'campo_envio_admin_email',
        'ajustes-envio',
        'seccion_envio'
    );

    //Campo de Mensaje de Agradecimiento
    add_settings_field(
        'envio_gracias_mensaje',
        'Mensaje de agradecimiento',
        'campo_envio_gracias_mensaje',
        'ajustes-envio',
        'seccion_envio'
    );
}
//inicializa el área de administración de WordPress
add_action('admin_init', 'registrar_ajustes_envio');

//Funciones de callback para mostrar los campos, mostrarán los campos en la página de ajustes
function campo_envio_admin_email() {
    $email = get_option('envio_admin_email', get_option('admin_email'));
    echo '<input type="email" name="envio_admin_email" value="' . esc_attr($email) . '" class="regular-text">';
}

function campo_envio_gracias_mensaje() {
    $mensaje = get_option('envio_gracias_mensaje', '¡Gracias por tu envío!');
    echo '<textarea name="envio_gracias_mensaje" class="large-text" rows="3">' . esc_textarea($mensaje) . '</textarea>';
}

// Mostrar mensaje de agradecimiento personalizado
function mostrar_mensaje_gracias() {
    $mensaje = get_option('envio_gracias_mensaje', '¡Gracias por tu envío!');
    echo '<p>' . esc_html($mensaje) . '</p>';
}
