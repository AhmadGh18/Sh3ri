<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            // Corpus curation
            'poem.create', 'poem.update', 'poem.delete',
            'poet.create', 'poet.update', 'poet.delete',
            'taxonomy.manage',
            // Moderation
            'submission.review', 'submission.approve', 'submission.reject',
            'report.handle',
            // Users
            'user.list', 'user.ban', 'user.impersonate',
        ];

        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'web');
            Permission::findOrCreate($name, 'sanctum');
        }

        $admin = Role::findOrCreate('admin', 'sanctum');
        $moderator = Role::findOrCreate('moderator', 'sanctum');
        Role::findOrCreate('user', 'sanctum');

        $admin->syncPermissions(Permission::where('guard_name', 'sanctum')->pluck('name')->all());

        $moderator->syncPermissions([
            'submission.review', 'submission.approve', 'submission.reject',
            'report.handle',
        ]);
    }
}
