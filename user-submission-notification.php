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
