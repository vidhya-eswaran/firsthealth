<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Faq;
use Illuminate\Support\Facades\Log;

class FAQController extends Controller
{
    // Display all FAQs
    public function index(Request $request)
    {
        $type = $request->query('type'); // Get type from request (user or driver)
    
        $faqs = FAQ::when($type, function ($query) use ($type) {
            return $query->where('type', $type);
        })->get()->makeHidden(['created_at', 'updated_at']);
    
        return response()->json($faqs);
    }


    // Store a new FAQ
    public function store(Request $request)
    {
        $request->validate([
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
            'type' => 'required',
        ]);

        $faq = FAQ::create($request->all());
        return response()->json($faq, 201);
    }

    // Display a specific FAQ
    public function show($id)
    {
        $faq = FAQ::findOrFail($id);
        return response()->json($faq);
    }

    // Update a specific FAQ
    public function update(Request $request)
    {
        $request->validate([
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
            'id' => 'required',
            'type' => 'required',
        ]);

        $faq = FAQ::findOrFail($request->id);
        $faq->update($request->all());
        return response()->json($faq);
    }
    
    public function updateweb(Request $request)
    {
        Log::info('Updating FAQ');
    
        $request->validate([
            'question' => 'required',
            'answer' => 'required',
        ]);
    
         $faq = FAQ::find($request->id);
    
        if ($faq) {
         Log::info('Updating FAQ', ['faq_id' => $faq->id, 'question' => $request->question]);
    
             $faq->update($request->only(['question', 'answer']));
            return response()->json($faq, 200);   
        }
    
        return response()->json(['error' => 'FAQ not found'], 404);
    }
    // Delete a specific FAQ
    public function destroy(Request $request)
    {
        $id = $request->query('id'); 
        $faq = FAQ::findOrFail($id);
        $faq->delete();
        return response()->json(['message' => 'Faqs deleted successfully.'], 204);
    }
}

