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
            'email'   => 'required|string|max:255',
            'user_type' => 'required|integer',
            'message'   => 'required|string',
        ]);


        $contact = ContactUs::create($validated);
        
        // Send email to user
        $dataUser['email'] = $request->email;
        $dataUser['fullName'] = $request->full_name;
        $dataUser['subject'] = "Thank you for contacting Localists – We've received your request";
        Mail::send('emails.contact_form.contact_form_user', $dataUser, function ($message) use ($dataUser) {
            $message->from('contactform@localistssenders.com');
            $message->to($dataUser['email']);
            $message->subject($dataUser['subject']);
        });



        return response()->json([
            'success' => true,
            'data'    => $contact,
            'message' => 'Contact Details Saved Successfully'
        ]);
    }
}
