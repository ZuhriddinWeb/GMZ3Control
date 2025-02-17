<?php

namespace App\Observers;

use App\Models\ValuesParameters;
use App\Models\Calculator;
use App\Models\GraphicsParamenters;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ValuesParametersObserver
{
    /**
     * Handle the ValuesParameters "saved" event.
     */
    public function saved(ValuesParameters $valuesParameters)
    {
        // dd($valuesParameters->ChangeID,$valuesParameters->Created);
        DB::transaction(function () use ($valuesParameters) {
            // TimeID bir xil bo‘lgan barcha mos calculator yozuvlarini olish
            $calculators = Calculator::where('TimeID', $valuesParameters->TimeID)->get();
            dd($calculators);

            // Har bir calculator uchun ishga tushirish
            foreach ($calculators as $calculator) {
                // Ushbu ParametersID uchun GraphicsParameter yozuvini olish
                $param = GraphicsParamenters::where('ParametersID', $calculator->ParametersID)->first();
                if (!$param) {
                    continue; // Agar mos keluvchi GraphicsParameter topilmasa, keyingi siklga o'tish
                }

                // `Calculate` maydonini JSON stringdan massivga aylantirish, agar u string bo'lsa
                $calculateArray = is_string($calculator->Calculate) ? json_decode($calculator->Calculate, true) : $calculator->Calculate;

                // O‘zgaruvchilarni ishga tushirish
                $result = null;
                $numberBuffer = "";
                $values = [];
                $operatorStack = [];
                $parameters = [];

                // `Calculate` ichidagi har bir `ParametersID` va `TimeID` ni `ValuesParameters` jadvalidan olish
                foreach ($calculateArray as $item) {
                    if (strpos($item, 'Pid=') === 0) {
                        $parameterId = substr($item, 4);
                    } elseif (strpos($item, 'Tid=') === 0) {
                        $timeId = substr($item, 4);
                
                        // **1️⃣ `graphic_times` jadvalidan `TimeID` bo‘yicha `Name` ni olish**
                        $timeName = DB::table('graphic_times')
                            ->where('id', $timeId)
                            ->value('Name');
                
                        // Agar `graphic_times` jadvalidan `Name` topilmagan bo‘lsa, logga yozamiz
                        if (!$timeName) {
                            \Log::error("graphic_times dan `Name` topilmadi! TimeID: $timeId");
                            continue;
                        }
                
                        // **2️⃣ `ValuesParameters` dan `graphic_times.Name` bo‘yicha `Value` ni olish**
                        $parameters[$parameterId][$timeId] = DB::table('values_parameters AS vp')
                            ->leftJoin('graphic_times AS gt', 'vp.TimeID', '=', 'gt.id')
                            ->where('vp.ParametersID', $parameterId)
                            ->where('gt.Name', $timeName) // `graphic_times` dan kelgan `Name` bo‘yicha filter
                            ->where('vp.Created', $valuesParameters->Created)
                            ->value('vp.Value') ?? 0;
                
                        // **Log qo‘shish (tekshirish uchun)**
                        \Log::info("Graphic Timesdan topilgan Name: $timeName | ValuesParameters dagi Value: {$parameters[$parameterId][$timeId]}");
                    }
                }
                

                // Hisoblash ifodasini yaratish uchun `calculateArray` ichidagi har bir elementni ko‘rib chiqish
                foreach ($calculateArray as $item) {
                    if (strpos($item, 'Pid=') === 0) {
                        $parameterId = substr($item, 4);
                    } elseif (strpos($item, 'Tid=') === 0) {
                        $timeId = substr($item, 4);
                        $value = $parameters[$parameterId][$timeId] ?? 0;
                        $numberBuffer .= (string) $value;
                    } elseif (in_array($item, ['+', '-', '*', '÷', '/', '=', '(', ')'])) {
                        if ($numberBuffer !== "") {
                            $values[] = $numberBuffer;
                            $numberBuffer = "";
                        }

                        if ($item === '÷') {
                            $item = '/';
                        }

                        if ($item === '=') {
                            break;
                        } elseif ($item === '(') {
                            $values[] = $item;
                        } elseif ($item === ')') {
                            while (!empty($operatorStack) && end($operatorStack) !== '(') {
                                $values[] = array_pop($operatorStack);
                            }
                            if (!empty($operatorStack) && end($operatorStack) === '(') {
                                array_pop($operatorStack);
                            }
                            $values[] = $item;
                        } else {
                            $values[] = $item;
                        }
                    } else {
                        $numberBuffer .= $item;
                    }
                }

                if ($numberBuffer !== "") {
                    $values[] = $numberBuffer;
                }
                // Hisoblash ifodasini birlashtirish
                $calculateString = implode(' ', $values);
                dd($calculateString);

                try {
                    // Ifodani hisoblash
                    $result = eval("return $calculateString;");
                    logger()->info("Hisoblangan natija: $result"); // Natijani logga yozish
                } catch (\Exception $e) {
                    logger()->error("Hisoblashda xato: " . $e->getMessage());
                    continue;
                }

                // Ma'lumotlarni qo‘shish yoki yangilashni hodisalarsiz amalga oshirish
                ValuesParameters::withoutEvents(function () use ($valuesParameters, $param, $result) {
                    $data = [
                        'ParametersID' => (string) $param->ParametersID,
                        'SourceID' => (string) $param->SourceID,
                        'GTid' => (string) $valuesParameters->TimeID,
                        'Value' => round($result, 2),
                        'GraphicsTimesID' => (string) $param->GrapicsID,
                        'BlogID' => (string) $param->BlogsID,
                        'FactoryStructureID' => (string) $param->FactoryStructureID,
                        'ChangeID'=>$valuesParameters->ChangeID,
                        'created_at' => now(),
                        'Created' => $valuesParameters->Created,
                    ];
                    $newOrUpdateRecord = ValuesParameters::updateOrCreate(
                        [
                            'TimeID' => $data['GTid'],
                            'ParametersID' => $data['ParametersID'],
                            'SourcesID' => $data['SourceID'],
                            'Created' => $valuesParameters->Created,
                        ],
                        [
                            'id' => (string) Str::uuid(), // UUID ni qo'shish
                            'Value' => $data['Value'],
                            'GraphicsTimesID' => $data['GraphicsTimesID'],
                            'BlogID' => $data['BlogID'],
                            'FactoryStructureID' => $data['FactoryStructureID'],
                            'ChangeID'=>$valuesParameters->ChangeID,
                            'Created' => $valuesParameters->Created,
                            'updated_at' => now(),
                        ]
                    );

                    // Tekshirish: Natija bazaga to'g'ri yozilganligini ko'rish uchun
                    logger()->info("Bazaga yozilgan yozuv: ", $newOrUpdateRecord->toArray());
                    // dd($newOrUpdateRecord); // Agar kerak bo'lsa, bu qator natijani tekshirish uchun ishlatilishi mumkin
                });
            }
        });
    }
}
