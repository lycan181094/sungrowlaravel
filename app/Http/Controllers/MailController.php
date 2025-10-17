<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\FormSubmissionMail;

class MailController extends Controller
{
    public function send(Request $request)
    {
        $fields = $request->all();

        Mail::to('info@witmakers.solucionesgt360.com')->send(new FormSubmissionMail($fields));

        if ($request->has('email') && !empty($request->email)) {
            Mail::to($request->email)->send(new FormSubmissionMail($fields, true));
        }

        return response()->json(['message' => 'Email sent successfully']);
    }
}
