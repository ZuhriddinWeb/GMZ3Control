<?php

namespace App\Observers;

use App\Models\ValuesParameters;
use App\Models\Calculator;
use App\Models\GraphicsParamenters;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ValuesParametersObserver
{
    protected static $processedCalculators = []; // Oldin ishlangan kalkulyatorlarni saqlash

    public function saved(ValuesParameters $valuesParameters)
    {
        DB::transaction(function () use ($valuesParameters) {
            // **1️⃣ `graphic_times` dan `Name` olish**
            $graphicTimeName = DB::table('graphic_times')
                ->where('id', $valuesParameters->TimeID)
                ->value('Name');

            if (!$graphicTimeName) {
                \Log::error("graphic_times dagi `Name` topilmadi! TimeID: {$valuesParameters->TimeID}");
                return;
            }

            // **2️⃣ Shu `Name` ga mos keladigan barcha `TimeID` larni olish**
            $relatedTimeIds = DB::table('graphic_times')
                ->where('Name', $graphicTimeName)
                ->pluck('id');

            // **3️⃣ Shu `TimeID` lar orqali `Calculator` jadvalidan formulalarni olish**
            $calculators = Calculator::whereIn('TimeID', $relatedTimeIds)->get();

            // **4️⃣ Har bir `Calculator` uchun hisoblashni boshlash**
            foreach ($calculators as $calculator) {
                if (in_array($calculator->id, self::$processedCalculators)) {
                    continue; // Oldin hisoblangan bo'lsa, qaytadan ishlashga hojat yo'q
                }

                self::$processedCalculators[] = $calculator->id; // Hisoblangan kalkulyatorni qo‘shish

                $calculateArray = is_string($calculator->Calculate) ? json_decode($calculator->Calculate, true) : $calculator->Calculate;

                if (!$calculateArray) {
                    \Log::warning("Calculator dagi `Calculate` bo‘sh! CalculatorID: {$calculator->id}");
                    continue;
                }

                // **5️⃣ Formula ishlatiladigan `Pid` larni olish**
                $parameterIdsInCalculate = [];
                foreach ($calculateArray as $item) {
                    if (strpos($item, 'Pid=') === 0) {
                        $parameterIdsInCalculate[] = substr($item, 4);
                    }
                }

                if (!in_array($valuesParameters->ParametersID, $parameterIdsInCalculate)) {
                    continue;
                }

                // **6️⃣ GraphicsParamenters ma'lumotlarini olish**
                $param = GraphicsParamenters::where('ParametersID', $calculator->ParametersID)->first();
                if (!$param) {
                    \Log::warning("GraphicsParamenters topilmadi! CalculatorID: {$calculator->id}");
                    continue;
                }

                // **7️⃣ O‘zgaruvchilarni ishga tushirish**
                $result = null;
                $values = [];
                $parameters = [];
                $missingParameters = false;

                // **8️⃣ `Calculate` ichidagi har bir `Pid` va `Tid` ni `ValuesParameters` dan olish**
                foreach ($calculateArray as $item) {
                    if (strpos($item, 'Pid=') === 0) {
                        $parameterId = substr($item, 4);
                    } elseif (strpos($item, 'Tid=') === 0) {
                        $timeId = substr($item, 4);

                        $relatedTimeIds = DB::table('graphic_times')
                            ->where('Name', $graphicTimeName)
                            ->pluck('id');

                        $paramValue = ValuesParameters::where('ParametersID', $parameterId)
                            ->whereIn('TimeID', $relatedTimeIds)
                            ->where('Created', $valuesParameters->Created)
                            ->whereNotNull('Value') // **Hisoblangan qiymat bor-yo‘qligini tekshirish**
                            ->value('Value');

                        if (is_null($paramValue)) {
                            $missingParameters = true;
                        }

                        $parameters[$parameterId][$timeId] = $paramValue ?? 0;
                    }
                }

                // **9️⃣ Agar barcha qiymatlar mavjud bo'lmasa, qayta hisoblashni rejalashtirish**
                if ($missingParameters) {
                    \Log::warning("Formula hali to‘liq hisoblanmadi: {$calculator->id}. Keyinchalik qayta hisoblanadi.");
                    continue;
                }

                // **🔢 10️⃣ Hisoblashni boshlash**
                $numberBuffer = "";
                foreach ($calculateArray as $item) {
                    if (strpos($item, 'Pid=') === 0) {
                        $parameterId = substr($item, 4);
                    } elseif (strpos($item, 'Tid=') === 0) {
                        $timeId = substr($item, 4);
                        $values[] = $parameters[$parameterId][$timeId] ?? 0;
                    } elseif (in_array($item, ['+', '-', '*', '÷', '/', '=', '(', ')'])) {
                        if ($item === '÷') {
                            $item = '/';
                        }
                        if ($numberBuffer !== "") {
                            $values[] = $numberBuffer;
                            $numberBuffer = "";
                        }
                        $values[] = $item;
                    } else {
                        $numberBuffer .= $item;
                    }
                }

                if ($numberBuffer !== "") {
                    $values[] = $numberBuffer;
                }

                $calculateString = implode(' ', $values);
                $calculateString = preg_replace('/\s+/', ' ', $calculateString);

                try {
                    if (empty($calculateString)) {
                        throw new \Exception("Bo‘sh matematik ifoda!");
                    }

                    \Log::info("Hisoblash ifodasi: $calculateString");
                    $result = eval("return ($calculateString);");
                    if ($result === false) {
                        throw new \Exception("Eval noto‘g‘ri bajarildi: $calculateString");
                    }
                } catch (\Throwable $e) {
                    \Log::error("Hisoblashda xato: " . $e->getMessage());
                    continue;
                }

                // **🔄 11️⃣ Natijani bazaga yozish**
                ValuesParameters::withoutEvents(function () use ($valuesParameters, $param, $result) {
                    $data = [
                        'ParametersID' => (string) $param->ParametersID,
                        'SourceID' => (string) $param->SourceID,
                        'GTid' => (string) $valuesParameters->TimeID,
                        'Value' => round($result, 2),
                        'GraphicsTimesID' => (string) $param->GrapicsID,
                        'BlogID' => (string) $param->BlogsID,
                        'FactoryStructureID' => (string) $param->FactoryStructureID,
                        'ChangeID' => $valuesParameters->ChangeID,
                        'created_at' => now(),
                        'Created' => $valuesParameters->Created,
                    ];
                    ValuesParameters::updateOrCreate(
                        [
                            'TimeID' => $data['GTid'],
                            'ParametersID' => $data['ParametersID'],
                            'SourcesID' => $data['SourceID'],
                            'Created' => $valuesParameters->Created,
                        ],
                        [
                            'id' => (string) Str::uuid(),
                            'Value' => $data['Value'],
                            'GraphicsTimesID' => $data['GraphicsTimesID'],
                            'BlogID' => $data['BlogID'],
                            'FactoryStructureID' => $data['FactoryStructureID'],
                            'ChangeID' => $valuesParameters->ChangeID,
                            'Created' => $valuesParameters->Created,
                            'updated_at' => now(),
                        ]
                    );
                });

                  // **🔄 12️⃣ Rekursiv hisoblash - faqat to'liq hisoblangan qiymatlar uchun!**
                  $dependentCalculators = Calculator::whereIn('TimeID', $relatedTimeIds)->get();
                  foreach ($dependentCalculators as $depCalculator) {
                      $depCalculateArray = is_string($depCalculator->Calculate) ? json_decode($depCalculator->Calculate, true) : $depCalculator->Calculate;
                      if (!$depCalculateArray) continue;
  
                      foreach ($depCalculateArray as $item) {
                          if ($item === "Pid={$param->ParametersID}") {
                              $dependentValuesParameters = ValuesParameters::where('ParametersID', $param->ParametersID)
                                  ->whereIn('TimeID', $relatedTimeIds)
                                  ->whereNotNull('Value') // **Faqat hisoblangan qiymatlar uchun!**
                                  ->first();
  
                              if ($dependentValuesParameters) {
                                  $this->saved($dependentValuesParameters);
                              }
                              break;
                          }
                      }
                  }
            }
        });
    }
}
