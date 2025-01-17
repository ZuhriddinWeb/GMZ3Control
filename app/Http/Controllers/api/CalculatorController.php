<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Calculator;
use App\Models\ValuesParameters;
use App\Models\GraphicsParamenters;
use Illuminate\Support\Str;
use App\Http\Controllers\api\ParametrValueController;
use Illuminate\Support\Facades\Route;
class CalculatorController extends Controller
{
    public function handle(Request $request, $id = null)
    {
        if ($id !== null && $request->isMethod('get')) {
            return $this->getRowUnit($id);
        }

        switch ($request->method()) {
            case 'GET':
                return $this->index();
            case 'POST':
                return $this->create($request);
            case 'PUT':
                return $this->update($request, $id);
            case 'DELETE':
                return $this->delete($request, $id);
            default:
                return response()->json(['message' => 'Method not allowed'], 405);
        }
    }

    private function index()
    {
        $units = Calculator::all();
        return response()->json($units);
    }
    public function getForFormuleId($paramId, $timeid)
    {
        $unit = Calculator::where('ParametersID', $paramId)->where('TimeID', $timeid)->first();
        return $unit;
    }
    private function getRowUnit($id)
    {
        // dd($id);
        // Step 1: Fetch all Calculator entries where the Calculate field contains the given $id (ParametersID)
        $calculator = Calculator::whereJsonContains('Calculate', $id)->first();
        // dd($calculator->ParametersID);
        $param = GraphicsParamenters::where('ParametersID', $calculator->ParametersID)->first();
        // dd($param);

        if (!$calculator) {
            return response()->json(['error' => 'Calculator entry not found'], 404);
        }

        // Step 2: Directly access the 'Calculate' field as an array
        $calculateArray = $calculator->Calculate;

        // Step 3: Extract the parameter IDs from the 'Calculate' array
        $parameterIds = [];
        $operators = [];

        foreach ($calculateArray as $item) {
            if (strlen($item) === 36) {
                // This is likely a ParametersID
                $parameterIds[] = $item;
            } elseif (in_array($item, ['+', '-', '*', '÷', '/'])) {
                // This is an arithmetic operator
                $operators[] = $item;
            }
        }

        // Check if we have any ParametersID to query
        if (empty($parameterIds)) {
            return response()->json(['error' => 'No valid ParametersID found in Calculate field'], 400);
        }
        $timeId = ValuesParameters::whereIn('ParametersID', $parameterIds)->get()->pluck('TimeID', )->toArray();
        dd($timeId[0]);
        // Step 4: Query the parameters_value table using the extracted ParametersID values
        $parameters = ValuesParameters::whereIn('ParametersID', $parameterIds)->get()->pluck('Value', 'ParametersID')->toArray();

        if (empty($parameters)) {
            return response()->json(['error' => 'No parameter values found'], 404);
        }

        // Initialize variables for calculation
        $result = null;
        $currentOperator = null;
        $numberBuffer = "";
        $values = [];

        // Parse the calculation array
        foreach ($calculateArray as $item) {
            if (strlen($item) === 36) {
                // This is a ParametersID, retrieve its value
                $value = $parameters[$item] ?? 0; // Use 0 if the parameter is missing

                // Concatenate the value to the buffer
                $numberBuffer .= (string) $value; // Ensure it's a string
            } elseif (in_array($item, ['+', '-', '*', '÷', '/', '='])) {
                // If we have a buffered number, convert it to a float and store it
                if ($numberBuffer !== "") {
                    $values[] = (float) $numberBuffer; // Store the buffered number as float
                    $numberBuffer = ""; // Reset the buffer
                }

                if ($item === '=') {
                    break; // End of calculation
                } else {
                    // Store the operator
                    $values[] = $item;
                }
            } else {
                // Concatenate number values
                $numberBuffer .= $item; // Concatenate numbers
            }
        }

        // Handle the last buffered number if any
        if ($numberBuffer !== "") {
            $values[] = (float) $numberBuffer; // Store the last number as float
        }

        // Perform calculations respecting operator precedence
        if (empty($values)) {
            return null; // No values to calculate
        }

        // Call the calculation function with precedence handling
        $result = $this->calculateWithPrecedence($values);

        // return round($result, 2); // Return the final calculated result rounded to 2 decimal places
        $data = [
            'ParametersID' => (string) $param->ParametersID,
            'SourceID' => (string) $param->SourceID,
            'GTid' => (string) $timeId[0],
            'Value' => round($result, 2),
            'GraphicsTimesID' => (string) $param->GrapicsID,
            'BlogID' => (string) $param->BlogsID,
            'FactoryStructureID' => (string) $param->FactoryStructureID,
            'created_at' => now(),
            'Created' => now(),
        ];
        dd($data);
        $existingValue = ValuesParameters::where([
            'ParametersID' => $data['ParametersID'],
            'TimeID' => $data['GTid'],
            'SourcesID' => $data['SourceID'],
        ])->first();
        // dd($existingValue);
        if ($existingValue) {
            // Update the existing record
            $existingValue->update([
                'Value' => $data['Value'],
                'GraphicsTimesID' => $data['GraphicsTimesID'],
                'BlogID' => $data['BlogID'],
                'FactoryStructureID' => $data['FactoryStructureID'],
                'updated_at' => now(),
            ]);

            return response()->json(['message' => 'Value updated successfully']);
        } else {
            // Create a new record
            $request = new Request($data);
            $parameterValueController = new ParametrValueController();
            return $parameterValueController->create($request);
        }
        // $request = new Request($data);

        // $parameterValueController = new ParametrValueController();
        // return $parameterValueController->create($request);

    }

