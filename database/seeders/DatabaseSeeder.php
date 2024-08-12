<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use DB;
use App\Models\Graphics;
use Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::table('graphics')->insert([
            'Name' => 'Har 1 soatda',
            'Comment' => 'Sutka davomida har soatda kiritiladi',
        ]);
        DB::table('users')->insert([
            'name' => 'Zuhriddin',
            'phone' => '5972323',
            'login' => '123123',
            'password' => Hash::make('123123'),
            'structure_id'=>1,
        ]);
        DB::table('user_roles')->insert([
            ['user_id' => 1,'role_id'=>1,'view'=>1,'create'=>1,'update'=>1,'delete'=>1],
            ['user_id' => 1,'role_id'=>2,'view'=>1,'create'=>1,'update'=>1,'delete'=>1],
            ['user_id' => 1,'role_id'=>3,'view'=>1,'create'=>1,'update'=>1,'delete'=>1],
            ['user_id' => 1,'role_id'=>4,'view'=>1,'create'=>1,'update'=>1,'delete'=>1],
            ['user_id' => 1,'role_id'=>5,'view'=>1,'create'=>1,'update'=>1,'delete'=>1],
            ['user_id' => 1,'role_id'=>6,'view'=>1,'create'=>1,'update'=>1,'delete'=>1],
            ['user_id' => 1,'role_id'=>7,'view'=>1,'create'=>1,'update'=>1,'delete'=>1],
            ['user_id' => 1,'role_id'=>8,'view'=>1,'create'=>1,'update'=>1,'delete'=>1],
            ['user_id' => 1,'role_id'=>9,'view'=>1,'create'=>1,'update'=>1,'delete'=>1],
            ['user_id' => 1,'role_id'=>10,'view'=>1,'create'=>1,'update'=>1,'delete'=>1],
            ['user_id' => 1,'role_id'=>11,'view'=>1,'create'=>1,'update'=>1,'delete'=>1],
            ['user_id' => 1,'role_id'=>12,'view'=>1,'create'=>1,'update'=>1,'delete'=>1],
            ['user_id' => 1,'role_id'=>13,'view'=>1,'create'=>1,'update'=>1,'delete'=>1],
            ['user_id' => 1,'role_id'=>14,'view'=>1,'create'=>1,'update'=>1,'delete'=>1],
            ['user_id' => 1,'role_id'=>15,'view'=>1,'create'=>1,'update'=>1,'delete'=>1],

        ]);
        DB::table('roles')->insert([
            ['Name' => 'menu.dashboard'],
            ['Name' => 'menu.factory'],
            ['Name' => 'menu.structure'],
            ['Name' => 'menu.blogs'],
            ['Name' => 'menu.units'],
            ['Name' => 'menu.graphics'],
            ['Name' => 'menu.graphictimes'],
            ['Name' => 'menu.paramtypes'],
            ['Name' => 'menu.params'],
            ['Name' => 'menu.sources'],
            ['Name' => 'menu.changes'],
            ['Name' => 'menu.paramgraphics'],
            ['Name' => 'menu.vparams'],
            ['Name' => 'menu.users'],
            ['Name' => 'menu.logout'],
        ]);
        DB::table('factory_structures')->insert([
            ['Name' => 'Maydalash sexi'],
            ['Name' => '1-sex'],

        ]);
        DB::table('parameters')->insert([
            ['id'=>'62f0a8e1-b7cd-47f3-a25b-6f6f837e6079','Name' => 'Chiziqli o\'lchov','ShortName'=>'Chiziqli o\'lchov','ParametrTypeID'=>1,'UnitsID'=>9,'Min'=>'5','Max'=>'5'],
            ['id'=>'fb3a19c0-89fe-4a00-bd3c-19f4ede0afeb','Name' => 'Differensial transformator','ShortName'=>'Differensial transformator`','ParametrTypeID'=>1,'UnitsID'=>9,'Min'=>'500','Max'=>'700'],
            ['id'=>'f78fedae-d7d1-4f2b-9d6d-1d883299feb9','Name' => 'Ekscentrik harorati №1','ShortName'=>'Differensial transformator`','ParametrTypeID'=>1,'UnitsID'=>6,'Min'=>'50','Max'=>'70'],
            ['id'=>'366280ab-7800-43f3-84a8-f86b5b1c9f2e','Name' => 'Ekscentrik harorati №2','ShortName'=>'Differensial transformator`','ParametrTypeID'=>1,'UnitsID'=>6,'Min'=>'50','Max'=>'70'],
            ['id'=>'ca1abe46-a810-400e-b4d8-1a596de974ce','Name' => 'Ekscentrik harorati №3','ShortName'=>'Differensial transformator`','ParametrTypeID'=>1,'UnitsID'=>6,'Min'=>'50','Max'=>'70'],
            ['id'=>'ca1abe46-a810-400e-b4d8-1a596de974cb','Name' => 'Ekscentrik harorati №4','ShortName'=>'Differensial transformator`','ParametrTypeID'=>1,'UnitsID'=>6,'Min'=>'50','Max'=>'70'],
            ['id'=>'f2b4ee93-9a6c-4373-ab8a-c8f9f84f1098','Name' => 'Moy kirishi','ShortName'=>'Moy kirishi`','ParametrTypeID'=>1,'UnitsID'=>6,'Min'=>'50','Max'=>'70'],
            ['id'=>'c62cc14c-5dde-4cbc-ba11-91e2f5a1225e','Name' => 'Moy chiqishi','ShortName'=>'Moy chiqishi`','ParametrTypeID'=>1,'UnitsID'=>6,'Min'=>'50','Max'=>'70'],
            ['id'=>'b21d769d-f63c-4ce3-bf58-26e7ca1fda79','Name' => 'Moy baki','ShortName'=>'Moy baki`','ParametrTypeID'=>1,'UnitsID'=>6,'Min'=>'50','Max'=>'70'],
            ['id'=>'f38678ff-9b4e-4e14-acac-c5e64c944d40','Name' => 'Yog\'ta\'minot bosimi','ShortName'=>'Yog\'ta\'minot bosimi`','ParametrTypeID'=>1,'UnitsID'=>10,'Min'=>'1','Max'=>'1.2'],
            ['id'=>'74859597-8263-440e-b912-b24fe544a62d','Name' => 'Vagonlar soni','ShortName'=>'Vagonlar soni`','ParametrTypeID'=>1,'UnitsID'=>11,'Min'=>'','Max'=>''],
        ]);
        DB::table('blogs')->insert([
            ['StructureID' => 1,'Name'=>'Maydalagichni tushirish uyasi kengligi'],
            ['StructureID' => 1,'Name'=>'Buzg\'unchi pozitsiyakonus'],
            ['StructureID' => 1,'Name'=>'Maydalagich'],
            ['StructureID' => 1,'Name'=>'Maydalagich'],
            ['StructureID' => 1,'Name'=>'Maydalagich'],
            ['StructureID' => 1,'Name'=>'Maydalagich'],
            ['StructureID' => 1,'Name'=>'Moy baki'],
            ['StructureID' => 1,'Name'=>'Moy baki'],
            ['StructureID' => 1,'Name'=>'Moy baki'],
            ['StructureID' => 1,'Name'=>'Moy baki'],
            ['StructureID' => 1,'Name'=>'Vagonlar'],
        ]);
        DB::table('graphics_paramenters')->insert([
            ['OrderNumber' => 1,'ParametersID'=>'62f0a8e1-b7cd-47f3-a25b-6f6f837e6079','FactoryStructureID'=>1,'BlogsID'=>1,'GrapicsID'=>1,'SourceID'=>1],
            ['OrderNumber' => 2,'ParametersID'=>'fb3a19c0-89fe-4a00-bd3c-19f4ede0afeb','FactoryStructureID'=>1,'BlogsID'=>1,'GrapicsID'=>1,'SourceID'=>1],
            ['OrderNumber' => 3,'ParametersID'=>'f78fedae-d7d1-4f2b-9d6d-1d883299feb9','FactoryStructureID'=>1,'BlogsID'=>1,'GrapicsID'=>1,'SourceID'=>1],
            ['OrderNumber' => 4,'ParametersID'=>'366280ab-7800-43f3-84a8-f86b5b1c9f2e','FactoryStructureID'=>1,'BlogsID'=>1,'GrapicsID'=>1,'SourceID'=>1],
            ['OrderNumber' => 5,'ParametersID'=>'ca1abe46-a810-400e-b4d8-1a596de974ce','FactoryStructureID'=>1,'BlogsID'=>1,'GrapicsID'=>1,'SourceID'=>1],
            ['OrderNumber' => 6,'ParametersID'=>'ca1abe46-a810-400e-b4d8-1a596de974cb','FactoryStructureID'=>1,'BlogsID'=>1,'GrapicsID'=>1,'SourceID'=>1],
            ['OrderNumber' => 7,'ParametersID'=>'f2b4ee93-9a6c-4373-ab8a-c8f9f84f1098','FactoryStructureID'=>1,'BlogsID'=>1,'GrapicsID'=>1,'SourceID'=>1],
            ['OrderNumber' => 8,'ParametersID'=>'c62cc14c-5dde-4cbc-ba11-91e2f5a1225e','FactoryStructureID'=>1,'BlogsID'=>1,'GrapicsID'=>1,'SourceID'=>1],
            ['OrderNumber' => 9,'ParametersID'=>'b21d769d-f63c-4ce3-bf58-26e7ca1fda79','FactoryStructureID'=>1,'BlogsID'=>1,'GrapicsID'=>1,'SourceID'=>1],
            ['OrderNumber' => 10,'ParametersID'=>'f38678ff-9b4e-4e14-acac-c5e64c944d40','FactoryStructureID'=>1,'BlogsID'=>1,'GrapicsID'=>1,'SourceID'=>1],
            ['OrderNumber' => 11,'ParametersID'=>'74859597-8263-440e-b912-b24fe544a62d','FactoryStructureID'=>1,'BlogsID'=>1,'GrapicsID'=>1,'SourceID'=>1],

        ]);
        DB::table('graphic_times')->insert([
            ['GraphicsID' => 1, 'Change' => 1, 'Name' => '8:00', 'StartTime' => '08:00:00.0000000', 'EndTime' => '08:05:00.0000000'],
            ['GraphicsID' => 1, 'Change' => 1, 'Name' => '9:00', 'StartTime' => '09:00:00.0000000', 'EndTime' => '09:05:00.0000000'],
            ['GraphicsID' => 1, 'Change' => 1, 'Name' => '10:00', 'StartTime' => '10:00:00.0000000', 'EndTime' => '10:05:00.0000000'],
            ['GraphicsID' => 1, 'Change' => 1, 'Name' => '11:00', 'StartTime' => '11:00:00.0000000', 'EndTime' => '11:05:00.0000000'],
            ['GraphicsID' => 1, 'Change' => 1, 'Name' => '12:00', 'StartTime' => '12:00:00.0000000', 'EndTime' => '12:05:00.0000000'],
            ['GraphicsID' => 1, 'Change' => 1, 'Name' => '13:00', 'StartTime' => '13:00:00.0000000', 'EndTime' => '13:05:00.0000000'],
            ['GraphicsID' => 1, 'Change' => 1, 'Name' => '14:00', 'StartTime' => '14:00:00.0000000', 'EndTime' => '14:05:00.0000000'],
            ['GraphicsID' => 1, 'Change' => 1, 'Name' => '15:00', 'StartTime' => '15:00:00.0000000', 'EndTime' => '15:05:00.0000000'],
            ['GraphicsID' => 1, 'Change' => 1, 'Name' => '16:00', 'StartTime' => '16:00:00.0000000', 'EndTime' => '16:05:00.0000000'],
            ['GraphicsID' => 1, 'Change' => 1, 'Name' => '17:00', 'StartTime' => '17:00:00.0000000', 'EndTime' => '17:05:00.0000000'],
            ['GraphicsID' => 1, 'Change' => 1, 'Name' => '18:00', 'StartTime' => '18:00:00.0000000', 'EndTime' => '18:05:00.0000000'],
            ['GraphicsID' => 1, 'Change' => 1, 'Name' => '19:00', 'StartTime' => '19:00:00.0000000', 'EndTime' => '19:05:00.0000000'],
            ['GraphicsID' => 1, 'Change' => 2, 'Name' => '20:00', 'StartTime' => '20:00:00.0000000', 'EndTime' => '20:05:00.0000000'],
            ['GraphicsID' => 1, 'Change' => 2, 'Name' => '21:00', 'StartTime' => '21:00:00.0000000', 'EndTime' => '21:05:00.0000000'],
            ['GraphicsID' => 1, 'Change' => 2, 'Name' => '22:00', 'StartTime' => '22:00:00.0000000', 'EndTime' => '22:05:00.0000000'],
            ['GraphicsID' => 1, 'Change' => 2, 'Name' => '23:00', 'StartTime' => '23:00:00.0000000', 'EndTime' => '23:05:00.0000000'],
            ['GraphicsID' => 1, 'Change' => 2, 'Name' => '00:00', 'StartTime' => '00:00:00.0000000', 'EndTime' => '00:05:00.0000000'],
            ['GraphicsID' => 1, 'Change' => 2, 'Name' => '01:00', 'StartTime' => '01:00:00.0000000', 'EndTime' => '01:05:00.0000000'],
            ['GraphicsID' => 1, 'Change' => 2, 'Name' => '02:00', 'StartTime' => '02:00:00.0000000', 'EndTime' => '02:05:00.0000000'],
            ['GraphicsID' => 1, 'Change' => 2, 'Name' => '03:00', 'StartTime' => '03:00:00.0000000', 'EndTime' => '03:05:00.0000000'],
            ['GraphicsID' => 1, 'Change' => 2, 'Name' => '04:00', 'StartTime' => '04:00:00.0000000', 'EndTime' => '04:05:00.0000000'],
            ['GraphicsID' => 1, 'Change' => 2, 'Name' => '05:00', 'StartTime' => '05:00:00.0000000', 'EndTime' => '05:05:00.0000000'],
            ['GraphicsID' => 1, 'Change' => 2, 'Name' => '06:00', 'StartTime' => '06:00:00.0000000', 'EndTime' => '06:05:00.0000000'],
            ['GraphicsID' => 1, 'Change' => 2, 'Name' => '07:00', 'StartTime' => '07:00:00.0000000', 'EndTime' => '07:05:00.0000000'],
        ]);
        DB::table('sources')->insert([
            ['Name' => 'Qo`lda kiritiladi', 'Shortname' => 'Kiritiladi'],
            ['Name' => 'WINCC tizimidan olinadi', 'Shortname' => 'WINCC'],
        ]);
        DB::table('paramenters_types')->insert([
            ['Name' => 'Son', 'Comment' => 'float'],
            ['Name' => 'Matn', 'Comment' => 'string'],
            ['Name' => 'Ro`yxat', 'Comment' => 'Combobox'],
        ]);
        DB::table('changes')->insert([
            ['FactoryID' => 1, 'Change' => 1, 'StartingDay' => 0, 'StartingTime' => '08:00:00.0000001', 'EndingDay' => 0, 'EndingTime' => '20:00:00.0000000'],
            ['FactoryID' => 1, 'Change' => 2, 'StartingDay' => 1, 'StartingTime' => '20:00:00.0000001', 'EndingDay' => 0, 'EndingTime' => '08:00:00.0000000'],
            ['FactoryID' => 1, 'Change' => 2, 'StartingDay' => 0, 'StartingTime' => '20:00:00.0000001', 'EndingDay' => 1, 'EndingTime' => '08:00:00.0000000'],
        ]);
        DB::table('units')->insert([
            ['Name' => 'килограмм', 'ShortName' => 'кг.'],
            ['Name' => 'метр куб', 'ShortName' => 'м³'],
            ['Name' => 'тонна', 'ShortName' => 'т.'],
            ['Name' => 'грамм.', 'ShortName' => 'г.'],
            ['Name' => 'босим', 'ShortName' => 'Pa'],
            ['Name' => 'харорат', 'ShortName' => '°С'],
            ['Name' => 'cекунд', 'ShortName' => 's'],
            ['Name' => 'сатхи', 'ShortName' => 'Sh'],
            ['Name' => 'Миллиметр', 'ShortName' => 'мм'],
            ['Name' => 'Килограмм-сила', 'ShortName' => 'kg/sm2'],
            ['Name' => 'Дона', 'ShortName' => 'шт'],
        ]);
        DB::table('type_factories')->insert([
            ['Name' => '3-gidrometallurgiya zavodi', 'ShortName' => 'GMZ-3'],
        ]);
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
