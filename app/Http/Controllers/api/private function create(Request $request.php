<?php
     function create(Request $request)
    {
        $uuidString = (string) Str::uuid();
        try {
            ValuesParameters::updateOrInsert(
                [
                    'ParametersID' => $request->ParametersID,
                    'SourcesID' => $request->SourceID,
                    'TimeID' => $request->GTid,
                    'GraphicsTimesID' => $request->GrapicsID,
                ],
                [
                    'id' => $uuidString,
                    'Value' => $request->Value,
                    'BlogID' => $request->BlogsID,
                    'Comment' => $request->Comment,
                    'updated_at' => now()
                ]
            );
            
            $unit = ValuesParameters::where('id', $uuidString)->first();


            return response()->json([
                'status' => 200,
                'message' => "Ma`lumot muvafaqiyatli qo'shildi",
                'unit' => $unit
            ]);

        } catch (\Exception $e) {
            \Log::error('Error creating/updating unit:', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => 500,
                'message' => 'There was an error processing the request.',
                'error' => $e->getMessage()
            ]);
        }
    }
?>