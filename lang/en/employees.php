<?php

return [
    'navigation_label' => 'Employees',
    'title' => [
        'own' => 'Employees',
        'record' => 'Employees: :client',
        'create' => 'New employee',
        'edit' => 'Edit employee',
        'view' => 'View employee',
    ],
    'subtitle' => 'Your configured employees',
    'intro_read_only' => 'These are the employees configured for your client.',
    'count' => 'Employees (:count)',
    'empty' => [
        'heading' => 'No employees',
        'editable' => 'Add employees to this client so they can be linked to surveys later.',
        'read_only' => 'There are no employees configured for this client.',
    ],
    'actions' => [
        'add' => 'Add employee',
        'delete_confirm' => 'Delete :name? This action cannot be undone.',
        'link_copied' => 'Survey link copied',
        'delete_forbidden' => 'You cannot delete this employee',
        'deleted' => 'Employee deleted',
    ],
    'resource' => [
        'model_label' => 'Employee',
        'plural_model_label' => 'Employees',
    ],
    'form' => [
        'section_data' => 'Employee details',
        'section_nfc' => 'NFC token',
        'section_nfc_description' => '1-to-1 NFC token per employee. Not editable.',
        'client_name' => 'Client name',
        'name' => 'Name',
        'alias' => 'Alias / identifier',
        'alias_help' => 'Short identifier to use in surveys or as a code.',
        'photo' => 'Photo',
        'position' => 'Position',
        'active' => 'Active',
        'nfc_token' => 'NFC token',
        'nfc_generated_on_save' => 'It will be generated on save',
        'nfc_survey_url' => 'Copy survey link',
        'copy_failed' => 'Could not copy',
    ],
    'table' => [
        'empty_heading' => 'No employees',
        'empty_description' => 'Add the first employee to get started.',
        'delete_selected_heading' => 'Delete selected employees',
        'delete_selected_description' => 'Are you sure you want to delete these employees? This action cannot be undone.',
    ],
];
