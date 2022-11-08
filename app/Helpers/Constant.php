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

    const ACL_PERMISSIONS = [
        'POST'      => ['create', 'update'],
        'GET'       => ['read'],
        'PUT'       => ['update'],
        'PATCH'     => ['update'],
        'DELETE'    => ['delete'],
    ];

    const TIMELINE_DEFAULT_DURATION_DAYS = 25;
    const TIMELINE_DEFAULT_OFFSET = 5;

    Const EMPLOYEES_DUTIES = [
        'fire_extinguishing' => 'fire extinguishing',
        'first_aid' => 'first aid',
        'special_training' => 'special training'
    ];

    Const EMPLOYEES_DUTIES_DEFAULT_EXPIRES = [
        'fire_extinguishing' => " +3 year",
        'first_aid' => " +2 year"
    ];
    
    const EMAIL_SENDER = [
        'notification' => 'remind@fasi.app',
        'invitation' => 'invite@fasi.app'
    ];

    const EMAIL_SUBJECT = [
        'notification' => 'Reminder to register employee in atleast 1 training',
        'invitation' => 'Invitation email'
    ];

    const PAGINATION_LIMIT = 10;
    const SHARED_TABLES = [
        'employees' => 'employees',
        'companies' => 'companies',
        'invitations' => 'invitations'
    ];


}

