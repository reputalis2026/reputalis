<?php

return [
    'navigation_label' => 'Empleados',
    'title' => [
        'own' => 'Empleados',
        'record' => 'Empleados: :client',
        'create' => 'Nuevo empleado',
        'edit' => 'Editar empleado',
        'view' => 'Ver empleado',
    ],
    'subtitle' => 'Tus empleados configurados',
    'intro_read_only' => 'Estos son los empleados configurados para tu cliente.',
    'count' => 'Empleados (:count)',
    'empty' => [
        'heading' => 'No hay empleados',
        'editable' => 'Añade empleados a este cliente para poder asociarlos a encuestas más adelante.',
        'read_only' => 'No hay empleados configurados para este cliente.',
    ],
    'actions' => [
        'add' => 'Añadir empleado',
        'delete_confirm' => '¿Eliminar a :name? Esta acción no se puede deshacer.',
        'link_copied' => 'Enlace de encuesta copiado',
        'delete_forbidden' => 'No puedes eliminar este empleado',
        'deleted' => 'Empleado eliminado',
    ],
    'resource' => [
        'model_label' => 'Empleado',
        'plural_model_label' => 'Empleados',
    ],
    'form' => [
        'section_data' => 'Datos del empleado',
        'section_nfc' => 'Token NFC',
        'section_nfc_description' => 'Token NFC 1–1 por empleado. No editable.',
        'client_name' => 'Nombre del cliente',
        'name' => 'Nombre',
        'alias' => 'Alias / identificador',
        'alias_help' => 'Identificador corto para usar en encuestas o como código.',
        'photo' => 'Foto',
        'position' => 'Puesto',
        'active' => 'Activo',
        'nfc_token' => 'Token NFC',
        'nfc_generated_on_save' => 'Se generará al guardar',
        'nfc_survey_url' => 'Copiar enlace de encuesta',
        'copy_failed' => 'No se pudo copiar',
    ],
    'table' => [
        'empty_heading' => 'No hay empleados',
        'empty_description' => 'Añade el primer empleado para comenzar.',
        'delete_selected_heading' => 'Eliminar empleados seleccionados',
        'delete_selected_description' => '¿Estás seguro de que deseas eliminar estos empleados? Esta acción no se puede deshacer.',
    ],
];
