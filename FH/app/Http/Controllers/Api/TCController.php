<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TC;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class TCController extends Controller
{
    public function list(Request $request)
    {
        try {
            // Build the query
            $tc = TC::get();
            
            //dd($tc);

            return response()->json($tc, 200);
        } catch (\Exception $e) {
            \Log::error('Failed to retrieve terms and conditions: ' . $e->getMessage());

            return response()->json([
                'error' => 'Failed to retrieve terms and conditions',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the authenticated user's personal information.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {


        $validatedData = $request->validate([
            'id' => 'required',
            'terms' => 'required',
        ]);

        $tc = TC::first();
        if ($tc) {
            $tc->update($request->only([
                'terms',
            ]));
            return response()->json(['message' => 'Terms and Condition updated successfully.'], 200);
        } else {
            $tc = TC::create($validatedData);
            return response()->json(['message' => 'Terms and Condition created successfully.'], 200);
        }
    }
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'terms' => 'required',
        ]);
    
        TC::create($validatedData);
    
        return response()->json(['message' => 'Terms and Conditions created successfully.'], 201);
    }
    public function destroy(Request $request)
    {
        $id = $request->query('id'); 
        $tc = TC::findOrFail($id);
        $tc->delete();
        return response()->json(['message' => 'Terms and Conditions deleted successfully.'], 204);
    }
    public function downloadPdf(Request $request)
    {
        try {
            $terms = TC::all();
    
            if (!$terms) {
                return response()->json(['error' => 'No terms and conditions found'], 404);
            }
    
            $pdf = Pdf::loadView('pdf.terms', compact('terms'));
    
            if (!$pdf) {
                return response()->json(['error' => 'Failed to generate PDF'], 500);
            }
    
            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->stream();
            }, 'terms-and-conditions.pdf', [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="terms-and-conditions.pdf"',
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Failed to generate PDF: ' . $e->getMessage());
    
            return response()->json([
                'error' => 'Failed to generate PDF',
                'message' => $e->getMessage()
            ], 500);
        }
    }

}
