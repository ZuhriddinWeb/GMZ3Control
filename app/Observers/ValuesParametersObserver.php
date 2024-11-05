<?php

namespace App\Observers;

use App\Models\ValuesParameters;
use App\Models\Calculator;
use App\Models\GraphicsParamenters;
use Illuminate\Http\Request;
use App\Http\Controllers\api\ParametrValueController;

class ValuesParametersObserver
{
    /**
     * Handle the ValuesParameters "created" event.
     */
    public function saved(ValuesParameters $valuesParameters)
    {
        // dd($valuesParameters);
        $calculators = Calculator::where('TimeID',$valuesParameters->TimeID)->get(); // Load all calculators and filter in PHP since partial JSON queries are limited
        // Find the first calculator where the 'Calculate' array contains "Pid=<ParametersID>" and matches the given TimeID
        $calculator = $calculators->firstWhere(function ($calc) use ($valuesParameters) {
            // Decode Calculate if it's JSON
            $calculateArray = is_string($calc->Calculate) ? json_decode($calc->Calculate, true) : $calc->Calculate;
        
            // Ensure calculation array is valid and contains required conditions
            return is_array($calculateArray) &&
                   in_array("Pid={$valuesParameters->ParametersID}", $calculateArray) ;
                //    in_array("Tid={$valuesParameters->TimeID}", $calculateArray);
        });
        
        
        // dd($calculator);
        if (!$calculator) {
            return response()->json(['error' => 'Calculator entry not found'], 404);
        }
        // dd($calculator);
        $param = GraphicsParamenters::where('ParametersID', $calculator->ParametersID)->first();

        if (!$calculator) {
            return response()->json(['error' => 'Calculator entry not found'], 404);
        }

        // Step 2: Directly access the 'Calculate' field as an array
        $calculateArray = $calculator->Calculate;
        // dd($calculateArray);
        // Step 3: Extract the parameter IDs from the 'Calculate' array
        $parameterIds = [];
        $timeIds = [];
        $operators = [];

        // Iterate through each item in the calculate array
        foreach ($calculateArray as $item) {
            if (strpos($item, 'Pid=') === 0) {
                // This item is a ParametersID, so we add it to parameterIds after extracting the ID
                $parameterIds[] = substr($item, 4); // Extract ID after "Pid="
            } elseif (strpos($item, 'Tid=') === 0) {
                // This item is a TimeID, so we add it to timeIds after extracting the ID
                $timeIds[] = substr($item, 4); // Extract ID after "Tid="
            } elseif (in_array($item, ['+', '-', '*', '÷', '/'])) {
                // This item is an operator
                $operators[] = $item;
            }
        }

        // dd($operators, $parameterIds, $timeIds);
        // Check if we have any ParametersID to query
        if (empty($parameterIds)) {
            return response()->json(['error' => 'No valid ParametersID found in Calculate field'], 400);
        }
        // $timeId = ValuesParameters::whereIn('ParametersID', $parameterIds)->get()->pluck('TimeID', 'ParametersID')->toArray();
        // dd($timeId);
        // Step 4: Query the parameters_value table using the extracted ParametersID values
        // Loop over each parameter ID to create a query with the corresponding TimeID
        foreach ($parameterIds as $index => $parameterId) {
            if (isset($timeIds[$index])) {
                $timeId = $timeIds[$index];
                // dd($timeId);
                // Find the record with both matching ParametersID and TimeID
                $parameterValue = ValuesParameters::where('ParametersID', $parameterId)
                    ->where('TimeID', $timeId)
                    ->value('Value');

                if ($parameterValue !== null) {
                    $parameters[$parameterId] = $parameterValue; // Store with ParametersID as the key
                }
            }
        }
        // dd($parameters);
        if (empty($parameters)) {
            return response()->json(['error' => 'No parameter values found'], 404);
        }
        // dd($parameters);
        // Initialize variables for calculation
        $result = null;
        $currentOperator = null;
        $numberBuffer = "";
        $values = [];
        $operatorStack = [];
        foreach ($calculateArray as $item) {
            if (strpos($item, 'Pid=') === 0) {
                // Extract the ParametersID after "Pid="
                $parameterId = substr($item, 4);

                // Get the associated value for this ParametersID and TimeID if available
                $value = $parameters[$parameterId] ?? 0; // Use 0 if the parameter is missing
                $numberBuffer .= (string) $value; // Ensure it's a string

            } elseif (strpos($item, 'Tid=') === 0) {
                // Extract the TimeID after "Tid="
                $timeId = substr($item, 4);
                // Additional logic if needed for Tid; here, we assume Tid is already used to find values

            } elseif (in_array($item, ['+', '-', '*', '÷', '/', '=', '(', ')'])) {
                // If we have a buffered number, store it in the values array
                if ($numberBuffer !== "") {
                    // Store the buffered number as a string
                    $values[] = $numberBuffer;
                    $numberBuffer = ""; // Reset the buffer
                }

                if ($item === '=') {
                    break; // End of calculation
                } elseif ($item === '(') {
                    // Store opening parentheses directly in values array
                    $values[] = $item; // Keep the opening parenthesis
                } elseif ($item === ')') {
                    // When encountering a closing parenthesis, perform all operations until matching '('
                    while (!empty($operatorStack) && end($operatorStack) !== '(') {
                        $values[] = array_pop($operatorStack); // Store the operator
                    }
                    // Pop the '(' from the operator stack without storing it
                    if (!empty($operatorStack) && end($operatorStack) === '(') {
                        array_pop($operatorStack); // Remove the matching '(' from the stack
                    }
                    // Store the closing parenthesis in the values array
                    $values[] = $item; // Keep the closing parenthesis
                } else {
                    // Store the operator in the values array
                    $values[] = $item; // Keep the operator
                }
            } else {
                // Concatenate number values
                $numberBuffer .= $item; // Concatenate numbers
            }
        }

        // Handle the last buffered number if any
        if ($numberBuffer !== "") {
            $values[] = $numberBuffer; // Store the last number as a string
        }

        // Check if values are empty before proceeding
        if (empty($values)) {
            return null; // No values to calculate
        }
        // dd($values);

        $calculateString = implode(' ', $values);

        // Output the final string for debugging
        $result = eval ("return $calculateString;");
        
        $data = [
            'ParametersID' => (string) $param->ParametersID,
            'SourceID' => (string) $param->SourceID,
            'GTid' => (string) $valuesParameters->TimeID,
            'Value' => round($result, 2),
            'GraphicsTimesID' => (string) $param->GrapicsID,
            'BlogID' => (string) $param->BlogsID,
            'FactoryStructureID' => (string) $param->FactoryStructureID,
            'created_at' => now(),
            'Created' => now(),
        ];
        $existingValue = ValuesParameters::where([
            'TimeID' => $data['GTid'],
            'ParametersID' => $data['ParametersID'],
            'SourcesID' => $data['SourceID'],
        ])->first();
        if ($existingValue) {
            // dd($existingValue);
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
        // dd($result);

    }

    public function deleted(ValuesParameters $valuesParameters): void
    {
        //
    }

    /**
     * Handle the ValuesParameters "restored" event.
     */
    public function restored(ValuesParameters $valuesParameters): void
    {
        //
    }

    /**
     * Handle the ValuesParameters "force deleted" event.
     */
    public function forceDeleted(ValuesParameters $valuesParameters): void
    {
        //
    }
}
