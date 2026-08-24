<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SystemSetting;

class SystemSettingsSeeder extends Seeder
{
    public function run()
    {
        $defaults = [
            ['key'=>'system_name','value'=>'University Resources Booking System'],
            ['key'=>'organization_name','value'=>'State University'],
            ['key'=>'contact_email','value'=>'admin@university.edu'],
            ['key'=>'phone_number','value'=>'+1 (555) 123-4567'],
            ['key'=>'address','value'=>'123 University Ave, Campus City'],
            ['key'=>'logo','value'=>null,'type'=>'file'],
            ['key'=>'storage_usage','value'=>'75%','type'=>'status'],
        ];

        foreach ($defaults as $d) {
            SystemSetting::updateOrCreate(['key'=>$d['key']], $d);
        }
    }
}