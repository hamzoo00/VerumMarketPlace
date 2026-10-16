<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Creates or updates an administrator.
 *
 * Admin accounts never come from a web form; this command is the only way in.
 */
class CreateAdmin extends Command
{
    protected $signature = 'admin:create
        {username : The login username}
        {--name= : Display name}
        {--email= : Contact email}
        {--password= : Password; prompted for when omitted}
        {--force : Overwrite an existing admin with this username}';

    protected $description = 'Create an administrator account';

    public function handle(): int
    {
        $usernameArg = $this->argument('username');
        $username = is_string($usernameArg) ? $usernameArg : '';
        $existing = Admin::where('username', $username)->first();

        if ($existing !== null && ! $this->option('force')) {
            $this->error("Admin [{$username}] already exists. Pass --force to update it.");

            return self::FAILURE;
        }

        $password = $this->option('password');

        if (! is_string($password) || $password === '') {
            $password = $this->secret('Password');
        }

        $data = [
            'username' => $username,
            'name' => $this->option('name') ?: $username,
            'email' => $this->option('email') ?: null,
            'password' => $password,
        ];

        $validator = Validator::make($data, [
            'username' => ['required', 'string', 'min:3', 'max:60', 'alpha_dash'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'password' => ['required', 'string', Password::default()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $admin = Admin::updateOrCreate(
            ['username' => $username],
            [...$data, 'is_active' => true],
        );

        $this->info(($existing === null ? 'Created' : 'Updated')." admin [{$admin->username}].");

        return self::SUCCESS;
    }
}
