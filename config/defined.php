<?php

/*
|--------------------------------------------------------------------------
| WRITING CONSTANTS FOR THE APPLICATION
|--------------------------------------------------------------------------
|
| The elements are the constants that the application uses for its operation, 
| the declaration is the following:
|   
|  '@NAME' => '@VALUE',
|
*/

return [
    /**
     * ----------------------------------------------------------------------------------------
     *  Global messages for user activity logging
     * ----------------------------------------------------------------------------------------
     */
    'LOG_CREATE' => 'AGREGAR', // References log message
    'LOG_EDIT' => 'MODIFICAR', // References log message
    'LOG_DESTROY' => 'ELIMINAR', // References log message

    /**
     * ----------------------------------------------------------------------------------------
     *  Collection catalogo.cat_estatus
     * ----------------------------------------------------------------------------------------
     */

    'PROCESO' => 1, // References item
    'COMPLETADO_POR_EMPLEADO' => 2, // References item
    'COMPLETADO_POR_REVISOR' => 3, // References item
    'COMPLETADO_POR_SUPERVISOR' => 4, // References item
    'COMPLETADO_POR _DGCES' => 5, // References item
    'RECHAZADO' => 6, // References item

    /**
     * ----------------------------------------------------------------------------------------
     *  Collection catalogo.cat_estatus_documento
     * ----------------------------------------------------------------------------------------
     */

    'DOCUMENT_ESTATUS_RECHAZADO' => 1, // References item
    'DOCUMENT_ESTATUS_ACEPTADO' => 2, // References item
    'DOCUMENT_ESTATUS_PROCESO' => 3, // References item

    /**
     * ----------------------------------------------------------------------------------------
     *  Collection administration.tbl_role
     * ----------------------------------------------------------------------------------------
     */

    'ROLE_ADMINISTRADOR' => 1, // References item
    'ROLE_REVISOR' => 3, // References item
    'ROLE_SUPERVISOR' => 4, // References item
    'ROLE_DGCES' => 5, // References item
    'ROLE_EMPLEADO' => 2, // References item

    /**
     * ----------------------------------------------------------------------------------------
     *  Collection catalogo.cat_config
     * ----------------------------------------------------------------------------------------
     */

    'FECHA_BLOQUEO_USUARIO' => 1, // References item
    'FECHA_CREACION_USUARIO' => 3, // References item
];