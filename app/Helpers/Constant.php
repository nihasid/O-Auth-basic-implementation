<?php

/* *created by Niha Siddiqui 2022-10-05
    * all constants defined in this file
*/

namespace App\Helpers;


class Constant
{
    const HTTP_RESPONSE_STATUSES = [
        'success'               => 200,
        'failed'                => 400,
        'validationError'       => 422,
        'authenticationError'   => 401,
        'authorizationError'    => 403,
        'serverError'           => 500,
    ];

    const PROJECT_STAGES = [
        'site_survey'           => 'Site survey',
        'schematics_drawing'    => 'Schematics drawing',
        'technical_drawings'    => 'Technical drawings',
        'material_procurement'  => 'Mall approval',
        'carpentry'             => 'Demolition',
        'painting'              => 'Ceiling',
        'assembly'              => 'Floor',
        'quality_check'         => 'Infra & HVAC',
        'packaging'             => 'Store front',
        'delivery'              => 'Fixture delivery',
        'installation'          => 'Fixture installation',
        'completion'            => 'Snagging report',
    ];

    const DATE_TIME_FORMAT = 'Y-m-d H:i:s';
    const DATE_FORMAT = 'Y-m-d';
    const DATE_DISPLAY = 'd M Y';

    const UNSERIALIZABLE_FIELDS = [
        'file',
        'image',
        'avatar',
        'logo',
        'profileImage',
        'attachments'
    ];

    const USER_TYPES = [
        1 => 'admin',
        2 => 'store'
    ];

    const USER_ROLES = [
        'super-admin'    => 1,
        'pro-admin'      => 2,
        'pro-data'       => 3,
        'standard-admin' => 4,
        'standard-data'  => 5
    ];

    const CRUD_OPERATIONS = [
        1 => 'create',
        2 => 'read',
        3 => 'update',
        4 => 'delete',
    ];

    const TOKEN_EXPIRY_TIME = '1 week';

    const BOOL_STR = [
      'true'    => true,
      'false'   => false,
      '1'       => true,
      '0'       => false,
    ];

    const DEFAULT_DB_RECORDS_LIMIT = 10;

    const ACL_PERMISSIONS = [
        'POST'      => ['create', 'update'],
        'GET'       => ['read'],
        'PUT'       => ['update'],
        'PATCH'     => ['update'],
        'DELETE'    => ['delete'],
    ];

    const TIMELINE_DEFAULT_DURATION_DAYS = 25;
    const TIMELINE_DEFAULT_OFFSET = 5;
}