    // Helper function to calculate with precedence
    private function calculateWithPrecedence(array $values)
    {
        // Initialize the result with the first numeric value
        $result = array_shift($values); // Take the first value and remove it from the array

        while (!empty($values)) {
            $currentOperator = array_shift($values); // Get the next operator
            $nextValue = array_shift($values); // Get the next numeric value

            if (is_numeric($nextValue)) { // Ensure nextValue is numeric
                switch ($currentOperator) {
                    case '+':
                        $result += $nextValue;
                        break;
                    case '-':
                        $result -= $nextValue;
                        break;
                    case '*':
                        $result *= $nextValue;
                        break;
                    case '÷':
                    case '/':
                        $result /= ($nextValue != 0) ? $nextValue : 1; // Avoid division by zero
                        break;
                    default:
                        break; // Handle any unexpected operators
                }
            }
        }

        return $result; // Return the calculated result
    }



    private function create(Request $request)
    {
        $tidValues = []; // Asl Tid massiv
        $integerTidValues = []; // Integerga o'zgartirilgan Tid massiv
    
        foreach ($request['Calculate'] as $item) {
            if (is_array($item)) { // Faqat Tid massivlarini ajratamiz
                $tidValues = $item; // Tid massivini o'zida saqlab qolamiz
    
                // Tid= ni olib tashlaymiz va integerga aylantiramiz
                $integerTidValues = array_map(function ($tid) {
                    return (int) str_replace("Tid=", "", $tid);
                }, $item);
            }
        }
    
        // Formulalarni yaratish va bazaga yozish
        if ($request['Comment'] === "1") {
            $totalTidCount = count($tidValues); // Umumiy Tid qiymatlari soni
            for ($i = 1; $i <= $totalTidCount; $i++) {
                $formula = []; // Har bir sikl uchun yangi formula
    
                // Birinchi element uchun formula
                if ($i === 1) {
                    $formula = [
                        "(",
                        $request['Calculate'][1], // Pid qiymati
                        "{$tidValues[0]}",
                        ")",
                        "/",
                        "1"
                    ];
                } else {
                    $formula[] = "(";
                    for ($j = 0; $j < $i; $j++) {
                        $formula[] = $request['Calculate'][1]; // Pid qiymati
                        $formula[] = "{$tidValues[$j]}"; // Tid qiymati
                        if ($j < $i - 1) {
                            $formula[] = "+"; // "+" operator
                        }
                    }
                    $formula[] = ")";
                    $formula[] = "/";
                    $formula[] = (string) $i; // Division value
                }
    
                // Bazaga yozish
                Calculator::create([
                    'TimeID' => (int) str_replace("Tid=", "", $tidValues[$i - 1]), // TimeID ni tozalash va intga o‘tkazish
                    'ParametersID' => $request['id'], // Parametr ID qiymati
                    'Calculate' => $formula, // JSON formatdagi formula
                    'Comment' => $request['Comment'], // Komment qiymati
                ]);
            }
        } elseif ($request['Comment'] === "2") {
            foreach ($integerTidValues as $key => $timeID) {
                $newCalculate = []; // Har bir "Tid" uchun yangi Calculate massiv
                foreach ($request['Calculate'] as $subItem) {
                    if (is_array($subItem)) {
                        $newCalculate[] = $tidValues[$key]; // Asl Tid qiymatini qo'shamiz
                    } else {
                        $newCalculate[] = $subItem; // Qolgan qismlar o'z holicha
                    }
                }
    
                Calculator::create([
                    'TimeID' => $timeID,
                    'ParametersID' => $request['id'],
                    'Calculate' => $newCalculate,
                    'Comment' => $request['Comment'],
                ]);
            }
        } elseif ($request['Comment'] === "3") {
            $pid1 = $request['Calculate'][1]; // Birinchi parameter ID
            $pid2 = $request['Calculate'][4]; // Ikkinchi parameter ID
            $totalTidCount = count($tidValues); // Tid qiymatlarining umumiy soni
    
            for ($i = 0; $i < $totalTidCount; $i++) {
                $formula = [
                    "(",
                    $pid1,            // Birinchi parameter ID
                    $tidValues[$i],   // Birinchi Tid
                    "+",
                    $pid2,            // Ikkinchi parameter ID
                    $tidValues[$i],   // Ikkinchi Tid
                    ")",
                    "/",
                    "2"
                ];
    
                // Bazaga yozish
                Calculator::create([
                    'TimeID' => (int) str_replace("Tid=", "", $tidValues[$i]), // TimeID ni tozalash va intga o‘tkazish
                    'ParametersID' => $request['id'], // Parametr ID qiymati
                    'Calculate' => $formula, // JSON formatdagi formula
                    'Comment' => $request['Comment'], // Komment qiymati
                ]);
            }
        } elseif ($request['Comment'] === "4") {
            $pid1 = $request['Calculate'][1]; // Birinchi parameter ID
            $pid2 = $request['Calculate'][4]; // Ikkinchi parameter ID
            $totalTidCount = count($tidValues); // Tid qiymatlarining umumiy soni
    
            for ($i = 0; $i < $totalTidCount; $i++) {
                $formula = [
                    "(",
                    $pid1,            // Birinchi parameter ID
                    $tidValues[$i],   // Birinchi Tid
                    "*",
                    $pid2,            // Ikkinchi parameter ID
                    $tidValues[$i],   // Ikkinchi Tid
                    ")"
                ];
    
                // Bazaga yozish
                Calculator::create([
                    'TimeID' => (int) str_replace("Tid=", "", $tidValues[$i]), // TimeID ni tozalash va intga o‘tkazish
                    'ParametersID' => $request['id'], // Parametr ID qiymati
                    'Calculate' => $formula, // JSON formatdagi formula
                    'Comment' => $request['Comment'], // Komment qiymati
                ]);
            }
        }
        else {
            $totalTidCount = count($tidValues); // Umumiy Tid qiymatlari soni
            for ($i = 1; $i <= $totalTidCount; $i++) {
                $formula = []; // Har bir sikl uchun yangi formula
    
                if ($i === 1) {
                    $formula = [
                        "(",
                        $request['Calculate'][1], // Pid qiymati
                        "{$tidValues[0]}",
                        ")"
                    ];
                } else {
                    $formula[] = "(";
                    for ($j = 0; $j < $i; $j++) {
                        $formula[] = $request['Calculate'][1]; // Pid qiymati
                        $formula[] = "{$tidValues[$j]}"; // Tid qiymati
                        if ($j < $i - 1) {
                            $formula[] = "+"; // "+" operator
                        }
                    }
                    $formula[] = ")";
                }
    
                // Bazaga yozish
                Calculator::create([
                    'TimeID' => (int) str_replace("Tid=", "", $tidValues[$i - 1]), // TimeID ni tozalash va intga o‘tkazish
                    'ParametersID' => $request['id'], // Parametr ID qiymati
                    'Calculate' => $formula, // JSON formatdagi formula
                    'Comment' => $request['Comment'], // Komment qiymati
                ]);
            }
        }
    
        return response()->json([
            'status' => 200,
            'message' => "Javob muvafaqiyatli qo'shildi",
        ]);
    }
    

