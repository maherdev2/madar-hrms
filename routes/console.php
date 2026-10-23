<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Role;
use App\Models\User;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('hrms:bootstrap-admin {email} {name}', function (string $email, string $name) {
    $email = mb_strtolower(trim($email));
    $password = $this->secret('كلمة مرور مدير النظام (12 حرفًا على الأقل)');
    $data = ['email'=>$email,'name'=>trim($name),'password'=>$password];
    $validator = Validator::make($data, [
        'email'=>['required','email','unique:users,email'],
        'name'=>['required','string','max:255'],
        'password'=>['required','string','min:12'],
    ]);
    if ($validator->fails()) {
        $this->error($validator->errors()->first());
        return 1;
    }
    $role = Role::where('code','super_admin')->first();
    if (!$role) {
        $this->error('نفّذ زرع البيانات المرجعية أولًا.');
        return 1;
    }
    DB::transaction(function () use ($data,$role) {
        $user = User::create($data);
        $user->forceFill(['email_verified_at'=>now(),'is_active'=>true])->save();
        $user->roles()->attach($role->id);
    });
    $this->info('تم إنشاء حساب مدير النظام.');
    return 0;
})->purpose('Create the first production super admin account');
