<?php

namespace App\Support;

final class ScopeKey
{
    public const GLOBAL = 'global';
    public const GLOBAL_SUPER_ADMIN = 'global_super_admin';

    public static function branch(int|string $idBranch): string
    {
        return 'branch_' . $idBranch;
    }

    public static function user(int|string $idUser): string
    {
        return 'user_' . $idUser;
    }
}
