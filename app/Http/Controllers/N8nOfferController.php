<?php

namespace App\Http\Controllers;

use App\Services\N8nOfferService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class N8nOfferController extends Controller
{
    public function __construct(
        private readonly N8nOfferService $n8nOfferService
    ) {
    }

    /**
     * Webhook endpoint for n8n to call back after generating offer packet
     * Receives JSON with file_path and file_type, updates the offer record
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function webhookOfferPacket(Request $request): JsonResponse
    {
        try {
            // Handle JSON requests properly
            $data = [];
            if (method_exists($request, 'json') && $request->json()) {
                $data = $request->json()->all();
            }
            
            if (empty($data)) {
                $data = $request->all();
            }
            
            if (empty($data) && $request->getContent()) {
                $jsonData = json_decode($request->getContent(), true);
                if (json_last_error() === JSON_ERROR_NONE && $jsonData) {
                    $data = $jsonData;
                }
            }

            // Process the webhook data
            $result = $this->n8nOfferService->processOfferPacketWebhook($data);

            // Return success response
            return $this->responseJSON($result, 'success', 200);

        } catch (ValidationException $e) {
            return $this->responseJSON(
                $e->errors(),
                'Validation failed',
                422
            );
        } catch (Exception $e) {
            Log::error('Offer packet webhook failed: ' . $e->getMessage(), [
                'error' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            return $this->responseJSON(
                'Webhook processing failed: ' . $e->getMessage(),
                'failure',
                500
            );
        }
    }
}

