<?php


namespace App\Http\Controllers;

use App\Models\ContactUs;
use Illuminate\Http\Request;

class ContactUsController extends Controller
{
    public function store(Request $request)
    {

        $validated = $request->validate([
            'full_name' => 'required|string|max:55',
            'phone'     => 'required|string|max:55',
            'company'   => 'required|string|max:55',
            'user_type' => 'required|integer',
            'message'   => 'required|string',
        ]);


        $contact = ContactUs::create($validated);

        return response()->json([
            'success' => true,
            'data'    => $contact,
            'message' => 'Contact Details Saved Successfully'
        ]);
    }
}
