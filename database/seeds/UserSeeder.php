<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = App\User::all();

        // foreach ($user as $u) {
        //     $data = App\User::where('nik', $u->nik)->first();
        //     DB::table('hakakses')
        //     // ->where('nik', $data->nik)
        //     ->update([
        //         'password' => $data->pass
        //     ]);
        // }
        DB::table('hakakses')->orderBy('nik')->chunk(100, function ($users) {
            foreach ($users as $user) {
                DB::table('hakakses')
                    ->where('nik', $user->nik)
                    ->update(['password' => app('hash')->make($user->pass)]);
            }
        });
    }
}
