<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use DB;
use App\Models\Graphics;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::table('graphics')->insert([
            'Name' => 'Har 1 soatda',
            'Comment' =>'Sutka davomida har soatda kiritiladi',
        ]);
        DB::table('graphic_times')->insert([
            [ 'GraphicsID' => 1,'Change'=>1,'Name'=>'8:00','StartTime'=>'08:00:00.0000000','EndTime'=>'08:05:00.0000000'],
            [ 'GraphicsID' => 1,'Change'=>1,'Name'=>'9:00','StartTime'=>'09:00:00.0000000','EndTime'=>'09:05:00.0000000'],
            [ 'GraphicsID' => 1,'Change'=>1,'Name'=>'10:00','StartTime'=>'10:00:00.0000000','EndTime'=>'10:05:00.0000000'],
            [ 'GraphicsID' => 1,'Change'=>1,'Name'=>'11:00','StartTime'=>'11:00:00.0000000','EndTime'=>'11:05:00.0000000'],
            [ 'GraphicsID' => 1,'Change'=>1,'Name'=>'12:00','StartTime'=>'12:00:00.0000000','EndTime'=>'12:05:00.0000000'],
            [ 'GraphicsID' => 1,'Change'=>1,'Name'=>'13:00','StartTime'=>'13:00:00.0000000','EndTime'=>'13:05:00.0000000'],
            [ 'GraphicsID' => 1,'Change'=>1,'Name'=>'14:00','StartTime'=>'14:00:00.0000000','EndTime'=>'14:05:00.0000000'],
            [ 'GraphicsID' => 1,'Change'=>1,'Name'=>'15:00','StartTime'=>'15:00:00.0000000','EndTime'=>'15:05:00.0000000'],
            [ 'GraphicsID' => 1,'Change'=>1,'Name'=>'16:00','StartTime'=>'16:00:00.0000000','EndTime'=>'16:05:00.0000000'],
            [ 'GraphicsID' => 1,'Change'=>1,'Name'=>'17:00','StartTime'=>'17:00:00.0000000','EndTime'=>'17:05:00.0000000'],
            [ 'GraphicsID' => 1,'Change'=>1,'Name'=>'18:00','StartTime'=>'18:00:00.0000000','EndTime'=>'18:05:00.0000000'],
            [ 'GraphicsID' => 1,'Change'=>1,'Name'=>'19:00','StartTime'=>'19:00:00.0000000','EndTime'=>'19:05:00.0000000'],
            [ 'GraphicsID' => 1,'Change'=>2,'Name'=>'20:00','StartTime'=>'20:00:00.0000000','EndTime'=>'20:05:00.0000000'],
            [ 'GraphicsID' => 1,'Change'=>2,'Name'=>'21:00','StartTime'=>'21:00:00.0000000','EndTime'=>'21:05:00.0000000'],
            [ 'GraphicsID' => 1,'Change'=>2,'Name'=>'22:00','StartTime'=>'22:00:00.0000000','EndTime'=>'22:05:00.0000000'],
            [ 'GraphicsID' => 1,'Change'=>2,'Name'=>'23:00','StartTime'=>'23:00:00.0000000','EndTime'=>'23:05:00.0000000'],
            [ 'GraphicsID' => 1,'Change'=>2,'Name'=>'00:00','StartTime'=>'00:00:00.0000000','EndTime'=>'00:05:00.0000000'],
            [ 'GraphicsID' => 1,'Change'=>2,'Name'=>'01:00','StartTime'=>'01:00:00.0000000','EndTime'=>'01:05:00.0000000'],
            [ 'GraphicsID' => 1,'Change'=>2,'Name'=>'02:00','StartTime'=>'02:00:00.0000000','EndTime'=>'02:05:00.0000000'],
            [ 'GraphicsID' => 1,'Change'=>2,'Name'=>'03:00','StartTime'=>'03:00:00.0000000','EndTime'=>'03:05:00.0000000'],
            [ 'GraphicsID' => 1,'Change'=>2,'Name'=>'04:00','StartTime'=>'04:00:00.0000000','EndTime'=>'04:05:00.0000000'],
            [ 'GraphicsID' => 1,'Change'=>2,'Name'=>'05:00','StartTime'=>'05:00:00.0000000','EndTime'=>'05:05:00.0000000'],
            [ 'GraphicsID' => 1,'Change'=>2,'Name'=>'06:00','StartTime'=>'06:00:00.0000000','EndTime'=>'06:05:00.0000000'],
            [ 'GraphicsID' => 1,'Change'=>2,'Name'=>'07:00','StartTime'=>'07:00:00.0000000','EndTime'=>'07:05:00.0000000'],
        ]);
        DB::table('sources')->insert([
            [ 'Name' => 'Qo`lda kiritiladi','Shortname'=>'Kiritiladi'],
            [ 'Name' => 'WINCC tizimidan olinadi','Shortname'=>'WINCC'],
        ]);
        DB::table('paramenters_types')->insert([
            [ 'Name' => 'Son','Comment'=>'float'],
            [ 'Name' => 'Matn','Comment'=>'string'],
            [ 'Name' => 'Ro`yxat','Comment'=>'Combobox'],
        ]);
        DB::table('changes')->insert([
            [ 'FactoryID' => 1,'Change'=>1,'StartingDay'=>0,'StartingTime'=>'08:00:00.0000001','EndingDay'=>0,'EndingTime'=>'20:00:00.0000000'],
            [ 'FactoryID' => 1,'Change'=>2,'StartingDay'=>1,'StartingTime'=>'20:00:00.0000001','EndingDay'=>0,'EndingTime'=>'08:00:00.0000000'],
            [ 'FactoryID' => 1,'Change'=>2,'StartingDay'=>0,'StartingTime'=>'20:00:00.0000001','EndingDay'=>1,'EndingTime'=>'08:00:00.0000000'],
        ]);
        DB::table('units')->insert([
            [ 'Name' => 'килограмм','ShortName'=>'кг.'],
            [ 'Name' => 'метр куб','ShortName'=>'м³'],
            [ 'Name' => 'тонна','ShortName'=>'т.'],
            [ 'Name' => 'грамм.','ShortName'=>'г.'],
            [ 'Name' => 'босим','ShortName'=>'Pa'],
            [ 'Name' => 'харорат','ShortName'=>'°С'],
            [ 'Name' => 'cекунд','ShortName'=>'s'],
            [ 'Name' => 'сатхи','ShortName'=>'Sh'],
        ]);
        DB::table('type_factories')->insert([
            [ 'Name' => '3-gidrometallurgiya zavodi','ShortName'=>'GMZ-3'],
        ]);
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
