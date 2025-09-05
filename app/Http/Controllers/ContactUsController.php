<?php


namespace App\Http\Controllers;

use Mail;
use App\Models\ContactUs;
use Illuminate\Http\Request;
use App\Helpers\Zoho\ZohoHelper;

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
        
        // try {
        //     Mail::send('emails.contact_form.contact_form_user', $dataUser, function ($message) use ($dataUser) {
        //         $message->from('contactform@localistssenders.com');
        //         $message->to($dataUser['email']);
        //         $message->subject($dataUser['subject']);
        //     });
        // } catch (\Throwable $e) {
        //     return response()->json([
        //         'status' => 'error',
        //         'message' => $e->getMessage()
        //     ]);
        // }


        // $dataAdmin['to'] = 'michael.marshall@localists.com';
        // $dataAdmin['fullName'] = $request->full_name;
        // $dataAdmin['email'] = $request->email;
        // $dataAdmin['phone'] = $request->phone;
        // $dataAdmin['userType'] = $request->user_type;
        // $dataAdmin['user_message'] = $request->message;
        // $dataAdmin['subject'] = "New Contact Form Submission – Localists";
        // try {
        //     Mail::send('emails.contact_form.contact_form_admin', $dataAdmin, function ($message) use ($dataAdmin) {
        //         $message->from('contactform@localistssenders.com');
        //         $message->to($dataAdmin['to']);
        //         $message->cc(['zoofishan@zuzucodes.com', 'nathan.oconnor@localists.com']); // <-- Add multiple CCs
        //         $message->subject($dataAdmin['subject']);
        //     });
        // } catch (\Throwable $e) {
        //     return response()->json([
        //         'status' => 'error',
        //         'message' => $e->getMessage()
        //     ]);
        // }



        return response()->json([
            'success' => true,
            'data'    => $contact,
            'message' => 'Contact Details Saved Successfully'
        ]);
    }


    public function sendMailInBackground(Request $request)
    {
        $dataUser['email'] = $request->email;
        $dataUser['fullName'] = $request->full_name;
        $dataUser['subject'] = "Thank you for contacting Localists – We've received your request";

        ZohoHelper::executeTaskInBackground(function() use ($dataUser) {
            $this->sentUserMail($dataUser);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Email is being sent in the background'
        ]);
    }


    public function sentUserMail($dataUser)
    {
        try {
            Mail::send('emails.contact_form.contact_form_user', $dataUser, function ($message) use ($dataUser) {
                $message->from('contactform@localistssenders.com');
                $message->to($dataUser['email']);
                $message->subject($dataUser['subject']);
            });
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

}
