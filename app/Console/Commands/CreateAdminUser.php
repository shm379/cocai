<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create {email?} {--password=} {--name=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create or promote a user to administrator for Filament admin panel';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email') ?? $this->ask('آدرس ایمیل ادمین را وارد کنید', 'admin@cocai.com');
        $name = $this->option('name') ?? $this->ask('نام ادمین', 'فرمانده ارشد CoCAI');
        $password = $this->option('password') ?? $this->secret('رمز عبور ادمین', 'password');

        $user = User::firstOrNew(['email' => $email]);
        $user->name = $name;
        $user->is_admin = true;
        if (!empty($password)) {
            $user->password = Hash::make($password);
        }
        $user->email_verified_at = now();
        $user->save();

        $this->info("کاربر ادمین با موفقیت ذخیره/ارتقا یافت: {$user->email}");
        $this->info("پنل مدیریت در آدرس: /admin در دسترس است.");
    }
}
