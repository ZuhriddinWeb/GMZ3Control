<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Calculator;
use App\Models\ValuesParameters;
use App\Models\GraphicsParamenters;
use Illuminate\Support\Str;

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
    private function getRowUnit($id)
    {
        // Step 1: Fetch all Calculator entries where the Calculate field contains the given $id (ParametersID)
        $calculator = Calculator::whereJsonContains('Calculate', $id)->first();
        // dd($calculator->ParametersID);
        $param = GraphicsParamenters::where('ParametersID',$calculator->ParametersID)->first();
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
                $numberBuffer .= (string)$value; // Ensure it's a string
            } elseif (in_array($item, ['+', '-', '*', '÷', '/', '='])) {
                // If we have a buffered number, convert it to a float and store it
                if ($numberBuffer !== "") {
                    $values[] = (float)$numberBuffer; // Store the buffered number as float
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
            $values[] = (float)$numberBuffer; // Store the last number as float
        }
    
        // Perform calculations respecting operator precedence
        if (empty($values)) {
            return null; // No values to calculate
        }
    
        // Call the calculation function with precedence handling
        $result = $this->calculateWithPrecedence($values);
        
        // return round($result, 2); // Return the final calculated result rounded to 2 decimal places
        $uuidString = (string) Str::uuid();
        ValuesParameters::create([
            'id' => $uuidString,
            'ParametersID' => $param->ParametersID,
            'SourcesID' => $param->SourceID,
            'TimeID' => $param->GrapicsID,
            'Value' => round($result, 2),
            'GraphicsTimesID' => $param->GrapicsID,
            'BlogID' => $param->BlogsID,
            'FactoryStructureID' => $param->FactoryStructureID,
            // 'Comment' => $request->Comment,
            'created_at' => now(),
            'Created' => now(),
            // 'Creator' => $request->userId,  // Yaratgan foydalanuvchini saqlash
        ]);
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
        $unit = Calculator::create([
            'ParametersID' => $request->id,
            'Calculate' => $request->Calculate,
            'Comment' => $request->Comment,
        ]);

        return response()->json([
            'status' => 200,
            'message' => "Javob muvafaqiyatli qo'shildi",
            'unit' => $unit
        ]);
    }

    private function update(Request $request)
    {


        $unit = Calculator::find($request->id);
        $unit->update([
            'FormulaId' => $request->id,
            'Calculate' => $request->Calculate,
            'Comment' => $request->Comment,
        ]);

        return response()->json([
            'status' => 200,
            'message' => "Javob muvafaqiyatli yangilandi",
            'unit' => $unit
        ]);
    }

    public function delete(Request $request, $id)
    {
        try {
            $unit = Units::findOrFail($id);
            $unit->delete();

            return response()->json(['status' => 200, 'message' => 'Unit deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => 'Error deleting unit: ' . $e->getMessage()]);
        }
    }

}