    private function update(Request $request)
    {
        // dd($request);
        // Mavjud yozuvni `TimeID` va `ParametersID` qiymatlari bo‘yicha topamiz
        $unit = Calculator::where('TimeID', $request->TimeID)
            ->where('ParametersID', $request->id)
            ->first();

        if ($unit) {
            // Agar yozuv topilsa, uni yangilaymiz
            $unit->update([
                'Calculate' => $request->Calculate,
                'Comment' => $request->Comment,
            ]);

            return response()->json([
                'status' => 200,
                'message' => "Yozuv muvaffaqiyatli yangilandi",
                'unit' => $unit
            ]);
        } else {
            // Agar yozuv topilmasa, xato xabarini qaytarish
            return response()->json([
                'status' => 404,
                'message' => "Yozuv topilmadi"
            ]);
        }
    }


    public function delete(Request $request, $parametersID)
    {
       
        try {
            // ParametersID bo'yicha barcha yozuvlarni topish
            $records = Calculator::where('ParametersID', $parametersID)->get();
    
            // Agar hech narsa topilmasa, xato qaytarish
            if ($records->isEmpty()) {
                return response()->json(['status' => 404, 'message' => 'No records found for the given ParametersID']);
            }
    
            // Yozuvlarni o'chirish
            Calculator::where('ParametersID', $parametersID)->delete();
    
            return response()->json(['status' => 200, 'message' => 'Records deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => 'Error deleting records: ' . $e->getMessage()]);
        }
    }

}
