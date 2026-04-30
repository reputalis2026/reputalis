<?php

return [
    'navigation_label' => 'Funcionários',
    'title' => [
        'own' => 'Funcionários',
        'record' => 'Funcionários: :client',
        'create' => 'Novo funcionário',
        'edit' => 'Editar funcionário',
        'view' => 'Ver funcionário',
    ],
    'subtitle' => 'Seus funcionários configurados',
    'intro_read_only' => 'Estes são os funcionários configurados para o seu cliente.',
    'count' => 'Funcionários (:count)',
    'empty' => [
        'heading' => 'Não há funcionários',
        'editable' => 'Adicione funcionários a este cliente para os associar a inquéritos mais tarde.',
        'read_only' => 'Não há funcionários configurados para este cliente.',
    ],
    'actions' => [
        'add' => 'Adicionar funcionário',
        'delete_confirm' => 'Eliminar :name? Esta ação não pode ser desfeita.',
        'link_copied' => 'Link do inquérito copiado',
        'delete_forbidden' => 'Não pode eliminar este funcionário',
        'deleted' => 'Funcionário eliminado',
    ],
    'resource' => [
        'model_label' => 'Funcionário',
        'plural_model_label' => 'Funcionários',
    ],
    'form' => [
        'section_data' => 'Dados do funcionário',
        'section_nfc' => 'Token NFC',
        'section_nfc_description' => 'Token NFC 1–1 por funcionário. Não editável.',
        'client_name' => 'Nome do cliente',
        'name' => 'Nome',
        'alias' => 'Alias / identificador',
        'alias_help' => 'Identificador curto para usar em inquéritos ou como código.',
        'photo' => 'Foto',
        'position' => 'Cargo',
        'active' => 'Ativo',
        'nfc_token' => 'Token NFC',
        'nfc_generated_on_save' => 'Será gerado ao guardar',
        'nfc_survey_url' => 'Copiar link do inquérito',
        'copy_failed' => 'Não foi possível copiar',
    ],
    'table' => [
        'empty_heading' => 'Não há funcionários',
        'empty_description' => 'Adicione o primeiro funcionário para começar.',
        'delete_selected_heading' => 'Eliminar funcionários selecionados',
        'delete_selected_description' => 'Tem a certeza de que deseja eliminar estes funcionários? Esta ação não pode ser desfeita.',
    ],
];
