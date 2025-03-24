<?php

namespace App\Models\Enums;

enum RoleUserEnum: string
{
    case ADMIN = 'admin';
    case MANAGER = 'manager';
    case OPERATOR = 'operator';
}

enum StatusCompanyEnum: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}

