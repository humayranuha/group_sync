<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;

class NodeEmailController extends Controller
{
    // Check Node.js server status
    public function checkStatus()
    {
        try {
            $client = new Client(['timeout' => 5]);
            $response = $client->get('http://localhost:5000/api/health');
            $data = json_decode($response->getBody(), true);
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Node.js server is not running'
            ], 503);
        }
    }

    // Send email via Node.js
    public function sendEmail(Request $request)
    {
        try {
            $client = new Client(['timeout' => 30]);
            $response = $client->post('http://localhost:5000/api/send-email', [
                'json' => [
                    'to' => $request->email,
                    'subject' => $request->subject,
                    'body' => $request->message,
                    'studentName' => $request->student_name
                ]
            ]);
            
            $data = json_decode($response->getBody(), true);
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send email: ' . $e->getMessage()
            ], 500);
        }
    }

    // Send feedback via Node.js
    public function sendFeedback(Request $request)
    {
        try {
            $client = new Client(['timeout' => 30]);
            $response = $client->post('http://localhost:5000/api/send-feedback', [
                'json' => [
                    'studentEmail' => $request->email,
                    'studentName' => $request->student_name,
                    'feedbackType' => $request->feedback_type,
                    'feedbackMessage' => $request->message
                ]
            ]);
            
            $data = json_decode($response->getBody(), true);
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send feedback: ' . $e->getMessage()
            ], 500);
        }
    }
}