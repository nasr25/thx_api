<?php

return [
    /*
    | External employee directory search endpoint.
    | The app sends {"search": "<term>"} and expects a JSON array of employees.
    | When 'url' is empty, search falls back to the local users table.
    */
    'url'         => env('EMPLOYEE_SEARCH_URL', ''),
    'method'      => strtoupper(env('EMPLOYEE_SEARCH_METHOD', 'POST')),
    'timeout'     => (int) env('EMPLOYEE_SEARCH_TIMEOUT', 10),
    'token'       => env('EMPLOYEE_SEARCH_TOKEN', ''),

    // JSON key that wraps the array (e.g. 'data'). Empty = response is the array.
    'results_key' => env('EMPLOYEE_SEARCH_RESULTS_KEY', ''),

    // The request field name that carries the search term.
    'query_field' => env('EMPLOYEE_SEARCH_QUERY_FIELD', 'search'),

    /*
    | Field-name candidates used to map the external response onto our employee
    | shape. The first present key wins, so this works with many AD/HR formats.
    */
    'fields' => [
        'username'   => ['username', 'userName', 'samaccountname', 'sAMAccountName', 'uid', 'login', 'employeeId', 'employee_id'],
        'full_name'  => ['full_name', 'fullName', 'name', 'displayName', 'DisplayName', 'cn', 'FullName'],
        'email'      => ['email', 'mail', 'Email', 'emailAddress', 'userPrincipalName'],
        'department' => ['department', 'Department', 'dept', 'departmentName', 'division'],
        'job_title'  => ['job_title', 'jobTitle', 'title', 'Title', 'position', 'jobtitle'],
        'photo'      => ['photo', 'photoUrl', 'photo_url', 'picture', 'avatar', 'thumbnailPhoto'],
    ],
];
