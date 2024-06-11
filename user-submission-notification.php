<?php
/*
Plugin Name: Plugin de Envíos y Notificaciones de Usuarios
Description: Permite a los usuarios enviar publicaciones y notifica al administrador por correo electrónico.
Version: 1.0
Author: Paulo Nievas
*/

// medida de seguridad común en los plugins y temas de WordPress
//ABSPATH es una constante definida por WordPress que representa la ruta absoluta del directorio raíz de la instalación de WordPress
//El propósito de este código es asegurarse de que el archivo PHP en el que está incluido solo se ejecute dentro del contexto de WordPress. Si alguien intenta acceder directamente al archivo desde el navegador, el archivo no se ejecutará, ya que ABSPATH no estará definido y el script se detendrá inmediatamente.
//Esto es importante por razones de seguridad. Sin esta comprobación, alguien podría intentar acceder directamente a los archivos del plugin o tema, lo que podría potencialmente llevar a la ejecución de código no deseado o malicioso. 

if (!defined('ABSPATH')) { // Verifica si la constante ABSPATH está definida
    exit; // Si no está definida, detiene la ejecución del script
}

// Registrar el tipo de contenido personalizado 'envio'
function crear_tipo_contenido_envio() { //Esta función define un nuevo tipo de contenido personalizado llamado envio y lo registra con WordPress.
    $labels = array( //El array $labels define las etiquetas que se utilizarán en la interfaz de administración de WordPress para el nuevo tipo de contenido.
        'name' => 'Envíos',  //Nombre plural del tipo de contenido.
        'singular_name' => 'Envío',  //Nombre singular del tipo de contenido.
        'menu_name' => 'Envíos',  //Nombre que se muestra en el menú de administración.
        'name_admin_bar' => 'Envío',  //Nombre que se muestra en la barra de administración.
        'add_new' => 'Añadir Nuevo',  //Texto para el enlace de añadir nuevo.
        'add_new_item' => 'Añadir Nuevo Envío',  //Texto para el título de añadir nuevo ítem.
        'new_item' => 'Nuevo Envío',  //Texto para el nuevo ítem.
        'edit_item' => 'Editar Envío',  //Texto para editar el ítem.
        'view_item' => 'Ver Envío',  //Texto para ver el ítem.
        'all_items' => 'Todos los Envíos',  //Texto para todos los ítems.
        'search_items' => 'Buscar Envíos',  //Texto para buscar ítems.
        'not_found' => 'No se encontraron envíos.',  //Texto para no encontrado.
        'not_found_in_trash' => 'No se encontraron envíos en la papelera.'  //Texto para no encontrado en la papelera.
    );

    $args = array(  //El array $args define las características y comportamientos del nuevo tipo de contenido.
        'labels' => $labels,  //Etiquetas definidas previamente.
        'public' => true,  //Si es verdadero, el tipo de contenido estará disponible en el frontend y backend.
        'has_archive' => true, //Si es verdadero, permite que el tipo de contenido tenga una página de archivo.
        'supports' => array('title', 'editor', 'custom-fields'),  // Define las características que el tipo de contenido soporta. En este caso, title (título), editor (editor de contenido) y custom-fields (campos personalizados).
    );
    //La función register_post_type registra el nuevo tipo de contenido con los argumentos especificados.
    register_post_type('envio', $args);
}
//Este hook  add_action asegura que la función crear_tipo_contenido_envio se ejecuta cuando WordPress inicializa (en el init hook).
add_action('init', 'crear_tipo_contenido_envio');

//Este código crea un tipo de contenido personalizado llamado "envío" en WordPress con varias configuraciones personalizadas, y lo hace disponible tanto en el frontend como en el backend.


// Crear el formulario en el front-end - permite a los usuarios enviar contenido mediante un formulario en el frontend, siempre y cuando estén autenticados, y notifica al administrador del sitio por correo electrónico sobre cada nuevo envío.
function mostrar_formulario_envio() {  //Esta función genera y maneja un formulario de envío de contenido en el frontend de un sitio de WordPress. esta función se vincula a un shortcode
    if (is_user_logged_in()) {  //verifica si el usuario está autenticado 
        //Si el usuario está autenticado, el siguiente bloque de código maneja la presentación del formulario
        if (isset($_POST['envio_titulo'])) {  //Esto verifica si el formulario ha sido enviado comprobando si el campo envio_titulo está presente en $_POST.
            //Estas funciones sanitizan los datos del formulario para evitar ataques XSS y otros problemas de seguridad.
            $titulo = sanitize_text_field($_POST['envio_titulo']);
            $contenido = sanitize_textarea_field($_POST['envio_contenido']);
            $email = sanitize_email($_POST['envio_email']);
            //Aquí se crea un nuevo post de tipo envio con el estado pending (pendiente de revisión)
            $nuevo_envio = array(
                'post_title' => $titulo,
                'post_content' => $contenido,
                'post_status' => 'pending', // Publicación pendiente de revisión
                'post_type' => 'envio',
            );
            $post_id = wp_insert_post($nuevo_envio);
            if ($post_id) {
                update_post_meta($post_id, 'envio_email', $email);  //Si la creación del post es exitosa, se añade un meta dato envio_email al post con el email proporcionado.
                //Se obtiene el email del administrador y se envía una notificación con los detalles del envío.
                $admin_email = get_option('envio_admin_email', get_option('admin_email'));
                $mensaje = "Nuevo envío recibido:\n\nTítulo: $titulo\n\nContenido: $contenido\n\nEmail: $email";
                wp_mail($admin_email, 'Nuevo Envío Recibido', $mensaje);
                return '<p>¡Gracias por tu envío!</p>';
            }
        }
        ob_start();
        ?>
        <!--Si el formulario no ha sido enviado, se muestra el formulario HTML.-->
        <!--El formulario utiliza el método post para enviar los datos al servidor.-->
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
        return '<p>Debes iniciar sesión para enviar un contenido.</p>';  //Si el usuario no está autenticado, se muestra un mensaje indicándole que debe iniciar sesión
    }
}
//El siguiente código registra un shortcode para la función mostrar_formulario_envio
add_shortcode('formulario_envio', 'mostrar_formulario_envio');

// Crear la página de ajustes en el admin - Esta función utiliza la función add_options_page de WordPress para crear una nueva página de ajustes en el menú de administración.
function crear_pagina_ajustes_envio() {
    add_options_page(  //La función add_options_page añade una nueva página de opciones en el menú de "Ajustes" del área de administración de WordPress
        'Ajustes de Envíos',  //título que se mostrará en la barra de título del navegador cuando se visualice la página de ajustes
        'Ajustes de Envíos',  //texto que se mostrará en el menú de "Ajustes" en el área de administración de WordPress.
        'manage_options',  //es una capacidad que normalmente solo tienen los administradores
        'ajustes-envio',  //identificador único para esta página de ajustes. Se utiliza en la URL para acceder a la página.
        'mostrar_pagina_ajustes_envio'  //función que se llamará para mostrar el contenido de la página de ajustes. Esta función debe ser definida para renderizar el HTML de la página de ajustes
    );
}
add_action('admin_menu', 'crear_pagina_ajustes_envio');  //añade la función crear_pagina_ajustes_envio al hook admin_menu
    //Este hook 'admin_menu' se ejecuta cuando se está construyendo el menú de administración de WordPress. Permite a los desarrolladores añadir sus propios menús y submenús en el área de administración.

    //Esta función genera la interfaz HTML para la página de ajustes de un plugin en el área de administración de WordPress.
function mostrar_pagina_ajustes_envio() {
    ?>
    <div class="wrap">
        <h1>Ajustes de Envíos</h1>
        <form method="post" action="options.php">  <!--Los datos del formulario se enviarán al script options.php, que es el encargado de manejar la actualización de opciones en WordPress-->
            <?php
            settings_fields('grupo_ajustes_envio');  //Esta función genera campos ocultos necesarios para que WordPress maneje los ajustes de manera segura
            do_settings_sections('ajustes-envio');  //Esta función imprime todas las secciones y campos registrados para la página de ajustes especificada
            submit_button();  //genera un boton
            ?>
        </form>
    </div>
    <?php
}

//Esta función para registrar los ajustes, secciones y campos personalizados en el área de administración de WordPress.
function registrar_ajustes_envio() {
    //register_setting - Registra una configuración con el grupo de ajustes especificado
    register_setting('grupo_ajustes_envio', 'envio_admin_email'); //grupo_ajustes_envio - El nombre del grupo de ajustes. Este grupo se utilizará para agrupar varias configuraciones relacionadas.
    register_setting('grupo_ajustes_envio', 'envio_gracias_mensaje'); //envio_admin_email - El nombre del ajuste (en este caso, el correo electrónico de notificación).

    //Añade una nueva sección de ajustes a la página de ajustes
    add_settings_section(
        'seccion_envio',  //ID de la sección
        'Ajustes de Notificación',  //Título de la sección que se mostrará en la página de ajustes
        null,  //Función de callback para mostrar la descripción de la sección. En este caso, no se proporciona
        'ajustes-envio'  //La página de ajustes donde se mostrará la sección
    );

    //Añade un nuevo campo de ajuste a una sección específica en una página de ajustes
    add_settings_field(
        'envio_admin_email',  //ID del campo
        'Correo electrónico de notificación',  //Título del campo que se mostrará en la página de ajustes
        'campo_envio_admin_email',  //Función de callback que se llamará para mostrar el campo de entrada
        'ajustes-envio',  //La página de ajustes donde se mostrará el campo
        'seccion_envio'  //La sección dentro de la página de ajustes donde se mostrará el campo
    );
    //Campo de Mensaje de Agradecimiento
    add_settings_field(
        'envio_gracias_mensaje', //ID del campo
        'Mensaje de agradecimiento', //Título del campo
        'campo_envio_gracias_mensaje',  //Función de callback que se llamará para mostrar el campo de entrada
        'ajustes-envio',
        'seccion_envio'
    );
}
//inicializa el área de administración de WordPress
add_action('admin_init', 'registrar_ajustes_envio');

//Funciones de callback para mostrar los campos, mostrarán los campos en la página de ajustes
function campo_envio_admin_email() {
    $email = get_option('envio_admin_email', get_option('admin_email'));  //Esta función obtiene el valor de la opción envio_admin_email desde la base de datos de WordPress. Si la opción no existe, devuelve el valor predeterminado, que en este caso es el correo electrónico del administrador del sitio obtenido con get_option('admin_email')
    return '<input type="email" name="envio_admin_email" value="' . esc_attr($email) . '" class="regular-text">';  //esc_attr es una función de WordPress que asegura que el valor se escape correctamente para evitar vulnerabilidades XSS.
}

function campo_envio_gracias_mensaje() {
    $mensaje = get_option('envio_gracias_mensaje', '¡Gracias por tu envío!');
    return '<textarea name="envio_gracias_mensaje" class="large-text" rows="3">' . esc_textarea($mensaje) . '</textarea>';
}

// Mostrar mensaje de agradecimiento personalizado
function mostrar_mensaje_gracias() {
    $mensaje = get_option('envio_gracias_mensaje', '¡Gracias por tu envío!');
    return '<p>' . esc_html($mensaje) . '</p>';
}
